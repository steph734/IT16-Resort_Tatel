<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('category', 50); // 'rental_item' or 'resort_supply'
            $table->string('sub_category', 50)->nullable(); // hygiene_kits, cleaning_materials, beddings, food_supplies, kitchen_wares, etc.
            $table->text('description')->nullable();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('reorder_level')->default(10); // Low stock threshold
            $table->decimal('average_cost', 10, 2)->default(0.00);
            $table->string('unit_of_measure', 20)->default('piece'); // piece, box, kg, liter, etc.
            $table->string('sku', 50)->nullable()->unique(); // Stock Keeping Unit
            $table->string('location', 100)->nullable(); // Storage location
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
