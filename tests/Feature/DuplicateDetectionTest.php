<?php

namespace Tests\Feature;

use App\Actions\FindPotentialDuplicateReports;
use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearby_report_is_found_after_many_newer_candidates_outside_the_radius(): void
    {
        $category = Category::factory()->create();
        $nearby = Report::factory()->create(['latitude' => -7.96682, 'longitude' => 112.63291, 'created_at' => now()->subDay()]);
        $nearby->categories()->attach($category);
        Report::factory()->count(21)->create(['latitude' => -7.96552, 'longitude' => 112.63421])
            ->each(fn ($report) => $report->categories()->attach($category));

        $matches = app(FindPotentialDuplicateReports::class)->handle(-7.96682, 112.63291, [$category->id]);
        $this->assertSame([$nearby->id], $matches->pluck('id')->all());
    }

    public function test_radius_boundary_and_inactive_old_or_unrelated_reports_are_handled(): void
    {
        $category = Category::factory()->create();
        $inside = Report::factory()->create(['latitude' => rad2deg(149.99 / 6371000), 'longitude' => 112]);
        $inside->categories()->attach($category);
        foreach ([
            ['latitude' => rad2deg(150.1 / 6371000)],
            ['status' => ReportStatus::Selesai],
            ['status' => ReportStatus::Ditolak],
            ['created_at' => now()->subDays(31)],
        ] as $attributes) {
            Report::factory()->create([...['latitude' => 0, 'longitude' => 112], ...$attributes])->categories()->attach($category);
        }
        Report::factory()->create(['latitude' => 0, 'longitude' => 112]);

        $matches = app(FindPotentialDuplicateReports::class)->handle(0, 112, [$category->id]);
        $this->assertSame([$inside->id], $matches->pluck('id')->all());
    }
}
