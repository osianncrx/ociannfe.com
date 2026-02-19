<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\PlansSeeder::class);
    }

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_register_page_loads(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_user_can_register(): void
    {
        $plan = Plan::first();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_name' => 'Test Company',
            'plan_id' => $plan->id,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'test@test.com']);
        $this->assertDatabaseHas('tenants', ['name' => 'Test Company']);
    }

    public function test_user_can_login(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test',
            'slug' => 'test',
            'email' => 'test@test.com',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('user');

        $response = $this->post('/login', [
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_api_login(): void
    {
        $tenant = Tenant::create([
            'name' => 'API Test',
            'slug' => 'api-test',
            'email' => 'api@test.com',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'API User',
            'email' => 'api@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'api@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
    }

    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_admin_dashboard_requires_admin_role(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test',
            'slug' => 'test-role',
            'email' => 'role@test.com',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Normal User',
            'email' => 'normal@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('user');

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(403);
    }
}
