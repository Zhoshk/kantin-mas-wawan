<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    // GET /api/menu-items/{menuItemId}/reviews — Get reviews for a menu item
    public function index(int $menuItemId): JsonResponse
    {
        $reviews = Review::where('menu_item_id', $menuItemId)
            ->visible()
            ->with(['customer', 'order'])
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    // POST /api/reviews — Submit a review
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'customer_id' => 'required|exists:customers,id',
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'string', // image URLs
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        // Check if customer has already reviewed this item from this order
        $existingReview = Review::where('order_id', $validated['order_id'])
            ->where('menu_item_id', $validated['menu_item_id'])
            ->where('customer_id', $validated['customer_id'])
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'Kamu sudah memberi ulasan untuk item ini',
            ], 422);
        }

        // Verify order belongs to customer
        $order = Order::where('id', $validated['order_id'])
            ->where('customer_id', $validated['customer_id'])
            ->where('status', 'completed')
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan atau belum selesai',
            ], 422);
        }

        $review = Review::create($validated);

        // Mark order as reviewed if all items are reviewed
        $orderItemsCount = $order->items()->count();
        $reviewedItemsCount = Review::where('order_id', $order->id)->count();

        if ($orderItemsCount === $reviewedItemsCount) {
            $order->update(['is_reviewed' => true]);
        }

        return response()->json([
            'message' => 'Terima kasih atas ulasannya!',
            'data' => $review->load(['menuItem', 'customer']),
        ], 201);
    }

    // PUT /api/reviews/{id} — Update review
    public function update(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ]);

        $review->update($validated);

        return response()->json([
            'message' => 'Ulasan berhasil diupdate',
            'data' => $review,
        ]);
    }

    // POST /api/reviews/{id}/helpful — Mark review as helpful
    public function markHelpful(Review $review): JsonResponse
    {
        $review->increment('helpful_count');

        return response()->json([
            'message' => 'Terima kasih atas feedback-nya!',
            'data' => $review,
        ]);
    }

    // ── ADMIN ROUTES ───────────────────────────────────────────

    // GET /api/admin/reviews
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Review::with(['menuItem', 'customer', 'order'])->latest();

        if ($request->has('menu_item_id')) {
            $query->where('menu_item_id', $request->menu_item_id);
        }

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->has('visibility')) {
            $query->where('is_visible', $request->visibility === 'visible');
        }

        return response()->json($query->paginate(20));
    }

    // POST /api/admin/reviews/{id}/respond — Admin response
    public function respond(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'response' => 'required|string|max:500',
        ]);

        $review->update([
            'admin_response' => $validated['response'],
            'admin_response_at' => now(),
        ]);

        return response()->json([
            'message' => 'Respon berhasil ditambahkan',
            'data' => $review,
        ]);
    }

    // PATCH /api/admin/reviews/{id}/toggle-visibility
    public function toggleVisibility(Review $review): JsonResponse
    {
        $review->update(['is_visible' => !$review->is_visible]);

        $status = $review->is_visible ? 'ditampilkan' : 'disembunyikan';

        return response()->json([
            'message' => "Ulasan berhasil $status",
            'data' => $review,
        ]);
    }

    // DELETE /api/admin/reviews/{id}
    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json(['message' => 'Ulasan berhasil dihapus']);
    }
}
