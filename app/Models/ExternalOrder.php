<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalOrder extends Model
{
    protected $fillable = [
        'order_number',
        'customer_name',
        'restaurant_name',
        'items_text',
        'notes',
        'estimated_price',
        'status',
    ];

    protected $casts = [
        'estimated_price' => 'integer',
    ];
}