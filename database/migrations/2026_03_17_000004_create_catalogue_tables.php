<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fonds_archives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('period_label', 80)->nullable();
            $table->boolean('unesco')->default(false)->index();
            $table->unsignedInteger('estimated_documents_count')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fonds_id')->index()->constrained('fonds_archives')->cascadeOnDelete();
            $table->string('title')->index();
            $table->string('reference_code', 80)->nullable()->index();
            $table->text('summary')->nullable();
            $table->enum('type', ['MANUSCRIPT', 'NEWSPAPER', 'MAP', 'PHOTO', 'REGISTER', 'REPORT', 'LETTER', 'AUDIO', 'VIDEO', 'OTHER'])->index();
            $table->enum('visibility', ['PUBLIC', 'REGISTERED', 'RESTRICTED', 'ADMIN_ONLY'])->index();
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable()->index();
            $table->string('language', 30)->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->dateTime('published_at')->nullable()->index();
            $table->timestamps();
            $table->index(['fonds_id', 'visibility', 'published_at']);
            $table->index(['type', 'visibility']);
        });

        Schema::create('document_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->index()->constrained('documents')->cascadeOnDelete();
            $table->enum('kind', ['PDF', 'IMAGE_JPEG', 'IMAGE_PNG', 'THUMBNAIL', 'OCR_TEXT', 'OTHER']);
            $table->string('storage_key', 512)->unique();
            $table->string('content_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->unsignedInteger('version');
            $table->uuid('uploaded_by')->nullable();
            $table->dateTime('uploaded_at');
            $table->index(['document_id', 'kind']);
            $table->index(['document_id', 'version']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('document_tag', function (Blueprint $table) {
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignUuid('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['document_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('access_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->index()->constrained('documents')->cascadeOnDelete();
            $table->enum('rule', ['ALLOW', 'DENY', 'REQUIRE_MFA', 'REQUIRE_APPROVAL']);
            $table->json('conditions_json')->nullable();
            $table->timestamps();
        });

        Schema::create('document_view_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->index()->constrained('documents')->cascadeOnDelete();
            $table->uuid('user_id')->nullable()->index();
            $table->dateTime('viewed_at')->index();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_view_events');
        Schema::dropIfExists('access_policies');
        Schema::dropIfExists('document_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('document_files');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('fonds_archives');
    }
};

