<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 20)->unique();
            $table->string('email', 100)->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->string('tier')->default('bronze'); // bronze, silver, gold, platinum
            $table->integer('total_orders')->default(0);
            $table->integer('total_spent')->default(0);
            $table->date('birth_date')->nullable();
            $table->json('dietary_preferences')->nullable(); // vegetarian, halal, etc
            $table->json('allergens')->nullable(); // list of allergens
            $table->string('avatar')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
