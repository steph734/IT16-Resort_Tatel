<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseEntry;
use App\Models\PurchaseEntryItem;
use App\Models\StockMovement;
use App\Models\InventoryItem;
use App\Models\User;
use Carbon\Carbon;

class PurchaseEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 5 purchase entries with diverse items from all categories
     * - Each purchase has 3-6 items from different categories
     * - Purchases dated within the past 3 months
     * - Updates inventory quantities and creates stock movements
     */
    public function run(): void
    {
        // Get inventory items
        $inventoryItems = InventoryItem::where('is_active', true)->get();
        if ($inventoryItems->isEmpty()) {
            $this->command->error('No inventory items found. Please run InventoryItemSeeder first.');
            return;
        }

        // Get admin user to record purchases
        $users = User::whereIn('role', ['admin', 'staff'])->where('status', 'active')->pluck('user_id')->toArray();
        if (empty($users)) {
            $this->command->error('No active admin/staff users found. Please run AdminUserSeeder first.');
            return;
        }

        $vendors = [
            'Metro Wholesale Supplies',
            'Davao Equipment Center',
            'CDO General Merchandise',
            'GenSan Trading Co.',
            'Island Distributors Inc.',
        ];

        // Create 5 purchase entries
        $purchases = [
            [
                'date' => Carbon::now()->subMonths(2)->subDays(15),
                'vendor' => $vendors[0],
                'receipt' => 'MWS-2025-' . rand(1000, 9999),
                'categories' => ['cleaning', 'kitchen', 'amenity'],
            ],
            [
                'date' => Carbon::now()->subMonths(2)->subDays(5),
                'vendor' => $vendors[1],
                'receipt' => 'DEC-2025-' . rand(1000, 9999),
                'categories' => ['rental_item'],
            ],
            [
                'date' => Carbon::now()->subMonths(1)->subDays(20),
                'vendor' => $vendors[2],
                'receipt' => 'CDO-2025-' . rand(1000, 9999),
                'categories' => ['cleaning', 'kitchen'],
            ],
            [
                'date' => Carbon::now()->subMonths(1)->subDays(3),
                'vendor' => $vendors[3],
                'receipt' => 'GST-2025-' . rand(1000, 9999),
                'categories' => ['amenity', 'rental_item'],
            ],
            [
                'date' => Carbon::now()->subDays(10),
                'vendor' => $vendors[4],
                'receipt' => 'IDI-2025-' . rand(1000, 9999),
                'categories' => ['cleaning', 'kitchen', 'amenity', 'rental_item'],
            ],
        ];

        $totalPurchases = 0;
        $totalItems = 0;

        foreach ($purchases as $index => $purchaseData) {
            $entryNumber = PurchaseEntry::generateEntryNumber();
            
            // Get items from specified categories
            $availableItems = $inventoryItems->whereIn('category', $purchaseData['categories']);
            $itemCount = rand(3, min(6, $availableItems->count()));
            $selectedItems = $availableItems->random($itemCount);

            $totalAmount = 0;
            $purchaseItems = [];

            foreach ($selectedItems as $item) {
                $quantity = rand(5, 25); // Purchase 5-25 units
                $unitCost = $item->average_cost;
                $subtotal = $quantity * $unitCost;
                $totalAmount += $subtotal;

                $purchaseItems[] = [
                    'sku' => $item->sku,
                    'item_name' => $item->name,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                ];

                $totalItems++;
            }

            // Create purchase entry
            $purchase = PurchaseEntry::create([
                'entry_number' => $entryNumber,
                'purchase_date' => $purchaseData['date'],
                'total_amount' => $totalAmount,
                'vendor_name' => $purchaseData['vendor'],
                'receipt_no' => $purchaseData['receipt'],
                'notes' => 'Regular inventory restock - ' . implode(', ', $purchaseData['categories']),
                'created_by' => $users[array_rand($users)],
            ]);

            // Create purchase entry items and stock movements
            foreach ($purchaseItems as $itemData) {
                // Create purchase entry item
                PurchaseEntryItem::create([
                    'entry_number' => $entryNumber,
                    'sku' => $itemData['sku'],
                    'item_name' => $itemData['item_name'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                    'subtotal' => $itemData['subtotal'],
                ]);

                // Update inventory quantity
                $inventoryItem = InventoryItem::where('sku', $itemData['sku'])->first();
                if ($inventoryItem) {
                    $inventoryItem->increment('quantity_on_hand', $itemData['quantity']);

                    // Create stock movement
                    StockMovement::create([
                        'sku' => $itemData['sku'],
                        'movement_type' => 'in',
                        'quantity' => $itemData['quantity'],
                        'reason' => 'purchase',
                        'entry_number' => $entryNumber,
                        'rental_id' => null,
                        'notes' => "Added via purchase from {$purchaseData['vendor']}",
                        'performed_by' => $purchase->created_by,
                    ]);
                }
            }

            $totalPurchases++;
        }

        $this->command->info("Successfully created {$totalPurchases} purchase entries:");
        $this->command->info("- Total items purchased: {$totalItems}");
        $this->command->info("- Purchase dates: Last 2-3 months");
        $this->command->info("- Categories: Cleaning, Kitchen, Amenity, Rental items");
        $this->command->info("- Inventory quantities updated");
        $this->command->info("- Stock movements recorded");
    }
}
