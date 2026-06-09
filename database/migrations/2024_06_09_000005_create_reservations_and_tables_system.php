<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tables
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('table_number', 10);
            $table->string('table_name')->nullable();
            $table->enum('table_type', ['standard', 'booth', 'bar', 'outdoor', 'private_room'])->default('standard');
            $table->integer('capacity')->default(4);
            $table->integer('min_capacity')->default(1);
            $table->string('floor')->nullable(); // Ground, 1st, 2nd
            $table->string('zone')->nullable(); // Smoking, Non-smoking, VIP
            $table->enum('status', ['available', 'occupied', 'reserved', 'cleaning', 'maintenance'])->default('available');
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_deposit')->default(false);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->json('features')->nullable(); // window_view, near_kitchen, quiet, etc
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['location_id', 'table_number']);
        });

        // Reservations
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_code', 20)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->foreignId('table_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->integer('party_size');
            $table->integer('duration_minutes')->default(120);
            $table->enum('status', [
                'pending',      // awaiting confirmation
                'confirmed',    // confirmed by restaurant
                'seated',       // customer arrived and seated
                'completed',    // meal finished
                'no_show',      // customer didn't show up
                'cancelled'     // cancelled by customer or restaurant
            ])->default('pending');
            $table->enum('special_occasion', ['none', 'birthday', 'anniversary', 'business', 'date', 'celebration'])->default('none');
            $table->text('special_requests')->nullable();
            $table->json('dietary_requirements')->nullable();
            $table->boolean('deposit_paid')->default(false);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('seated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->text('staff_notes')->nullable();
            $table->timestamps();
            
            $table->index(['reservation_date', 'location_id']);
        });

        // Reservation History
        Schema::create('reservation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('previous_status');
            $table->string('new_status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Table Occupancy Log
        Schema::create('table_occupancy_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained()->onDelete('cascade');
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('occupied_at');
            $table->timestamp('vacated_at')->nullable();
            $table->integer('party_size');
            $table->integer('duration_minutes')->nullable();
            $table->decimal('revenue_generated', 12, 2)->default(0);
            $table->timestamps();
        });

        // Add table reference to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable()->after('location_id')->constrained()->onDelete('set null');
            $table->foreignId('reservation_id')->nullable()->after('table_id')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropForeign(['reservation_id']);
            $table->dropColumn(['table_id', 'reservation_id']);
        });

        Schema::dropIfExists('table_occupancy_logs');
        Schema::dropIfExists('reservation_history');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('tables');
    }
};
