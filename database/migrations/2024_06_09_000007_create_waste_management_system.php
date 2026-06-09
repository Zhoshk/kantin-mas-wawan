<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Waste Logs
        Schema::create('waste_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('menu_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('ingredient_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('waste_type', [
                'spoilage',         // expired or spoiled
                'preparation',      // waste during prep
                'cooking',          // burnt/overcooked
                'customer_return',  // customer sent back
                'overproduction',   // made too much
                'contamination',    // dropped or contaminated
                'quality_control',  // didn't meet standards
                'other'
            ])->default('other');
            $table->decimal('quantity', 10, 2);
            $table->enum('unit', ['kg', 'g', 'l', 'ml', 'pcs', 'portions'])->default('kg');
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->text('reason');
            $table->date('waste_date');
            $table->time('waste_time');
            $table->boolean('is_preventable')->default(true);
            $table->text('prevention_notes')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });

        // Waste Reduction Initiatives
        Schema::create('waste_reduction_initiatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['planning', 'active', 'completed', 'cancelled'])->default('planning');
            $table->decimal('target_reduction_percentage', 5, 2)->default(0);
            $table->decimal('actual_reduction_percentage', 5, 2)->default(0);
            $table->decimal('cost_savings', 12, 2)->default(0);
            $table->text('implementation_notes')->nullable();
            $table->timestamps();
        });

        // Sustainability Metrics
        Schema::create('sustainability_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->date('metric_date');
            $table->decimal('total_waste_kg', 10, 2)->default(0);
            $table->decimal('food_waste_kg', 10, 2)->default(0);
            $table->decimal('packaging_waste_kg', 10, 2)->default(0);
            $table->decimal('waste_cost', 12, 2)->default(0);
            $table->decimal('recycling_percentage', 5, 2)->default(0);
            $table->decimal('compost_percentage', 5, 2)->default(0);
            $table->integer('energy_consumption_kwh')->default(0);
            $table->integer('water_consumption_liters')->default(0);
            $table->decimal('carbon_footprint_kg', 10, 2)->default(0);
            $table->integer('reusable_containers_used')->default(0);
            $table->integer('local_ingredients_percentage')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['location_id', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sustainability_metrics');
        Schema::dropIfExists('waste_reduction_initiatives');
        Schema::dropIfExists('waste_logs');
    }
};
