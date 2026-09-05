<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_dashboard_only_lists_reports_that_passed_verification(): void
    {
        $submitted = Report::factory()->create(['title' => 'Laporan masih diajukan']);
        $verified = Report::factory()->verified()->create(['title' => 'Laporan sudah terverifikasi']);

        $this->get(route('public-reports.index'))
            ->assertOk()
            ->assertSee($verified->title)
            ->assertDontSee($submitted->title);
    }

    public function test_public_can_view_verified_detail_without_reporter_identity(): void
    {
        $reporter = User::factory()->warga()->create([
            'name' => 'Nama Warga Rahasia',
            'email' => 'rahasia@example.com',
        ]);
        $report = Report::factory()->for($reporter, 'reporter')->verified()->create();

        $this->get(route('public-reports.show', $report))
            ->assertOk()
            ->assertSee($report->title)
            ->assertSee('Identitas dilindungi')
            ->assertDontSee($reporter->name)
            ->assertDontSee($reporter->email);
    }

    public function test_submitted_report_detail_is_not_public(): void
    {
        $report = Report::factory()->create(['status' => ReportStatus::Diajukan]);

        $this->get(route('public-reports.show', $report))->assertNotFound();
    }

    public function test_rejected_reports_are_not_public_even_when_requested_by_filter(): void
    {
        $report = Report::factory()->create(['status' => ReportStatus::Ditolak, 'title' => 'Laporan ditolak rahasia']);
        $this->get(route('public-reports.index', ['status' => 'ditolak']))
            ->assertOk()->assertDontSee($report->title);
        $this->get(route('public-reports.show', $report))->assertNotFound();
    }

    public function test_public_filters_combine_search_status_and_category_for_list_and_map(): void
    {
        $category = Category::factory()->create();
        $match = Report::factory()->verified()->create(['title' => 'Drainase Jalan Mawar']);
        $match->categories()->attach($category);
        $wrongCategory = Report::factory()->verified()->create(['title' => 'Drainase kategori berbeda']);
        $wrongStatus = Report::factory()->create(['title' => 'Drainase belum diverifikasi']);
        $wrongStatus->categories()->attach($category);

        $this->get(route('public-reports.index', [
            'search' => 'DRAINASE', 'status' => 'diverifikasi', 'category' => $category->id,
        ]))->assertOk()->assertSee($match->title)->assertDontSee($wrongCategory->title)
            ->assertDontSee($wrongStatus->title)
            ->assertViewHas('mapReports', fn ($reports) => $reports->count() === 1 && $reports->first()['title'] === $match->title);
    }

    public function test_completed_public_report_shows_resolution_proof(): void
    {
        $report = Report::factory()->create([
            'status' => ReportStatus::Selesai,
            'verified_at' => now()->subDays(2),
            'processed_at' => now()->subDay(),
            'resolved_at' => now(),
            'resolution_photo_path' => 'report-resolutions/selesai.png',
        ]);

        $this->get(route('public-reports.show', $report))
            ->assertOk()
            ->assertSee('Bukti penyelesaian')
            ->assertSee('report-resolutions/selesai.png', false);
    }
}
