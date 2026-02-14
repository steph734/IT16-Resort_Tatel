<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Package;
use App\Models\Amenity;

class PackageSeeder extends Seeder
{
    public function run()
    {
        DB::table('packages')->upsert([
            [
                'PackageID' => 'PK001',
                'Name' => 'Package A',
                'Description' => "Full Resort Access\nUp to 30 guests\nCasa Keona (main house)\n2 Airconditioned rooms\nDining room with minibar\nSpacious Cozy Porch\n4 Indian Rooms (Non-AC)",
                'Price' => 15000.00,
                'max_guests' => 30,
                'excess_rate' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'PackageID' => 'PK002',
                'Name' => 'Package B',
                'Description' => "Whole resort amenities (except CASA KEONA)\nUp to 20 persons\n4 Indian Rooms (Non-Air)\nOverview Deck\nSwing Garden\nDining Area\nReceiving Area\n2 Cabana",
                'Price' => 12000.00,
                'max_guests' => 20,
                'excess_rate' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['PackageID']);

        // Attach amenities to packages via pivot table
        // Define mapping of package IDs to amenity names (must match AmenitySeeder names)
        $mapping = [
            'PK001' => [
                'Casa Keona (main house)',
                '2 Airconditioned Rooms',
                'Dining Room with Minibar',
                'Spacious Cozy Porch',
                '4 Indian Rooms (Non-Air)',
            ],
            'PK002' => [
                '4 Indian Rooms (Non-Air)',
                'Overview Deck',
                'Swing Garden',
                'Dining Area',
                'Receiving Area',
                '2 Cabana',
            ],
        ];

        foreach ($mapping as $packageId => $amenityNames) {
            $package = Package::find($packageId);
            if (!$package) {
                continue;
            }

            // Resolve amenity IDs and sync pivot
            $amenityIds = Amenity::whereIn('name', $amenityNames)->pluck('id')->toArray();
            if (!empty($amenityIds)) {
                $package->amenities()->sync($amenityIds);
            }
        }
    }
}
