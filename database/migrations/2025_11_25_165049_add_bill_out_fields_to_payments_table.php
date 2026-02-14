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
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('total_outstanding', 10, 2)->nullable()->after('Amount');
            $table->decimal('amount_received', 10, 2)->nullable()->after('total_outstanding');
            $table->decimal('change_amount', 10, 2)->nullable()->after('amount_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['total_outstanding', 'amount_received', 'change_amount']);
        });
    }
};
