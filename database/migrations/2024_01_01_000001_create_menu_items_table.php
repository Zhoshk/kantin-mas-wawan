<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('price');
            $table->string('emoji')->default('🍔');
            $table->enum('category', ['burger', 'rice', 'snack', 'drink'])->default('burger');
            $table->boolean('is_hot')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('variants')->nullable(); // [{name, price, emoji}]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
