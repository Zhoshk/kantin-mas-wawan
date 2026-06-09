<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    // POST /api/orders — buat pesanan baru (dari pelanggan)
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:100',
            'items'         => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.variant_name' => 'nullable|string',
            'items.*.price'        => 'required|integer|min:0',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        $totalPrice = collect($validated['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

        $order = Order::create([
            'order_number'   => Order::generateOrderNumber(),
            'customer_name'  => $validated['customer_name'],
            'total_price'    => $totalPrice,
            'status'         => 'pending',
            'payment_status' => 'paid', // sudah bayar QRIS sebelum konfirmasi
        ]);

        foreach ($validated['items'] as $item) {
            $menuItem = MenuItem::find($item['menu_item_id']);
            $order->items()->create([
                'menu_item_id' => $item['menu_item_id'],
                'item_name'    => $menuItem->name,
                'variant_name' => $item['variant_name'] ?? null,
                'price'        => $item['price'],
                'quantity'     => $item['quantity'],
                'subtotal'     => $item['price'] * $item['quantity'],
            ]);
        }

        return response()->json([
            'message'      => 'Pesanan berhasil dibuat',
            'order_number' => $order->order_number,
            'data'         => $order->load('items'),
        ], 201);
    }

    // GET /api/admin/orders — daftar semua pesanan (admin)
    public function index(Request $request): JsonResponse
    {
        $query = Order::with('items')->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('customer_name', 'like', "%{$request->search}%")
                  ->orWhere('order_number', 'like', "%{$request->search}%");
            });
        }

        $orders = $query->paginate(20);
        return response()->json($orders);
    }

    // GET /api/admin/orders/{id} — detail pesanan
    public function show(Order $order): JsonResponse
    {
        return response()->json(['data' => $order->load('items.menuItem')]);
    }

    // PATCH /api/admin/orders/{id}/status — update status pesanan
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,ready,completed,cancelled',
        ]);

        $order->update($validated);
        return response()->json(['message' => 'Status pesanan diupdate', 'data' => $order]);
    }
}
