<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\EmpresaController;
use App\Http\Controllers\User\ComprobanteController;
use App\Http\Controllers\User\RecepcionController;
use App\Http\Controllers\User\DeclaracionController;
use App\Http\Controllers\User\ApiKeyController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\ApiKeyAdminController;
use App\Http\Controllers\Admin\ApiUsageLogController;
use App\Http\Controllers\Api\OpenApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('user.dashboard') : redirect()->route('login');
});

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Public XML access (clave serves as auth — 50-digit unique key)
Route::get('/comprobantes/{clave}/xml', [ComprobanteController::class, 'xml'])->name('comprobantes.xml');

// User routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('user.dashboard');

    Route::post('/empresas/lookup-cedula', [EmpresaController::class, 'lookupCedula'])->name('empresas.lookup-cedula');
    Route::post('/empresas/{id}/verificar-credenciales', [EmpresaController::class, 'verificarCredenciales'])->name('empresas.verificar-credenciales');
    Route::get('/empresas/{id}/plantilla-pdf', [EmpresaController::class, 'editPlantillaPdf'])->name('empresas.plantilla-pdf');
    Route::put('/empresas/{id}/plantilla-pdf', [EmpresaController::class, 'updatePlantillaPdf'])->name('empresas.plantilla-pdf.update');
    Route::get('/empresas/{id}/plantilla-pdf/preview', [EmpresaController::class, 'previewPlantillaPdf'])->name('empresas.plantilla-pdf.preview');
    Route::resource('empresas', EmpresaController::class);
    Route::resource('comprobantes', ComprobanteController::class)->only(['index', 'show', 'create', 'store']);
    Route::post('/comprobantes/{id}/procesar-envio', [ComprobanteController::class, 'procesarEnvio'])->name('comprobantes.procesar-envio');
    Route::post('/comprobantes/{id}/consultar-estado', [ComprobanteController::class, 'consultarEstado'])->name('comprobantes.consultar-estado');
    Route::get('/comprobantes/{id}/pdf', [ComprobanteController::class, 'pdf'])->name('comprobantes.pdf');
    Route::post('/comprobantes/buscar-cabys', [ComprobanteController::class, 'buscarCabys'])->name('comprobantes.buscar-cabys');
    Route::post('/recepciones/parsear-xml', [RecepcionController::class, 'parsearXml'])->name('recepciones.parsear-xml');
    Route::post('/recepciones/parsear-xml-multiple', [RecepcionController::class, 'parsearXmlMultiple'])->name('recepciones.parsear-xml-multiple');
    Route::post('/recepciones/store-masivo', [RecepcionController::class, 'storeMasivo'])->name('recepciones.store-masivo');
    Route::resource('recepciones', RecepcionController::class)->only(['index', 'show', 'create', 'store']);
    Route::resource('declaraciones', DeclaracionController::class)->except(['edit']);

    Route::resource('api-keys', ApiKeyController::class)->except(['show', 'edit', 'update']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// API Documentation (public)
Route::get('/api/docs', [OpenApiController::class, 'docs'])->name('api.docs');
Route::get('/api/openapi.json', [OpenApiController::class, 'json'])->name('api.openapi');

// Admin routes
Route::middleware(['auth', 'role:super_admin|admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('tenants', TenantController::class);
    Route::resource('planes', PlanController::class);
    Route::resource('usuarios', UserController::class);
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    // API Keys management
    Route::get('/api-keys', [ApiKeyAdminController::class, 'index'])->name('api-keys.index');
    Route::get('/api-keys/create', [ApiKeyAdminController::class, 'create'])->name('api-keys.create');
    Route::post('/api-keys', [ApiKeyAdminController::class, 'store'])->name('api-keys.store');
    Route::get('/api-keys/{id}', [ApiKeyAdminController::class, 'show'])->name('api-keys.show');
    Route::patch('/api-keys/{id}/toggle', [ApiKeyAdminController::class, 'toggleStatus'])->name('api-keys.toggle');
    Route::delete('/api-keys/{id}', [ApiKeyAdminController::class, 'destroy'])->name('api-keys.destroy');

    // API Usage Logs
    Route::get('/api-logs', [ApiUsageLogController::class, 'index'])->name('api-logs.index');

    // API Documentation (admin view)
    Route::get('/api-docs', fn () => view('admin.api-docs'))->name('api-docs');
});
