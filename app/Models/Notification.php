<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'customer_id', 'type', 'title', 'message', 'data', 'channel',
        'sent_at', 'read_at', 'is_delivered',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'is_delivered' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Mark as read
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    // Mark as delivered
    public function markAsDelivered(): void
    {
        $this->update([
            'is_delivered' => true,
            'sent_at' => $this->sent_at ?? now(),
        ]);
    }

    // Scope: unread
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // Scope: delivered
    public function scopeDelivered($query)
    {
        return $query->where('is_delivered', true);
    }
}
