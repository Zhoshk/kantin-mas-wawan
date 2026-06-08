<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    // POST /api/customers — Register/Get customer by phone
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'birth_date' => 'nullable|date',
            'dietary_preferences' => 'nullable|array',
            'allergens' => 'nullable|array',
        ]);

        // Check if customer exists
        $customer = Customer::where('phone', $validated['phone'])->first();

        if ($customer) {
            // Update existing customer
            $customer->update($validated);
            return response()->json([
                'message' => 'Profil berhasil diupdate',
                'data' => $customer,
                'is_new' => false,
            ]);
        }

        // Create new customer
        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Selamat datang! Profil berhasil dibuat',
            'data' => $customer,
            'is_new' => true,
        ], 201);
    }

    // GET /api/customers/{phone} — Get customer by phone
    public function getByPhone(string $phone): JsonResponse
    {
        $customer = Customer::where('phone', $phone)
            ->with(['orders', 'favorites.menuItem', 'loyaltyTransactions'])
            ->firstOrFail();

        $benefits = $customer->getTierBenefits();

        return response()->json([
            'data' => $customer,
            'tier_benefits' => $benefits,
        ]);
    }

    // GET /api/customers/{customerId}/favorites — Get favorites
    public function favorites(int $customerId): JsonResponse
    {
        $favorites = Customer::findOrFail($customerId)
            ->favorites()
            ->with('menuItem')
            ->latest()
            ->get();

        return response()->json(['data' => $favorites]);
    }

    // POST /api/customers/{customerId}/favorites — Add to favorites
    public function addFavorite(Request $request, int $customerId): JsonResponse
    {
        $validated = $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
        ]);

        $customer = Customer::findOrFail($customerId);

        $favorite = $customer->favorites()->firstOrCreate([
            'menu_item_id' => $validated['menu_item_id'],
        ]);

        return response()->json([
            'message' => 'Ditambahkan ke favorit',
            'data' => $favorite->load('menuItem'),
        ]);
    }

    // DELETE /api/customers/{customerId}/favorites/{menuItemId}
    public function removeFavorite(int $customerId, int $menuItemId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);
        $customer->favorites()->where('menu_item_id', $menuItemId)->delete();

        return response()->json(['message' => 'Dihapus dari favorit']);
    }

    // GET /api/customers/{customerId}/loyalty — Get loyalty history
    public function loyalty(int $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);
        $transactions = $customer->loyaltyTransactions()
            ->latest()
            ->paginate(20);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'loyalty_points' => $customer->loyalty_points,
                'tier' => $customer->tier,
                'tier_benefits' => $customer->getTierBenefits(),
            ],
            'transactions' => $transactions,
        ]);
    }

    // GET /api/customers/{customerId}/stats — Get customer statistics
    public function stats(int $customerId): JsonResponse
    {
        $customer = Customer::findOrFail($customerId);

        $stats = [
            'total_orders' => $customer->total_orders,
            'total_spent' => $customer->total_spent,
            'loyalty_points' => $customer->loyalty_points,
            'tier' => $customer->tier,
            'favorite_items' => $customer->favorites()->count(),
            'reviews_written' => $customer->reviews()->count(),
            'avg_order_value' => $customer->total_orders > 0 
                ? (int) ($customer->total_spent / $customer->total_orders)
                : 0,
            'last_order_date' => $customer->last_order_at?->format('Y-m-d'),
            'member_since' => $customer->created_at->format('Y-m-d'),
        ];

        // Get most ordered items
        $topItems = $customer->orders()
            ->with('items.menuItem')
            ->get()
            ->pluck('items')
            ->flatten()
            ->groupBy('menu_item_id')
            ->map(function ($items) {
                return [
                    'menu_item' => $items->first()->menuItem,
                    'times_ordered' => $items->sum('quantity'),
                ];
            })
            ->sortByDesc('times_ordered')
            ->take(5)
            ->values();

        return response()->json([
            'stats' => $stats,
            'top_items' => $topItems,
            'tier_benefits' => $customer->getTierBenefits(),
        ]);
    }
}
