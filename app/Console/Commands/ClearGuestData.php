<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Guest;
use App\Models\Booking;
use App\Models\Payment;

class ClearGuestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guests:clear {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all guest, booking, and payment data from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Count records before deletion
        $guestCount = Guest::count();
        $bookingCount = Booking::count();
        $paymentCount = Payment::count();

        $this->info("Current records in database:");
        $this->info("- Guests: {$guestCount}");
        $this->info("- Bookings: {$bookingCount}");
        $this->info("- Payments: {$paymentCount}");
        $this->newLine();

        if ($guestCount === 0 && $bookingCount === 0 && $paymentCount === 0) {
            $this->info('No data to clear. Database is already empty.');
            return 0;
        }

        // Ask for confirmation unless --force is used
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to delete ALL guests, bookings, and payments? This action cannot be undone!', false)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Clearing data...');

        try {
            DB::beginTransaction();

            // Delete in order: payments -> bookings -> guests
            $this->info('Deleting payments...');
            Payment::truncate();
            $this->info('✓ Payments deleted');

            $this->info('Deleting bookings...');
            Booking::truncate();
            $this->info('✓ Bookings deleted');

            $this->info('Deleting guests...');
            Guest::truncate();
            $this->info('✓ Guests deleted');

            DB::commit();

            $this->newLine();
            $this->info('✓ All guest data cleared successfully!');
            
            // Verify deletion
            $this->newLine();
            $this->info('Verification:');
            $this->info('- Guests remaining: ' . Guest::count());
            $this->info('- Bookings remaining: ' . Booking::count());
            $this->info('- Payments remaining: ' . Payment::count());

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error clearing data: ' . $e->getMessage());
            return 1;
        }
    }
}
