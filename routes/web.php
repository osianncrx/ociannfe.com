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
use App\Http\Controllers\User\ApiKeyController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LogController;
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

// User routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('user.dashboard');

    Route::post('/empresas/lookup-cedula', [EmpresaController::class, 'lookupCedula'])->name('empresas.lookup-cedula');
    Route::post('/empresas/{id}/verificar-credenciales', [EmpresaController::class, 'verificarCredenciales'])->name('empresas.verificar-credenciales');
    Route::resource('empresas', EmpresaController::class);
    Route::resource('comprobantes', ComprobanteController::class)->only(['index', 'show', 'create', 'store']);
    Route::get('/comprobantes/{clave}/xml', [ComprobanteController::class, 'xml'])->name('comprobantes.xml');
    Route::resource('recepciones', RecepcionController::class)->only(['index', 'show', 'create', 'store']);

    Route::resource('api-keys', ApiKeyController::class)->except(['show', 'edit', 'update']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

// Admin routes
Route::middleware(['auth', 'role:super_admin|admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('tenants', TenantController::class);
    Route::resource('planes', PlanController::class);
    Route::resource('usuarios', UserController::class);
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
});
