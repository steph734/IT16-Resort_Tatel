<?php

// Load the Laravel application
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Update existing users to have status if they don't
    $updated = \App\Models\User::whereIn('role', ['admin', 'staff'])
                               ->whereNull('status')
                               ->update(['status' => 'active']);
    
    if ($updated > 0) {
        echo "Updated {$updated} users to have 'active' status.\n";
    }
    
    // Create a test disabled staff account if it doesn't exist
    $disabledStaff = \App\Models\User::where('email', 'disabled.staff@jara.com')->first();
    if (!$disabledStaff) {
        \App\Models\User::create([
            'name' => 'Disabled Staff',
            'email' => 'disabled.staff@jara.com',
            // Use 12-character sample password to match new policy
            'password' => \Illuminate\Support\Facades\Hash::make('123456789012'),
            'role' => 'staff',
            'status' => 'disabled',
            'email_verified_at' => now(),
        ]);
        echo "Created test disabled staff account: disabled.staff@jara.com\n";
    } else {
        echo "Disabled staff account already exists.\n";
    }
    
    // Display all admin/staff accounts
    echo "\n=== Current Admin/Staff Accounts ===\n";
    $users = \App\Models\User::whereIn('role', ['admin', 'staff'])->get();
    foreach ($users as $user) {
        echo sprintf(
            "ID: %s | Name: %s | Email: %s | Role: %s | Status: %s\n",
            $user->user_id,
            $user->name,
            $user->email,
            $user->role,
            $user->status
        );
    }
    
    echo "\n=== Test Credentials ===\n";
    echo "Admin Login: admin@jara.com / 123456789012\n";
    echo "Staff Login (Active): kaye@jara.com / 123456789012\n";
    echo "Staff Login (Disabled): disabled.staff@jara.com / 123456789012\n";
    
    echo "\n✓ Database setup complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}