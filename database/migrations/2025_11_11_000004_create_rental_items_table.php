<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique(); // FK to inventory_items (unique: one-to-one relationship)
            $table->enum('rate_type', ['Per-Day', 'Flat'])->default('Per-Day');
            $table->decimal('rate', 10, 2); // Rate amount
            $table->text('description')->nullable(); // Item description
            $table->enum('status', ['Active', 'Archived'])->default('Active');
            $table->timestamps();
            $table->softDeletes(); // Soft delete for archived items
            
            // Foreign key
            $table->foreign('sku')->references('sku')->on('inventory_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_items');
    }
};
