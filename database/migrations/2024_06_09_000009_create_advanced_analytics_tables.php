<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product Performance Analytics
        Schema::create('product_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('analytics_date');
            $table->integer('units_sold')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->decimal('profit_margin_percentage', 5, 2)->default(0);
            $table->integer('orders_count')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->integer('returns_count')->default(0);
            $table->decimal('return_rate_percentage', 5, 2)->default(0);
            $table->integer('prep_time_avg_minutes')->default(0);
            $table->integer('stock_outs')->default(0);
            $table->decimal('waste_quantity', 10, 2)->default(0);
            $table->integer('peak_hour')->nullable(); // 0-23
            $table->json('hour_breakdown')->nullable();
            $table->timestamps();
            
            $table->unique(['menu_item_id', 'location_id', 'analytics_date'], 'prod_analytics_unique');
        });

        // Customer Behavior Analytics
        Schema::create('customer_behavior_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->date('last_calculated_at');
            $table->integer('total_orders')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->decimal('avg_order_value', 12, 2)->default(0);
            $table->integer('days_since_first_order')->default(0);
            $table->integer('days_since_last_order')->default(0);
            $table->decimal('order_frequency', 5, 2)->default(0); // orders per month
            $table->enum('recency_segment', ['active', 'at_risk', 'dormant', 'lost'])->default('active');
            $table->enum('frequency_segment', ['champion', 'loyal', 'occasional', 'rare'])->default('occasional');
            $table->enum('monetary_segment', ['high_value', 'medium_value', 'low_value'])->default('medium_value');
            $table->string('rfm_score', 10)->nullable(); // e.g., "555" best customer
            $table->json('favorite_categories')->nullable();
            $table->json('favorite_items')->nullable();
            $table->json('order_time_preferences')->nullable(); // preferred days/hours
            $table->decimal('churn_probability', 5, 2)->default(0);
            $table->decimal('predicted_ltv', 12, 2)->default(0); // lifetime value
            $table->timestamps();
            
            $table->unique(['customer_id', 'last_calculated_at'], 'customer_behavior_unique');
        });

        // Sales Forecasting
        Schema::create('sales_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('menu_item_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('forecast_date');
            $table->enum('forecast_type', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->integer('predicted_orders')->default(0);
            $table->decimal('predicted_revenue', 12, 2)->default(0);
            $table->decimal('confidence_level', 5, 2)->default(0);
            $table->integer('actual_orders')->nullable();
            $table->decimal('actual_revenue', 12, 2)->nullable();
            $table->decimal('accuracy_percentage', 5, 2)->nullable();
            $table->json('factors_considered')->nullable(); // weather, holidays, events
            $table->string('model_version')->nullable();
            $table->timestamps();
        });

        // Market Basket Analysis (items frequently bought together)
        Schema::create('market_basket_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_a_id')->constrained('menu_items')->onDelete('cascade');
            $table->foreignId('item_b_id')->constrained('menu_items')->onDelete('cascade');
            $table->integer('co_occurrence_count')->default(0);
            $table->decimal('support', 5, 4)->default(0); // % of transactions with both
            $table->decimal('confidence', 5, 4)->default(0); // A->B probability
            $table->decimal('lift', 5, 4)->default(0); // strength of association
            $table->date('last_calculated_at');
            $table->timestamps();
            
            $table->unique(['item_a_id', 'item_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_basket_analysis');
        Schema::dropIfExists('sales_forecasts');
        Schema::dropIfExists('customer_behavior_analytics');
        Schema::dropIfExists('product_analytics');
    }
};
