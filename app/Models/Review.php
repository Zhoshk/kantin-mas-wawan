<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'menu_item_id', 'customer_id', 'order_id', 'rating', 'comment',
        'images', 'tags', 'helpful_count', 'is_verified_purchase',
        'admin_response_at', 'admin_response', 'is_visible',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
        'tags' => 'array',
        'helpful_count' => 'integer',
        'is_verified_purchase' => 'boolean',
        'admin_response_at' => 'datetime',
        'is_visible' => 'boolean',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Update menu item average rating
    protected static function booted()
    {
        static::created(function ($review) {
            $review->updateMenuItemRating();
        });

        static::updated(function ($review) {
            $review->updateMenuItemRating();
        });

        static::deleted(function ($review) {
            $review->updateMenuItemRating();
        });
    }

    public function updateMenuItemRating(): void
    {
        $menuItem = $this->menuItem;
        $averageRating = $menuItem->reviews()->where('is_visible', true)->avg('rating');
        $reviewCount = $menuItem->reviews()->where('is_visible', true)->count();

        $menuItem->update([
            'average_rating' => $averageRating ?? 0,
            'review_count' => $reviewCount,
        ]);
    }

    // Scope: visible reviews
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    // Scope: verified purchases
    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }
}
