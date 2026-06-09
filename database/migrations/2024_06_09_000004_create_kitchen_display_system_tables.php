<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kitchen Stations
        Schema::create('kitchen_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Grill, Fryer, Wok, Prep, Dessert
            $table->string('code', 10)->unique();
            $table->text('description')->nullable();
            $table->integer('priority')->default(1); // 1=highest
            $table->integer('capacity')->default(5); // how many items can be prepared simultaneously
            $table->boolean('is_active')->default(true);
            $table->json('assigned_categories')->nullable(); // which menu categories this station handles
            $table->timestamps();
        });

        // Order Items Kitchen Status (extends OrderItem with kitchen workflow)
        Schema::create('order_item_kitchen_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('kitchen_station_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_chef_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->enum('status', [
                'pending',      // just received
                'queued',       // waiting in line
                'preparing',    // actively cooking
                'ready',        // done cooking
                'served',       // delivered to customer
                'delayed',      // taking longer than expected
                'cancelled'     // order cancelled
            ])->default('pending');
            $table->integer('queue_position')->default(0);
            $table->integer('estimated_time_minutes')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->integer('actual_prep_time_minutes')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('chef_notes')->nullable();
            $table->boolean('is_priority')->default(false);
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->timestamps();
        });

        // Kitchen Performance Metrics
        Schema::create('kitchen_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->foreignId('kitchen_station_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('set null');
            $table->date('metric_date');
            $table->time('metric_hour')->nullable(); // for hourly breakdown
            $table->integer('orders_completed')->default(0);
            $table->integer('orders_delayed')->default(0);
            $table->integer('orders_cancelled')->default(0);
            $table->decimal('avg_preparation_time', 5, 2)->default(0);
            $table->decimal('efficiency_percentage', 5, 2)->default(100);
            $table->integer('total_items_prepared')->default(0);
            $table->json('item_breakdown')->nullable(); // count by category
            $table->timestamps();
            
            $table->unique(['location_id', 'kitchen_station_id', 'employee_id', 'metric_date', 'metric_hour'], 'kitchen_perf_unique');
        });

        // Add kitchen timestamps to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('kitchen_received_at')->nullable()->after('created_at');
            $table->timestamp('kitchen_started_at')->nullable()->after('kitchen_received_at');
            $table->timestamp('kitchen_completed_at')->nullable()->after('kitchen_started_at');
            $table->timestamp('served_at')->nullable()->after('kitchen_completed_at');
            $table->integer('total_prep_time_minutes')->nullable()->after('served_at');
            $table->boolean('is_delayed')->default(false)->after('total_prep_time_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'kitchen_received_at',
                'kitchen_started_at',
                'kitchen_completed_at',
                'served_at',
                'total_prep_time_minutes',
                'is_delayed'
            ]);
        });

        Schema::dropIfExists('kitchen_performance_metrics');
        Schema::dropIfExists('order_item_kitchen_status');
        Schema::dropIfExists('kitchen_stations');
    }
};
