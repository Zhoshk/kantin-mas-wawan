<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Support Tickets
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->onDelete('set null');
            $table->enum('category', ['order_issue', 'payment', 'food_quality', 'delivery', 'refund', 'complaint', 'feedback', 'other'])->default('other');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'waiting_customer', 'waiting_internal', 'resolved', 'closed', 'reopened'])->default('open');
            $table->string('subject');
            $table->text('description');
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('response_time_minutes')->nullable();
            $table->integer('resolution_time_minutes')->nullable();
            $table->decimal('customer_satisfaction_rating', 3, 2)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });

        // Support Ticket Messages
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('sender_type', ['customer', 'staff', 'system'])->default('customer');
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal_note')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Live Chat Sessions
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 36)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('agent_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['waiting', 'active', 'ended', 'transferred'])->default('waiting');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('agent_joined_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('wait_time_seconds')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->decimal('customer_rating', 3, 2)->nullable();
            $table->text('customer_feedback')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        // Chat Messages
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('chat_sessions')->onDelete('cascade');
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('sender_type', ['customer', 'agent', 'bot'])->default('customer');
            $table->text('message');
            $table->enum('message_type', ['text', 'image', 'file', 'quick_reply', 'system'])->default('text');
            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
