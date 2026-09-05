<?php

namespace App\Http\Controllers;

use App\Actions\StoreSanitizedPhoto;
use App\Actions\TransitionReportStatus;
use App\Enums\ReportStatus;
use App\Http\Requests\UpdateReportStatusRequest;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReportStatusController extends Controller
{
    public function update(
        UpdateReportStatusRequest $request,
        Report $report,
        TransitionReportStatus $transition,
        StoreSanitizedPhoto $photos,
    ): RedirectResponse {
        $nextStatus = ReportStatus::from($request->validated('status'));
        $resolutionPhotoPath = $nextStatus === ReportStatus::Selesai
            ? $photos->handle($request->file('resolution_photo'), 'report-resolutions', 'resolution_photo')
            : null;

        try {
            $transition->handle(
                $report,
                $nextStatus,
                $request->user(),
                $request->validated('officer_note'),
                $resolutionPhotoPath,
            );
        } catch (Throwable $exception) {
            if ($resolutionPhotoPath) {
                Storage::disk('public')->delete($resolutionPhotoPath);
            }

            throw $exception;
        }

        return redirect()->route('reports.show', $report)
            ->with('success', 'Status laporan berhasil diperbarui.');
    }
}
