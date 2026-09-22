<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth', 'role:owner'])->get('/test-owner-only', function () {
            return response('Owner Granted', 200);
        });

        Route::middleware(['auth', 'role:owner,staff'])->get('/test-staff-route', function () {
            return response('Staff/Owner Granted', 200);
        });

        Route::middleware(['auth', 'role:owner,baker'])->get('/test-baker-route', function () {
            return response('Baker/Owner Granted', 200);
        });

        Route::middleware(['auth', 'role:owner,delivery'])->get('/test-delivery-route', function () {
            return response('Delivery/Owner Granted', 200);
        });
    }

    public function test_owner_can_access_owner_only_routes(): void
    {
        $owner = User::create([
            'first_name' => 'Maria',
            'last_name' => 'SweetNest',
            'email' => 'owner@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->get('/test-owner-only');

        $response->assertStatus(200);
        $response->assertSee('Owner Granted');
    }

    public function test_staff_is_forbidden_from_accessing_owner_only_routes(): void
    {
        $staff = User::create([
            'first_name' => 'Anna',
            'last_name' => 'Reyes',
            'email' => 'staff@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)->get('/test-owner-only');

        $response->assertStatus(403);
    }

    public function test_staff_can_access_staff_routes(): void
    {
        $staff = User::create([
            'first_name' => 'Anna',
            'last_name' => 'Reyes',
            'email' => 'staff@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)->get('/test-staff-route');

        $response->assertStatus(200);
        $response->assertSee('Staff/Owner Granted');
    }

    public function test_baker_cannot_access_staff_routes(): void
    {
        $baker = User::create([
            'first_name' => 'Carlos',
            'last_name' => 'Mendoza',
            'email' => 'baker@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'baker',
            'is_active' => true,
        ]);

        $response = $this->actingAs($baker)->get('/test-staff-route');

        $response->assertStatus(403);
    }

    public function test_delivery_staff_can_access_delivery_routes(): void
    {
        $delivery = User::create([
            'first_name' => 'Rico',
            'last_name' => 'Dela Cruz',
            'email' => 'delivery@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'delivery',
            'is_active' => true,
        ]);

        $response = $this->actingAs($delivery)->get('/test-delivery-route');

        $response->assertStatus(200);
        $response->assertSee('Delivery/Owner Granted');
    }

    public function test_delivery_staff_cannot_access_baker_routes(): void
    {
        $delivery = User::create([
            'first_name' => 'Rico',
            'last_name' => 'Dela Cruz',
            'email' => 'delivery@sweetnest.com',
            'password' => Hash::make('password'),
            'role' => 'delivery',
            'is_active' => true,
        ]);

        $response = $this->actingAs($delivery)->get('/test-baker-route');

        $response->assertStatus(403);
    }
}
