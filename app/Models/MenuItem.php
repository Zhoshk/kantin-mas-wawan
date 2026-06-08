<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'name', 'description', 'price', 'emoji', 'image', 'stock',
        'category', 'is_hot', 'is_active', 'variants', 'preparation_time',
        'calories', 'ingredients', 'allergens', 'dietary_tags', 'spice_level',
        'average_rating', 'review_count', 'times_ordered', 'low_stock_threshold',
        'optimal_stock_level', 'is_featured', 'is_seasonal', 'available_from',
        'available_until', 'available_days', 'available_from_time', 'available_until_time',
        'max_per_order', 'requires_pre_order', 'pre_order_hours',
    ];

    protected $casts = [
        'is_hot'    => 'boolean',
        'is_active' => 'boolean',
        'variants'  => 'array',
        'price'     => 'integer',
        'stock'     => 'integer',
        'preparation_time' => 'integer',
        'calories' => 'integer',
        'ingredients' => 'array',
        'allergens' => 'array',
        'dietary_tags' => 'array',
        'spice_level' => 'integer',
        'average_rating' => 'decimal:2',
        'review_count' => 'integer',
        'times_ordered' => 'integer',
        'low_stock_threshold' => 'integer',
        'optimal_stock_level' => 'integer',
        'is_featured' => 'boolean',
        'is_seasonal' => 'boolean',
        'available_from' => 'date',
        'available_until' => 'date',
        'available_days' => 'array',
        'available_from_time' => 'datetime:H:i',
        'available_until_time' => 'datetime:H:i',
        'max_per_order' => 'integer',
        'requires_pre_order' => 'boolean',
        'pre_order_hours' => 'integer',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    // Scope: hanya menu aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope: featured items
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Scope: low stock items
    public function scopeLowStock($query)
    {
        return $query->whereNotNull('stock')
            ->whereColumn('stock', '<=', 'low_stock_threshold');
    }

    // Check if item is available now
    public function isAvailableNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        // Check seasonal availability
        if ($this->is_seasonal) {
            if ($this->available_from && $now->lt($this->available_from)) {
                return false;
            }
            if ($this->available_until && $now->gt($this->available_until)) {
                return false;
            }
        }

        // Check day of week
        if ($this->available_days && !in_array($now->dayOfWeek, $this->available_days)) {
            return false;
        }

        // Check time of day
        if ($this->available_from_time) {
            $fromTime = \Carbon\Carbon::createFromFormat('H:i', $this->available_from_time);
            if ($now->lt($fromTime)) {
                return false;
            }
        }

        if ($this->available_until_time) {
            $untilTime = \Carbon\Carbon::createFromFormat('H:i', $this->available_until_time);
            if ($now->gt($untilTime)) {
                return false;
            }
        }

        return true;
    }

    // Check if stock is low
    public function isLowStock(): bool
    {
        if ($this->stock === null) {
            return false; // unlimited stock
        }

        return $this->stock <= $this->low_stock_threshold;
    }
}