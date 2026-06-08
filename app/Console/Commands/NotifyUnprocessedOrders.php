<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyUnprocessedOrders extends Command
{
    /**
     * Nama command Artisan.
     * Jalankan manual: php artisan wa:remind-orders
     * Atau otomatis via scheduler di console.php
     */
    protected $signature = 'wa:remind-orders
                            {--minutes=10 : Batas menit setelah pesanan masuk sebelum dikirim reminder}
                            {--dry-run    : Hanya tampilkan tanpa kirim WA}';

    protected $description = 'Kirim reminder WA ke admin untuk pesanan yang sudah dibayar tapi belum diproses';

    public function handle(WhatsAppService $wa): int
    {
        $minutes = (int) $this->option('minutes');
        $dryRun  = $this->option('dry-run');

        $this->info("Mengecek pesanan yang belum diproses > {$minutes} menit...");

        // Ambil pesanan hari ini yang:
        // - sudah dibayar (payment_status = paid)
        // - status masih pending (belum ada aksi dari admin)
        // - masuk lebih dari $minutes menit yang lalu
        // - belum pernah dikirim reminder (wa_reminded_at null)
        //   ATAU sudah lebih dari 15 menit sejak reminder terakhir
        $cutoff  = Carbon::now('Asia/Jakarta')->subMinutes($minutes);
        $remind  = Carbon::now('Asia/Jakarta')->subMinutes(15); // interval antar reminder

        $orders = Order::with('items')
            ->whereDate('created_at', today())                  // hanya hari ini
            ->where('payment_status', 'paid')
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoff)               // sudah > $minutes menit
            ->where(function ($q) use ($remind) {
                $q->whereNull('wa_reminded_at')                 // belum pernah diremind
                  ->orWhere('wa_reminded_at', '<=', $remind);  // atau sudah > 15 menit
            })
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Tidak ada pesanan yang perlu diingatkan. ✓');
            return self::SUCCESS;
        }

        $this->table(
            ['Order', 'Pelanggan', 'Total', 'Masuk', 'Terakhir Diingatkan'],
            $orders->map(fn($o) => [
                $o->order_number,
                $o->customer_name,
                'Rp ' . number_format($o->total_price, 0, ',', '.'),
                Carbon::parse($o->created_at)->setTimezone('Asia/Jakarta')->format('H:i'),
                $o->wa_reminded_at
                    ? Carbon::parse($o->wa_reminded_at)->setTimezone('Asia/Jakarta')->format('H:i')
                    : '—',
            ])
        );

        if ($dryRun) {
            $this->warn('[DRY RUN] Pesan tidak dikirim.');
            return self::SUCCESS;
        }

        // Kirim satu pesan WA yang memuat semua pesanan sekaligus
        $sent = $wa->notifyUnprocessedReminder($orders);

        if ($sent) {
            // Update wa_reminded_at supaya tidak spam
            $orders->each(fn($o) => $o->update(['wa_reminded_at' => now()]));
            $this->info("✓ Reminder terkirim ke admin untuk {$orders->count()} pesanan.");
        } else {
            $this->error('✗ Gagal kirim reminder WA. Cek log Laravel.');
        }

        return $sent ? self::SUCCESS : self::FAILURE;
    }
}
