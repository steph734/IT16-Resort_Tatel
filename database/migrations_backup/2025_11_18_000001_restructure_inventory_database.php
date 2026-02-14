<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration restructures the inventory subsystem database:
     * 1. Removes unused tables (purchase_orders, purchase_order_items, suppliers)
     * 2. Keeps essential tables (inventory_items, purchase_entries, purchase_entry_items, stock_movements)
     * 3. Adds proper foreign keys and constraints
     * 4. Adds receipt_no field to purchase_entries
     */
    public function up(): void
    {
        // Drop unused tables in correct order (respecting foreign key constraints)
        if (Schema::hasTable('purchase_order_items')) {
            Schema::dropIfExists('purchase_order_items');
        }
        
        if (Schema::hasTable('purchase_orders')) {
            Schema::dropIfExists('purchase_orders');
        }
        
        if (Schema::hasTable('suppliers')) {
            Schema::dropIfExists('suppliers');
        }

        // Add receipt_no to purchase_entries if not exists
        if (Schema::hasTable('purchase_entries')) {
            Schema::table('purchase_entries', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_entries', 'receipt_no')) {
                    $table->string('receipt_no', 100)->nullable()->after('vendor_name');
                }
            });
        }

        // Ensure inventory_items has all necessary columns and proper structure
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                // Add indexes for better query performance
                if (!Schema::hasIndex('inventory_items', ['category'])) {
                    $table->index('category');
                }
                if (!Schema::hasIndex('inventory_items', ['is_active'])) {
                    $table->index('is_active');
                }
                if (!Schema::hasIndex('inventory_items', ['sku'])) {
                    $table->index('sku');
                }
            });
        }

        // Ensure purchase_entries has proper indexes
        if (Schema::hasTable('purchase_entries')) {
            Schema::table('purchase_entries', function (Blueprint $table) {
                if (!Schema::hasIndex('purchase_entries', ['purchase_date'])) {
                    $table->index('purchase_date');
                }
                if (!Schema::hasIndex('purchase_entries', ['entry_number'])) {
                    $table->index('entry_number');
                }
            });
        }

        // Ensure stock_movements has proper indexes
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                if (!Schema::hasIndex('stock_movements', ['movement_type'])) {
                    $table->index('movement_type');
                }
                if (!Schema::hasIndex('stock_movements', ['created_at'])) {
                    $table->index('created_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate suppliers table
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('contact_person', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Recreate purchase_orders table
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->string('created_by', 8)->nullable();
            $table->string('approved_by', 8)->nullable();
            $table->string('received_by', 8)->nullable();
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('received_by')->references('user_id')->on('users')->onDelete('set null');
            $table->timestamps();
        });

        // Recreate purchase_order_items table
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('restrict');
            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Remove receipt_no from purchase_entries
        if (Schema::hasTable('purchase_entries')) {
            Schema::table('purchase_entries', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_entries', 'receipt_no')) {
                    $table->dropColumn('receipt_no');
                }
            });
        }

        // Remove indexes
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropIndex(['category']);
                $table->dropIndex(['is_active']);
                $table->dropIndex(['sku']);
            });
        }

        if (Schema::hasTable('purchase_entries')) {
            Schema::table('purchase_entries', function (Blueprint $table) {
                $table->dropIndex(['purchase_date']);
                $table->dropIndex(['entry_number']);
            });
        }

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropIndex(['movement_type']);
                $table->dropIndex(['created_at']);
            });
        }
    }
};
