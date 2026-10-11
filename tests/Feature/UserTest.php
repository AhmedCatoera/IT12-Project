<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SweetNestData;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase, SweetNestData;

    public function test_owner_creates_a_staff_account_that_can_log_in(): void
    {
        $this->actingAs($this->owner())->post('/users', [
            'first_name' => 'Jose',
            'last_name' => 'Rizal',
            'email' => 'jose@example.com',
            'role' => 'staff',
            'password' => 'bakery-2026',
            'password_confirmation' => 'bakery-2026',
        ])->assertSessionHasNoErrors();

        $this->post('/logout');
        $this->post('/login', ['email' => 'jose@example.com', 'password' => 'bakery-2026'])->assertRedirect(route('dashboard'));
    }

    public function test_only_owner_and_staff_roles_can_be_given(): void
    {
        $this->actingAs($this->owner())->post('/users', [
            'first_name' => 'Rider',
            'last_name' => 'One',
            'email' => 'rider@example.com',
            'role' => 'delivery',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrorsIn('createUser', ['role']);

        $this->assertNull(User::where('email', 'rider@example.com')->first());
    }

    public function test_owner_resets_a_password(): void
    {
        $owner = $this->owner();
        $staff = $this->staff(['email' => 'staff@example.com']);

        $this->actingAs($owner)->patch("/users/{$staff->id}/password", [
            'new_password' => 'new-secret-1',
            'new_password_confirmation' => 'new-secret-1',
        ])->assertSessionHasNoErrors();

        $this->post('/logout');
        $this->post('/login', ['email' => 'staff@example.com', 'password' => 'new-secret-1'])->assertRedirect(route('dashboard'));
    }

    public function test_a_typed_password_is_not_kept_after_an_error(): void
    {
        $staff = $this->staff();

        $this->actingAs($this->owner())->patch("/users/{$staff->id}/password", [
            'new_password' => 'secret-typed-once',
            'new_password_confirmation' => 'does-not-match',
        ])->assertSessionHasErrorsIn('resetPassword', ['new_password']);

        $this->assertNull(session()->getOldInput('new_password'));
    }

    public function test_owner_cannot_lock_themselves_out(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->patch("/users/{$owner->id}/status")->assertSessionHas('error');
        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_owner_can_deactivate_another_account_and_it_is_signed_out(): void
    {
        $owner = $this->owner();
        $staff = $this->staff();

        $this->actingAs($owner)->patch("/users/{$staff->id}/status")->assertSessionHas('success');
        $this->assertFalse($staff->fresh()->is_active);

        $this->actingAs($staff->fresh())->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_users_are_deactivated_not_deleted(): void
    {
        $staff = $this->staff();

        $this->actingAs($this->owner())->delete("/users/{$staff->id}")->assertNotFound();
        $this->assertModelExists($staff);
    }
}
