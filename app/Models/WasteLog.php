<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteLog extends Model
{
    protected $fillable = [
        'location_id', 'employee_id', 'menu_item_id', 'ingredient_id', 'waste_type',
        'quantity', 'unit', 'estimated_cost', 'reason', 'waste_date', 'waste_time',
        'is_preventable', 'prevention_notes', 'photo',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'waste_date' => 'date',
        'is_preventable' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public static function getTotalWasteForPeriod(Location $location, \DateTime $start, \DateTime $end): array
    {
        $logs = self::where('location_id', $location->id)
            ->whereBetween('waste_date', [$start, $end])
            ->get();

        return [
            'total_quantity_kg' => $logs->sum('quantity'),
            'total_cost' => $logs->sum('estimated_cost'),
            'preventable_percentage' => $logs->where('is_preventable', true)->count() / max($logs->count(), 1) * 100,
            'by_type' => $logs->groupBy('waste_type')->map->count(),
        ];
    }
}
