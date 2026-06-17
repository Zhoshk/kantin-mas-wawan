<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ExternalOrderController;

/*
|--------------------------------------------------------------------------
| API Routes — Kantin Mas Wawan
|--------------------------------------------------------------------------
*/

// ── PUBLIC (pelanggan) ──────────────────────────────────────────────────

// Ambil semua menu aktif
Route::get('/menu', [MenuController::class, 'index']);

// Cek stok real-time per menu item
Route::get('/menu/{menuItem}/stock', [MenuController::class, 'stock']);

// Cek jam cutoff kantin
Route::get('/cutoff', function () {
    $hour = (int) env('ORDER_CUTOFF_HOUR', 11);
    $now  = \Carbon\Carbon::now('Asia/Jakarta');
    return response()->json([
        'cutoff_hour' => $hour,
        'is_closed'   => $now->hour >= $hour,
        'server_time' => $now->format('H:i'),
    ]);
});

// Buat pesanan kantin baru
Route::post('/orders', [OrderController::class, 'store']);

// Cek status pesanan (tracking pelanggan)
Route::get('/orders/{orderNumber}/status', [OrderController::class, 'status']);

// ── EXTERNAL ORDERS (publik) ─────────────────────────────────────────────

// Cek jam cutoff order makanan luar
// PENTING: route ini harus SEBELUM /{externalOrder} agar tidak bentrok
Route::get('/external-orders/cutoff', [ExternalOrderController::class, 'cutoff']);

// Buat order makanan luar baru
Route::post('/external-orders', [ExternalOrderController::class, 'store']);


// ── ADMIN ───────────────────────────────────────────────────────────────
Route::middleware('admin.key')->prefix('admin')->group(function () {

    // Menu CRUD
    Route::get('/menu',               [MenuController::class, 'adminIndex']);
    Route::post('/menu',              [MenuController::class, 'store']);
    Route::get('/menu/{menuItem}',    [MenuController::class, 'show']);
    Route::put('/menu/{menuItem}',    [MenuController::class, 'update']);
    Route::delete('/menu/{menuItem}', [MenuController::class, 'destroy']);
    Route::patch('/menu/{menuItem}/toggle', [MenuController::class, 'toggle']);

    // Pesanan kantin
    Route::get('/orders',                  [OrderController::class, 'index']);
    Route::get('/orders/{order}',          [OrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

    // Order makanan luar
    Route::get('/external-orders',                         [ExternalOrderController::class, 'index']);
    Route::patch('/external-orders/{externalOrder}/status', [ExternalOrderController::class, 'updateStatus']);

    // Laporan
    Route::get('/reports/summary',  [ReportController::class, 'summary']);
    Route::get('/reports/category', [ReportController::class, 'byCategory']);
    Route::get('/reports/top-menu', [ReportController::class, 'topMenu']);
    Route::get('/reports/orders',   [ReportController::class, 'orders']);
    Route::get('/reports/export',   [ReportController::class, 'export']);
});