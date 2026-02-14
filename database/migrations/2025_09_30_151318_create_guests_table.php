<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates guests table with final structure.
     * Removed: Suffix, Socialmedia fields
     * Removed: unique constraints from Email and Phone
     */
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->string('GuestID', 10)->primary();
            $table->string('FName', 30);
            $table->string('MName', 30)->nullable();
            $table->string('LName', 30);
            $table->string('Email', 50); // No unique constraint
            $table->string('Phone', 30); // No unique constraint
            $table->string('Address', 100);
            $table->boolean('Contactable')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
