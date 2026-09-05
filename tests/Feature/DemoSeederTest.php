<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_contains_accounts_relationships_and_valid_status_evidence(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('categories', 6);
        $this->assertDatabaseCount('reports', 9);
        foreach (['admin' => 1, 'petugas' => 1, 'warga' => 3] as $role => $count) {
            $this->assertSame($count, User::where('role', $role)->count());
        }
        foreach (User::all() as $user) {
            $this->assertTrue(Hash::check('Password123!', $user->password));
        }
        foreach ([ReportStatus::Diverifikasi, ReportStatus::Ditolak, ReportStatus::Selesai] as $status) {
            $this->assertSame(3, Report::where('status', $status)->count());
        }
        foreach (Report::with(['categories', 'reporter', 'officer'])->get() as $report) {
            $this->assertNotNull($report->demo_key);
            $this->assertNotNull($report->latitude);
            $this->assertNotNull($report->longitude);
            $this->assertNotEmpty($report->categories);
            $this->assertTrue($report->reporter->hasRole('warga'));
            $this->assertTrue($report->officer->hasRole('petugas'));
            Storage::disk('public')->assertExists($report->photo_path);
            $this->assertTrue($report->created_at->lt($report->updated_at));
            if ($report->status === ReportStatus::Selesai) {
                Storage::disk('public')->assertExists($report->resolution_photo_path);
                $this->assertTrue($report->verified_at->lt($report->processed_at));
                $this->assertTrue($report->processed_at->lt($report->resolved_at));
            } elseif ($report->status === ReportStatus::Ditolak) {
                $this->assertNotEmpty($report->officer_note);
                $this->assertNotNull($report->rejected_at);
                $this->assertNull($report->verified_at);
                $this->assertNull($report->resolution_photo_path);
            }
        }
        foreach (User::where('role', 'warga')->get() as $resident) {
            $this->assertSame(3, $resident->reports()->count());
        }
    }

    public function test_rerunning_seed_preserves_existing_and_edited_data_without_duplicates(): void
    {
        Storage::fake('public');
        $this->seed();
        $user = User::where('email', 'warga@laporkita.test')->firstOrFail();
        $user->update(['name' => 'Nama telah diubah', 'password' => 'PasswordBaru123!']);
        $report = Report::where('status', ReportStatus::Diverifikasi)->firstOrFail();
        $report->title = 'Judul telah diubah';
        $report->status = ReportStatus::Diproses;
        $report->photo_path = 'reports/foto-baru.png';
        $report->save();
        $report->categories()->sync([Category::first()->id]);
        Report::factory()->create();
        $before = $this->snapshot();
        $this->seed();
        $this->assertSame($before, $this->snapshot());
    }

    private function snapshot(): array
    {
        return collect(['users', 'categories', 'reports', 'category_report'])
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
    }
}
