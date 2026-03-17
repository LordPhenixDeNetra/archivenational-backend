<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('display_name', 120)->nullable();
            $table->enum('status', ['ACTIVE', 'SUSPENDED', 'PENDING', 'DELETED'])->default('ACTIVE')->index();
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
