<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates stock_movements table with all final fields.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50); // FK to inventory_items
            $table->string('movement_type', 20)->index(); // 'in' or 'out'
            $table->integer('quantity');
            $table->enum('reason', [
                'purchase',
                'adjustment_in',
                'adjustment_out',
                'usage',
                'rental_damage',
                'lost',
                'expired'
            ])->nullable();

            $table->string('entry_number', 50)->nullable(); // FK to purchase_entries (nullable)
            $table->foreignId('rental_id')->nullable()->constrained('rentals')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->string('performed_by', 8)->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('sku')->references('sku')->on('inventory_items')->onDelete('restrict');
            $table->foreign('entry_number')->references('entry_number')->on('purchase_entries')->onDelete('set null');
            $table->foreign('performed_by')->references('user_id')->on('users')->onDelete('set null');
            
            // Index for performance
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
