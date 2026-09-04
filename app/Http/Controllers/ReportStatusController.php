<?php

namespace App\Http\Controllers;

use App\Actions\TransitionReportStatus;
use App\Enums\ReportStatus;
use App\Http\Requests\UpdateReportStatusRequest;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;

class ReportStatusController extends Controller
{
    public function update(
        UpdateReportStatusRequest $request,
        Report $report,
        TransitionReportStatus $transition,
    ): RedirectResponse {
        $transition->handle(
            $report,
            ReportStatus::from($request->validated('status')),
            $request->user(),
            $request->validated('officer_note'),
        );

        return redirect()->route('reports.show', $report)
            ->with('success', 'Status laporan berhasil diperbarui.');
    }
}
