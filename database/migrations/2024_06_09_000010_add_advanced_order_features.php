<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Only add columns that don't already exist
            if (!Schema::hasColumn('orders', 'delivery_lat')) {
                $table->decimal('delivery_lat', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_lng')) {
                $table->decimal('delivery_lng', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_distance_km')) {
                $table->integer('delivery_distance_km')->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_notes')) {
                $table->text('delivery_notes')->nullable();
            }
            if (!Schema::hasColumn('orders', 'estimated_delivery_time')) {
                $table->timestamp('estimated_delivery_time')->nullable();
            }
            if (!Schema::hasColumn('orders', 'actual_delivery_time')) {
                $table->timestamp('actual_delivery_time')->nullable();
            }
            if (!Schema::hasColumn('orders', 'is_scheduled')) {
                $table->boolean('is_scheduled')->default(false);
            }
            if (!Schema::hasColumn('orders', 'service_charge')) {
                $table->decimal('service_charge', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'loyalty_discount')) {
                $table->decimal('loyalty_discount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (!Schema::hasColumn('orders', 'order_source')) {
                $table->string('order_source')->default('pos');
            }
            if (!Schema::hasColumn('orders', 'tip_amount')) {
                $table->decimal('tip_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'food_rating')) {
                $table->decimal('food_rating', 3, 2)->nullable();
            }
            if (!Schema::hasColumn('orders', 'service_rating')) {
                $table->decimal('service_rating', 3, 2)->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_rating')) {
                $table->decimal('delivery_rating', 3, 2)->nullable();
            }
            if (!Schema::hasColumn('orders', 'customer_feedback')) {
                $table->text('customer_feedback')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop only the columns we added
            $columns = [
                'delivery_lat', 'delivery_lng', 'delivery_distance_km', 'delivery_notes',
                'estimated_delivery_time', 'actual_delivery_time', 'is_scheduled',
                'service_charge', 'loyalty_discount', 'metadata', 'order_source',
                'tip_amount', 'food_rating', 'service_rating', 'delivery_rating', 'customer_feedback'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
