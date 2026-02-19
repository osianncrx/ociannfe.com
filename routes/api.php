<?php
declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmpresaApiController;
use App\Http\Controllers\Api\V1\ComprobanteApiController;
use App\Http\Controllers\Api\V1\RecepcionApiController;
use App\Http\Controllers\Api\V1\ColaApiController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Middleware\LogApiUsage;
use Illuminate\Support\Facades\Route;

// Public webhook endpoint
Route::post('/v1/webhook/hacienda', [WebhookController::class, 'hacienda']);

// Auth endpoints
Route::prefix('v1/auth')->middleware(LogApiUsage::class)->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated via Sanctum token
Route::prefix('v1')->middleware(['auth:sanctum', LogApiUsage::class])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::apiResource('empresas', EmpresaApiController::class)->names([
        'index' => 'api.empresas.index',
        'store' => 'api.empresas.store',
        'show' => 'api.empresas.show',
        'update' => 'api.empresas.update',
        'destroy' => 'api.empresas.destroy',
    ]);

    Route::get('/comprobantes', [ComprobanteApiController::class, 'index']);
    Route::post('/comprobantes/emitir', [ComprobanteApiController::class, 'emitir']);
    Route::get('/comprobantes/{clave}', [ComprobanteApiController::class, 'show']);
    Route::get('/comprobantes/{clave}/estado', [ComprobanteApiController::class, 'estado']);
    Route::get('/comprobantes/{clave}/xml', [ComprobanteApiController::class, 'xml']);

    Route::get('/recepciones', [RecepcionApiController::class, 'index']);
    Route::get('/recepciones/{clave}', [RecepcionApiController::class, 'show']);

    Route::get('/cola', [ColaApiController::class, 'index']);
    Route::post('/cola/procesar', [ColaApiController::class, 'procesar']);
});

// Authenticated via API Key
Route::prefix('v1')->middleware(['api.key', LogApiUsage::class])->group(function () {
    Route::get('/key/empresas', [EmpresaApiController::class, 'index']);
    Route::post('/key/comprobantes/emitir', [ComprobanteApiController::class, 'emitir']);
    Route::get('/key/comprobantes/{clave}', [ComprobanteApiController::class, 'show']);
    Route::get('/key/comprobantes/{clave}/estado', [ComprobanteApiController::class, 'estado']);
    Route::get('/key/comprobantes/{clave}/xml', [ComprobanteApiController::class, 'xml']);
});
