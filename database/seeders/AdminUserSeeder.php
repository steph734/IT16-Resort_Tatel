<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing users to have status
        User::whereIn('role', ['admin', 'staff'])
            ->whereNull('status')
            ->update(['status' => 'active']);

        // Check if admin user exists, if not create it
        if (!User::where('email', 'admin@jara.com')->exists()) {
            User::create([
                'name' => 'Jara',
                'email' => 'admin@jara.com',
                    // Use 12-character sample password to match new policy
                    'password' => Hash::make('123456789012'),
                'role' => Role::Admin->value,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        // Create an owner account if it doesn't exist
        if (!User::where('email', 'owner@jara.com')->exists()) {
            User::create([
                'name' => 'Owner',
                'email' => 'owner@jara.com',
                    'password' => Hash::make('123456789012'),
                'role' => Role::Owner->value,
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        // Create additional staff if they don't exist
        $staffUsers = [
            ['name' => 'Kaye', 'email' => 'kaye@jara.com'],
            ['name' => 'John David', 'email' => 'johndavid@jara.com'],
            ['name' => 'Stephen', 'email' => 'stephen@jara.com'],
        ];

        foreach ($staffUsers as $staff) {
            if (!User::where('email', $staff['email'])->exists()) {
                User::create([
                    'name' => $staff['name'],
                    'email' => $staff['email'],
                        'password' => Hash::make('123456789012'),
                    'role' => Role::Staff->value,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);
            }
        }

        // Create one disabled user for testing
        if (!User::where('email', 'disabled@jara.com')->exists()) {
            User::create([
                'name' => 'Disabled User',
                'email' => 'disabled@jara.com',
                    'password' => Hash::make('123456789012'),
                'role' => Role::Staff->value,
                'status' => 'disabled',
                'email_verified_at' => now(),
            ]);
        }
    }
}
