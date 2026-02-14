<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('restrict');
            $table->string('movement_type', 20); // 'in' or 'out'
            $table->integer('quantity');
            $table->string('reason', 50); // 'po_receipt', 'adjustment', 'rental_damage', 'lost', 'expired', 'usage', etc.
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->onDelete('set null');
            $table->foreignId('rental_id')->nullable()->constrained('rentals')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->string('performed_by', 8)->nullable();
            $table->foreign('performed_by')->references('user_id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
