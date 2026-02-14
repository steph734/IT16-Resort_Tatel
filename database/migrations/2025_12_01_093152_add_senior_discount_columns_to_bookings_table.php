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
        Schema::table('bookings', function (Blueprint $table) {
            // Add senior discount columns if they don't exist
            if (!Schema::hasColumn('bookings', 'senior_discount')) {
                $table->decimal('senior_discount', 10, 2)->default(0)->after('NumOfAdults');
            }
            if (!Schema::hasColumn('bookings', 'actual_seniors_at_checkout')) {
                $table->integer('actual_seniors_at_checkout')->default(0)->after('senior_discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['senior_discount', 'actual_seniors_at_checkout']);
        });
    }
};
