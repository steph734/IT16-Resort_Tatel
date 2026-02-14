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
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->string('BookingID'); // Foreign key to bookings
            $table->unsignedBigInteger('rental_item_id'); // Foreign key to rental_items
            $table->integer('quantity'); // Quantity rented
            $table->decimal('rate_snapshot', 10, 2); // Rate at time of rental
            $table->enum('rate_type_snapshot', ['Per-Day', 'Flat']); // Rate type at time of rental
            $table->enum('status', ['Issued', 'Returned', 'Lost', 'Damaged'])->default('Issued');
            $table->integer('returned_quantity')->nullable(); // Quantity returned
            $table->enum('condition', ['Good', 'Damaged', 'Lost'])->nullable(); // Item condition on return
            $table->text('notes')->nullable(); // General notes
            $table->text('damage_description')->nullable(); // Damage/loss description
            $table->timestamp('issued_at'); // When issued
            $table->timestamp('returned_at')->nullable(); // When returned
            $table->string('issued_by', 8)->nullable(); // User who issued
            $table->string('returned_by', 8)->nullable(); // User who processed return
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('BookingID')->references('BookingID')->on('bookings')->onDelete('cascade');
            $table->foreign('rental_item_id')->references('id')->on('rental_items')->onDelete('cascade');
            $table->foreign('issued_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('returned_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
