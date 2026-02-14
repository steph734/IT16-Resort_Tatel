<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rental;
use App\Models\RentalFee;
use App\Models\Booking;
use App\Models\RentalItem;
use App\Models\User;
use Carbon\Carbon;

class RentalTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 1-5 rental transactions per booking for 70% of completed bookings
     * - Diversified return statuses: Majority "Good", few "Damage", few "Lost"
     * - Damage/Lost fees are >= item cost
     * - All rentals are returned since bookings are completed (past)
     */
    public function run(): void
    {
        // Get completed bookings from past (these should have rental activity)
        $bookings = Booking::where('BookingStatus', 'Completed')
            ->with(['guest', 'package'])
            ->get();

        if ($bookings->isEmpty()) {
            $this->command->error('No completed bookings found. Please run PastBookingsSeeder first.');
            return;
        }

        // Get available rental items
        $rentalItems = RentalItem::where('status', 'Active')->get();
        if ($rentalItems->isEmpty()) {
            $this->command->error('No rental items found. Please run RentalItemSeeder first.');
            return;
        }

        // Get admin users who can issue/return rentals
        $users = User::whereIn('role', ['admin', 'staff'])->where('status', 'active')->pluck('user_id')->toArray();
        if (empty($users)) {
            $this->command->error('No active admin/staff users found. Please run AdminUserSeeder first.');
            return;
        }

        // Select 70% of bookings to have rentals
        $bookingsWithRentals = $bookings->random((int) ($bookings->count() * 0.7));

        $totalRentals = 0;
        $goodCondition = 0;
        $damagedItems = 0;
        $lostItems = 0;

        foreach ($bookingsWithRentals as $booking) {
            // Random number of rental transactions (1-5)
            $rentalCount = rand(1, 5);

            // Get random rental items for this booking
            $selectedItems = $rentalItems->random(min($rentalCount, $rentalItems->count()));

            foreach ($selectedItems as $rentalItem) {
                $quantity = rand(1, 3); // 1-3 items per rental
                
                // Issue rental on check-in date or day after
                $issuedAt = Carbon::parse($booking->CheckInDate)->addHours(rand(2, 12));
                
                // Return rental on check-out date or day before
                $returnedAt = Carbon::parse($booking->CheckOutDate)->subHours(rand(1, 6));

                // Determine return condition
                // 85% Good, 10% Damaged, 5% Lost
                $rand = rand(1, 100);
                if ($rand <= 85) {
                    $condition = 'Good';
                    $returnedQuantity = $quantity;
                } elseif ($rand <= 95) {
                    $condition = 'Damaged';
                    $returnedQuantity = $quantity; // All returned but damaged
                } else {
                    $condition = 'Lost';
                    $returnedQuantity = rand(0, $quantity - 1); // Some or all lost
                }

                // Create rental record
                $rental = Rental::create([
                    'BookingID' => $booking->BookingID,
                    'rental_item_id' => $rentalItem->id,
                    'quantity' => $quantity,
                    'rate_snapshot' => $rentalItem->rate,
                    'rate_type_snapshot' => $rentalItem->rate_type,
                    'status' => 'Returned',
                    'returned_quantity' => $returnedQuantity,
                    'condition' => $condition,
                    'notes' => null,
                    'damage_description' => $condition === 'Damaged' ? 'Item shows signs of wear and tear' : null,
                    'issued_at' => $issuedAt,
                    'returned_at' => $returnedAt,
                    'issued_by' => $users[array_rand($users)],
                    'returned_by' => $users[array_rand($users)],
                    'is_paid' => true,
                ]);

                $totalRentals++;

                // Add damage/loss fees if applicable
                if ($condition === 'Damaged') {
                    $damagedItems++;
                    // Damage fee should be >= item cost
                    $itemCost = $rentalItem->inventoryItem->average_cost ?? 500;
                    $damageFee = $itemCost * rand(100, 150) / 100; // 100-150% of item cost

                    RentalFee::create([
                        'rental_id' => $rental->id,
                        'type' => 'Damage',
                        'amount' => $damageFee,
                        'reason' => 'Item returned with damage - ' . ($rentalItem->inventoryItem->name ?? 'rental item'),
                        'photo_path' => null,
                        'added_by' => $users[array_rand($users)],
                    ]);
                } elseif ($condition === 'Lost') {
                    $lostItems++;
                    $lostCount = $quantity - $returnedQuantity;
                    // Lost fee should be >= item cost per lost item
                    $itemCost = $rentalItem->inventoryItem->average_cost ?? 500;
                    $lostFee = $itemCost * $lostCount * rand(120, 200) / 100; // 120-200% of item cost

                    RentalFee::create([
                        'rental_id' => $rental->id,
                        'type' => 'Loss',
                        'amount' => $lostFee,
                        'reason' => "Lost {$lostCount} unit(s) - " . ($rentalItem->inventoryItem->name ?? 'rental item'),
                        'photo_path' => null,
                        'added_by' => $users[array_rand($users)],
                    ]);
                } else {
                    $goodCondition++;
                }
            }
        }

        $this->command->info("Successfully created {$totalRentals} rental transactions:");
        $this->command->info("- Applied to " . $bookingsWithRentals->count() . " bookings (70% of completed bookings)");
        $this->command->info("- 1-5 rental items per booking");
        $this->command->info("- Return conditions:");
        $this->command->info("  • Good: {$goodCondition} rentals (" . round($goodCondition / $totalRentals * 100, 1) . "%)");
        $this->command->info("  • Damage: {$damagedItems} rentals (" . round($damagedItems / $totalRentals * 100, 1) . "%)");
        $this->command->info("  • Lost: {$lostItems} rentals (" . round($lostItems / $totalRentals * 100, 1) . "%)");
        $this->command->info("- All rentals marked as Returned and Paid");
    }
}
