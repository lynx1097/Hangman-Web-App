<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Name can be any reasonable length
            $table->string('name');
            // Email needs to be unique and indexed for quick lookups
            $table->string('email')->unique();
            // Email verification timestamp
            $table->timestamp('email_verified_at')->nullable();
            // Password column must be at least 60 characters for bcrypt
            $table->string('password', 60);
            // Remember token for "remember me" functionality
            $table->rememberToken();
            // Two-factor authentication columns (optional)
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            // Timestamps for record keeping
            $table->timestamps();
        });

        // Create a password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Create a failed login attempts table for security
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('ip_address', 45);
            $table->timestamps();
            
            // Index for quick lookups when checking failed attempts
            $table->index(['email', 'ip_address', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('failed_login_attempts');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};