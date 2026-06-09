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

// Cek stok real-time per menu item (dipakai frontend saat tambah ke cart)
Route::get('/menu/{menuItem}/stock', [MenuController::class, 'stock']);

// Cek jam cutoff
Route::get('/cutoff', function () {
    $hour = (int) env('ORDER_CUTOFF_HOUR', 11);
    $now  = \Carbon\Carbon::now('Asia/Jakarta');
    return response()->json([
        'cutoff_hour' => $hour,
        'is_closed'   => $now->hour >= $hour,
        'server_time' => $now->format('H:i'),
    ]);
});

// Buat pesanan baru
Route::post('/orders', [OrderController::class, 'store']);

// Cek status pesanan (untuk halaman tracking pelanggan)
Route::get('/orders/{orderNumber}/status', [OrderController::class, 'status']);


// ── ADMIN ───────────────────────────────────────────────────────────────
Route::middleware('admin.key')->prefix('admin')->group(function () {

    // Menu CRUD
    Route::get('/menu',               [MenuController::class, 'adminIndex']);
    Route::post('/menu',              [MenuController::class, 'store']);
    Route::get('/menu/{menuItem}',    [MenuController::class, 'show']);
    Route::put('/menu/{menuItem}',    [MenuController::class, 'update']);
    Route::delete('/menu/{menuItem}', [MenuController::class, 'destroy']);
    Route::patch('/menu/{menuItem}/toggle', [MenuController::class, 'toggle']);

    // Pesanan
    Route::get('/orders',                  [OrderController::class, 'index']);
    Route::get('/orders/{order}',          [OrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

    // Laporan
    Route::get('/reports/summary',  [ReportController::class, 'summary']);
    Route::get('/reports/category', [ReportController::class, 'byCategory']);
    Route::get('/reports/top-menu', [ReportController::class, 'topMenu']);
    Route::get('/reports/orders',   [ReportController::class, 'orders']);
    Route::get('/reports/export',   [ReportController::class, 'export']);
});