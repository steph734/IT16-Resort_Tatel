<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIXING PAYMENT ID REUSE ISSUE ===\n\n";

DB::beginTransaction();

try {
    // Find the payment that reused PY188
    $py188 = DB::table('payments')->where('PaymentID', 'PY188')->first();
    
    if (!$py188) {
        echo "❌ PY188 not found in database\n";
        DB::rollBack();
        exit(1);
    }
    
    echo "Found PY188:\n";
    echo "  BookingID: {$py188->BookingID}\n";
    echo "  Amount: ₱" . number_format($py188->Amount, 2) . "\n";
    echo "  Is Voided: " . ($py188->is_voided ? 'YES' : 'NO') . "\n";
    echo "  Created: {$py188->created_at}\n\n";
    
    // Check if this is the voided one or the new one
    if ($py188->is_voided) {
        echo "✅ PY188 is already voided - no duplicate issue\n";
        DB::rollBack();
        exit(0);
    }
    
    // This is the new payment that reused PY188
    echo "⚠️  PY188 is active (the new payment that reused the ID)\n";
    echo "We need to rename this to PY189\n\n";
    
    // Check if PY189 already exists
    $py189Exists = DB::table('payments')->where('PaymentID', 'PY189')->exists();
    
    if ($py189Exists) {
        echo "❌ PY189 already exists! Cannot rename.\n";
        echo "Manual intervention needed.\n";
        DB::rollBack();
        exit(1);
    }
    
    echo "Step 1: Update payment PY188 → PY189\n";
    DB::table('payments')
        ->where('PaymentID', 'PY188')
        ->where('is_voided', false)
        ->update(['PaymentID' => 'PY189']);
    echo "  ✅ Payment renamed to PY189\n\n";
    
    echo "Step 2: Update transactions reference_id PY188 → PY189\n";
    $updatedTx = DB::table('transactions')
        ->where('reference_id', 'PY188')
        ->where('is_voided', false)
        ->update(['reference_id' => 'PY189']);
    echo "  ✅ Updated {$updatedTx} transaction(s)\n\n";
    
    DB::commit();
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ SUCCESS!\n\n";
    echo "Result:\n";
    echo "  • Active payment: PY189 (renamed from duplicate PY188)\n";
    echo "  • Voided payment: PY188 (if it exists)\n";
    echo "  • Next payment will be: PY190\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
