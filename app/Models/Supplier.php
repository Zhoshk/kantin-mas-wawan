<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'company_name', 'contact_person', 'phone', 'email', 'address',
        'city', 'tax_id', 'status', 'rating', 'payment_terms_days', 'delivery_cost',
        'min_order_amount', 'supply_categories', 'notes',
    ];

    protected $casts = [
        'delivery_cost' => 'decimal:2',
        'supply_categories' => 'array',
    ];

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'preferred_supplier_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function getTotalPurchasesAmount(): float
    {
        return $this->purchaseOrders()
            ->where('status', 'received')
            ->sum('total_amount');
    }

    public function getAverageDeliveryTime(): float
    {
        $orders = $this->purchaseOrders()
            ->where('status', 'received')
            ->whereNotNull('actual_delivery_date')
            ->get();

        if ($orders->isEmpty()) {
            return 0;
        }

        $totalDays = $orders->sum(function ($order) {
            return $order->order_date->diffInDays($order->actual_delivery_date);
        });

        return $totalDays / $orders->count();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
