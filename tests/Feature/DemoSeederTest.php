<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_reports_have_map_locations_categories_and_completion_evidence(): void
    {
        Storage::fake('public');
        $this->seed();

        $reports = Report::with('categories')->get();
        $this->assertCount(4, $reports);
        foreach ($reports as $report) {
            $this->assertNotNull($report->latitude);
            $this->assertNotNull($report->longitude);
            $this->assertNotEmpty($report->categories);
            Storage::disk('public')->assertExists($report->photo_path);
            if ($report->status === ReportStatus::Selesai) {
                $this->assertNotNull($report->resolution_photo_path);
                Storage::disk('public')->assertExists($report->resolution_photo_path);
            }
        }
    }
}
