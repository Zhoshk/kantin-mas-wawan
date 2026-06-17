<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'name', 'description', 'price', 'emoji', 'image', 'stock',
        'category', 'is_hot', 'is_active', 'variants',
    ];

    protected $casts = [
        'is_hot' => 'boolean',
        'is_active' => 'boolean',
        'variants' => 'array',
        'price' => 'integer',
        'stock' => 'integer',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scope: hanya menu aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
