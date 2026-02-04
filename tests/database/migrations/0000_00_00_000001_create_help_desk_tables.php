<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_desk_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('help_desk_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('help_desk_departments')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('help_desk_categories')->nullOnDelete();
            $table->unique(['department_id', 'slug']);
        });

        Schema::create('help_desk_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference_number', 32)->unique();
            $table->foreignId('department_id')->constrained('help_desk_departments')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('help_desk_categories')->nullOnDelete();
            $table->string('user_type');
            $table->unsignedBigInteger('user_id');
            $table->string('assigned_to_type')->nullable();
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->string('title');
            $table->longText('description');
            $table->string('status', 32)->default('open')->index();
            $table->string('priority', 16)->default('medium')->index();
            $table->string('source', 32)->default('web');
            $table->string('email_message_id')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_replied_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_type', 'user_id']);
            $table->index(['assigned_to_type', 'assigned_to_id']);
            $table->index(['status', 'priority']);
            $table->index(['department_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('help_desk_ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('help_desk_tickets')->cascadeOnDelete();
            $table->string('author_type');
            $table->unsignedBigInteger('author_id');
            $table->longText('body');
            $table->string('type', 16)->default('reply');
            $table->boolean('is_internal')->default(false)->index();
            $table->string('email_message_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['author_type', 'author_id']);
            $table->index('created_at');
        });

        Schema::create('help_desk_ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ticket_id')->constrained('help_desk_tickets')->cascadeOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained('help_desk_ticket_comments')->nullOnDelete();
            $table->string('uploaded_by_type');
            $table->unsignedBigInteger('uploaded_by_id');
            $table->string('file_name');
            $table->string('file_path', 512);
            $table->string('disk', 32)->default('local');
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('file_size');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['uploaded_by_type', 'uploaded_by_id']);
        });

        Schema::create('help_desk_ticket_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('help_desk_tickets')->cascadeOnDelete();
            $table->string('performer_type')->nullable();
            $table->unsignedBigInteger('performer_id')->nullable();
            $table->string('action', 64);
            $table->string('field', 64)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['performer_type', 'performer_id']);
        });

        Schema::create('help_desk_department_operator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('help_desk_departments')->cascadeOnDelete();
            $table->string('operator_type');
            $table->unsignedBigInteger('operator_id');
            $table->string('role', 32)->default('operator');
            $table->timestamps();

            $table->unique(['department_id', 'operator_type', 'operator_id'], 'dept_operator_unique');
            $table->index(['operator_type', 'operator_id']);
        });

        Schema::create('help_desk_ticket_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('help_desk_tickets')->cascadeOnDelete();
            $table->string('watcher_type');
            $table->unsignedBigInteger('watcher_id');
            $table->timestamp('created_at')->nullable();

            $table->unique(['ticket_id', 'watcher_type', 'watcher_id'], 'ticket_watcher_unique');
            $table->index(['watcher_type', 'watcher_id']);
        });

        Schema::create('help_desk_canned_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('help_desk_departments')->nullOnDelete();
            $table->string('title')->index();
            $table->longText('body');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('help_desk_email_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('help_desk_departments')->nullOnDelete();
            $table->string('name');
            $table->string('driver', 32);
            $table->string('email_address')->unique();
            $table->json('settings');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_polled_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('help_desk_inbound_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_channel_id')->nullable()->constrained('help_desk_email_channels')->nullOnDelete();
            $table->string('message_id')->unique();
            $table->string('in_reply_to')->nullable()->index();
            $table->text('references')->nullable();
            $table->string('from_address')->index();
            $table->string('from_name')->nullable();
            $table->json('to_addresses');
            $table->json('cc_addresses')->nullable();
            $table->string('subject')->nullable();
            $table->longText('text_body')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('raw_payload')->nullable();
            $table->foreignId('ticket_id')->nullable()->constrained('help_desk_tickets')->nullOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained('help_desk_ticket_comments')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_desk_inbound_emails');
        Schema::dropIfExists('help_desk_email_channels');
        Schema::dropIfExists('help_desk_canned_responses');
        Schema::dropIfExists('help_desk_ticket_watchers');
        Schema::dropIfExists('help_desk_department_operator');
        Schema::dropIfExists('help_desk_ticket_history');
        Schema::dropIfExists('help_desk_ticket_attachments');
        Schema::dropIfExists('help_desk_ticket_comments');
        Schema::dropIfExists('help_desk_tickets');
        Schema::dropIfExists('help_desk_categories');
        Schema::dropIfExists('help_desk_departments');
    }
};
