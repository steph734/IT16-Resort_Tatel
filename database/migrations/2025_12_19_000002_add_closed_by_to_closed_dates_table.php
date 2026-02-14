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
        Schema::table('closed_dates', function (Blueprint $table) {
            // users.user_id is a string(8)
            $table->string('closed_by', 8)->nullable()->after('reason');

            $table->foreign('closed_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('closed_dates', function (Blueprint $table) {
            $table->dropForeign(['closed_by']);
            $table->dropColumn('closed_by');
        });
    }
};
