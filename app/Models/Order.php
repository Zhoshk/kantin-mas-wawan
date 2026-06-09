<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'total_price',
        'status', 'payment_status', 'payment_method',
        'wa_reminded_at',
    ];

    protected $casts = [
        'total_price'    => 'integer',
        'wa_reminded_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderNumber(): string
    {
        $last = self::latest()->first();
        $next = $last ? ((int) substr($last->order_number, 4)) + 1 : 1;
        return 'ORD-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}