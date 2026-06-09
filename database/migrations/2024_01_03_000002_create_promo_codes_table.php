<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed', 'free_delivery', 'buy_x_get_y']);
            $table->integer('discount_value')->nullable(); // percentage or fixed amount
            $table->integer('min_purchase')->default(0);
            $table->integer('max_discount')->nullable(); // cap for percentage discounts
            $table->integer('usage_limit')->nullable(); // total uses allowed
            $table->integer('usage_per_customer')->default(1);
            $table->integer('times_used')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->json('applicable_categories')->nullable(); // which categories eligible
            $table->json('applicable_items')->nullable(); // specific menu items
            $table->json('excluded_items')->nullable();
            $table->json('customer_tiers')->nullable(); // ['gold', 'platinum']
            $table->boolean('is_active')->default(true);
            $table->boolean('first_order_only')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
