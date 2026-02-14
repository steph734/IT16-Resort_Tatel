<?php

// Load the Laravel application
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check database structure
try {
    // Check if status column exists
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('users');
    echo "Users table columns: " . implode(', ', $columns) . "\n\n";

    // Get all admin and staff users
    $users = \App\Models\User::whereIn('role', ['admin', 'staff'])->get();
    echo "Found " . $users->count() . " admin/staff users:\n";
    
    foreach ($users as $user) {
        echo "ID: {$user->user_id}, Name: {$user->name}, Role: {$user->role}, Status: " . ($user->status ?? 'NULL') . "\n";
    }
    
    // Update existing users to have status if it's null
    $updated = \App\Models\User::whereIn('role', ['admin', 'staff'])
                               ->whereNull('status')
                               ->update(['status' => 'active']);
    
    if ($updated > 0) {
        echo "\nUpdated {$updated} users to have 'active' status.\n";
    }
    
    echo "\nDatabase is ready for testing!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}