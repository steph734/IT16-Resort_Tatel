<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates purchase_entries table with all final fields.
     */
    public function up(): void
    {
        Schema::create('purchase_entries', function (Blueprint $table) {
            $table->string('entry_number', 50)->primary(); // entry_number as primary key (PE001, PE002, etc.)
            $table->date('purchase_date')->index();
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('vendor_name', 100); // Simple text field (no FK)
            $table->string('receipt_no', 100)->nullable(); // Optional receipt number
            $table->text('notes')->nullable();
            $table->string('created_by', 8)->nullable();
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_entries');
    }
};
