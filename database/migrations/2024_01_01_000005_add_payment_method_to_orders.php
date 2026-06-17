<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom payment_method ke tabel orders.
     * Error: SQLSTATE[42S22] Column not found 'payment_method'
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Tambah payment_method setelah kolom payment_status
            $table->string('payment_method')->default('tunai')->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};