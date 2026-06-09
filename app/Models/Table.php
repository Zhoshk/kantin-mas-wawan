<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    protected $fillable = [
        'location_id', 'table_number', 'table_name', 'table_type', 'capacity',
        'min_capacity', 'floor', 'zone', 'status', 'is_active', 'requires_deposit',
        'deposit_amount', 'features', 'sort_order', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_deposit' => 'boolean',
        'deposit_amount' => 'decimal:2',
        'features' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function occupancyLogs(): HasMany
    {
        return $this->hasMany(TableOccupancyLog::class);
    }

    public function isAvailableAt(\DateTime $dateTime, int $partySize): bool
    {
        if (!$this->is_active || $this->status !== 'available') {
            return false;
        }

        if ($partySize > $this->capacity || $partySize < $this->min_capacity) {
            return false;
        }

        // Check if there's a reservation at that time
        $existingReservation = $this->reservations()
            ->where('reservation_date', $dateTime->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->where(function ($query) use ($dateTime) {
                $query->where('reservation_time', '<=', $dateTime->format('H:i:s'))
                      ->whereRaw('DATE_ADD(reservation_time, INTERVAL duration_minutes MINUTE) > ?', [$dateTime->format('H:i:s')]);
            })
            ->exists();

        return !$existingReservation;
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->where('status', 'available');
    }

    public function scopeByFloor($query, string $floor)
    {
        return $query->where('floor', $floor);
    }

    public function scopeByZone($query, string $zone)
    {
        return $query->where('zone', $zone);
    }
}
