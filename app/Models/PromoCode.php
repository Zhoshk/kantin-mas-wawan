<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'type', 'discount_value', 'min_purchase',
        'max_discount', 'usage_limit', 'usage_per_customer', 'times_used',
        'valid_from', 'valid_until', 'applicable_categories', 'applicable_items',
        'excluded_items', 'customer_tiers', 'is_active', 'first_order_only',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'min_purchase' => 'integer',
        'max_discount' => 'integer',
        'usage_limit' => 'integer',
        'usage_per_customer' => 'integer',
        'times_used' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'applicable_categories' => 'array',
        'applicable_items' => 'array',
        'excluded_items' => 'array',
        'customer_tiers' => 'array',
        'is_active' => 'boolean',
        'first_order_only' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // Check if promo is valid
    public function isValid(Customer $customer = null, int $orderTotal = 0): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (now() < $this->valid_from || now() > $this->valid_until) {
            return false;
        }

        if ($orderTotal < $this->min_purchase) {
            return false;
        }

        if ($this->usage_limit && $this->times_used >= $this->usage_limit) {
            return false;
        }

        if ($customer) {
            // Check customer tier eligibility
            if ($this->customer_tiers && !in_array($customer->tier, $this->customer_tiers)) {
                return false;
            }

            // Check first order only
            if ($this->first_order_only && $customer->total_orders > 0) {
                return false;
            }

            // Check usage per customer
            $customerUsageCount = Order::where('customer_id', $customer->id)
                ->where('promo_code_id', $this->id)
                ->count();

            if ($customerUsageCount >= $this->usage_per_customer) {
                return false;
            }
        }

        return true;
    }

    // Calculate discount amount
    public function calculateDiscount(int $orderTotal, array $items = []): int
    {
        if ($this->type === 'fixed') {
            return $this->discount_value;
        }

        if ($this->type === 'percentage') {
            $discount = (int) ($orderTotal * ($this->discount_value / 100));
            if ($this->max_discount) {
                $discount = min($discount, $this->max_discount);
            }
            return $discount;
        }

        if ($this->type === 'free_delivery') {
            return 0; // handled separately in order
        }

        return 0;
    }

    // Mark as used
    public function markAsUsed(): void
    {
        $this->increment('times_used');
    }

    // Scope: active promos
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }
}
