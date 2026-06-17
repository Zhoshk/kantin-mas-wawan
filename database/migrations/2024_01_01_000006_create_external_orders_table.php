<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('external_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();   // EXT-001
            $table->string('customer_name');
            $table->string('restaurant_name');           // nama warung/resto
            $table->text('items_text');                  // daftar pesanan (teks bebas)
            $table->text('notes')->nullable();           // catatan tambahan
            $table->integer('estimated_price')->nullable(); // estimasi harga
            $table->enum('status', ['pending', 'bought', 'delivered', 'cancelled'])
                  ->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_orders');
    }
};