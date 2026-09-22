<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('SweetNest OIMS');
        $response->assertSee('Email Address');
    }

    public function test_users_can_authenticate_using_valid_credentials(): void
    {
        $user = User::create([
            'first_name' => 'Maria',
            'last_name' => 'SweetNest',
            'email' => 'owner@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'owner@sweetnest.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        User::create([
            'first_name' => 'Maria',
            'last_name' => 'SweetNest',
            'email' => 'owner@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'owner@sweetnest.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_deactivated_users_cannot_authenticate(): void
    {
        User::create([
            'first_name' => 'Inactive',
            'last_name' => 'Worker',
            'email' => 'inactive@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@sweetnest.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_authenticated_users_can_logout(): void
    {
        $user = User::create([
            'first_name' => 'Maria',
            'last_name' => 'SweetNest',
            'email' => 'owner@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }
}
