<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'customer_id', 'promo_code_id', 'promo_code_used',
        'total_price', 'subtotal', 'discount_amount', 'loyalty_points_used', 'loyalty_points_earned',
        'delivery_fee', 'service_fee', 'tax_amount', 'status', 'payment_status', 'payment_method',
        'order_type', 'table_number', 'delivery_address', 'special_instructions', 'scheduled_for',
        'accepted_at', 'preparing_at', 'ready_at', 'completed_at', 'cancelled_at', 'cancellation_reason',
        'estimated_preparation_time', 'rating', 'feedback', 'is_reviewed', 'wa_reminded_at',
    ];

    protected $casts = [
        'total_price'    => 'integer',
        'subtotal'       => 'integer',
        'discount_amount' => 'integer',
        'loyalty_points_used' => 'integer',
        'loyalty_points_earned' => 'integer',
        'delivery_fee' => 'integer',
        'service_fee' => 'integer',
        'tax_amount' => 'integer',
        'rating' => 'integer',
        'is_reviewed' => 'boolean',
        'scheduled_for' => 'datetime',
        'accepted_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'wa_reminded_at' => 'datetime',
        'estimated_preparation_time' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderHistory::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public static function generateOrderNumber(): string
    {
        $last = self::latest()->first();
        $next = $last ? ((int) substr($last->order_number, 4)) + 1 : 1;
        return 'ORD-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    // Calculate estimated preparation time based on items
    public function calculateEstimatedTime(): int
    {
        return $this->items->sum(function ($item) {
            return $item->menuItem ? $item->menuItem->preparation_time * $item->quantity : 10;
        });
    }

    // Scope: today's orders
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Scope: scheduled orders
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_for')
            ->where('scheduled_for', '>', now());
    }

    // Scope: by order type
    public function scopeByType($query, string $type)
    {
        return $query->where('order_type', $type);
    }
}