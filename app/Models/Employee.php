<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_code', 'user_id', 'location_id', 'first_name', 'last_name', 'phone',
        'email', 'address', 'birth_date', 'id_number', 'gender', 'role', 'employment_type',
        'hire_date', 'termination_date', 'status', 'hourly_rate', 'monthly_salary',
        'skills', 'certifications', 'performance_rating', 'total_orders_handled',
        'average_order_time', 'customer_complaints', 'customer_compliments', 'photo', 'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'performance_rating' => 'decimal:2',
        'average_order_time' => 'decimal:2',
        'skills' => 'array',
        'certifications' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function employeeShifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function performanceLogs(): HasMany
    {
        return $this->hasMany(EmployeePerformanceLog::class);
    }

    public function ordersAsCashier(): HasMany
    {
        return $this->hasMany(Order::class, 'cashier_id');
    }

    public function ordersAsChef(): HasMany
    {
        return $this->hasMany(Order::class, 'chef_id');
    }

    public function ordersAsServer(): HasMany
    {
        return $this->hasMany(Order::class, 'server_id');
    }

    public function ordersAsDelivery(): HasMany
    {
        return $this->hasMany(Order::class, 'delivery_person_id');
    }

    public function updatePerformanceRating(): void
    {
        $totalCompliments = $this->customer_compliments;
        $totalComplaints = $this->customer_complaints;
        $totalOrders = $this->total_orders_handled;

        if ($totalOrders == 0) {
            $this->performance_rating = 3.0;
            $this->save();
            return;
        }

        $complimentRate = $totalCompliments / $totalOrders;
        $complaintRate = $totalComplaints / $totalOrders;

        $rating = 3.0 + ($complimentRate * 2) - ($complaintRate * 2);
        $this->performance_rating = max(0, min(5, $rating));
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
