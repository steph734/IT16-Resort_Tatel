<?php

// Load the Laravel application
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Check which migrations exist in the migrations table
    $existingMigrations = \Illuminate\Support\Facades\DB::table('migrations')->pluck('migration')->toArray();
    echo "Existing migrations in database:\n";
    foreach ($existingMigrations as $migration) {
        echo "- $migration\n";
    }
    
    // Migrations that should be marked as run
    $migrationsToAdd = [
        '2025_09_30_151320_create_bookings_table'
    ];
    
    foreach ($migrationsToAdd as $migration) {
        if (!in_array($migration, $existingMigrations)) {
            \Illuminate\Support\Facades\DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => 1
            ]);
            echo "Added migration: $migration\n";
        } else {
            echo "Migration already exists: $migration\n";
        }
    }
    
    echo "\nMigration status updated!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}