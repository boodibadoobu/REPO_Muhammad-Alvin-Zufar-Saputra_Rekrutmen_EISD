<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PublicReportController extends Controller
{
    /** @var list<ReportStatus> */
    private const PUBLIC_STATUSES = [
        ReportStatus::Diverifikasi,
        ReportStatus::Diproses,
        ReportStatus::Selesai,
    ];

    public function index(Request $request): View
    {
        $query = Report::query()
            ->with('categories')
            ->whereIn('status', array_map(
                fn (ReportStatus $status): string => $status->value,
                self::PUBLIC_STATUSES,
            ))
            ->when($request->filled('status'), function (Builder $builder) use ($request): void {
                $status = ReportStatus::tryFrom($request->string('status')->toString());

                if ($status && in_array($status, self::PUBLIC_STATUSES, true)) {
                    $builder->where('status', $status);
                }
            })
            ->when($request->filled('category'), fn (Builder $builder) => $builder->whereHas(
                'categories',
                fn (Builder $categoryQuery) => $categoryQuery->whereKey($request->integer('category')),
            ))
            ->when($request->filled('search'), function (Builder $builder) use ($request): void {
                $term = '%'.$request->string('search').'%';
                $builder->where(fn (Builder $search) => $search
                    ->whereLike('title', $term)
                    ->orWhereLike('address', $term));
            });

        $mapReports = (clone $query)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (Report $report): array => [
                'title' => $report->title,
                'latitude' => (float) $report->latitude,
                'longitude' => (float) $report->longitude,
                'status' => $report->status->label(),
                'url' => route('public-reports.show', $report),
            ]);

        $counts = collect(self::PUBLIC_STATUSES)->mapWithKeys(
            fn (ReportStatus $status): array => [
                $status->value => Report::query()->where('status', $status)->count(),
            ],
        );

        return view('public-reports.index', [
            'reports' => $query->latest()->paginate(9)->withQueryString(),
            'mapReports' => $mapReports,
            'categories' => Category::query()->orderBy('name')->get(),
            'statuses' => self::PUBLIC_STATUSES,
            'counts' => $counts,
        ]);
    }

    public function show(Report $report): View
    {
        abort_unless(in_array($report->status, self::PUBLIC_STATUSES, true), 404);

        return view('public-reports.show', [
            'report' => $report->load(['categories', 'officer']),
        ]);
    }
}
