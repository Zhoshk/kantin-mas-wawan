<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('order_number')->constrained()->onDelete('set null');
            $table->foreignId('promo_code_id')->nullable()->after('customer_id')->constrained()->onDelete('set null');
            $table->string('promo_code_used', 50)->nullable()->after('promo_code_id');
            $table->integer('subtotal')->default(0)->after('total_price');
            $table->integer('discount_amount')->default(0)->after('subtotal');
            $table->integer('loyalty_points_used')->default(0)->after('discount_amount');
            $table->integer('loyalty_points_earned')->default(0)->after('loyalty_points_used');
            $table->integer('delivery_fee')->default(0)->after('loyalty_points_earned');
            $table->integer('service_fee')->default(0)->after('delivery_fee');
            $table->integer('tax_amount')->default(0)->after('service_fee');
            $table->enum('order_type', ['dine_in', 'take_away', 'delivery'])->default('take_away')->after('tax_amount');
            $table->string('table_number', 20)->nullable()->after('order_type');
            $table->text('delivery_address')->nullable()->after('table_number');
            $table->text('special_instructions')->nullable()->after('delivery_address');
            $table->timestamp('scheduled_for')->nullable()->after('special_instructions'); // for pre-orders
            $table->timestamp('accepted_at')->nullable()->after('scheduled_for');
            $table->timestamp('preparing_at')->nullable()->after('accepted_at');
            $table->timestamp('ready_at')->nullable()->after('preparing_at');
            $table->timestamp('completed_at')->nullable()->after('ready_at');
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
            $table->integer('estimated_preparation_time')->nullable()->after('cancellation_reason'); // minutes
            $table->integer('rating')->nullable()->after('estimated_preparation_time'); // order rating 1-5
            $table->text('feedback')->nullable()->after('rating');
            $table->boolean('is_reviewed')->default(false)->after('feedback');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn([
                'customer_id', 'promo_code_id', 'promo_code_used', 'subtotal', 'discount_amount',
                'loyalty_points_used', 'loyalty_points_earned', 'delivery_fee', 'service_fee',
                'tax_amount', 'order_type', 'table_number', 'delivery_address', 'special_instructions',
                'scheduled_for', 'accepted_at', 'preparing_at', 'ready_at', 'completed_at',
                'cancelled_at', 'cancellation_reason', 'estimated_preparation_time', 'rating',
                'feedback', 'is_reviewed'
            ]);
        });
    }
};
