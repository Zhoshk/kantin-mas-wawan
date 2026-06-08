<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes — Kantin Mas Wawan
|--------------------------------------------------------------------------
|
| Dua schedule utama:
|
|  1. wa:remind-orders   — Cek tiap 5 menit apakah ada pesanan paid+pending
|                          yang sudah lewat 10 menit tanpa diproses admin.
|                          Kalau ada → kirim reminder WA ke admin.
|
|  2. wa:daily-summary   — Kirim rekap harian setiap jam 15:00 WIB
|                          (setelah jam makan siang selesai).
|
| Pastikan cron job berikut aktif di server:
|   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
|
*/

// ── Reminder pesanan belum diproses (tiap 5 menit) ───────────────────────
Schedule::command('wa:remind-orders --minutes=10')
    ->everyFiveMinutes()
    ->between('07:00', '14:00')   // hanya saat jam operasional kantin
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->runInBackground();

// ── Rekap harian (jam 15:00 WIB) ─────────────────────────────────────────
Schedule::command('wa:daily-summary')
    ->dailyAt('15:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Bawaan Laravel
Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');
