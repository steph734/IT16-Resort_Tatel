<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 50)->unique(); // PE-2025-0001
            $table->date('purchase_date');
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('vendor_name', 100); // Simple text field, no FK to suppliers
            $table->text('notes')->nullable();
            $table->string('created_by', 8)->nullable();
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_entries');
    }
};
