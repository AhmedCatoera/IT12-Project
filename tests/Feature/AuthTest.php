<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertOk()->assertSee('Log in');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/orders')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
    }

    public function test_user_can_log_in_and_out(): void
    {
        $owner = $this->owner(['email' => 'owner@example.com']);

        $this->post('/login', ['email' => 'owner@example.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($owner);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->owner(['email' => 'owner@example.com']);

        $this->from('/login')->post('/login', ['email' => 'owner@example.com', 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** B20: login attempts are limited. */
    public function test_login_locks_after_five_wrong_tries(): void
    {
        $this->owner(['email' => 'owner@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'owner@example.com', 'password' => 'nope']);
        }

        // Even the right password is refused while locked.
        $this->post('/login', ['email' => 'owner@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Too many attempts.', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $this->staff(['email' => 'staff@example.com', 'is_active' => false]);

        $this->post('/login', ['email' => 'staff@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** B16: a user deactivated while logged in is signed out on the next page. */
    public function test_deactivated_user_is_signed_out_on_next_page(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->get('/dashboard')->assertOk();

        $staff->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /** B4: dates and times follow Philippine time. */
    public function test_app_uses_philippine_time(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('Asia/Manila', now()->timezoneName);
    }

    /** B21: there is no "remember me" option. */
    public function test_login_form_has_no_remember_me(): void
    {
        $this->get('/login')->assertDontSee('remember', false);
    }
}
