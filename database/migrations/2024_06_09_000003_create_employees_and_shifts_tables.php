<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employees
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->virtualAs("CONCAT(first_name, ' ', last_name)");
            $table->string('phone', 20);
            $table->string('email')->unique()->nullable();
            $table->text('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('id_number', 30)->nullable(); // KTP/passport
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->enum('role', ['manager', 'cashier', 'chef', 'waiter', 'delivery', 'cleaner'])->default('waiter');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'freelance'])->default('full_time');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->enum('status', ['active', 'on_leave', 'suspended', 'terminated'])->default('active');
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->json('skills')->nullable(); // [cooking, customer_service, etc]
            $table->json('certifications')->nullable(); // food safety, etc
            $table->decimal('performance_rating', 3, 2)->default(0)->comment('0-5');
            $table->integer('total_orders_handled')->default(0);
            $table->decimal('average_order_time', 5, 2)->default(0); // minutes
            $table->integer('customer_complaints')->default(0);
            $table->integer('customer_compliments')->default(0);
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Shifts
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Morning, Afternoon, Night
            $table->time('start_time');
            $table->time('end_time');
            $table->json('days_of_week'); // [1,2,3,4,5] for Mon-Fri
            $table->integer('required_staff')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Employee Shifts (schedule)
        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->date('scheduled_date');
            $table->time('actual_start_time')->nullable();
            $table->time('actual_end_time')->nullable();
            $table->enum('status', ['scheduled', 'checked_in', 'checked_out', 'absent', 'late', 'on_break'])->default('scheduled');
            $table->integer('break_duration_minutes')->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->integer('orders_handled')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['scheduled_date', 'employee_id']);
        });

        // Employee Performance Logs
        Schema::create('employee_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->date('log_date');
            $table->enum('type', ['compliment', 'complaint', 'warning', 'review', 'achievement'])->default('review');
            $table->decimal('rating', 3, 2)->nullable()->comment('0-5');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });

        // Add employee tracking to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('cashier_id')->nullable()->after('customer_name')->constrained('employees')->onDelete('set null');
            $table->foreignId('chef_id')->nullable()->after('cashier_id')->constrained('employees')->onDelete('set null');
            $table->foreignId('server_id')->nullable()->after('chef_id')->constrained('employees')->onDelete('set null');
            $table->foreignId('delivery_person_id')->nullable()->after('server_id')->constrained('employees')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cashier_id']);
            $table->dropForeign(['chef_id']);
            $table->dropForeign(['server_id']);
            $table->dropForeign(['delivery_person_id']);
            $table->dropColumn(['cashier_id', 'chef_id', 'server_id', 'delivery_person_id']);
        });

        Schema::dropIfExists('employee_performance_logs');
        Schema::dropIfExists('employee_shifts');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('employees');
    }
};
