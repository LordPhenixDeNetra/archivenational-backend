<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->constrained('users')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at')->index();
            $table->dateTime('revoked_at')->nullable()->index();
            $table->string('user_agent')->nullable();
            $table->string('ip', 45)->nullable();
            $table->dateTime('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('refresh_tokens');
    }
};

