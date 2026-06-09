<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payment Methods
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('type', ['cash', 'card', 'ewallet', 'bank_transfer', 'qris', 'credit', 'voucher'])->default('cash');
            $table->string('provider')->nullable(); // gopay, ovo, dana, bca, mandiri, etc
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_verification')->default(false);
            $table->decimal('transaction_fee_percentage', 5, 2)->default(0);
            $table->decimal('transaction_fee_fixed', 10, 2)->default(0);
            $table->integer('min_amount')->default(0);
            $table->integer('max_amount')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // Payment Transactions
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 50)->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->decimal('transaction_fee', 10, 2)->default(0);
            $table->decimal('net_amount', 12, 2); // amount - fee
            $table->enum('status', [
                'pending',
                'processing',
                'success',
                'failed',
                'cancelled',
                'refunded',
                'partial_refund'
            ])->default('pending');
            $table->string('external_transaction_id')->nullable(); // from payment gateway
            $table->string('payment_reference')->nullable();
            $table->json('payment_details')->nullable(); // card last 4 digits, account, etc
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->text('failure_reason')->nullable();
            $table->text('refund_reason')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });

        // Refunds
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_id', 50)->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained('payment_transactions')->onDelete('cascade');
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('refund_amount', 12, 2);
            $table->enum('refund_type', ['full', 'partial'])->default('full');
            $table->enum('reason', [
                'order_cancelled',
                'food_quality',
                'wrong_order',
                'late_delivery',
                'customer_request',
                'system_error',
                'other'
            ])->default('other');
            $table->text('reason_details')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed', 'completed'])->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // Financial Reports Cache
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('report_type', ['daily', 'weekly', 'monthly', 'yearly'])->default('daily');
            $table->date('report_date');
            $table->decimal('gross_revenue', 12, 2)->default(0);
            $table->decimal('discounts', 12, 2)->default(0);
            $table->decimal('refunds', 12, 2)->default(0);
            $table->decimal('net_revenue', 12, 2)->default(0);
            $table->decimal('transaction_fees', 12, 2)->default(0);
            $table->decimal('cogs', 12, 2)->default(0); // cost of goods sold
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('overhead_cost', 12, 2)->default(0);
            $table->decimal('gross_profit', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->decimal('profit_margin_percentage', 5, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->integer('total_customers')->default(0);
            $table->decimal('avg_order_value', 12, 2)->default(0);
            $table->json('payment_breakdown')->nullable();
            $table->json('category_breakdown')->nullable();
            $table->timestamps();
            
            $table->unique(['location_id', 'report_type', 'report_date'], 'financial_report_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_methods');
    }
};
