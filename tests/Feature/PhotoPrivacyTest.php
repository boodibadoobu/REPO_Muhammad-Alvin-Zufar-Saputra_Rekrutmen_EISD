<?php

namespace Tests\Feature;

use App\Actions\StoreSanitizedPhoto;
use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhotoPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_upload_routes_strip_metadata_and_preserve_visual_orientation(): void
    {
        Storage::fake('public');
        $resident = User::factory()->warga()->create();
        $category = Category::factory()->create();
        $payload = [
            'title' => 'Jalan lingkungan berlubang',
            'description' => 'Jalan lingkungan berlubang dan berbahaya bagi warga sekitar.',
            'address' => 'Jalan Melati RT 03 RW 02',
            'latitude' => -7.96, 'longitude' => 112.63,
            'category_ids' => [$category->id], 'captcha_answer' => 12,
        ];
        $this->actingAs($resident)->withSession(['report_captcha_answer' => 12])
            ->post(route('reports.store'), [...$payload, 'photo' => $this->taggedJpeg()])
            ->assertSessionHasNoErrors()->assertRedirect();
        $report = Report::firstOrFail();
        $this->assertClean($report->photo_path);
        $oldPath = $report->photo_path;

        $this->put(route('reports.update', $report), [...$payload, 'photo' => $this->taggedJpeg()])
            ->assertSessionHasNoErrors()->assertRedirect();
        $report->refresh();
        $this->assertClean($report->photo_path);
        Storage::disk('public')->assertMissing($oldPath);

        $report->forceFill(['status' => ReportStatus::Diproses])->save();
        $this->actingAs(User::factory()->petugas()->create())
            ->patch(route('reports.status.update', $report), [
                'status' => 'selesai', 'officer_note' => 'Perbaikan selesai di lapangan.',
                'resolution_photo' => $this->taggedJpeg(),
            ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertClean($report->refresh()->resolution_photo_path);
        $this->assertSame(ReportStatus::Selesai, $report->status);
        $this->assertCount(2, Storage::disk('public')->allFiles());
    }

    public function test_png_text_and_webp_xmp_are_removed(): void
    {
        Storage::fake('public');
        foreach (['png', 'webp'] as $format) {
            $gd = imagecreatetruecolor(20, 10);
            ob_start();
            $format === 'png' ? imagepng($gd) : imagewebp($gd);
            $bytes = ob_get_clean();
            imagedestroy($gd);
            if ($format === 'png') {
                $text = "Author\0PRIVATE-GPS";
                $chunk = pack('N', strlen($text)).'tEXt'.$text.pack('N', crc32('tEXt'.$text));
                $bytes = substr($bytes, 0, 33).$chunk.substr($bytes, 33);
            } else {
                $text = '<x:xmpmeta>PRIVATE-GPS</x:xmpmeta>';
                $bytes .= 'XMP '.pack('V', strlen($text)).$text.(strlen($text) % 2 ? "\0" : '');
                $bytes = substr_replace($bytes, pack('V', strlen($bytes) - 8), 4, 4);
            }
            $this->assertStringContainsString('PRIVATE-GPS', $bytes);
            $path = app(StoreSanitizedPhoto::class)->handle(
                UploadedFile::fake()->createWithContent('photo.'.$format, $bytes), 'reports',
            );
            $result = Storage::disk('public')->get($path);
            $this->assertStringNotContainsString('PRIVATE-GPS', $result);
            $this->assertSame([20, 10], array_slice(getimagesizefromstring($result), 0, 2));
        }
    }

    public function test_invalid_image_does_not_write_any_file(): void
    {
        Storage::fake('public');
        try {
            app(StoreSanitizedPhoto::class)->handle(
                UploadedFile::fake()->createWithContent('bad.jpg', 'PRIVATE-GPS'),
                'report-resolutions', 'resolution_photo',
            );
            $this->fail('Invalid image must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('resolution_photo', $exception->errors());
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_rejected_processing_preserves_existing_photo_and_status(): void
    {
        Storage::fake('public');
        $resident = User::factory()->warga()->create();
        $report = Report::factory()->for($resident, 'reporter')->create(['photo_path' => 'reports/old.jpg']);
        Storage::disk('public')->put('reports/old.jpg', 'existing photo');
        $category = Category::factory()->create();
        $gd = imagecreatetruecolor(4000, 2100);
        ob_start();
        imagepng($gd);
        $bytes = ob_get_clean();
        imagedestroy($gd);
        $this->actingAs($resident)->put(route('reports.update', $report), [
            'title' => 'Perubahan yang harus dibatalkan',
            'description' => 'Perubahan deskripsi yang tidak boleh tersimpan ketika foto gagal.',
            'address' => 'Jalan Melati RT 03 RW 02',
            'latitude' => -7.96, 'longitude' => 112.63,
            'category_ids' => [$category->id],
            'photo' => UploadedFile::fake()->createWithContent('large.png', $bytes),
        ])->assertSessionHasErrors('photo');
        $this->assertSame($report->title, $report->fresh()->title);
        $this->assertSame('reports/old.jpg', $report->fresh()->photo_path);
        $this->assertSame('existing photo', Storage::disk('public')->get('reports/old.jpg'));

        $report->forceFill(['status' => ReportStatus::Diproses])->save();
        $this->actingAs(User::factory()->petugas()->create())->patch(route('reports.status.update', $report), [
            'status' => 'selesai', 'officer_note' => 'Penanganan selesai di lapangan.',
            'resolution_photo' => UploadedFile::fake()->createWithContent('large.png', $bytes),
        ])->assertSessionHasErrors('resolution_photo');
        $this->assertSame(ReportStatus::Diproses, $report->fresh()->status);
        $this->assertNull($report->fresh()->resolution_photo_path);
        $this->assertSame(['reports/old.jpg'], Storage::disk('public')->allFiles());
    }

    private function assertClean(string $path): void
    {
        $bytes = Storage::disk('public')->get($path);
        $this->assertStringNotContainsString('Exif', $bytes);
        $this->assertStringNotContainsString('PRIVATE-GPS', $bytes);
        $this->assertSame([10, 20], array_slice(getimagesizefromstring($bytes), 0, 2));
    }

    private function taggedJpeg(): UploadedFile
    {
        $gd = imagecreatetruecolor(20, 10);
        ob_start();
        imagejpeg($gd);
        $bytes = ob_get_clean();
        imagedestroy($gd);
        // Valid TIFF with orientation, camera Make, and a GPS latitude IFD.
        $make = "PRIVATE-GPS\0";
        $gpsOffset = 50 + strlen($make);
        $tiff = 'II'.pack('vV', 42, 8).pack('v', 3)
            .pack('vvV', 0x010F, 2, strlen($make)).pack('V', 50)
            .pack('vvV', 0x0112, 3, 1).pack('v', 6)."\0\0"
            .pack('vvV', 0x8825, 4, 1).pack('V', $gpsOffset)
            .pack('V', 0).$make
            .pack('v', 2)
            .pack('vvV', 1, 2, 2)."S\0\0\0"
            .pack('vvV', 2, 5, 3).pack('V', $gpsOffset + 30)
            .pack('V', 0).pack('VVVVVV', 7, 1, 57, 1, 36, 1);
        $exif = "Exif\0\0".$tiff;
        $bytes = substr($bytes, 0, 2)."\xff\xe1".pack('n', strlen($exif) + 2).$exif.substr($bytes, 2);
        $file = UploadedFile::fake()->createWithContent('private.jpg', $bytes);
        $metadata = exif_read_data($file->getPathname());
        $this->assertSame(6, $metadata['Orientation']);
        $this->assertSame('S', $metadata['GPSLatitudeRef']);
        $this->assertSame(['7/1', '57/1', '36/1'], $metadata['GPSLatitude']);
        $this->assertSame('PRIVATE-GPS', $metadata['Make']);

        return $file;
    }
}
