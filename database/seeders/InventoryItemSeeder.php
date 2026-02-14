<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryItem;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates inventory items across multiple categories:
     * - 5 Cleaning supplies
     * - 5 Kitchen items
     * - 5 Amenity items
     * - 10 Rental items (resort equipment)
     */
    public function run(): void
    {
        $items = [
            // Cleaning supplies (5 items)
            [
                'sku' => 'CLN-001',
                'name' => 'Floor Cleaner (1L)',
                'category' => 'cleaning',
                'description' => 'Multi-surface floor cleaning solution',
                'quantity_on_hand' => 50,
                'reorder_level' => 15,
                'average_cost' => 85.00,
                'unit_of_measure' => 'bottle',
                'is_active' => true,
            ],
            [
                'sku' => 'CLN-002',
                'name' => 'Disinfectant Spray (500ml)',
                'category' => 'cleaning',
                'description' => 'Antibacterial surface disinfectant',
                'quantity_on_hand' => 75,
                'reorder_level' => 20,
                'average_cost' => 95.00,
                'unit_of_measure' => 'bottle',
                'is_active' => true,
            ],
            [
                'sku' => 'CLN-003',
                'name' => 'Toilet Bowl Cleaner',
                'category' => 'cleaning',
                'description' => 'Heavy-duty toilet cleaning gel',
                'quantity_on_hand' => 40,
                'reorder_level' => 10,
                'average_cost' => 65.00,
                'unit_of_measure' => 'bottle',
                'is_active' => true,
            ],
            [
                'sku' => 'CLN-004',
                'name' => 'Glass Cleaner (750ml)',
                'category' => 'cleaning',
                'description' => 'Streak-free glass and mirror cleaner',
                'quantity_on_hand' => 30,
                'reorder_level' => 10,
                'average_cost' => 75.00,
                'unit_of_measure' => 'bottle',
                'is_active' => true,
            ],
            [
                'sku' => 'CLN-005',
                'name' => 'Laundry Detergent (2kg)',
                'category' => 'cleaning',
                'description' => 'Powder laundry detergent for linens',
                'quantity_on_hand' => 25,
                'reorder_level' => 8,
                'average_cost' => 250.00,
                'unit_of_measure' => 'pack',
                'is_active' => true,
            ],

            // Kitchen items (5 items)
            [
                'sku' => 'KTC-001',
                'name' => 'Cooking Oil (1L)',
                'category' => 'kitchen',
                'description' => 'Vegetable cooking oil for kitchen use',
                'quantity_on_hand' => 35,
                'reorder_level' => 12,
                'average_cost' => 120.00,
                'unit_of_measure' => 'bottle',
                'is_active' => true,
            ],
            [
                'sku' => 'KTC-002',
                'name' => 'Dish Soap (500ml)',
                'category' => 'kitchen',
                'description' => 'Dishwashing liquid soap',
                'quantity_on_hand' => 60,
                'reorder_level' => 15,
                'average_cost' => 45.00,
                'unit_of_measure' => 'bottle',
                'is_active' => true,
            ],
            [
                'sku' => 'KTC-003',
                'name' => 'Aluminum Foil Roll',
                'category' => 'kitchen',
                'description' => 'Heavy-duty aluminum foil for cooking',
                'quantity_on_hand' => 20,
                'reorder_level' => 5,
                'average_cost' => 180.00,
                'unit_of_measure' => 'roll',
                'is_active' => true,
            ],
            [
                'sku' => 'KTC-004',
                'name' => 'Plastic Food Container Set',
                'category' => 'kitchen',
                'description' => 'Set of 5 food storage containers',
                'quantity_on_hand' => 15,
                'reorder_level' => 5,
                'average_cost' => 350.00,
                'unit_of_measure' => 'set',
                'is_active' => true,
            ],
            [
                'sku' => 'KTC-005',
                'name' => 'Kitchen Sponge (Pack of 5)',
                'category' => 'kitchen',
                'description' => 'Scrubbing sponges for dishes',
                'quantity_on_hand' => 40,
                'reorder_level' => 10,
                'average_cost' => 55.00,
                'unit_of_measure' => 'pack',
                'is_active' => true,
            ],

            // Amenity items (5 items)
            [
                'sku' => 'AMN-001',
                'name' => 'Bath Towel (White)',
                'category' => 'amenity',
                'description' => 'Large cotton bath towel',
                'quantity_on_hand' => 100,
                'reorder_level' => 30,
                'average_cost' => 150.00,
                'unit_of_measure' => 'piece',
                'is_active' => true,
            ],
            [
                'sku' => 'AMN-002',
                'name' => 'Hand Towel (White)',
                'category' => 'amenity',
                'description' => 'Medium cotton hand towel',
                'quantity_on_hand' => 120,
                'reorder_level' => 40,
                'average_cost' => 75.00,
                'unit_of_measure' => 'piece',
                'is_active' => true,
            ],
            [
                'sku' => 'AMN-003',
                'name' => 'Shampoo Sachet',
                'category' => 'amenity',
                'description' => 'Single-use shampoo packet',
                'quantity_on_hand' => 300,
                'reorder_level' => 100,
                'average_cost' => 8.00,
                'unit_of_measure' => 'piece',
                'is_active' => true,
            ],
            [
                'sku' => 'AMN-004',
                'name' => 'Soap Bar',
                'category' => 'amenity',
                'description' => 'Individual wrapped bath soap',
                'quantity_on_hand' => 250,
                'reorder_level' => 80,
                'average_cost' => 12.00,
                'unit_of_measure' => 'piece',
                'is_active' => true,
            ],
            [
                'sku' => 'AMN-005',
                'name' => 'Tissue Box (200 pulls)',
                'category' => 'amenity',
                'description' => 'Facial tissue box for guest rooms',
                'quantity_on_hand' => 80,
                'reorder_level' => 25,
                'average_cost' => 45.00,
                'unit_of_measure' => 'box',
                'is_active' => true,
            ],

            // Rental items (10 items) - Typical resort rentable equipment
            [
                'sku' => 'RNT-001',
                'name' => 'Kayak (Single)',
                'category' => 'rental_item',
                'description' => 'Single-person kayak for water activities',
                'quantity_on_hand' => 8,
                'reorder_level' => 2,
                'average_cost' => 12000.00,
                'unit_of_measure' => 'unit',
                'is_active' => true,
            ],
            [
                'sku' => 'RNT-002',
                'name' => 'Life Jacket (Adult)',
                'category' => 'rental_item',
                'description' => 'Adult-size life jacket for water safety',
                'quantity_on_hand' => 50,
                'reorder_level' => 15,
                'average_cost' => 800.00,
                'unit_of_measure' => 'piece',
                'is_active' => true,
            ],
            [
                'sku' => 'RNT-003',
                'name' => 'Snorkeling Set',
                'category' => 'rental_item',
                'description' => 'Mask, snorkel, and fins set',
                'quantity_on_hand' => 30,
                'reorder_level' => 10,
                'average_cost' => 1500.00,
                'unit_of_measure' => 'set',
                'is_active' => true,
            ],
            [
                'sku' => 'RNT-004',
                'name' => 'Beach Umbrella',
                'category' => 'rental_item',
                'description' => 'Large beach umbrella with stand',
                'quantity_on_hand' => 20,
                'reorder_level' => 5,
                'average_cost' => 2500.00,
                'unit_of_measure' => 'unit',
                'is_active' => true,
            ],
            [
                'sku' => 'RNT-005',
                'name' => 'Beach Chair',
                'category' => 'rental_item',
                'description' => 'Foldable beach lounge chair',
                'quantity_on_hand' => 40,
                'reorder_level' => 10,
                'average_cost' => 1200.00,
                'unit_of_measure' => 'unit',
                'is_active' => true,
            ],
            [
                'sku' => 'RNT-006',
                'name' => 'Inflatable Float (Large)',
                'category' => 'rental_item',
                'description' => 'Large inflatable float for pool/sea',
                'quantity_on_hand' => 25,
                'reorder_level' => 8,
                'average_cost' => 600.00,
                'unit_of_measure' => 'piece',
                'is_active' => true,
            ],
            [
                'sku' => 'RNT-007',
                'name' => 'Volleyball Net Set',
                'category' => 'rental_item',
                'description' => 'Beach volleyball net with poles',
                'quantity_on_hand' => 5,
                'reorder_level' => 2,
                'average_cost' => 3500.00,
                'unit_of_measure' => 'set',
                'is_active' => true,
            ],
            [
                'sku' => 'RNT-008',
                'name' => 'Cooler Box (48L)',
                'category' => 'rental_item',
                'description' => 'Insulated cooler box for beverages',
                'quantity_on_hand' => 15,
                'reorder_level' => 5,
                'average_cost' => 1800.00,
                'unit_of_measure' => 'unit',
                'is_active' => true,
            ],
            [
                'sku' => 'RNT-009',
                'name' => 'Paddleboard',
                'category' => 'rental_item',
                'description' => 'Stand-up paddleboard with paddle',
                'quantity_on_hand' => 6,
                'reorder_level' => 2,
                'average_cost' => 15000.00,
                'unit_of_measure' => 'unit',
                'is_active' => true,
            ],
            [
                'sku' => 'RNT-010',
                'name' => 'Beach Ball',
                'category' => 'rental_item',
                'description' => 'Inflatable beach ball for games',
                'quantity_on_hand' => 35,
                'reorder_level' => 10,
                'average_cost' => 150.00,
                'unit_of_measure' => 'piece',
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            InventoryItem::updateOrCreate(
                ['sku' => $item['sku']],
                $item
            );
        }

        $this->command->info('Successfully created 25 inventory items:');
        $this->command->info('- 5 Cleaning supplies (CLN-001 to CLN-005)');
        $this->command->info('- 5 Kitchen items (KTC-001 to KTC-005)');
        $this->command->info('- 5 Amenity items (AMN-001 to AMN-005)');
        $this->command->info('- 10 Rental items (RNT-001 to RNT-010)');
    }
}
