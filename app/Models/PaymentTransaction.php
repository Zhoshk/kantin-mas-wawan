<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'transaction_id', 'order_id', 'payment_method_id', 'customer_id', 'amount',
        'transaction_fee', 'net_amount', 'status', 'external_transaction_id',
        'payment_reference', 'payment_details', 'paid_at', 'failed_at', 'refunded_at',
        'refunded_amount', 'failure_reason', 'refund_reason', 'gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'payment_details' => 'array',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'transaction_id');
    }

    public static function generateTransactionId(): string
    {
        return 'TXN-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -6));
    }

    public function calculateNetAmount(): void
    {
        $fee = ($this->amount * $this->paymentMethod->transaction_fee_percentage / 100)
             + $this->paymentMethod->transaction_fee_fixed;

        $this->update([
            'transaction_fee' => $fee,
            'net_amount' => $this->amount - $fee,
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'success',
            'paid_at' => now(),
        ]);

        $this->order->update(['payment_status' => 'paid']);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }
}
