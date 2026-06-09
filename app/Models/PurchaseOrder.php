<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'po_number', 'supplier_id', 'location_id', 'created_by', 'approved_by', 'status',
        'order_date', 'expected_delivery_date', 'actual_delivery_date', 'subtotal',
        'tax_amount', 'delivery_cost', 'total_amount', 'payment_status', 'paid_amount',
        'notes', 'rejection_reason',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'delivery_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('total_price');
        $taxAmount = $subtotal * 0.11; // 11% PPN

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount + $this->delivery_cost,
        ]);
    }

    public static function generatePONumber(): string
    {
        $last = self::latest()->first();
        $next = $last ? ((int) substr($last->po_number, 3)) + 1 : 1;
        return 'PO-' . date('Ymd') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function receiveOrder(): void
    {
        if ($this->status !== 'sent') {
            return;
        }

        foreach ($this->items as $item) {
            $item->ingredient->addStock(
                $item->received_quantity,
                'purchase',
                $this->id,
                "Received from PO #{$this->po_number}"
            );
        }

        $this->update([
            'status' => 'received',
            'actual_delivery_date' => now(),
        ]);
    }
}
