<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actor_user_id')->nullable()->index();
            $table->string('action', 80)->index();
            $table->string('entity_type', 80)->index();
            $table->uuid('entity_id')->nullable()->index();
            $table->json('metadata_json')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->dateTime('created_at')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};

