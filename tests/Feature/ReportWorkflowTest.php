<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_move_a_report_through_the_valid_workflow(): void
    {
        $petugas = User::factory()->petugas()->create();
        $report = Report::factory()->create();

        foreach ([
            ReportStatus::Diverifikasi,
            ReportStatus::Diproses,
            ReportStatus::Selesai,
        ] as $status) {
            $this->actingAs($petugas)
                ->patch(route('reports.status.update', $report), [
                    'status' => $status->value,
                    'officer_note' => 'Status diperbarui oleh petugas lapangan.',
                ])->assertRedirect(route('reports.show', $report));

            $report->refresh();
            $this->assertSame($status, $report->status);
        }

        $this->assertTrue($report->officer->is($petugas));
        $this->assertNotNull($report->verified_at);
        $this->assertNotNull($report->processed_at);
        $this->assertNotNull($report->resolved_at);
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
}
