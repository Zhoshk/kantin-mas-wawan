<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Customer info
            $table->foreignId('customer_id')->nullable()->after('order_number')->constrained()->onDelete('set null');
            
            // Order type & delivery
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery', 'catering'])->default('takeaway')->after('customer_name');
            $table->string('delivery_address')->nullable()->after('order_type');
            $table->decimal('delivery_lat', 10, 7)->nullable()->after('delivery_address');
            $table->decimal('delivery_lng', 10, 7)->nullable()->after('delivery_lat');
            $table->integer('delivery_distance_km')->nullable()->after('delivery_lng');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('delivery_distance_km');
            $table->text('delivery_notes')->nullable()->after('delivery_fee');
            $table->timestamp('estimated_delivery_time')->nullable()->after('delivery_notes');
            $table->timestamp('actual_delivery_time')->nullable()->after('estimated_delivery_time');
            
            // Scheduling
            $table->boolean('is_scheduled')->default(false)->after('actual_delivery_time');
            $table->timestamp('scheduled_for')->nullable()->after('is_scheduled');
            
            // Pricing breakdown
            $table->decimal('subtotal', 12, 2)->default(0)->after('total_price');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal');
            $table->decimal('service_charge', 10, 2)->default(0)->after('tax_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('service_charge');
            $table->foreignId('promo_code_id')->nullable()->after('discount_amount')->constrained()->onDelete('set null');
            $table->integer('loyalty_points_used')->default(0)->after('promo_code_id');
            $table->decimal('loyalty_discount', 10, 2)->default(0)->after('loyalty_points_used');
            
            // Additional info
            $table->text('special_instructions')->nullable()->after('loyalty_discount');
            $table->json('metadata')->nullable()->after('special_instructions');
            $table->string('order_source')->default('pos')->after('metadata'); // pos, app, web, phone
            $table->decimal('tip_amount', 10, 2)->default(0)->after('order_source');
            
            // Ratings
            $table->decimal('food_rating', 3, 2)->nullable()->after('tip_amount');
            $table->decimal('service_rating', 3, 2)->nullable()->after('food_rating');
            $table->decimal('delivery_rating', 3, 2)->nullable()->after('service_rating');
            $table->text('customer_feedback')->nullable()->after('delivery_rating');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn([
                'customer_id', 'order_type', 'delivery_address', 'delivery_lat', 'delivery_lng',
                'delivery_distance_km', 'delivery_fee', 'delivery_notes', 'estimated_delivery_time',
                'actual_delivery_time', 'is_scheduled', 'scheduled_for', 'subtotal', 'tax_amount',
                'service_charge', 'discount_amount', 'promo_code_id', 'loyalty_points_used',
                'loyalty_discount', 'special_instructions', 'metadata', 'order_source', 'tip_amount',
                'food_rating', 'service_rating', 'delivery_rating', 'customer_feedback'
            ]);
        });
    }
};
