<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'first_name' => 'Maria',
            'last_name' => 'SweetNest',
            'email' => 'owner@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->staff = User::create([
            'first_name' => 'Anna',
            'last_name' => 'Reyes',
            'email' => 'staff@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    public function test_owner_can_view_user_accounts_list(): void
    {
        $response = $this->actingAs($this->owner)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('User Accounts Administration');
        $response->assertSee('Maria SweetNest');
        $response->assertSee('Anna Reyes');
        $response->assertSee('staff@sweetnest.com');
    }

    public function test_owner_can_register_new_staff_account(): void
    {
        $response = $this->actingAs($this->owner)->post(route('users.store'), [
            'first_name' => 'John',
            'middle_name' => 'D.',
            'last_name' => 'Cruz',
            'email' => 'john.cruz@sweetnest.com',
            'role' => 'staff',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Cruz',
            'email' => 'john.cruz@sweetnest.com',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $newUser = User::where('email', 'john.cruz@sweetnest.com')->first();
        $this->assertTrue(Hash::check('newpassword123', $newUser->password));
    }

    public function test_owner_can_toggle_user_active_status(): void
    {
        $this->assertTrue($this->staff->is_active);

        // Deactivate staff
        $response = $this->actingAs($this->owner)->patch(route('users.toggleStatus', $this->staff));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->staff->refresh();
        $this->assertFalse($this->staff->is_active);

        // Reactivate staff
        $response = $this->actingAs($this->owner)->patch(route('users.toggleStatus', $this->staff));
        $this->staff->refresh();
        $this->assertTrue($this->staff->is_active);
    }

    public function test_owner_cannot_deactivate_own_account(): void
    {
        $response = $this->actingAs($this->owner)->patch(route('users.toggleStatus', $this->owner));

        $response->assertSessionHas('error');
        $this->owner->refresh();
        $this->assertTrue($this->owner->is_active);
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $this->staff->update(['is_active' => false]);

        $response = $this->post(route('login.post'), [
            'email' => 'staff@sweetnest.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_owner_can_reset_staff_password(): void
    {
        $response = $this->actingAs($this->owner)->patch(route('users.resetPassword', $this->staff), [
            'new_password' => 'newSecretPass456',
            'new_password_confirmation' => 'newSecretPass456',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->staff->refresh();
        $this->assertTrue(Hash::check('newSecretPass456', $this->staff->password));
    }

    public function test_staff_is_forbidden_from_user_administration(): void
    {
        $response = $this->actingAs($this->staff)->get(route('users.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->staff)->get(route('users.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->staff)->post(route('users.store'), []);
        $response->assertStatus(403);

        $response = $this->actingAs($this->staff)->patch(route('users.toggleStatus', $this->owner));
        $response->assertStatus(403);
    }

    public function test_unauthenticated_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('users.index'));
        $response->assertRedirect(route('login'));
    }
}
