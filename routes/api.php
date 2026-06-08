<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\AnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes — Kantin Mas Wawan (Enhanced)
|--------------------------------------------------------------------------
*/

// ── PUBLIC (pelanggan) ──────────────────────────────────────────────────

// Menu
Route::get('/menu', [MenuController::class, 'index']);
Route::get('/menu/{menuItem}/stock', [MenuController::class, 'stock']);
Route::get('/menu-items/{menuItemId}/reviews', [ReviewController::class, 'index']);

// Customers
Route::post('/customers', [CustomerController::class, 'store']);
Route::get('/customers/{phone}', [CustomerController::class, 'getByPhone']);
Route::get('/customers/{customerId}/favorites', [CustomerController::class, 'favorites']);
Route::post('/customers/{customerId}/favorites', [CustomerController::class, 'addFavorite']);
Route::delete('/customers/{customerId}/favorites/{menuItemId}', [CustomerController::class, 'removeFavorite']);
Route::get('/customers/{customerId}/loyalty', [CustomerController::class, 'loyalty']);
Route::get('/customers/{customerId}/stats', [CustomerController::class, 'stats']);

// Reviews
Route::post('/reviews', [ReviewController::class, 'store']);
Route::put('/reviews/{review}', [ReviewController::class, 'update']);
Route::post('/reviews/{review}/helpful', [ReviewController::class, 'markHelpful']);

// Promo Codes
Route::get('/promo-codes', [PromoCodeController::class, 'index']);
Route::post('/promo-codes/validate', [PromoCodeController::class, 'validate']);

// Orders
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{orderNumber}/status', [OrderController::class, 'status']);

// Cutoff check
Route::get('/cutoff', function () {
    $hour = (int) env('ORDER_CUTOFF_HOUR', 11);
    $now  = \Carbon\Carbon::now('Asia/Jakarta');
    return response()->json([
        'cutoff_hour' => $hour,
        'is_closed'   => $now->hour >= $hour,
        'server_time' => $now->format('H:i'),
    ]);
});


// ── ADMIN ───────────────────────────────────────────────────────────────
Route::middleware('admin.key')->prefix('admin')->group(function () {

    // Menu CRUD
    Route::get('/menu',               [MenuController::class, 'adminIndex']);
    Route::post('/menu',              [MenuController::class, 'store']);
    Route::get('/menu/{menuItem}',    [MenuController::class, 'show']);
    Route::put('/menu/{menuItem}',    [MenuController::class, 'update']);
    Route::delete('/menu/{menuItem}', [MenuController::class, 'destroy']);
    Route::patch('/menu/{menuItem}/toggle', [MenuController::class, 'toggle']);

    // Orders
    Route::get('/orders',                  [OrderController::class, 'index']);
    Route::get('/orders/{order}',          [OrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

    // Reviews
    Route::get('/reviews', [ReviewController::class, 'adminIndex']);
    Route::post('/reviews/{review}/respond', [ReviewController::class, 'respond']);
    Route::patch('/reviews/{review}/toggle-visibility', [ReviewController::class, 'toggleVisibility']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // Promo Codes
    Route::get('/promo-codes', [PromoCodeController::class, 'adminIndex']);
    Route::post('/promo-codes', [PromoCodeController::class, 'store']);
    Route::put('/promo-codes/{promoCode}', [PromoCodeController::class, 'update']);
    Route::delete('/promo-codes/{promoCode}', [PromoCodeController::class, 'destroy']);
    Route::patch('/promo-codes/{promoCode}/toggle', [PromoCodeController::class, 'toggle']);

    // Analytics
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    Route::get('/analytics/customer-insights', [AnalyticsController::class, 'customerInsights']);
    Route::get('/analytics/menu-performance', [AnalyticsController::class, 'menuPerformance']);
    Route::get('/analytics/reviews-sentiment', [AnalyticsController::class, 'reviewsSentiment']);
    Route::get('/analytics/inventory', [AnalyticsController::class, 'inventory']);

    // Legacy Reports (keep for backward compatibility)
    Route::get('/reports/summary',  [ReportController::class, 'summary']);
    Route::get('/reports/category', [ReportController::class, 'byCategory']);
    Route::get('/reports/top-menu', [ReportController::class, 'topMenu']);
    Route::get('/reports/orders',   [ReportController::class, 'orders']);
    Route::get('/reports/export',   [ReportController::class, 'export']);
});