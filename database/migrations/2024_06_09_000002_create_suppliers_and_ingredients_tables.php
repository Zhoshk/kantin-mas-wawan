<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('contact_person');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city', 100);
            $table->string('tax_id', 50)->nullable(); // NPWP
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->enum('rating', ['excellent', 'good', 'average', 'poor'])->default('good');
            $table->integer('payment_terms_days')->default(30); // NET 30
            $table->decimal('delivery_cost', 12, 2)->default(0);
            $table->integer('min_order_amount')->default(0);
            $table->json('supply_categories')->nullable(); // [vegetables, meats, dairy, etc]
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Ingredients
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('category'); // vegetables, meats, dairy, spices, etc
            $table->enum('unit', ['kg', 'g', 'l', 'ml', 'pcs', 'pack'])->default('kg');
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('min_stock_level', 10, 2)->default(0); // reorder point
            $table->decimal('max_stock_level', 10, 2)->default(0);
            $table->decimal('avg_cost_per_unit', 12, 2)->default(0);
            $table->integer('shelf_life_days')->nullable(); // expiry tracking
            $table->boolean('is_perishable')->default(false);
            $table->decimal('wastage_percentage', 5, 2)->default(0); // typical wastage
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->json('allergen_info')->nullable();
            $table->text('storage_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Purchase Orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 30)->unique();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['draft', 'pending', 'approved', 'sent', 'received', 'cancelled'])->default('draft');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('delivery_cost', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Purchase Order Items
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->enum('unit', ['kg', 'g', 'l', 'ml', 'pcs', 'pack']);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->decimal('received_quantity', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Ingredient Stock Movements
        Schema::create('ingredient_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['purchase', 'usage', 'wastage', 'adjustment', 'transfer', 'return'])->default('usage');
            $table->decimal('quantity', 10, 2);
            $table->enum('unit', ['kg', 'g', 'l', 'ml', 'pcs', 'pack']);
            $table->decimal('cost_per_unit', 12, 2)->nullable();
            $table->decimal('stock_before', 10, 2);
            $table->decimal('stock_after', 10, 2);
            $table->text('reason')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        // Recipe Ingredients (what ingredients are needed for each menu item)
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_per_serving', 10, 3); // e.g., 0.250 kg
            $table->enum('unit', ['kg', 'g', 'l', 'ml', 'pcs', 'pack']);
            $table->boolean('is_optional')->default(false);
            $table->integer('preparation_step')->default(1);
            $table->text('preparation_notes')->nullable();
            $table->timestamps();
            
            $table->unique(['menu_item_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('ingredient_stock_movements');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('suppliers');
    }
};
