<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basico',
                'description' => 'Ideal para pequeños negocios. 1 empresa, 100 comprobantes/mes.',
                'price' => 9.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'max_empresas' => 1,
                'max_comprobantes_mes' => 100,
                'max_api_keys' => 2,
                'has_api_access' => true,
                'has_s3_storage' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Profesional',
                'slug' => 'profesional',
                'description' => 'Para empresas en crecimiento. 5 empresas, 1000 comprobantes/mes.',
                'price' => 29.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'max_empresas' => 5,
                'max_comprobantes_mes' => 1000,
                'max_api_keys' => 10,
                'has_api_access' => true,
                'has_s3_storage' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Sin límites. Empresas y comprobantes ilimitados.',
                'price' => 99.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'max_empresas' => -1,
                'max_comprobantes_mes' => -1,
                'max_api_keys' => -1,
                'has_api_access' => true,
                'has_s3_storage' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
