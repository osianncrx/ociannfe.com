<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'osiann'],
            [
                'name' => 'Osiann',
                'email' => 'admin@osiann.com',
                'is_active' => true,
            ]
        );

        $enterprisePlan = Plan::where('slug', 'enterprise')->first();
        if ($enterprisePlan) {
            Subscription::firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'plan_id' => $enterprisePlan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addYears(10),
                ]
            );
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@osiann.com'],
            [
                'name' => 'Admin Osiann',
                'password' => Hash::make('Admin2026!'),
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');
    }
}
