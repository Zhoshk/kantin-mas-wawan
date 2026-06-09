<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'type', 'address', 'city', 'province', 'postal_code',
        'latitude', 'longitude', 'phone', 'email', 'manager_name', 'parent_location_id',
        'capacity', 'table_count', 'opening_time', 'closing_time', 'operating_days',
        'is_active', 'accepts_delivery', 'accepts_takeaway', 'accepts_dine_in',
        'accepts_reservations', 'delivery_radius_km', 'avg_preparation_time',
        'supported_payment_methods', 'facilities', 'timezone', 'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
        'accepts_delivery' => 'boolean',
        'accepts_takeaway' => 'boolean',
        'accepts_dine_in' => 'boolean',
        'accepts_reservations' => 'boolean',
        'operating_days' => 'array',
        'supported_payment_methods' => 'array',
        'facilities' => 'array',
    ];

    public function parentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_location_id');
    }

    public function childLocations(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_location_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
    }

    public function kitchenStations(): HasMany
    {
        return $this->hasMany(KitchenStation::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function isOperatingNow(): bool
    {
        $now = now($this->timezone);
        $currentDay = $now->dayOfWeek;
        
        if (!in_array($currentDay, $this->operating_days ?? [])) {
            return false;
        }

        $currentTime = $now->format('H:i:s');
        return $currentTime >= $this->opening_time && $currentTime <= $this->closing_time;
    }

    public function getRevenueForPeriod(\DateTime $start, \DateTime $end): float
    {
        return $this->orders()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['completed', 'delivered'])
            ->sum('total_price');
    }
}
