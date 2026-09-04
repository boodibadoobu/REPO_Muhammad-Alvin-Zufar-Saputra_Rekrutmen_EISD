<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_report_creation(): void
    {
        $this->get(route('reports.create'))->assertRedirect(route('login'));
    }

    public function test_warga_can_create_a_report_with_multiple_categories_and_photo(): void
    {
        Storage::fake('public');
        $warga = User::factory()->warga()->create();
        $categories = Category::factory()->count(2)->create();

        $response = $this->actingAs($warga)->post(route('reports.store'), [
            'title' => 'Jalan lingkungan rusak berat',
            'description' => 'Permukaan jalan berlubang dan membahayakan pengguna jalan pada malam hari.',
            'address' => 'Jalan Melati RT 03 RW 02, Kelurahan Sukamaju',
            'photo' => $this->fakePng(),
            'category_ids' => $categories->modelKeys(),
        ]);

        $report = Report::query()->firstOrFail();

        $response->assertRedirect(route('reports.show', $report));
        $response->assertSessionHas('success');
        $this->assertSame(ReportStatus::Diajukan, $report->status);
        $this->assertTrue($report->reporter->is($warga));
        $this->assertCount(2, $report->categories);
        Storage::disk('public')->assertExists($report->photo_path);
    }

    public function test_report_submission_is_validated_server_side(): void
    {
        $warga = User::factory()->warga()->create();

        $this->actingAs($warga)
            ->post(route('reports.store'), [])
            ->assertSessionHasErrors([
                'title',
                'description',
                'address',
                'photo',
                'category_ids',
            ]);
    }

    public function test_warga_cannot_view_another_residents_report(): void
    {
        $owner = User::factory()->warga()->create();
        $otherWarga = User::factory()->warga()->create();
        $report = Report::factory()->for($owner, 'reporter')->create();

        $this->actingAs($otherWarga)
            ->get(route('reports.show', $report))
            ->assertForbidden();
    }

    public function test_warga_can_edit_own_submitted_report_but_not_a_verified_report(): void
    {
        $warga = User::factory()->warga()->create();
        $category = Category::factory()->create();
        $submitted = Report::factory()->for($warga, 'reporter')->create();
        $verified = Report::factory()->for($warga, 'reporter')->verified()->create();

        $this->actingAs($warga)
            ->put(route('reports.update', $submitted), [
                'title' => 'Judul laporan diperbarui',
                'description' => 'Deskripsi laporan yang sudah diperbarui dan tetap cukup panjang.',
                'address' => 'Jalan Mawar RT 05 RW 03, Kelurahan Sukamaju',
                'category_ids' => [$category->id],
            ])->assertRedirect(route('reports.show', $submitted));

        $this->assertDatabaseHas('reports', [
            'id' => $submitted->id,
            'title' => 'Judul laporan diperbarui',
        ]);

        $this->actingAs($warga)
            ->get(route('reports.edit', $verified))
            ->assertForbidden();
    }

    public function test_petugas_cannot_create_a_resident_report(): void
    {
        $petugas = User::factory()->petugas()->create();

        $this->actingAs($petugas)
            ->get(route('reports.create'))
            ->assertForbidden();
    }

    private function fakePng(): UploadedFile
    {
        $pixel = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );

        return UploadedFile::fake()->createWithContent('bukti.png', $pixel);
    }
}
