<?php

use App\Http\Controllers\WebhookDashboard\AuthController;
use App\Http\Controllers\WebhookDashboard\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('webhooks.dashboard.login');
});

Route::prefix('webhooks/dashboard')->name('webhooks.dashboard.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');

    Route::middleware('webhook.dashboard')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('deliveries/{delivery}', [DashboardController::class, 'show'])->name('show');
        Route::post('deliveries/{delivery}/retry', [DashboardController::class, 'retry'])->name('retry');
    });
});
