<?php

namespace App\Actions;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TransitionReportStatus
{
    /**
     * @throws ValidationException
     */
    public function handle(Report $report, ReportStatus $nextStatus, User $officer, ?string $note): Report
    {
        if (! $report->status->canTransitionTo($nextStatus)) {
            throw ValidationException::withMessages([
                'status' => "Status {$report->status->label()} tidak dapat langsung diubah menjadi {$nextStatus->label()}.",
            ]);
        }

        $report->officer_id = $officer->id;
        $report->status = $nextStatus;
        $report->officer_note = filled($note) ? trim((string) $note) : null;

        match ($nextStatus) {
            ReportStatus::Diverifikasi => $report->verified_at = now(),
            ReportStatus::Diproses => $report->processed_at = now(),
            ReportStatus::Selesai => $report->resolved_at = now(),
            ReportStatus::Ditolak => $report->rejected_at = now(),
            ReportStatus::Diajukan => null,
        };

        $report->save();

        return $report->refresh();
    }
}
