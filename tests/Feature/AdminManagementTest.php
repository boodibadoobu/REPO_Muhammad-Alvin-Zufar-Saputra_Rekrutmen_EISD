<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Drainase Tersumbat',
                'description' => 'Saluran air yang tersumbat atau tidak berfungsi.',
            ])->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Drainase Tersumbat',
            'slug' => 'drainase-tersumbat',
        ]);
    }

    public function test_admin_can_assign_a_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $warga = User::factory()->warga()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $warga), [
                'name' => $warga->name,
                'email' => $warga->email,
                'role' => UserRole::Petugas->value,
            ])->assertRedirect(route('admin.users.index'));

        $this->assertSame(UserRole::Petugas, $warga->refresh()->role);
    }

    public function test_petugas_cannot_manage_users(): void
    {
        $petugas = User::factory()->petugas()->create();

        $this->actingAs($petugas)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_category_attached_to_reports_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $report = Report::factory()->create();
        $report->categories()->attach($category);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
