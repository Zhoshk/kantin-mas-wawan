<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->integer('preparation_time')->default(10)->after('stock'); // in minutes
            $table->integer('calories')->nullable()->after('preparation_time');
            $table->json('ingredients')->nullable()->after('calories'); // ['flour', 'sugar', 'egg']
            $table->json('allergens')->nullable()->after('ingredients'); // ['gluten', 'dairy', 'nuts']
            $table->json('dietary_tags')->nullable()->after('allergens'); // ['vegetarian', 'halal', 'vegan']
            $table->integer('spice_level')->default(0)->after('dietary_tags'); // 0-5
            $table->decimal('average_rating', 3, 2)->default(0)->after('spice_level');
            $table->integer('review_count')->default(0)->after('average_rating');
            $table->integer('times_ordered')->default(0)->after('review_count');
            $table->integer('low_stock_threshold')->default(5)->after('times_ordered');
            $table->integer('optimal_stock_level')->default(50)->after('low_stock_threshold');
            $table->boolean('is_featured')->default(false)->after('optimal_stock_level');
            $table->boolean('is_seasonal')->default(false)->after('is_featured');
            $table->date('available_from')->nullable()->after('is_seasonal');
            $table->date('available_until')->nullable()->after('available_from');
            $table->json('available_days')->nullable()->after('available_until'); // [1,2,3,4,5] monday-friday
            $table->time('available_from_time')->nullable()->after('available_days');
            $table->time('available_until_time')->nullable()->after('available_from_time');
            $table->integer('max_per_order')->nullable()->after('available_until_time');
            $table->boolean('requires_pre_order')->default(false)->after('max_per_order');
            $table->integer('pre_order_hours')->nullable()->after('requires_pre_order');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn([
                'preparation_time', 'calories', 'ingredients', 'allergens', 'dietary_tags',
                'spice_level', 'average_rating', 'review_count', 'times_ordered',
                'low_stock_threshold', 'optimal_stock_level', 'is_featured', 'is_seasonal',
                'available_from', 'available_until', 'available_days', 'available_from_time',
                'available_until_time', 'max_per_order', 'requires_pre_order', 'pre_order_hours'
            ]);
        });
    }
};
