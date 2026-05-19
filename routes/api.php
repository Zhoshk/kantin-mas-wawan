<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes — Kantin Mas Wawan
|--------------------------------------------------------------------------
*/

// ── PUBLIC (pelanggan) ──────────────────────────────────────────────────

// Ambil semua menu aktif
Route::get('/menu', [MenuController::class, 'index']);

// Buat pesanan baru
Route::post('/orders', [OrderController::class, 'store']);


// ── ADMIN ───────────────────────────────────────────────────────────────
// Proteksi sederhana pakai middleware admin_key (cek header X-Admin-Key)

Route::middleware('admin.key')->prefix('admin')->group(function () {

    // Menu CRUD
    Route::get('/menu',              [MenuController::class, 'adminIndex']);
    Route::post('/menu',             [MenuController::class, 'store']);
    Route::get('/menu/{menuItem}',   [MenuController::class, 'show']);
    Route::put('/menu/{menuItem}',   [MenuController::class, 'update']);
    Route::delete('/menu/{menuItem}',[MenuController::class, 'destroy']);
    Route::patch('/menu/{menuItem}/toggle', [MenuController::class, 'toggle']);

    // Pesanan
    Route::get('/orders',                    [OrderController::class, 'index']);
    Route::get('/orders/{order}',            [OrderController::class, 'show']);
    Route::patch('/orders/{order}/status',   [OrderController::class, 'updateStatus']);

    // Laporan
    Route::get('/reports/summary',  [ReportController::class, 'summary']);
    Route::get('/reports/category', [ReportController::class, 'byCategory']);
    Route::get('/reports/top-menu', [ReportController::class, 'topMenu']);
    Route::get('/reports/orders',   [ReportController::class, 'orders']);
    Route::get('/reports/export',   [ReportController::class, 'export']);
});
