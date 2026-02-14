<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique(); // e.g., PO-2025-0001
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('status', 20)->default('draft'); // draft, issued, received, closed
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->string('created_by', 8)->nullable();
            $table->string('approved_by', 8)->nullable();
            $table->string('received_by', 8)->nullable();
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('received_by')->references('user_id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
