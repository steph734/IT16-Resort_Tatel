<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Amenity;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultAmenities = [
            ['name' => 'Casa Keona (main house)', 'display_order' => 1],
            ['name' => '2 Airconditioned Rooms', 'display_order' => 2],
            ['name' => '4 Indian Rooms (Non-Air)', 'display_order' => 3],
            ['name' => 'Receiving Area', 'display_order' => 4],
            ['name' => 'Dining Area', 'display_order' => 5],
            ['name' => 'Dining Room with Minibar', 'display_order' => 6],
            ['name' => 'Spacious Cozy Porch', 'display_order' => 7],
            ['name' => 'Overview Deck', 'display_order' => 8],
            ['name' => 'Swing Garden', 'display_order' => 9],
            ['name' => '2 Bathrooms (Indoor and Outdoor)', 'display_order' => 10],
            ['name' => '2 Public Bathroom', 'display_order' => 11],
            ['name' => '3 Open Shower', 'display_order' => 12],
            ['name' => '2 Kitchen (Indoor and Outdoor)', 'display_order' => 13],
            ['name' => '1 Dirty Kitchen', 'display_order' => 14],
            ['name' => '2 Cabana', 'display_order' => 15],
        ];

        foreach ($defaultAmenities as $index => $amenityData) {
            Amenity::updateOrCreate(
                ['name' => $amenityData['name']],
                [
                    'is_default' => true,
                    'display_order' => $amenityData['display_order']
                ]
            );
        }
    }
}
