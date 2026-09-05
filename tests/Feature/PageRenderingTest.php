<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_pages_render(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('LaporKita');
        $this->get(route('login'))->assertOk()->assertSee('Masuk ke LaporKita');
        $this->get(route('register'))->assertOk()->assertSee('Buat akun warga');
        $this->get(route('public-reports.index'))->assertOk()->assertSee('Laporan Publik');
    }

    public function test_warga_pages_render(): void
    {
        $warga = User::factory()->warga()->create();
        $category = Category::factory()->create();
        $report = Report::factory()->for($warga, 'reporter')->create();
        $report->categories()->attach($category);

        $this->actingAs($warga)->get(route('dashboard'))->assertOk();
        $this->actingAs($warga)->get(route('reports.index'))->assertOk()->assertSee($report->title);
        $this->actingAs($warga)->get(route('reports.create'))->assertOk();
        $this->actingAs($warga)->get(route('reports.show', $report))->assertOk();
        $this->actingAs($warga)->get(route('reports.edit', $report))->assertOk();
    }

    public function test_home_calls_to_action_follow_report_permissions(): void
    {
        $createLink = 'href="'.route('reports.create').'"';

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('register').'"', false)
            ->assertDontSee($createLink, false);

        $this->actingAs(User::factory()->warga()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee($createLink, false);

        foreach ([User::factory()->petugas()->create(), User::factory()->admin()->create()] as $officer) {
            $this->actingAs($officer)->get(route('home'))
                ->assertOk()
                ->assertSee('href="'.route('reports.index').'"', false)
                ->assertSee('Tinjau laporan')
                ->assertDontSee($createLink, false);

            $this->get(route('reports.create'))->assertForbidden();
        }
    }

    public function test_petugas_report_page_renders_with_status_action(): void
    {
        $petugas = User::factory()->petugas()->create();
        $report = Report::factory()->create();

        $this->actingAs($petugas)
            ->get(route('reports.show', $report))
            ->assertOk()
            ->assertSee('Tindak lanjut laporan');
    }

    public function test_admin_management_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $warga = User::factory()->warga()->create();

        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.categories.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.categories.edit', $category))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.edit', $warga))->assertOk();
    }
}
