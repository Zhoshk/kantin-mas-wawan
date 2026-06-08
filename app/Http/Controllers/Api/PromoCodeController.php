<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PromoCodeController extends Controller
{
    // GET /api/promo-codes — List active promo codes
    public function index(): JsonResponse
    {
        $promoCodes = PromoCode::active()->get();

        return response()->json(['data' => $promoCodes]);
    }

    // POST /api/promo-codes/validate — Validate promo code
    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
            'order_total' => 'required|integer|min:0',
            'items' => 'nullable|array',
        ]);

        $promoCode = PromoCode::where('code', $validated['code'])->first();

        if (!$promoCode) {
            return response()->json([
                'valid' => false,
                'message' => 'Kode promo tidak ditemukan',
            ], 404);
        }

        $customer = $validated['customer_id'] 
            ? Customer::find($validated['customer_id'])
            : null;

        $isValid = $promoCode->isValid($customer, $validated['order_total']);

        if (!$isValid) {
            $message = 'Kode promo tidak valid atau sudah tidak berlaku';

            if ($validated['order_total'] < $promoCode->min_purchase) {
                $message = "Minimal pembelian Rp " . number_format($promoCode->min_purchase, 0, ',', '.');
            }

            return response()->json([
                'valid' => false,
                'message' => $message,
            ], 422);
        }

        $discountAmount = $promoCode->calculateDiscount(
            $validated['order_total'],
            $validated['items'] ?? []
        );

        return response()->json([
            'valid' => true,
            'message' => 'Kode promo berhasil digunakan!',
            'data' => [
                'promo_code' => $promoCode,
                'discount_amount' => $discountAmount,
                'final_total' => max(0, $validated['order_total'] - $discountAmount),
            ],
        ]);
    }

    // ── ADMIN ROUTES ───────────────────────────────────────────
    
    // GET /api/admin/promo-codes
    public function adminIndex(): JsonResponse
    {
        $promoCodes = PromoCode::orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $promoCodes]);
    }

    // POST /api/admin/promo-codes
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:promo_codes,code|max:50',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed,free_delivery,buy_x_get_y',
            'discount_value' => 'required_unless:type,free_delivery|integer|min:0',
            'min_purchase' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_customer' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'applicable_categories' => 'nullable|array',
            'applicable_items' => 'nullable|array',
            'excluded_items' => 'nullable|array',
            'customer_tiers' => 'nullable|array',
            'is_active' => 'boolean',
            'first_order_only' => 'boolean',
        ]);

        $promoCode = PromoCode::create($validated);

        return response()->json([
            'message' => 'Kode promo berhasil dibuat',
            'data' => $promoCode,
        ], 201);
    }

    // PUT /api/admin/promo-codes/{id}
    public function update(Request $request, PromoCode $promoCode): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|unique:promo_codes,code,' . $promoCode->id . '|max:50',
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:percentage,fixed,free_delivery,buy_x_get_y',
            'discount_value' => 'sometimes|integer|min:0',
            'min_purchase' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_customer' => 'nullable|integer|min:1',
            'valid_from' => 'sometimes|date',
            'valid_until' => 'sometimes|date',
            'applicable_categories' => 'nullable|array',
            'applicable_items' => 'nullable|array',
            'excluded_items' => 'nullable|array',
            'customer_tiers' => 'nullable|array',
            'is_active' => 'boolean',
            'first_order_only' => 'boolean',
        ]);

        $promoCode->update($validated);

        return response()->json([
            'message' => 'Kode promo berhasil diupdate',
            'data' => $promoCode,
        ]);
    }

    // DELETE /api/admin/promo-codes/{id}
    public function destroy(PromoCode $promoCode): JsonResponse
    {
        $promoCode->delete();

        return response()->json(['message' => 'Kode promo berhasil dihapus']);
    }

    // PATCH /api/admin/promo-codes/{id}/toggle
    public function toggle(PromoCode $promoCode): JsonResponse
    {
        $promoCode->update(['is_active' => !$promoCode->is_active]);

        $status = $promoCode->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'message' => "Kode promo berhasil $status",
            'data' => $promoCode,
        ]);
    }
}
