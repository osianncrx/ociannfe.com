<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ColaService;
use App\Services\FacturacionService;
use Illuminate\Support\ServiceProvider;

class FacturacionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FacturacionService::class, function ($app) {
            return new FacturacionService();
        });

        $this->app->singleton(ColaService::class, function ($app) {
            return new ColaService($app->make(FacturacionService::class));
        });
    }

    public function boot(): void
    {
        $storagePath = config('facturacion.storage_path');
        if ($storagePath && !is_dir($storagePath)) {
            @mkdir($storagePath, 0755, true);
        }
    }
}
