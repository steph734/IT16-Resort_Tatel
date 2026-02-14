<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_entry_items', function (Blueprint $table) {
            $table->string('entry_number', 50); // FK to purchase_entries
            $table->string('sku', 50); // FK to inventory_items
            $table->string('item_name', 100); // Snapshot of item name at time of purchase
            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('subtotal', 10, 2); // quantity * unit_cost
            $table->timestamps();
            
            // Composite primary key: one item per purchase entry
            $table->primary(['entry_number', 'sku']);
            
            // Foreign keys
            $table->foreign('entry_number')->references('entry_number')->on('purchase_entries')->onDelete('cascade');
            $table->foreign('sku')->references('sku')->on('inventory_items')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_entry_items');
    }
};
