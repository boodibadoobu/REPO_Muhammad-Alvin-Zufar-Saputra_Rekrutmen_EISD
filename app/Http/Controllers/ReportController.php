<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Report::class);

        $query = Report::query()->with(['categories', 'reporter', 'officer']);

        if ($request->user()->hasRole(UserRole::Warga)) {
            $query->where('user_id', $request->user()->id);
        }

        $query
            ->when($request->filled('status'), fn (Builder $builder) => $builder->where('status', $request->string('status')))
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

        return view('reports.index', [
            'reports' => $query->latest()->paginate(10)->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(),
            'statuses' => ReportStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Report::class);

        $left = random_int(2, 9);
        $right = random_int(1, 9);
        $request->session()->put('report_captcha_answer', $left + $right);

        return view('reports.create', [
            'categories' => Category::query()->orderBy('name')->get(),
            'captchaQuestion' => "{$left} + {$right}",
        ]);
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $photoPath = $request->file('photo')->store('reports', 'public');

        try {
            $report = DB::transaction(function () use ($request, $photoPath): Report {
                $report = $request->user()->reports()->create([
                    ...$request->safe()->only(['title', 'description', 'address', 'latitude', 'longitude']),
                    'photo_path' => $photoPath,
                ]);
                $report->categories()->attach($request->validated('category_ids'));

                return $report;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($photoPath);
            throw $exception;
        }

        return redirect()->route('reports.show', $report)
            ->with('success', 'Laporan berhasil dikirim dan menunggu verifikasi petugas.');
    }

    public function show(Report $report): View
    {
        Gate::authorize('view', $report);
        $report->load(['categories', 'reporter', 'officer']);

        return view('reports.show', [
            'report' => $report,
            'nextStatuses' => $report->status->allowedTransitions(),
        ]);
    }

    public function edit(Report $report): View
    {
        Gate::authorize('update', $report);

        return view('reports.edit', [
            'report' => $report->load('categories'),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateReportRequest $request, Report $report): RedirectResponse
    {
        $oldPhotoPath = $report->photo_path;
        $newPhotoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('reports', 'public')
            : null;

        try {
            DB::transaction(function () use ($request, $report, $newPhotoPath): void {
                $report->fill(Arr::only($request->validated(), ['title', 'description', 'address', 'latitude', 'longitude']));

                if ($newPhotoPath) {
                    $report->photo_path = $newPhotoPath;
                }

                $report->save();
                $report->categories()->sync($request->validated('category_ids'));
            });
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }
            throw $exception;
        }

        if ($newPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return redirect()->route('reports.show', $report)
            ->with('success', 'Laporan berhasil diperbarui.');
    }

    public function destroy(Report $report): RedirectResponse
    {
        Gate::authorize('delete', $report);
        $photoPath = $report->photo_path;
        $resolutionPhotoPath = $report->resolution_photo_path;
        $report->delete();
        Storage::disk('public')->delete($photoPath);

        if ($resolutionPhotoPath) {
            Storage::disk('public')->delete($resolutionPhotoPath);
        }

        return redirect()->route('reports.index')->with('success', 'Laporan berhasil dihapus.');
    }
}
