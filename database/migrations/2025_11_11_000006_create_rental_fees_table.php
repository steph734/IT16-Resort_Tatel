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
        Schema::create('rental_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_id'); // Foreign key to rentals
            $table->enum('type', ['Rental', 'Adjustment', 'Damage', 'Loss']); // Fee type
            $table->decimal('amount', 10, 2); // Fee amount
            $table->text('reason')->nullable(); // Reason/notes for the fee
            $table->string('photo_path')->nullable(); // Path to damage/loss photo
            $table->string('added_by', 8)->nullable(); // User who added the fee
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('rental_id')->references('id')->on('rentals')->onDelete('cascade');
            $table->foreign('added_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_fees');
    }
};
