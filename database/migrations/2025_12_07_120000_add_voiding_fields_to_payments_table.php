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
            // Add voiding fields to preserve payment history
            $table->boolean('is_voided')->default(false)->after('change_amount');
            $table->timestamp('voided_at')->nullable()->after('is_voided');
            $table->string('voided_by', 50)->nullable()->after('voided_at'); // Admin user_id
            $table->text('void_reason')->nullable()->after('voided_by');
            
            // Add index for voiding queries
            $table->index('is_voided');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['is_voided']);
            $table->dropColumn(['is_voided', 'voided_at', 'voided_by', 'void_reason']);
        });
    }
};
