<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Guest;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Package;
use Carbon\Carbon;

class PastBookingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates past bookings (March-November 2025) for demo purposes with realistic distribution
     * - Varying booking volumes per month (5-30 bookings)
     * - Mix of Completed and Cancelled bookings
     * - Filipino names from Mindanao region
     * - Diverse stay lengths (1-5 days)
     * - Diverse guest counts with some excess guests and children
     */
    public function run(): void
    {
        // Get available packages
        $packages = Package::all();
        if ($packages->isEmpty()) {
            $this->command->error('No packages found. Please run PackageSeeder first.');
            return;
        }

        // Filipino names (Mindanao-based)
        $firstNames = [
            'Maria', 'Jose', 'Juan', 'Antonio', 'Pedro', 'Francisco', 'Ramon', 'Luis', 'Miguel', 'Carlos',
            'Rosa', 'Ana', 'Elena', 'Sofia', 'Carmen', 'Isabel', 'Teresa', 'Lucia', 'Cristina', 'Angela',
            'Ricardo', 'Manuel', 'Fernando', 'Roberto', 'Eduardo', 'Alberto', 'Jorge', 'Rafael', 'Gabriel', 'Daniel',
            'Patricia', 'Linda', 'Michelle', 'Jennifer', 'Jessica', 'Sharon', 'Melissa', 'Nicole', 'Kathleen', 'Nancy'
        ];

        $lastNames = [
            'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres', 'Gonzales', 'Lopez',
            'Flores', 'Rivera', 'Ramos', 'Castillo', 'Aquino', 'Villanueva', 'Santiago', 'Fernandez', 'Hernandez', 'Morales',
            'Dela Cruz', 'Soriano', 'Castro', 'Mercado', 'Gutierrez', 'Tan', 'Lim', 'Go', 'Ong', 'Chua',
            'Abdullah', 'Ibrahim', 'Mohammad', 'Hassan', 'Ali', 'Omar', 'Khalid', 'Rashid', 'Malik', 'Ahmed'
        ];

        $mindanaoCities = [
            'Davao City', 'Cagayan de Oro City', 'General Santos City', 'Zamboanga City', 'Butuan City',
            'Iligan City', 'Cotabato City', 'Tagum City', 'Koronadal City', 'Malaybalay City',
            'Kidapawan City', 'Tandag City', 'Dapitan City', 'Dipolog City', 'Ozamiz City',
            'Pagadian City', 'Bislig City', 'Marawi City', 'Surigao City', 'Tacurong City',
            'Oroquieta City', 'Valencia City', 'Mati City', 'Gingoog City', 'Samal City'
        ];

        // Define date ranges and booking counts per month
        // Total: 88 bookings (77 completed, 11 cancelled) distributed March-November 2025
        $monthsWithCounts = [
            ['start' => '2025-03-01', 'end' => '2025-03-31', 'count' => 6],   // March - Low season start
            ['start' => '2025-04-01', 'end' => '2025-04-30', 'count' => 9],   // April - Growing
            ['start' => '2025-05-01', 'end' => '2025-05-31', 'count' => 11],  // May - Steady growth
            ['start' => '2025-06-01', 'end' => '2025-06-30', 'count' => 13],  // June - Summer season
            ['start' => '2025-07-01', 'end' => '2025-07-31', 'count' => 18],  // July - Peak season
            ['start' => '2025-08-01', 'end' => '2025-08-31', 'count' => 14],  // August - Still high
            ['start' => '2025-09-01', 'end' => '2025-09-30', 'count' => 9],   // September - Declining
            ['start' => '2025-11-01', 'end' => '2025-11-30', 'count' => 8],   // November - Holiday season
        ];

        // Payment methods
        $paymentMethods = ['Cash', 'GCash', 'BankTransfer', 'PayMaya'];

        // Calculate total cancellations across all months
        $totalBookings = array_sum(array_column($monthsWithCounts, 'count'));
        $totalCancellations = 11; // Fixed 11 cancellations total
        $cancellationsRemaining = $totalCancellations;
        
        // Process each month
        foreach ($monthsWithCounts as $index => $monthData) {
            $bookingCount = $monthData['count'];
            
            // Distribute 10 cancellations across months (more in later months)
            if ($cancellationsRemaining > 0 && $index >= 2) { // Start cancellations from May onwards
                $cancelledCount = min($cancellationsRemaining, max(1, (int)($bookingCount * 0.15)));
                $cancellationsRemaining -= $cancelledCount;
            } else {
                $cancelledCount = 0;
            }
            
            $completedCount = $bookingCount - $cancelledCount;
            
            // Create bookings for this month
            $monthBookings = [];
            
            for ($i = 0; $i < $completedCount; $i++) {
                $monthBookings[] = [
                    'status' => 'Completed',
                    'payment_status' => 'Fully Paid'
                ];
            }
            
            // Cancelled bookings with varied payment statuses
            $cancelledPaymentStatuses = ['Downpayment', 'Partial', 'Fully Paid'];
            for ($i = 0; $i < $cancelledCount; $i++) {
                $monthBookings[] = [
                    'status' => 'Cancelled',
                    'payment_status' => $cancelledPaymentStatuses[array_rand($cancelledPaymentStatuses)]
                ];
            }
            
            // Shuffle to mix completed and cancelled
            shuffle($monthBookings);

            foreach ($monthBookings as $bookingData) {
                // Generate random check-in date within the month
                $startDate = Carbon::parse($monthData['start']);
                $endDate = Carbon::parse($monthData['end']);
                $daysInMonth = $startDate->diffInDays($endDate);
                $randomDay = rand(1, max(1, $daysInMonth - 7)); // Leave room for checkout
                
                $checkInDate = $startDate->copy()->addDays($randomDay);
                
                // Random stay length (1-5 days)
                $stayLength = rand(1, 5);
                $checkOutDate = $checkInDate->copy()->addDays($stayLength);
                
                // Booking date (2-4 weeks before check-in)
                $bookingDate = $checkInDate->copy()->subDays(rand(14, 28));

            // Create Guest
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $city = $mindanaoCities[array_rand($mindanaoCities)];
            
            // Create guest - let model generate GuestID
            $guest = Guest::create([
                'FName' => $firstName,
                'MName' => null,
                'LName' => $lastName,
                'Email' => strtolower($firstName . '.' . str_replace(' ', '', $lastName) . '@email.com'),
                'Phone' => '09' . rand(10000000, 99999999),
                'Address' => rand(1, 999) . ' ' . ['Rizal St.', 'Bonifacio Ave.', 'Luna St.', 'Mabini St.', 'Aguinaldo Rd.'][array_rand(['Rizal St.', 'Bonifacio Ave.', 'Luna St.', 'Mabini St.', 'Aguinaldo Rd.'])] . ', ' . $city,
                'Contactable' => true,
            ]);

            // Random package
            $package = $packages->random();
            $packagePrice = $package->Price;
            $maxGuests = $package->max_guests ?? 30;
            
            // Random guest count (sometimes with excess)
            $hasExcess = rand(1, 100) <= 30; // 30% chance of excess guests
            if ($hasExcess) {
                $numAdults = rand($maxGuests + 1, $maxGuests + 5); // 1-5 excess guests
            } else {
                $numAdults = rand(5, min($maxGuests, 20));
            }
            
            // Random children (30% chance)
            $hasChildren = rand(1, 100) <= 30;
            $numChildren = $hasChildren ? rand(1, 4) : 0;
            $totalPax = $numAdults + $numChildren;

            // Calculate amounts
            $baseAmount = $packagePrice * $stayLength;
            $excessGuests = max(0, $numAdults - $maxGuests);
            $excessFee = $excessGuests * 100; // ₱100 per excess guest
            $totalAmount = $baseAmount + $excessFee;

            // Create Booking - let model generate BookingID
            $booking = Booking::create([
                'GuestID' => $guest->GuestID,
                'PackageID' => $package->PackageID,
                'BookingDate' => $bookingDate->format('Y-m-d'),
                'CheckInDate' => $checkInDate->format('Y-m-d'),
                'CheckOutDate' => $checkOutDate->format('Y-m-d'),
                'BookingStatus' => $bookingData['status'],
                'Pax' => $totalPax,
                'NumOfChild' => $numChildren,
                'NumOfAdults' => $numAdults,
            ]);

            // Create Payment(s) based on payment status
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
            
            if ($bookingData['payment_status'] === 'Fully Paid') {
                // Single full payment
                Payment::create([
                    'BookingID' => $booking->BookingID,
                    'Amount' => $totalAmount,
                    'TotalAmount' => $totalAmount,
                    'PaymentMethod' => $paymentMethod,
                    'PaymentDate' => $checkInDate->copy()->subDays(rand(1, 3))->format('Y-m-d'),
                    'PaymentStatus' => 'Verified',
                    'PaymentPurpose' => 'Full Payment',
                    'ReferenceNumber' => $paymentMethod !== 'Cash' ? 'REF' . rand(100000, 999999) : null,
                ]);
            } elseif ($bookingData['payment_status'] === 'Downpayment') {
                // Single downpayment (₱1,000 or 50%)
                $daysUntilCheckIn = $bookingDate->diffInDays($checkInDate);
                $downpayment = $daysUntilCheckIn >= 14 ? 1000 : ($totalAmount * 0.5);
                
                Payment::create([
                    'BookingID' => $booking->BookingID,
                    'Amount' => $downpayment,
                    'TotalAmount' => $totalAmount,
                    'PaymentMethod' => $paymentMethod,
                    'PaymentDate' => $bookingDate->copy()->addDays(1)->format('Y-m-d'),
                    'PaymentStatus' => 'Verified',
                    'PaymentPurpose' => 'Downpayment',
                    'ReferenceNumber' => $paymentMethod !== 'Cash' ? 'REF' . rand(100000, 999999) : null,
                ]);
            } else { // Partial
                // Two payments: downpayment + additional partial
                $daysUntilCheckIn = $bookingDate->diffInDays($checkInDate);
                $downpayment = $daysUntilCheckIn >= 14 ? 1000 : ($totalAmount * 0.5);
                $additionalPayment = ($totalAmount - $downpayment) * rand(30, 70) / 100; // 30-70% of remaining
                
                Payment::create([
                    'BookingID' => $booking->BookingID,
                    'Amount' => $downpayment,
                    'TotalAmount' => $totalAmount,
                    'PaymentMethod' => $paymentMethod,
                    'PaymentDate' => $bookingDate->copy()->addDays(1)->format('Y-m-d'),
                    'PaymentStatus' => 'Verified',
                    'PaymentPurpose' => 'Downpayment',
                    'ReferenceNumber' => $paymentMethod !== 'Cash' ? 'REF' . rand(100000, 999999) : null,
                ]);
                
                $additionalMethod = $paymentMethods[array_rand($paymentMethods)];
                Payment::create([
                    'BookingID' => $booking->BookingID,
                    'Amount' => $additionalPayment,
                    'TotalAmount' => $totalAmount,
                    'PaymentMethod' => $additionalMethod,
                    'PaymentDate' => $checkInDate->copy()->subDays(rand(5, 10))->format('Y-m-d'),
                    'PaymentStatus' => 'Verified',
                    'PaymentPurpose' => 'Additional Payment',
                    'ReferenceNumber' => $additionalMethod !== 'Cash' ? 'REF' . rand(100000, 999999) : null,
                ]);
            }
            }
        }

        $totalBookings = array_sum(array_column($monthsWithCounts, 'count'));
        $completedBookings = $totalBookings - $totalCancellations;
        $this->command->info("Successfully created {$totalBookings} past bookings:");
        $this->command->info('- Distribution: March(6) → Apr(9) → May(11) → Jun(13) → Jul(18) → Aug(14) → Sep(9) → Nov(8)');
        $this->command->info("- {$completedBookings} Completed bookings (" . round($completedBookings / $totalBookings * 100, 1) . "%)");
        $this->command->info("- {$totalCancellations} Cancelled bookings (" . round($totalCancellations / $totalBookings * 100, 1) . "%)");
        $this->command->info('- Date range: March - November 2025');
        $this->command->info('- Diverse stay lengths: 1-5 days');
        $this->command->info('- Filipino names from Mindanao region');
        $this->command->info('- Some bookings with excess guests and children');
    }
}
