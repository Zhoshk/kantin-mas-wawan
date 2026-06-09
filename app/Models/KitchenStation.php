<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenStation extends Model
{
    protected $fillable = [
        'location_id', 'name', 'code', 'description', 'priority', 'capacity',
        'is_active', 'assigned_categories',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_categories' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function orderItemStatuses(): HasMany
    {
        return $this->hasMany(OrderItemKitchenStatus::class);
    }

    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(KitchenPerformanceMetric::class);
    }

    public function getPendingItemsCount(): int
    {
        return $this->orderItemStatuses()
            ->whereIn('status', ['pending', 'queued'])
            ->count();
    }

    public function getActiveItemsCount(): int
    {
        return $this->orderItemStatuses()
            ->where('status', 'preparing')
            ->count();
    }

    public function getCurrentLoad(): float
    {
        $active = $this->getActiveItemsCount();
        return ($active / $this->capacity) * 100;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
