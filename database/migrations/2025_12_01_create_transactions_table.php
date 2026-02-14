<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            
            // Core transaction info
            $table->enum('transaction_type', ['booking', 'rental', 'add-on'])->index();
            $table->string('reference_id', 50)->index(); // PaymentID, RentalID, etc.
            $table->dateTime('transaction_date')->index();
            
            // Financial details
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['Cash', 'GCash', 'BDO Transfer', 'BPI Transfer', 'GoTyme']);
            $table->enum('payment_status', ['Fully Paid', 'Downpayment', 'Partial Payment'])->index();
            $table->string('purpose', 100); // "Full Payment", "Downpayment", "Rent Item - Dart Set", etc.
            
            // Related entities
            $table->string('booking_id', 50)->nullable()->index();
            // store GuestID (string) and add FK to guests.GuestID for data integrity
            $table->string('guest_id', 10)->nullable()->index();
            $table->string('rental_id', 50)->nullable()->index();
            
            // Guest/Customer info (denormalized for reporting)
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_email', 100)->nullable();
            $table->string('customer_phone', 20)->nullable();
            
            // Processing info
            // The application's users table uses string `user_id` (e.g. A001) as PK (length 8)
            // Use the same length here and add a FK constraint to users.user_id
            $table->string('processed_by', 8)->nullable()->index(); // stores users.user_id
            $table->string('processor_name', 100)->nullable(); // Denormalized for display
            
            // Cash transaction fields
            $table->decimal('amount_received', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();
            
            // Additional details (stored as JSON for flexibility)
            $table->json('metadata')->nullable(); // Package name, item details, fees, etc.
            
            // Reference and notes
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            
            // Voiding
            $table->boolean('is_voided')->default(false)->index();
            $table->dateTime('voided_at')->nullable();
            // match users.user_id length and reference users.user_id
            $table->string('voided_by', 8)->nullable()->index();
            $table->text('void_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['transaction_type', 'transaction_date']);
            $table->index(['payment_status', 'is_voided']);

            // Foreign key constraints
            $table->foreign('processed_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('voided_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('guest_id')->references('GuestID')->on('guests')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Drop foreign keys first to avoid issues
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'processed_by')) {
                $table->dropForeign(['processed_by']);
            }
            if (Schema::hasColumn('transactions', 'voided_by')) {
                $table->dropForeign(['voided_by']);
            }
            if (Schema::hasColumn('transactions', 'guest_id')) {
                $table->dropForeign(['guest_id']);
            }
        });

        Schema::dropIfExists('transactions');
    }
};
