<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderHistory extends Model
{
    protected $table = 'order_history';

    protected $fillable = [
        'order_id', 'status_from', 'status_to', 'notes', 'changed_by',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Helper to log status change
    public static function logStatusChange(
        Order $order,
        string $statusFrom,
        string $statusTo,
        ?string $notes = null,
        ?string $changedBy = 'system'
    ): void {
        self::create([
            'order_id' => $order->id,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'notes' => $notes,
            'changed_by' => $changedBy,
        ]);
    }
}
