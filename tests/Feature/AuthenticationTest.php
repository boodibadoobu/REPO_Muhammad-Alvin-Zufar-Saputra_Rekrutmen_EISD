<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_always_creates_a_warga_account(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Warga Baru',
            'email' => 'warga@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => UserRole::Admin->value,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'warga@example.com',
            'role' => UserRole::Warga->value,
        ]);
    }

    public function test_a_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'warga@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('login'), [
            'email' => 'warga@example.com',
            'password' => 'Password123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_invalid_login_is_rejected(): void
    {
        User::factory()->create(['email' => 'warga@example.com']);

        $this->from(route('login'))->post(route('login'), [
            'email' => 'warga@example.com',
            'password' => 'salah-sekali',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_warga_cannot_access_admin_category_management(): void
    {
        $warga = User::factory()->warga()->create();

        $this->actingAs($warga)
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }
}
