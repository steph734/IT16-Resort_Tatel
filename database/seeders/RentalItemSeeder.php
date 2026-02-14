<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RentalItem;
use App\Models\InventoryItem;

class RentalItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates rental item configurations for the 10 rental inventory items
     * with diversified rate types (Per-Day and Flat rate)
     */
    public function run(): void
    {
        // Ensure inventory items exist
        $inventoryCount = InventoryItem::where('category', 'rental_item')->count();
        if ($inventoryCount === 0) {
            $this->command->error('No rental inventory items found. Please run InventoryItemSeeder first.');
            return;
        }

        $rentalItems = [
            [
                'sku' => 'RNT-001',
                'rate_type' => 'Per-Day',
                'rate' => 500.00,
                'description' => 'Kayak rental - per day rate',
                'status' => 'Active',
            ],
            [
                'sku' => 'RNT-002',
                'rate_type' => 'Flat',
                'rate' => 50.00,
                'description' => 'Life jacket rental - flat rate for entire stay',
                'status' => 'Active',
            ],
            [
                'sku' => 'RNT-003',
                'rate_type' => 'Per-Day',
                'rate' => 250.00,
                'description' => 'Snorkeling set rental - per day rate',
                'status' => 'Active',
            ],
            [
                'sku' => 'RNT-004',
                'rate_type' => 'Flat',
                'rate' => 150.00,
                'description' => 'Beach umbrella rental - flat rate for entire stay',
                'status' => 'Active',
            ],
            [
                'sku' => 'RNT-005',
                'rate_type' => 'Flat',
                'rate' => 100.00,
                'description' => 'Beach chair rental - flat rate for entire stay',
                'status' => 'Active',
            ],
            [
                'sku' => 'RNT-006',
                'rate_type' => 'Per-Day',
                'rate' => 150.00,
                'description' => 'Inflatable float rental - per day rate',
                'status' => 'Active',
            ],
            [
                'sku' => 'RNT-007',
                'rate_type' => 'Per-Day',
                'rate' => 400.00,
                'description' => 'Volleyball net set rental - per day rate',
                'status' => 'Active',
            ],
            [
                'sku' => 'RNT-008',
                'rate_type' => 'Flat',
                'rate' => 200.00,
                'description' => 'Cooler box rental - flat rate for entire stay',
                'status' => 'Active',
            ],
            [
                'sku' => 'RNT-009',
                'rate_type' => 'Per-Day',
                'rate' => 600.00,
                'description' => 'Paddleboard rental - per day rate',
                'status' => 'Active',
            ],
            [
                'sku' => 'RNT-010',
                'rate_type' => 'Flat',
                'rate' => 50.00,
                'description' => 'Beach ball rental - flat rate for entire stay',
                'status' => 'Active',
            ],
        ];

        foreach ($rentalItems as $item) {
            RentalItem::updateOrCreate(
                ['sku' => $item['sku']],
                $item
            );
        }

        $this->command->info('Successfully created 10 rental item configurations:');
        $this->command->info('- 5 Per-Day rate items (Kayak, Snorkeling Set, Float, Volleyball Net, Paddleboard)');
        $this->command->info('- 5 Flat rate items (Life Jacket, Beach Umbrella, Beach Chair, Cooler Box, Beach Ball)');
    }
}
