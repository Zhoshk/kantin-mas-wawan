<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom wa_reminded_at ke tabel orders.
     * Dipakai oleh NotifyUnprocessedOrders command agar tidak spam WA reminder.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('wa_reminded_at')
                  ->nullable()
                  ->after('payment_method')
                  ->comment('Kapan terakhir reminder WA dikirim untuk pesanan ini');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('wa_reminded_at');
        });
    }
};
