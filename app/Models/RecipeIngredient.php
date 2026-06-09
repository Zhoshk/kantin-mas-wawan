<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    protected $fillable = [
        'menu_item_id', 'ingredient_id', 'quantity_per_serving', 'unit',
        'is_optional', 'preparation_step', 'preparation_notes',
    ];

    protected $casts = [
        'quantity_per_serving' => 'decimal:3',
        'is_optional' => 'boolean',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function calculateCost(): float
    {
        return $this->quantity_per_serving * $this->ingredient->avg_cost_per_unit;
    }
}
