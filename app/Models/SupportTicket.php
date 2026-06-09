<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'ticket_number', 'customer_id', 'order_id', 'location_id', 'assigned_to',
        'category', 'priority', 'status', 'subject', 'description', 'first_response_at',
        'resolved_at', 'closed_at', 'response_time_minutes', 'resolution_time_minutes',
        'customer_satisfaction_rating', 'resolution_notes',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'customer_satisfaction_rating' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }

    public static function generateTicketNumber(): string
    {
        return 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    public function addMessage(string $message, $sender, string $senderType = 'customer'): void
    {
        $this->messages()->create([
            'sender_type' => $senderType,
            'message' => $message,
            'user_id' => $senderType === 'staff' ? $sender->id : null,
            'customer_id' => $senderType === 'customer' ? $sender->id : null,
        ]);

        if ($senderType === 'staff' && !$this->first_response_at) {
            $this->update([
                'first_response_at' => now(),
                'response_time_minutes' => $this->created_at->diffInMinutes(now()),
            ]);
        }
    }
}
