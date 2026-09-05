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
    public function handle(
        Report $report,
        ReportStatus $nextStatus,
        User $officer,
        ?string $note,
        ?string $resolutionPhotoPath = null,
    ): Report {
        if (! $report->status->canTransitionTo($nextStatus)) {
            throw ValidationException::withMessages([
                'status' => "Status {$report->status->label()} tidak dapat langsung diubah menjadi {$nextStatus->label()}.",
            ]);
        }

        if ($nextStatus === ReportStatus::Selesai && blank($resolutionPhotoPath)) {
            throw ValidationException::withMessages([
                'resolution_photo' => 'Foto bukti penyelesaian wajib diunggah untuk menutup laporan.',
            ]);
        }

        $report->officer_id = $officer->id;
        $report->status = $nextStatus;
        $report->officer_note = filled($note) ? trim((string) $note) : null;

        if ($nextStatus === ReportStatus::Selesai) {
            $report->resolution_photo_path = $resolutionPhotoPath;
        }

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
