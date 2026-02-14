<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('rental_item_id')->nullable()->after('is_active')->constrained('rental_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['rental_item_id']);
            $table->dropColumn('rental_item_id');
        });
    }
};
