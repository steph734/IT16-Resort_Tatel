<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_entry_id')->constrained('purchase_entries')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('restrict');
            $table->string('item_name', 100); // Snapshot of item name at time of purchase
            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('subtotal', 10, 2); // quantity * unit_cost
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_entry_items');
    }
};
