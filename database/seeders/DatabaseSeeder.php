<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Call seeders in order
        $this->call([
            AdminUserSeeder::class,              // 1. Create admin/staff users first
            AmenitySeeder::class,                // 2. Create amenities
            PackageSeeder::class,                // 3. Create packages for bookings (after amenities so pivot can be synced)
            InventoryItemSeeder::class,          // 4. Create inventory items (25 items across 4 categories)
            RentalItemSeeder::class,             // 5. Create rental item configurations (10 rental items)
            PurchaseEntrySeeder::class,          // 6. Create purchase entries (5 purchases, updates inventory)
            PastBookingsSeeder::class,           // 7. Create past bookings (88 bookings, March-November 2025)
            RentalTransactionSeeder::class,      // 8. Create rental transactions linked to bookings
            TransactionBackfillSeeder::class,    // 9. Backfill centralized transactions table
        ]);
    }
}
