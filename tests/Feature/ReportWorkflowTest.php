<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_move_a_report_through_the_valid_workflow(): void
    {
        Storage::fake('public');
        $petugas = User::factory()->petugas()->create();
        $report = Report::factory()->create();

        foreach ([
            ReportStatus::Diverifikasi,
            ReportStatus::Diproses,
            ReportStatus::Selesai,
        ] as $status) {
            $payload = [
                'status' => $status->value,
                'officer_note' => 'Status diperbarui oleh petugas lapangan.',
            ];

            if ($status === ReportStatus::Selesai) {
                $payload['resolution_photo'] = $this->fakePng();
            }

            $this->actingAs($petugas)
                ->patch(route('reports.status.update', $report), $payload)
                ->assertRedirect(route('reports.show', $report))
                ->assertSessionHasNoErrors();

            $report->refresh();
            $this->assertSame($status, $report->status);
        }

        $this->assertTrue($report->officer->is($petugas));
        $this->assertNotNull($report->verified_at);
        $this->assertNotNull($report->processed_at);
        $this->assertNotNull($report->resolved_at);
        $this->assertNotNull($report->resolution_photo_path);
        Storage::disk('public')->assertExists($report->resolution_photo_path);
    }

    public function test_rejection_requires_a_note(): void
    {
        $petugas = User::factory()->petugas()->create();
        $report = Report::factory()->create();

        $this->actingAs($petugas)
            ->patch(route('reports.status.update', $report), [
                'status' => ReportStatus::Ditolak->value,
                'officer_note' => '',
            ])->assertSessionHasErrors('officer_note');

        $this->assertSame(ReportStatus::Diajukan, $report->refresh()->status);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $petugas = User::factory()->petugas()->create();
        $report = Report::factory()->create();

        $this->actingAs($petugas)
            ->patch(route('reports.status.update', $report), [
                'status' => ReportStatus::Selesai->value,
                'officer_note' => 'Mencoba melompati proses.',
            ])->assertSessionHasErrors('status');

        $this->assertSame(ReportStatus::Diajukan, $report->refresh()->status);
    }

    public function test_warga_cannot_change_report_status(): void
    {
        $warga = User::factory()->warga()->create();
        $report = Report::factory()->for($warga, 'reporter')->create();

        $this->actingAs($warga)
            ->patch(route('reports.status.update', $report), [
                'status' => ReportStatus::Diverifikasi->value,
            ])->assertForbidden();
    }

    public function test_completing_a_report_requires_resolution_photo(): void
    {
        $petugas = User::factory()->petugas()->create();
        $report = Report::factory()->create([
            'status' => ReportStatus::Diproses,
            'officer_id' => $petugas->id,
            'verified_at' => now()->subDay(),
            'processed_at' => now(),
        ]);

        $this->actingAs($petugas)
            ->patch(route('reports.status.update', $report), [
                'status' => ReportStatus::Selesai->value,
                'officer_note' => 'Pekerjaan lapangan telah selesai.',
            ])
            ->assertSessionHasErrors('resolution_photo');

        $this->assertSame(ReportStatus::Diproses, $report->refresh()->status);
    }

    private function fakePng(): UploadedFile
    {
        $pixel = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );

        return UploadedFile::fake()->createWithContent('penyelesaian.png', $pixel);
    }
}
