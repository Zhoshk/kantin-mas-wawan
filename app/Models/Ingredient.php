<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'category', 'unit', 'current_stock', 'min_stock_level',
        'max_stock_level', 'avg_cost_per_unit', 'shelf_life_days', 'is_perishable',
        'wastage_percentage', 'preferred_supplier_id', 'allergen_info',
        'storage_instructions', 'is_active',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'avg_cost_per_unit' => 'decimal:2',
        'wastage_percentage' => 'decimal:2',
        'is_perishable' => 'boolean',
        'is_active' => 'boolean',
        'allergen_info' => 'array',
    ];

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(IngredientStockMovement::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function needsReorder(): bool
    {
        return $this->current_stock <= $this->min_stock_level;
    }

    public function addStock(float $quantity, string $type = 'purchase', ?int $purchaseOrderId = null, ?string $reason = null): void
    {
        $this->stockMovements()->create([
            'type' => $type,
            'quantity' => $quantity,
            'unit' => $this->unit,
            'stock_before' => $this->current_stock,
            'stock_after' => $this->current_stock + $quantity,
            'purchase_order_id' => $purchaseOrderId,
            'reason' => $reason,
        ]);

        $this->increment('current_stock', $quantity);
    }

    public function reduceStock(float $quantity, string $type = 'usage', ?int $orderId = null, ?string $reason = null): void
    {
        $this->stockMovements()->create([
            'type' => $type,
            'quantity' => -$quantity,
            'unit' => $this->unit,
            'stock_before' => $this->current_stock,
            'stock_after' => $this->current_stock - $quantity,
            'order_id' => $orderId,
            'reason' => $reason,
        ]);

        $this->decrement('current_stock', $quantity);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock_level');
    }
}
