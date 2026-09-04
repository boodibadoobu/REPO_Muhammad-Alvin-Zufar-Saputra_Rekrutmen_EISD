<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Report;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $baseQuery = Report::query();

        if ($request->user()->hasRole(UserRole::Warga)) {
            $baseQuery->where('user_id', $request->user()->id);
        }

        $counts = collect(ReportStatus::cases())->mapWithKeys(
            fn (ReportStatus $status): array => [$status->value => (clone $baseQuery)->where('status', $status)->count()],
        );

        $recentReports = (clone $baseQuery)
            ->with(['categories', 'reporter'])
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact('counts', 'recentReports'));
    }
}
