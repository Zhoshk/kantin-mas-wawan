<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'provider', 'is_active', 'requires_verification',
        'transaction_fee_percentage', 'transaction_fee_fixed', 'min_amount',
        'max_amount', 'icon', 'sort_order', 'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_verification' => 'boolean',
        'transaction_fee_percentage' => 'decimal:2',
        'transaction_fee_fixed' => 'decimal:2',
        'settings' => 'array',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function calculateFee(float $amount): float
    {
        return ($amount * $this->transaction_fee_percentage / 100) + $this->transaction_fee_fixed;
    }

    public function isValidAmount(float $amount): bool
    {
        if ($amount < $this->min_amount) {
            return false;
        }

        if ($this->max_amount && $amount > $this->max_amount) {
            return false;
        }

        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
