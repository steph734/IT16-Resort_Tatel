<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users table replaces your old accounts table
        Schema::create('users', function (Blueprint $table) {
            $table->string('user_id', 8)->primary(); // e.g., A001, S001
            $table->string('name'); // full name
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('user'); // admin, staff, etc.
            $table->string('status')->default('active'); // ✅ fixed — removed after('role')
         $table->integer('failed_attempts')->default(0);
        $table->timestamp('locked_until')->nullable();
               $table->integer('lock_level')->default(0);
        $table->timestamp('last_failed_login')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Password reset tokens (default Laravel)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions (default Laravel)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id', 8)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Logs table for tracking user activity
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 8)->nullable();
            $table->string('action'); // e.g., login, logout, create, update, delete
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
