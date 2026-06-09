<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type')->default('canteen'); // canteen, restaurant, cloud_kitchen
            $table->text('address');
            $table->string('city', 100);
            $table->string('province', 100);
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable();
            $table->foreignId('parent_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->integer('capacity')->default(50); // seating capacity
            $table->integer('table_count')->default(0);
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->json('operating_days')->nullable(); // [1,2,3,4,5] for Mon-Fri
            $table->boolean('is_active')->default(true);
            $table->boolean('accepts_delivery')->default(true);
            $table->boolean('accepts_takeaway')->default(true);
            $table->boolean('accepts_dine_in')->default(true);
            $table->boolean('accepts_reservations')->default(false);
            $table->integer('delivery_radius_km')->default(5);
            $table->integer('avg_preparation_time')->default(15); // minutes
            $table->json('supported_payment_methods')->nullable();
            $table->json('facilities')->nullable(); // wifi, parking, ac, etc
            $table->string('timezone')->default('Asia/Jakarta');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Add location_id to existing tables
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->boolean('available_all_locations')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn(['location_id', 'available_all_locations']);
        });

        Schema::dropIfExists('locations');
    }
};
