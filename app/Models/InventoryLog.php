<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLog extends Model
{
    protected $fillable = [
        'menu_item_id', 'type', 'quantity_before', 'quantity_change',
        'quantity_after', 'order_id', 'reason', 'performed_by',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_change' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Helper method to log inventory changes
    public static function logChange(
        MenuItem $menuItem,
        string $type,
        int $quantityChange,
        ?Order $order = null,
        ?string $reason = null,
        ?string $performedBy = 'system'
    ): void {
        $before = $menuItem->stock ?? 0;
        $after = $before + $quantityChange;

        self::create([
            'menu_item_id' => $menuItem->id,
            'type' => $type,
            'quantity_before' => $before,
            'quantity_change' => $quantityChange,
            'quantity_after' => $after,
            'order_id' => $order?->id,
            'reason' => $reason,
            'performed_by' => $performedBy,
        ]);
    }
}
