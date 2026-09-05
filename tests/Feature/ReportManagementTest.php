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

        $response = $this->actingAs($warga)->withSession(['report_captcha_answer' => 12])->post(route('reports.store'), [
            'title' => 'Jalan lingkungan rusak berat',
            'description' => 'Permukaan jalan berlubang dan membahayakan pengguna jalan pada malam hari.',
            'address' => 'Jalan Melati RT 03 RW 02, Kelurahan Sukamaju',
            'latitude' => -7.9668200,
            'longitude' => 112.6329100,
            'photo' => $this->fakePng(),
            'category_ids' => $categories->modelKeys(),
            'captcha_answer' => 12,
        ]);

        $response->assertSessionHasNoErrors();
        $report = Report::query()->firstOrFail();

        $response->assertRedirect(route('reports.show', $report));
        $response->assertSessionHas('success');
        $this->assertSame(ReportStatus::Diajukan, $report->status);
        $this->assertSame('-7.9668200', $report->latitude);
        $this->assertSame('112.6329100', $report->longitude);
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
                'latitude',
                'longitude',
                'photo',
                'category_ids',
                'captcha_answer',
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
                'latitude' => -7.9712300,
                'longitude' => 112.6298700,
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

    public function test_wrong_captcha_answer_is_rejected(): void
    {
        Storage::fake('public');
        $warga = User::factory()->warga()->create();
        $category = Category::factory()->create();

        $this->actingAs($warga)
            ->withSession(['report_captcha_answer' => 12])
            ->post(route('reports.store'), [
                'title' => 'Drainase lingkungan tersumbat',
                'description' => 'Saluran air tersumbat dan menyebabkan genangan setelah hujan turun.',
                'address' => 'Jalan Melati RT 03 RW 02, Kelurahan Sukamaju',
                'latitude' => -7.9668200,
                'longitude' => 112.6329100,
                'photo' => $this->fakePng(),
                'category_ids' => [$category->id],
                'captcha_answer' => 10,
            ])
            ->assertSessionHasErrors('captcha_answer');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_nearby_active_duplicate_requires_explicit_confirmation(): void
    {
        Storage::fake('public');
        $warga = User::factory()->warga()->create();
        $category = Category::factory()->create();
        $existing = Report::factory()->for($warga, 'reporter')->create([
            'latitude' => -7.9668200,
            'longitude' => 112.6329100,
        ]);
        $existing->categories()->attach($category);

        $payload = [
            'title' => 'Kerusakan lain pada jalan yang sama',
            'description' => 'Terdapat kerusakan berbeda beberapa meter dari laporan yang sebelumnya.',
            'address' => 'Jalan Melati RT 03 RW 02, Kelurahan Sukamaju',
            'latitude' => -7.9669000,
            'longitude' => 112.6329500,
            'category_ids' => [$category->id],
            'captcha_answer' => 12,
        ];

        $this->actingAs($warga)
            ->withSession(['report_captcha_answer' => 12])
            ->post(route('reports.store'), [...$payload, 'photo' => $this->fakePng()])
            ->assertSessionHasErrors('duplicate_confirmation');

        $this->assertDatabaseCount('reports', 1);

        $this->actingAs($warga)
            ->withSession(['report_captcha_answer' => 12])
            ->post(route('reports.store'), [
                ...$payload,
                'photo' => $this->fakePng(),
                'duplicate_confirmation' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reports', 2);
    }

    public function test_report_submission_is_limited_to_five_attempts_per_minute(): void
    {
        $this->actingAs(User::factory()->warga()->create());
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('reports.store'), [])->assertUnprocessable();
        }
        $this->postJson(route('reports.store'), [])->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertDatabaseCount('reports', 0);
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
