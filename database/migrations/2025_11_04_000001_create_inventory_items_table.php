<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates inventory_items table with all final fields.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->string('sku', 50)->primary(); // SKU as primary key (CLN-001, KTC-002, RNT-001, etc.)
            $table->string('name', 100);
            $table->string('category', 50)->index(); // cleaning, kitchen, amenity, rental_item
            $table->text('description')->nullable();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('reorder_level')->default(10);
            $table->decimal('average_cost', 10, 2)->default(0.00);
            $table->string('unit_of_measure', 20)->default('pcs');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
