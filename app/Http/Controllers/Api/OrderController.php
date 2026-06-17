<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(private WhatsAppService $wa) {}

    // ── POST /api/orders — buat pesanan baru (dari pelanggan) ──────────────
    public function store(Request $request): JsonResponse
    {
        // ── CUTOFF JAM ────────────────────────────────────────────────────
        $now = Carbon::now('Asia/Jakarta');
        $cutoffHour = (int) env('ORDER_CUTOFF_HOUR', 11);

        if ($now->hour >= $cutoffHour) {
            return response()->json([
                'message' => "Maaf, pemesanan sudah ditutup. Pesanan hanya bisa dilakukan sebelum jam {$cutoffHour}.00.",
                'cutoff' => true,
                'cutoff_hour' => $cutoffHour,
            ], 422);
        }

        // ── VALIDASI REQUEST ──────────────────────────────────────────────
        $validated = $request->validate([
            'customer_name' => 'required|string|max:100',
            'payment_method' => 'nullable|string|in:qris,tunai',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.variant_name' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1|max:99',
        ]);

        try {
            $order = DB::transaction(function () use ($validated) {

                $totalPrice = 0;
                $orderLines = [];

                foreach ($validated['items'] as $reqItem) {

                    $menuItem = MenuItem::lockForUpdate()
                        ->findOrFail($reqItem['menu_item_id']);

                    $price = $menuItem->price;

                    if (! empty($reqItem['variant_name']) && is_array($menuItem->variants)) {
                        $variant = collect($menuItem->variants)
                            ->firstWhere('name', $reqItem['variant_name']);

                        if ($variant) {
                            $price = (int) $variant['price'];
                        }
                    }

                    if ($menuItem->stock !== null && $menuItem->stock < $reqItem['quantity']) {
                        throw new \RuntimeException(json_encode([
                            'message' => "Stok {$menuItem->name} tidak mencukupi. Tersisa: {$menuItem->stock}.",
                            'stock_error' => true,
                            'item_name' => $menuItem->name,
                            'stock_left' => $menuItem->stock,
                        ]));
                    }

                    $subtotal = $price * $reqItem['quantity'];
                    $totalPrice += $subtotal;

                    $orderLines[] = [
                        'menuItem' => $menuItem,
                        'menu_item_id' => $reqItem['menu_item_id'],
                        'variant_name' => $reqItem['variant_name'] ?? null,
                        'price' => $price,
                        'quantity' => $reqItem['quantity'],
                        'subtotal' => $subtotal,
                    ];
                }

                $last = Order::lockForUpdate()->latest('id')->first();
                $nextNumber = $last ? ((int) substr($last->order_number, 4)) + 1 : 1;
                $orderNumber = 'ORD-'.str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'order_number' => $orderNumber,
                    'customer_name' => $validated['customer_name'],
                    'payment_method' => $validated['payment_method'] ?? 'tunai',
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'payment_status' => 'paid',
                ]);

                foreach ($orderLines as $line) {
                    $order->items()->create([
                        'menu_item_id' => $line['menu_item_id'],
                        'item_name' => $line['menuItem']->name,
                        'variant_name' => $line['variant_name'],
                        'price' => $line['price'],
                        'quantity' => $line['quantity'],
                        'subtotal' => $line['subtotal'],
                    ]);

                    if ($line['menuItem']->stock !== null) {
                        $line['menuItem']->decrement('stock', $line['quantity']);
                    }
                }

                return $order;
            });

        } catch (\RuntimeException $e) {
            $payload = json_decode($e->getMessage(), true);
            if ($payload && isset($payload['stock_error'])) {
                return response()->json($payload, 422);
            }
            throw $e;
        }

        // ── KIRIM NOTIFIKASI WHATSAPP ─────────────────────────────────────
        $this->wa->notifyNewOrder($order->load('items'));

        return response()->json([
            'message' => 'Pesanan berhasil dibuat',
            'order_number' => $order->order_number,
            'data' => $order->load('items'),
        ], 201);
    }

    // ── GET /api/orders/{orderNumber}/status ──────────────────────────────
    public function status(string $orderNumber): JsonResponse
    {
        $order = Order::with('items')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return response()->json(['data' => $order]);
    }

    // ── GET /api/admin/orders ─────────────────────────────────────────────
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
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', "%{$request->search}%")
                    ->orWhere('order_number', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->paginate(20));
    }

    // ── GET /api/admin/orders/{id} ────────────────────────────────────────
    public function show(Order $order): JsonResponse
    {
        return response()->json(['data' => $order->load('items.menuItem')]);
    }

    // ── PATCH /api/admin/orders/{id}/status ──────────────────────────────
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,ready,completed,cancelled',
        ]);

        $order->update($validated);

        return response()->json(['message' => 'Status pesanan diupdate', 'data' => $order]);
    }
}
