<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Drop the old purchase_order_id foreign key and column
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
            
            // Add new purchase_entry_id foreign key
            $table->foreignId('purchase_entry_id')->nullable()->after('reason')->constrained('purchase_entries')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Drop purchase_entry_id
            $table->dropForeign(['purchase_entry_id']);
            $table->dropColumn('purchase_entry_id');
            
            // Restore purchase_order_id
            $table->foreignId('purchase_order_id')->nullable()->after('reason')->constrained('purchase_orders')->onDelete('set null');
        });
    }
};
