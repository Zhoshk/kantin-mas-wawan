<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'loyalty_points', 'tier', 'total_orders',
        'total_spent', 'birth_date', 'dietary_preferences', 'allergens',
        'avatar', 'last_order_at',
    ];

    protected $casts = [
        'loyalty_points' => 'integer',
        'total_orders' => 'integer',
        'total_spent' => 'integer',
        'birth_date' => 'date',
        'dietary_preferences' => 'array',
        'allergens' => 'array',
        'last_order_at' => 'datetime',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    // Calculate tier based on total spent
    public function updateTier(): void
    {
        if ($this->total_spent >= 1000000) {
            $tier = 'platinum';
        } elseif ($this->total_spent >= 500000) {
            $tier = 'gold';
        } elseif ($this->total_spent >= 200000) {
            $tier = 'silver';
        } else {
            $tier = 'bronze';
        }

        if ($this->tier !== $tier) {
            $this->update(['tier' => $tier]);
        }
    }

    // Add loyalty points with expiry
    public function addLoyaltyPoints(int $points, Order $order, string $description): void
    {
        $this->increment('loyalty_points', $points);

        $this->loyaltyTransactions()->create([
            'order_id' => $order->id,
            'type' => 'earned',
            'points' => $points,
            'balance_after' => $this->loyalty_points,
            'description' => $description,
            'expires_at' => now()->addYear(),
        ]);
    }

    // Redeem loyalty points
    public function redeemLoyaltyPoints(int $points, Order $order): bool
    {
        if ($this->loyalty_points < $points) {
            return false;
        }

        $this->decrement('loyalty_points', $points);

        $this->loyaltyTransactions()->create([
            'order_id' => $order->id,
            'type' => 'redeemed',
            'points' => -$points,
            'balance_after' => $this->loyalty_points,
            'description' => "Redeemed for order #{$order->order_number}",
        ]);

        return true;
    }

    // Get tier benefits
    public function getTierBenefits(): array
    {
        return match($this->tier) {
            'platinum' => [
                'discount' => 15,
                'points_multiplier' => 2.0,
                'free_delivery_threshold' => 30000,
                'birthday_bonus' => 5000,
            ],
            'gold' => [
                'discount' => 10,
                'points_multiplier' => 1.5,
                'free_delivery_threshold' => 50000,
                'birthday_bonus' => 3000,
            ],
            'silver' => [
                'discount' => 5,
                'points_multiplier' => 1.2,
                'free_delivery_threshold' => 75000,
                'birthday_bonus' => 1000,
            ],
            default => [
                'discount' => 0,
                'points_multiplier' => 1.0,
                'free_delivery_threshold' => 100000,
                'birthday_bonus' => 500,
            ],
        };
    }
}
