<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDailySummary extends Command
{
    protected $signature = 'wa:daily-summary
                            {--date=      : Tanggal rekap (default: hari ini, format Y-m-d)}
                            {--dry-run    : Hanya tampilkan tanpa kirim WA}';

    protected $description = 'Kirim rekap harian omzet kantin ke admin via WhatsApp';

    public function handle(WhatsAppService $wa): int
    {
        $dateStr = $this->option('date') ?: today()->toDateString();
        $dryRun = $this->option('dry-run');

        $this->info("Merekap data tanggal {$dateStr}...");

        $orders = Order::with('items')
            ->whereDate('created_at', $dateStr)
            ->where('payment_status', 'paid')
            ->get();

        $completed = $orders->where('status', 'completed')->count();
        $cancelled = $orders->where('status', 'cancelled')->count();
        $totalRev = $orders->whereNotIn('status', ['cancelled'])->sum('total_price');

        // Top 5 menu terlaris hari itu
        $topItems = OrderItem::select('item_name', DB::raw('SUM(quantity) as qty'))
            ->whereHas('order', fn ($q) => $q->whereDate('created_at', $dateStr)
                ->where('payment_status', 'paid')
                ->where('status', '!=', 'cancelled'))
            ->groupBy('item_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn ($i) => ['name' => $i->item_name, 'qty' => $i->qty])
            ->toArray();

        $summary = [
            'total_orders' => $orders->count(),
            'total_revenue' => $totalRev,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'top_items' => $topItems,
        ];

        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Total Pesanan',  $summary['total_orders']],
                ['Selesai',        $summary['completed']],
                ['Dibatalkan',     $summary['cancelled']],
                ['Total Omzet',    'Rp '.number_format($summary['total_revenue'], 0, ',', '.')],
            ]
        );

        if ($dryRun) {
            $this->warn('[DRY RUN] Pesan tidak dikirim.');

            return self::SUCCESS;
        }

        if (! $wa->isEnabled()) {
            $this->error('Fonnte belum dikonfigurasi (FONNTE_TOKEN / WA_OB_NUMBER kosong).');

            return self::FAILURE;
        }

        $sent = $wa->notifyDailySummary($summary);

        $sent
            ? $this->info('✓ Rekap harian berhasil dikirim ke admin.')
            : $this->error('✗ Gagal kirim rekap. Cek log Laravel.');

        return $sent ? self::SUCCESS : self::FAILURE;
    }
}
