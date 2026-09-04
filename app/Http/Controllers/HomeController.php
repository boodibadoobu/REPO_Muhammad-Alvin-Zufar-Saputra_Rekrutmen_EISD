<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'totalReports' => Report::query()->count(),
            'resolvedReports' => Report::query()->where('status', ReportStatus::Selesai)->count(),
            'recentReports' => Report::query()
                ->with('categories')
                ->whereIn('status', [ReportStatus::Diverifikasi, ReportStatus::Diproses, ReportStatus::Selesai])
                ->latest()
                ->limit(3)
                ->get(),
        ]);
    }
}
