<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('requester_user_id')->nullable()->index();
            $table->string('requester_full_name', 160);
            $table->string('requester_email', 191)->index();
            $table->string('requester_phone', 30)->nullable();
            $table->enum('type', ['CONSULTATION', 'COPY_CERTIFIED', 'AUTHENTICATION', 'RESEARCH'])->index();
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'NEEDS_INFO', 'APPROVED', 'REJECTED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])->index();
            $table->enum('priority', ['LOW', 'NORMAL', 'HIGH', 'URGENT'])->index();
            $table->string('subject');
            $table->text('description');
            $table->dateTime('closed_at')->nullable()->index();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['type', 'status']);
        });

        Schema::create('request_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->index()->constrained('service_requests')->cascadeOnDelete();
            $table->string('storage_key', 512)->unique();
            $table->string('content_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->dateTime('uploaded_at');
        });

        Schema::create('request_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('request_id')->index()->constrained('service_requests')->cascadeOnDelete();
            $table->enum('from_status', ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'NEEDS_INFO', 'APPROVED', 'REJECTED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED']);
            $table->enum('to_status', ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'NEEDS_INFO', 'APPROVED', 'REJECTED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED']);
            $table->dateTime('changed_at')->index();
            $table->uuid('changed_by')->nullable();
            $table->text('note')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('request_status_histories');
        Schema::dropIfExists('request_attachments');
        Schema::dropIfExists('service_requests');
    }
};

