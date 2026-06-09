<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    protected $fillable = [
        'reservation_code', 'customer_id', 'location_id', 'table_id', 'customer_name',
        'customer_phone', 'customer_email', 'reservation_date', 'reservation_time',
        'party_size', 'duration_minutes', 'status', 'special_occasion',
        'special_requests', 'dietary_requirements', 'deposit_paid', 'deposit_amount',
        'confirmed_at', 'seated_at', 'completed_at', 'cancelled_at',
        'cancellation_reason', 'order_id', 'staff_notes',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'dietary_requirements' => 'array',
        'deposit_paid' => 'boolean',
        'deposit_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'seated_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ReservationHistory::class);
    }

    public function updateStatus(string $newStatus, ?string $notes = null): void
    {
        $oldStatus = $this->status;
        $this->update(['status' => $newStatus]);

        $this->history()->create([
            'previous_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
        ]);
    }

    public static function generateReservationCode(): string
    {
        return 'RES-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    }

    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', today())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeToday($query)
    {
        return $query->where('reservation_date', today());
    }
}
