<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WhatsAppService
{
    private string $token;
    private string $adminPhone;
    private bool   $enabled;

    public function __construct()
    {
        $this->token      = env('FONNTE_TOKEN', '');
        $this->adminPhone = env('WA_OB_NUMBER', '');
        $this->enabled    = !empty($this->token) && !empty($this->adminPhone);
    }

    // ── KIRIM PESAN RAW ──────────────────────────────────────────────────────

    public function send(string $phone, string $message): bool
    {
        if (!$this->enabled) {
            Log::info('[WA] Fonnte tidak dikonfigurasi — pesan tidak terkirim.');
            return false;
        }

        try {
            $res = Http::timeout(10)
                ->withHeaders(['Authorization' => $this->token])
                ->post('https://api.fonnte.com/send', [
                    'target'  => $phone,
                    'message' => $message,
                ]);

            if ($res->successful()) {
                Log::info("[WA] Pesan terkirim ke {$phone}");
                return true;
            }

            Log::warning("[WA] Gagal kirim ke {$phone}: " . $res->body());
            return false;

        } catch (\Throwable $e) {
            Log::error('[WA] Exception: ' . $e->getMessage());
            return false;
        }
    }

    // ── KIRIM KE ADMIN ───────────────────────────────────────────────────────

    public function sendToAdmin(string $message): bool
    {
        return $this->send($this->adminPhone, $message);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // TEMPLATE PESAN
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Notifikasi pesanan baru masuk — dikirim saat pelanggan checkout.
     */
    public function notifyNewOrder(Order $order): bool
    {
        $order->loadMissing('items');

        $time   = Carbon::parse($order->created_at)->setTimezone('Asia/Jakarta')->format('H:i');
        $method = strtoupper($order->payment_method ?? 'TUNAI');
        $emoji  = $order->payment_method === 'qris' ? '📱' : '💵';

        // ── Daftar item (dengan nomor urut, nama, qty, harga satuan, subtotal)
        $itemLines = $order->items->map(function ($item, $idx) {
            $name    = $item->item_name . ($item->variant_name ? " ({$item->variant_name})" : '');
            $harga   = $this->rupiah($item->price);
            $sub     = $this->rupiah($item->subtotal);
            $no      = $idx + 1;
            return "{$no}. {$name}\n"
                 . "   {$item->quantity} × {$harga} = *{$sub}*";
        })->implode("\n");

        $total     = $this->rupiah($order->total_price);
        $totalItem = $order->items->sum('quantity');

        $msg = "🔔 *PESANAN BARU — {$order->order_number}*\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "👤 *{$order->customer_name}*\n"
             . "🕐 {$time}  |  {$emoji} {$method}\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "🛒 *DAFTAR PESANAN ({$totalItem} item):*\n\n"
             . "{$itemLines}\n\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "💰 *TOTAL: {$total}*\n"
             . "✅ *Sudah Dibayar*\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "_Segera beli & siapkan pesanan ini! 🏃_";

        return $this->sendToAdmin($msg);
    }

    /**
     * Reminder jika pesanan belum diproses setelah beberapa menit.
     * Dipanggil oleh Artisan command terjadwal.
     *
     * @param  \Illuminate\Support\Collection<Order>  $orders
     */
    public function notifyUnprocessedReminder(\Illuminate\Support\Collection $orders): bool
    {
        if ($orders->isEmpty()) return true;

        $count    = $orders->count();
        $totalRp  = $this->rupiah($orders->sum('total_price'));
        $now      = Carbon::now('Asia/Jakarta')->format('H:i');

        $orderLines = $orders->map(function ($order) {
            $age      = Carbon::parse($order->created_at)->diffForHumans(null, true);
            $items    = $order->items->map(fn($i) =>
                "   • " . $i->item_name
                    . ($i->variant_name ? " ({$i->variant_name})" : '')
                    . " ×{$i->quantity} — " . $this->rupiah($i->subtotal)
            )->implode("\n");

            return "📦 *{$order->order_number}* ({$age} lalu)\n"
                 . "   👤 {$order->customer_name}\n"
                 . "{$items}\n"
                 . "   💰 Total: *" . $this->rupiah($order->total_price) . "*";
        })->implode("\n\n");

        $msg = "⚠️ *REMINDER — PESANAN BELUM DIPROSES*\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "Pukul {$now} — ada *{$count} pesanan* senilai *{$totalRp}* yang\n"
             . "sudah dibayar tapi belum diproses:\n\n"
             . "{$orderLines}\n\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "_Segera beli & proses pesanan di atas! 🏃_";

        return $this->sendToAdmin($msg);
    }

    /**
     * Notifikasi ringkasan harian — opsional, bisa dijadwal jam tutup.
     */
    public function notifyDailySummary(array $summary): bool
    {
        $date     = Carbon::today('Asia/Jakarta')->translatedFormat('d F Y');
        $total    = $this->rupiah($summary['total_revenue'] ?? 0);
        $orders   = $summary['total_orders'] ?? 0;
        $done     = $summary['completed'] ?? 0;
        $cancelled = $summary['cancelled'] ?? 0;

        // Top 3 menu terlaris
        $topLines = '';
        if (!empty($summary['top_items'])) {
            $topLines = "\n\n🏆 *TOP MENU HARI INI:*\n"
                . collect($summary['top_items'])->take(3)->map(function ($item, $i) {
                    return ($i + 1) . ". {$item['name']} — {$item['qty']} porsi";
                })->implode("\n");
        }

        $msg = "📊 *REKAPITULASI HARIAN — {$date}*\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "🧾 Total Pesanan : *{$orders}*\n"
             . "✅ Selesai       : *{$done}*\n"
             . "❌ Dibatalkan    : *{$cancelled}*\n"
             . "💰 Total Omzet  : *{$total}*\n"
             . $topLines . "\n"
             . "━━━━━━━━━━━━━━━━━━━━\n"
             . "_Terima kasih sudah kerja keras hari ini! 💪_";

        return $this->sendToAdmin($msg);
    }

    // ── HELPER ───────────────────────────────────────────────────────────────

    private function rupiah(int|float $amount): string
    {
        return 'Rp ' . number_format((int) $amount, 0, ',', '.');
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
