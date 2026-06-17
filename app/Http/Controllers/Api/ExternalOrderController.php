<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExternalOrder;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ExternalOrderController extends Controller
{
    // ── GET /api/external-orders/cutoff ──────────────────────────────────────
    // Cek apakah order luar masih buka (publik, tanpa auth)
    public function cutoff(): JsonResponse
    {
        $cutoffHour = (int) env('EXT_ORDER_CUTOFF_HOUR', 10);
        $now        = Carbon::now('Asia/Jakarta');
        $isClosed   = $now->hour >= $cutoffHour;

        return response()->json([
            'is_closed'    => $isClosed,
            'cutoff_hour'  => $cutoffHour,
            'current_hour' => $now->hour,
            'server_time'  => $now->format('H:i'),
        ]);
    }

    // ── POST /api/external-orders ─────────────────────────────────────────────
    // Buat order baru (publik)
    public function store(Request $request): JsonResponse
    {
        // Cek cutoff
        $cutoffHour = (int) env('EXT_ORDER_CUTOFF_HOUR', 10);
        $now        = Carbon::now('Asia/Jakarta');

        if ($now->hour >= $cutoffHour) {
            return response()->json([
                'message' => "Order makanan luar sudah ditutup. Batas pemesanan jam {$cutoffHour}.00.",
                'cutoff'  => true,
            ], 422);
        }

        $request->validate([
            'customer_name'   => 'required|string|max:100',
            'restaurant_name' => 'required|string|max:100',
            'items_text'      => 'required|string',
            'notes'           => 'nullable|string|max:500',
            'estimated_price' => 'nullable|integer|min:0',
        ]);

        // Generate nomor order EXT-001, EXT-002, dst
        $last     = ExternalOrder::latest('id')->first();
        $nextNum  = $last
            ? ((int) ltrim(substr($last->order_number, 4), '0') + 1)
            : 1;
        $orderNum = 'EXT-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $order = ExternalOrder::create([
            'order_number'    => $orderNum,
            'customer_name'   => $request->customer_name,
            'restaurant_name' => $request->restaurant_name,
            'items_text'      => $request->items_text,
            'notes'           => $request->notes,
            'estimated_price' => $request->estimated_price,
            'status'          => 'pending',
        ]);

        // Kirim notif WA ke Mas Wawan (fail-safe: tidak batalkan order jika WA gagal)
        try {
            $wa = new WhatsAppService();
            $wa->notifyExternalOrder($order);
        } catch (\Throwable $e) {
            \Log::warning('[WA] Notif order luar gagal: ' . $e->getMessage());
        }

        return response()->json($order, 201);
    }

    // ── GET /api/admin/external-orders ───────────────────────────────────────
    // List semua order luar (admin only)
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $query  = ExternalOrder::latest();

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(20);
        return response()->json($orders);
    }

    // ── PATCH /api/admin/external-orders/{id}/status ─────────────────────────
    // Update status order (admin only)
    public function updateStatus(Request $request, ExternalOrder $externalOrder): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,bought,delivered,cancelled',
        ]);

        $externalOrder->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status order diupdate.',
            'data'    => $externalOrder,
        ]);
    }
}