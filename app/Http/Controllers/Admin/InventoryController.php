<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\RentalItem;
use App\Models\PurchaseEntry;
use App\Models\PurchaseEntryItem;
use App\Models\StockMovement;
use App\Enums\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Audit_Log;

class InventoryController extends Controller
{
    // ==================== DASHBOARD ====================
    
    /**
     * Display the inventory dashboard (read-only overview).
     */
    public function index(Request $request)
    {
        // Handle date filtering
        $dateRange = $this->getDateRange($request);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
        // Calculate Purchase Amount (selected period) - using new PurchaseEntry
        $purchaseAmount = PurchaseEntry::whereBetween('purchase_date', [$startDate, $endDate])
            ->sum('total_amount');
        
        // Calculate previous period for comparison
        $daysDiff = $startDate->diffInDays($endDate);
        $prevPeriodEnd = $startDate->copy()->subDay();
        $prevPeriodStart = $prevPeriodEnd->copy()->subDays($daysDiff);
        
        $prevPurchaseAmount = PurchaseEntry::whereBetween('purchase_date', [$prevPeriodStart, $prevPeriodEnd])
            ->sum('total_amount');
        
        $purchaseChangePercent = $prevPurchaseAmount > 0 
            ? round((($purchaseAmount - $prevPurchaseAmount) / $prevPurchaseAmount) * 100, 1) 
            : ($purchaseAmount > 0 ? 100 : 0);
        
        // Count items by category
        $items = InventoryItem::all();
        $cleaningCount = $items->where('category', 'cleaning')->count();
        $kitchenCount = $items->where('category', 'kitchen')->count();
        $amenityCount = $items->where('category', 'amenity')->count();
        $rentalItemsCount = $items->where('category', 'rental_item')->count();
        
        // Calculate total inventory value
        $totalInventoryValue = $items->sum(function($item) {
            return $item->quantity_on_hand * $item->average_cost;
        });
        
        // Calculate stock turnover rate (simple approximation)
        // Turnover = (Items moved out / Average stock) * 100
        $stockMovementsOut = StockMovement::where('movement_type', 'out')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');
        $averageStock = $items->avg('quantity_on_hand') ?: 1;
        $turnoverRate = ($stockMovementsOut / $averageStock) * 100;
        
        // Stats
        $stats = [
            'total_inventory_value' => $totalInventoryValue,
            'monthly_purchases' => $purchaseAmount,
            'purchase_change_percent' => $purchaseChangePercent,
            'low_stock_count' => $items->filter(fn($item) => $item->isLowStock())->count(),
            'turnover_rate' => $turnoverRate,
            'cleaning_count' => $cleaningCount,
            'kitchen_count' => $kitchenCount,
            'amenity_count' => $amenityCount,
            'rental_items_count' => $rentalItemsCount,
            'total_items' => $items->count(),
        ];

        // Low stock items
        $lowStockItems = InventoryItem::all()
            ->filter(fn($item) => $item->isLowStock())
            ->take(10);

        // Chart data (only category breakdown, no trend)
        $chartData = [
            'categories' => $this->getStockByCategoryData(),
        ];

        // Recent activity
        $recentActivity = StockMovement::with(['inventoryItem', 'purchaseEntry'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->take(15)
            ->get();

        return view('admin.inventory.inventory-dashboard', compact('stats', 'lowStockItems', 'chartData', 'recentActivity', 'startDate', 'endDate'));
    }

    /**
     * Display the inventory list (main stock management).
     */
    public function inventoryList()
    {
        $items = InventoryItem::with([
                'stockMovements' => function($query) {
                    $query->latest()->limit(1);
                },
                'purchaseEntryItems.purchaseEntry' => function($query) {
                    $query->latest('purchase_date')->limit(1);
                }
            ])
            ->orderBy('name')
            ->get();
        
        return view('admin.inventory.inventory-list', compact('items'));
    }

    /**
     * Get stock by category data for chart.
     */
    private function getStockByCategoryData()
    {
        $items = InventoryItem::all();
        
        // Group by category (handle both enum and string values)
        $categories = $items->groupBy(function($item) {
            return is_object($item->category) ? $item->category->value : $item->category;
        });
        
        $labels = [];
        $values = [];
        
        foreach ($categories as $categoryValue => $group) {
            // Try to get enum label, fallback to raw value
            try {
                $categoryEnum = ItemCategory::from($categoryValue);
                $labels[] = $categoryEnum->label();
            } catch (\ValueError $e) {
                // Not a valid enum, use the string value as-is
                $labels[] = ucfirst(str_replace('_', ' ', $categoryValue));
            }
            $values[] = $group->count();
        }
        
        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Get purchasing trend data for chart (last 6 months).
     */
    private function getPurchasingTrendData()
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push([
                'label' => $date->format('M Y'),
                'value' => PurchaseEntry::whereYear('purchase_date', $date->year)
                    ->whereMonth('purchase_date', $date->month)
                    ->count()
            ]);
        }
        
        return [
            'labels' => $months->pluck('label')->toArray(),
            'values' => $months->pluck('value')->toArray(),
        ];
    }

    // ==================== INVENTORY ITEMS ====================

    /**
     * Get inventory items data (for AJAX/API).
     */
    public function getItems(Request $request): JsonResponse
    {
        $query = InventoryItem::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('low_stock') && $request->low_stock) {
            $query->lowStock();
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        $items = $query->with('stockMovements')->get();

        return response()->json($items);
    }

    /**
     * Store a new inventory item.
     */
    public function storeItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|string|in:cleaning,kitchen,amenity,rental_item',
            'sub_category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'quantity_on_hand' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'average_cost' => 'nullable|numeric|min:0',
            'unit_of_measure' => 'required|string|max:20',
            'sku' => 'nullable|string|max:50|unique:inventory_items,sku',
            'location' => 'nullable|string|max:100',
        ]);

        $item = InventoryItem::create($validated);

        // Audit log: inventory item created
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Create Inventory Item',
                'description' => 'Created inventory item ' . ($item->sku ?? $item->id ?? 'n/a') . ' name: ' . ($item->name ?? 'n/a'),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore logging errors
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory item created successfully',
            'item' => $item
        ]);
    }

    /**
     * Update an inventory item.
     */
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'category' => 'sometimes|required|string|in:cleaning,kitchen,amenity,rental_item',
            'sub_category' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'reorder_level' => 'sometimes|required|integer|min:0',
            'average_cost' => 'nullable|numeric|min:0',
            'unit_of_measure' => 'sometimes|required|string|max:20',
            'sku' => 'nullable|string|max:50|unique:inventory_items,sku,' . $id,
            'location' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $item->update($validated);

        // Audit log: inventory item updated
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Update Inventory Item',
                'description' => 'Updated inventory item ' . ($item->sku ?? $item->id ?? 'n/a'),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory item updated successfully',
            'item' => $item
        ]);
    }

    /**
     * Stock adjustment (manual add/subtract).
     */
   /**
 * NEW: Adjust stock directly to exact quantity + update reorder level
 * Used by the Inventory List modal (Standard Adjustment)
 */
public function adjustStock(Request $request): JsonResponse
{
    $validated = $request->validate([
        'item_id'           => 'required|exists:inventory_items,sku',
        'new_quantity'      => 'required|integer|min:0',
        'new_reorder_level' => 'required|integer|min:0',
        'reason'            => 'nullable|string',
        'admin_id'          => 'required|exists:users,user_id', // Admin verification
    ]);

    // Verify the person making the adjustment is an admin
    $admin = \App\Models\User::where('user_id', $validated['admin_id'])
        ->where('role', 'admin')
        ->first();
    
    if (!$admin) {
        return response()->json([
            'success' => false,
            'message' => 'Admin verification failed. Invalid admin ID or insufficient permissions.'
        ], 403);
    }

    DB::beginTransaction();
    try {
        $item = InventoryItem::where('sku', $validated['item_id'])->firstOrFail();
        $oldQuantity = $item->quantity_on_hand;
        $newQuantity = $validated['new_quantity'];
        $difference  = $newQuantity - $oldQuantity;

        // Only create movement if there's an actual quantity change
        if ($difference != 0) {
            // Determine the reason based on movement type
            $movementReason = $difference > 0 ? 'adjustment_in' : 'adjustment_out';
            
            StockMovement::create([
                'sku'               => $item->sku,
                'movement_type'     => $difference > 0 ? 'in' : 'out',
                'quantity'          => abs($difference),
                'reason'            => $movementReason,
                'notes'             => $validated['reason'] ?: null,
                'performed_by'      => $admin->user_id,
                'performed_at'      => now(),
            ]);
        }

        // Update the item with new values
        $item->update([
            'quantity_on_hand'  => $newQuantity,
            'reorder_level'     => $validated['new_reorder_level'],
        ]);

        DB::commit();

        // Audit log: stock adjusted
        try {
            Audit_Log::create([
                'user_id' => $admin->user_id ?? null,
                'action' => 'Adjust Stock',
                'description' => 'Adjusted stock for ' . ($item->sku ?? 'n/a') . ' from ' . $oldQuantity . ' to ' . $newQuantity . ' reason: ' . ($validated['reason'] ?? 'n/a'),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore logging errors
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock adjusted successfully',
            'item'    => [
                'sku'             => $item->sku,
                'name'            => $item->name,
                'quantity_on_hand'=> $item->quantity_on_hand,
                'reorder_level'   => $item->reorder_level,
                'is_low_stock'    => $item->isLowStock(),
            ],
            'difference' => $difference
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to adjust stock: ' . $e->getMessage()
        ], 500);
    }
}    public function stockMovements(Request $request)
    {
        $query = StockMovement::with(['inventoryItem', 'purchaseEntry', 'rental', 'performer'])
            ->orderBy('created_at', 'desc');

        if ($request->has('item_id')) {
            $query->where('inventory_item_id', $request->item_id);
        }

        if ($request->has('type')) {
            $query->where('movement_type', $request->type);
        }

        if ($request->has('reason')) {
            $query->where('reason', $request->reason);
        }

        $movements = $query->paginate(50);
        $items = InventoryItem::active()->orderBy('name')->get();

        return view('admin.inventory.stock-movements', compact('movements', 'items'));
    }

    /**
     * Get stock movements data (for AJAX/API).
     */
    public function getStockMovements(Request $request): JsonResponse
    {
        $query = StockMovement::with(['inventoryItem', 'purchaseOrder', 'purchaseEntry', 'rental', 'performer']);

        if ($request->has('item_id')) {
            $query->where('sku', $request->item_id);
        }

        if ($request->has('days')) {
            $query->recent($request->days);
        }

        $movements = $query->orderBy('created_at', 'desc')->get();

        return response()->json($movements);
    }

    /**
     * Get single stock movement details.
     */
    public function getMovementDetails($id): JsonResponse
    {
        try {
            $movement = StockMovement::with(['inventoryItem', 'performer'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'movement' => [
                    'id' => $movement->id,
                    'created_at' => $movement->created_at,
                    'item_name' => $movement->inventoryItem->name,
                    'item_sku' => $movement->inventoryItem->sku,
                    'movement_type' => $movement->movement_type,
                    'quantity' => $movement->quantity,
                    'reason' => $movement->reason,
                    'reason_display' => ucwords(str_replace('_', ' ', $movement->reason)),
                    'performed_by' => $movement->performer ? $movement->performer->name : 'System',
                    'notes' => $movement->notes,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Movement not found'
            ], 404);
        }
    }

    // ==================== PURCHASE ENTRIES (NEW SIMPLIFIED SYSTEM) ====================

    /**
     * Display list of purchase entries
     */
    public function purchases()
    {
        $purchases = PurchaseEntry::with(['items.inventoryItem', 'creator'])
            ->orderBy('purchase_date', 'desc')
            ->get();

        // Get all inventory items for the form dropdown (resort supplies only)
        $inventoryItems = InventoryItem::resortSupplies()
            ->orderBy('name')
            ->get(['sku', 'name', 'category']);

        return view('admin.inventory.purchases', compact('purchases', 'inventoryItems'));
    }

    /**
     * Show the purchase entry form page
     */
    public function purchaseEntry()
    {
        // Get all inventory items for the form
        $inventoryItems = InventoryItem::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['sku', 'name', 'category']);

        return view('admin.inventory.purchase-entry', compact('inventoryItems'));
    }

    /**
     * Store a new purchase entry with items and auto-create stock movements
     */
    public function storePurchase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'vendor_name' => 'required|string|max:100',
            'receipt_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,sku',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Create purchase entry
            $purchase = PurchaseEntry::create([
                'entry_number' => PurchaseEntry::generateEntryNumber(),
                'purchase_date' => $validated['purchase_date'],
                'vendor_name' => $validated['vendor_name'],
                'receipt_no' => $validated['receipt_no'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'total_amount' => 0, // Will calculate below
                'created_by' => Auth::user()->user_id,
            ]);

            $totalAmount = 0;
            $newlyCreatedItems = []; // Track newly created items by category

            // Create purchase items and stock movements
            foreach ($validated['items'] as $itemData) {
                // Check if item exists by ID or by name (case-insensitive)
                if (!empty($itemData['inventory_item_id'])) {
                    $inventoryItem = InventoryItem::find($itemData['inventory_item_id']);
                } else {
                    // First, check if an item with the same name already exists
                    $existingItem = InventoryItem::whereRaw('LOWER(name) = ?', [strtolower($itemData['item_name'])])->first();
                    
                    if ($existingItem) {
                        // Use existing item - no need to create duplicate
                        $inventoryItem = $existingItem;
                    } else {
                        // Create new inventory item with auto-generated SKU
                        // Account for items created in this batch
                        $sku = InventoryItem::generateSKU($itemData['category']);
                        
                        // If there are newly created items in this category, get the next number after them
                        $categoryNewItems = collect($newlyCreatedItems)->where('category', $itemData['category']);
                        if ($categoryNewItems->count() > 0) {
                            $prefix = [
                                'cleaning' => 'CLN',
                                'kitchen' => 'KTC',
                                'amenity' => 'AMN',
                                'rental_item' => 'RNT',
                            ][$itemData['category']] ?? 'ITM';
                            
                            // Get the highest number from newly created items
                            $lastNumber = 0;
                            foreach ($categoryNewItems as $item) {
                                $numberPart = (int) substr($item['sku'], -3);
                                if ($numberPart > $lastNumber) {
                                    $lastNumber = $numberPart;
                                }
                            }
                            
                            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                            $sku = "{$prefix}-{$newNumber}";
                        }
                        
                        $inventoryItem = InventoryItem::create([
                            'name' => $itemData['item_name'],
                            'category' => $itemData['category'],
                            'sku' => $sku,
                            'quantity_on_hand' => 0,
                            'reorder_level' => intval($itemData['quantity'] / 2),
                            'average_cost' => $itemData['unit_cost'],
                            'unit_of_measure' => 'pcs',
                            'is_active' => true,
                        ]);
                        
                        // Track this newly created item
                        $newlyCreatedItems[] = [
                            'sku' => $inventoryItem->sku,
                            'category' => $itemData['category'],
                        ];
                    }
                }
                
                // Create purchase entry item
                $purchaseItem = PurchaseEntryItem::create([
                    'entry_number' => $purchase->entry_number,
                    'sku' => $inventoryItem->sku,
                    'item_name' => $inventoryItem->name,
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $itemData['unit_cost'],
                ]);

                $totalAmount += $purchaseItem->subtotal;

                // Create stock movement (IN)
                StockMovement::create([
                    'sku' => $inventoryItem->sku,
                    'movement_type' => 'in',
                    'quantity' => $itemData['quantity'],
                    'reason' => 'purchase',
                    'entry_number' => $purchase->entry_number,
                    'notes' => "Purchase from {$validated['vendor_name']}",
                    'performed_by' => Auth::user()->user_id,
                ]);

                // Update inventory quantity
                $inventoryItem->increment('quantity_on_hand', $itemData['quantity']);

                // Update average cost
                $inventoryItem->update([
                    'average_cost' => $itemData['unit_cost'],
                ]);
            }

            // Update total amount
            $purchase->update(['total_amount' => $totalAmount]);

            DB::commit();

            // Audit log: purchase entry recorded
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'Create Purchase',
                    'description' => 'Recorded purchase entry ' . ($purchase->entry_number ?? 'n/a') . ' total: ' . ($totalAmount ?? 0),
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore logging errors
            }

            return response()->json([
                'success' => true,
                'message' => 'Purchase entry recorded successfully',
                'purchase' => $purchase->load('items'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record purchase: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a specific purchase entry
     */
    public function showPurchase(string $id)
    {
        $purchase = PurchaseEntry::with(['items.inventoryItem', 'stockMovements', 'creator'])
            ->findOrFail($id);

        // Return JSON for AJAX requests
        if (request()->wantsJson() || request()->ajax()) {
            // Ensure item_name is included in each item
            $purchase->items->each(function ($item) {
                if (!$item->item_name) {
                    $item->item_name = $item->inventoryItem?->name ?? 'Unknown';
                }
            });
            
            return response()->json($purchase);
        }

        return view('admin.inventory.purchase-details', compact('purchase'));
    }

    /**
     * Update a purchase entry (rare use case)
     */
    public function updatePurchase(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'vendor_name' => 'required|string|max:100',
            'receipt_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $purchase = PurchaseEntry::findOrFail($id);
        $purchase->update($validated);

        // Audit log: purchase entry updated
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Update Purchase',
                'description' => 'Updated purchase entry ' . ($purchase->entry_number ?? $id),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Purchase entry updated successfully',
            'purchase' => $purchase,
        ]);
    }

    /**
     * Delete a purchase entry and reverse stock movements
     */
    public function deletePurchase(string $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $purchase = PurchaseEntry::with('items')->findOrFail($id);

            // Reverse stock movements
            foreach ($purchase->items as $item) {
                $inventoryItem = InventoryItem::find($item->sku);
                
                if ($inventoryItem) {
                    // Decrease inventory quantity
                    $inventoryItem->decrement('quantity_on_hand', $item->quantity);
                }

                // Delete related stock movements
                StockMovement::where('entry_number', $purchase->entry_number)->delete();
            }

            // Delete purchase items
            $purchase->items()->delete();

            // Delete purchase entry
            $purchase->delete();

            DB::commit();

            // Audit log: purchase deleted and stock reversed
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'Delete Purchase',
                    'description' => 'Deleted purchase entry ' . ($purchase->entry_number ?? $id),
                    'ip_address' => request()->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore
            }

            return response()->json([
                'success' => true,
                'message' => 'Purchase entry deleted and stock reversed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete purchase: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== RENTAL INTEGRATION ====================

    /**
     * Sync rental items from Rentals subsystem to Inventory
     */
    public function syncRentalItems(): JsonResponse
    {
        $synced = 0;
        $skipped = 0;

        $rentalItems = RentalItem::all();

        foreach ($rentalItems as $rental) {
            // Check if already synced
            $existing = InventoryItem::where('rental_item_id', $rental->id)->first();

            if ($existing) {
                // Update existing
                $existing->update([
                    'name' => $rental->name,
                    'quantity_on_hand' => $rental->stock_on_hand,
                    'is_active' => $rental->status === 'Active',
                ]);
                $skipped++;
            } else {
                // Create new inventory record
                $rentalSku = 'R-' . $rental->code;
                
                InventoryItem::create([
                    'name' => $rental->name,
                    'category' => 'rental_item',
                    'sub_category' => 'rental_equipment',
                    'description' => $rental->description ?? "Rental item - {$rental->name}",
                    'quantity_on_hand' => $rental->stock_on_hand,
                    'reorder_level' => 5,
                    'average_cost' => 0.00,
                    'unit_of_measure' => 'pc',
                    'sku' => $rentalSku,
                    'location' => 'Rental Storage',
                    'is_active' => $rental->status === 'Active',
                    'rental_item_id' => $rental->id,
                ]);
                $synced++;
            }
        }

        // Audit log: rental items synced to inventory
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Sync Rental Items',
                'description' => "Synced {$synced} new rental items, updated {$skipped} existing items",
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => "Synced {$synced} new rental items, updated {$skipped} existing items",
            'synced' => $synced,
            'updated' => $skipped,
        ]);
    }

    /**
     * Get rental item stock availability
     */
    public function getRentalItemStock(int $rentalItemId): JsonResponse
    {
        $inventoryItem = InventoryItem::where('rental_item_id', $rentalItemId)->first();

        if (!$inventoryItem) {
            return response()->json([
                'success' => false,
                'message' => 'Rental item not found in inventory',
            ], 404);
        }

        $rentalItem = RentalItem::find($rentalItemId);

        return response()->json([
            'success' => true,
            'inventory_quantity' => $inventoryItem->quantity_on_hand,
            'available_quantity' => $rentalItem ? $rentalItem->getAvailableQuantity() : 0,
            'is_low_stock' => $inventoryItem->isLowStock(),
        ]);
    }

    /**
     * Export inventory list as PDF
     */
    public function exportPDF(Request $request)
    {
        try {
            $range = $request->input('range', 'all');
            $fileName = $request->input('fileName', 'inventory-report');
            $search = $request->input('search', '');
            $category = $request->input('category', '');
            $status = $request->input('status', '');
            $dateFrom = $request->input('dateFrom', '');
            $dateTo = $request->input('dateTo', '');

        // Build query with proper eager loading
        $query = InventoryItem::query()->with(['purchaseEntryItems.purchaseEntry']);

        // Apply filters based on range
        if ($range === 'filtered' || $range === 'current') {
            // Apply search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('sku', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            // Apply category filter
            if (!empty($category)) {
                $query->where('category', $category);
            }

            // Apply status filter (low stock)
            if ($status === 'low') {
                $query->whereRaw('quantity_on_hand <= reorder_level');
            } elseif ($status === 'normal') {
                $query->whereRaw('quantity_on_hand > reorder_level');
            }

            // Apply date filter (last purchase date)
            if (!empty($dateFrom) || !empty($dateTo)) {
                $query->whereHas('purchaseEntryItems.purchaseEntry', function($q) use ($dateFrom, $dateTo) {
                    if (!empty($dateFrom)) {
                        $q->where('purchase_date', '>=', $dateFrom);
                    }
                    if (!empty($dateTo)) {
                        $q->where('purchase_date', '<=', $dateTo);
                    }
                });
            }
        }

        // Get items
        $items = $query->orderBy('name')->get();

        // Calculate summary statistics
        $totalItems = $items->count();
        $totalValue = $items->sum(function($item) {
            return $item->quantity_on_hand * $item->average_cost;
        });
        $lowStockCount = $items->filter(fn($item) => $item->isLowStock())->count();
        
        // Calculate stock turnover rate
        $stockMovementsOut = StockMovement::where('movement_type', 'out')
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->sum('quantity');
        $averageStock = $items->avg('quantity_on_hand') ?: 1;
        $turnoverRate = round(($stockMovementsOut / $averageStock) * 100, 2);

        $summary = [
            'total_items' => $totalItems,
            'total_value' => $totalValue,
            'low_stock_count' => $lowStockCount,
            'turnover_rate' => $turnoverRate,
        ];
        
        // Get recent stock movements (last 30 days) - only if we have items
        $stockMovements = collect([]);
        if ($items->isNotEmpty()) {
            $stockMovements = StockMovement::with(['inventoryItem', 'performer'])
                ->whereIn('sku', $items->pluck('sku'))
                ->where('created_at', '>=', now()->subDays(30))
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
        }
        
        // Get recent purchase history (last 30 days) - only if we have items
        $purchaseHistory = collect([]);
        if ($items->isNotEmpty()) {
            $purchaseHistory = PurchaseEntryItem::with(['purchaseEntry', 'inventoryItem'])
                ->whereIn('sku', $items->pluck('sku'))
                ->whereHas('purchaseEntry', function($q) {
                    $q->where('purchase_date', '>=', now()->subDays(30));
                })
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
        }

        // Prepare report data
        $reportData = [
            'title' => 'Inventory Report',
            'generated_at' => now()->format('F d, Y h:i A'),
            'generated_by' => Auth::user()->name,
            'filters' => [
                'range' => ucfirst($range),
                'search' => $search,
                'category' => $category ? ucfirst(str_replace('_', ' ', $category)) : 'All',
                'status' => $status ? ucfirst($status) . ' Stock' : 'All',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'items' => $items,
            'summary' => $summary,
            'stock_movements' => $stockMovements,
            'purchase_history' => $purchaseHistory,
            'isPdf' => true,
        ];

            // Generate PDF using DomPDF
            $pdf = Pdf::loadView('admin.inventory.pdf.inventory-report', $reportData);
            
            // Set paper size and orientation
            $pdf->setPaper('a4', 'landscape');
            
            // Download the PDF
            return $pdf->download($fileName . '.pdf');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PDF Export Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'range' => $range ?? 'unknown',
                'filters' => [
                    'search' => $search ?? '',
                    'category' => $category ?? '',
                    'status' => $status ?? '',
                ]
            ]);
            
            return response()->json([
                'error' => 'Failed to generate PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get item movements (stock movements) for an inventory item.
     */
    public function getItemMovements(string $itemId): JsonResponse
    {
        $item = InventoryItem::findOrFail($itemId);
        
        $movements = StockMovement::where('sku', $itemId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($movements);
    }

    /**
     * Get purchase history for an inventory item (for API endpoint).
     */
    public function getItemPurchaseHistory(string $itemId): JsonResponse
    {
        $item = InventoryItem::findOrFail($itemId);
        
        $purchases = PurchaseEntryItem::where('sku', $itemId)
            ->with('purchaseEntry')
            ->orderByDesc('created_at')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'purchase_date' => $item->purchaseEntry->purchase_date,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                    'supplier_name' => $item->purchaseEntry->vendor_name ?? 'Unknown',
                ];
            });

        return response()->json($purchases);
    }

    /**
     * Export purchases to PDF with filters applied.
     */
    public function purchasesExportPDF(Request $request)
    {
        $range = $request->input('range', 'all');
        $fileName = $request->input('fileName', 'purchases-report');
        $search = $request->input('search', '');
        $dateFrom = $request->input('dateFrom', '');
        $dateTo = $request->input('dateTo', '');

        // Build query
        $query = PurchaseEntry::with(['items', 'creator'])->orderByDesc('purchase_date');

        // Apply filters based on range
        if ($range === 'filtered' || $range === 'current') {
            // Apply search filter
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $search_lower = strtolower($search);
                    $q->whereRaw('LOWER(entry_number) LIKE ?', ['%' . $search_lower . '%'])
                      ->orWhereRaw('LOWER(vendor_name) LIKE ?', ['%' . $search_lower . '%']);
                });
            }

            // Apply date filter
            if (!empty($dateFrom) || !empty($dateTo)) {
                $query->where(function($q) use ($dateFrom, $dateTo) {
                    if (!empty($dateFrom)) {
                        $q->where('purchase_date', '>=', $dateFrom);
                    }
                    if (!empty($dateTo)) {
                        $q->where('purchase_date', '<=', $dateTo);
                    }
                });
            }
        }

        // Get purchases
        $purchases = $query->get();

        // Calculate summary statistics
        $totalEntries = $purchases->count();
        $totalAmount = $purchases->sum('total_amount');
        $totalItems = $purchases->sum(function($p) {
            return $p->items->sum('quantity');
        });
        $vendorCount = $purchases->pluck('vendor_name')->unique()->count();

        $summary = [
            'total_entries' => $totalEntries,
            'total_amount' => $totalAmount,
            'total_items' => $totalItems,
            'vendor_count' => $vendorCount,
        ];

        // Prepare report data
        $reportData = [
            'title' => 'Purchase Report',
            'generated_at' => now()->format('F d, Y h:i A'),
            'generated_by' => Auth::user()->name,
            'filters' => [
                'range' => ucfirst($range),
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'purchases' => $purchases,
            'summary' => $summary,
        ];

        // Generate PDF using DomPDF
        $pdf = Pdf::loadView('admin.inventory.pdf.purchases-report', $reportData);
        
        // Set paper size and orientation
        $pdf->setPaper('a4', 'landscape');
        
        // Download the PDF
        return $pdf->download($fileName . '.pdf');
    }

    /**
     * Record a stock out movement.
     */
 /**
 * Record a stock out movement for an inventory item.
 */
public function recordStockOut(Request $request): JsonResponse
{
    // Validate inputs
    $validated = $request->validate([
        'item_id' => 'required|exists:inventory_items,sku',
        'reason' => 'required|in:adjustment_out,rental_damage,usage,lost,expired',
        'quantity' => 'required|integer|min:1',
        'notes' => 'nullable|string',
        'user_id' => 'required|exists:users,user_id',
    ]);

    // Verify authenticated user
    if (Auth::id() != $validated['user_id']) {
        return response()->json([
            'success' => false,
            'message' => 'User ID verification failed'
        ], 403);
    }

    try {
        DB::beginTransaction();

        // Fetch inventory item by SKU
        $item = InventoryItem::where('sku', $validated['item_id'])->firstOrFail();

        // Check available stock
        if ($item->quantity_on_hand < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient stock. Available: {$item->quantity_on_hand}, Requested: {$validated['quantity']}"
            ], 422);
        }

        // Create stock movement (OUT)
        $movement = StockMovement::create([
            'sku' => $item->sku,
            'movement_type' => 'out',
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'performed_by' => Auth::id(),
            'performed_at' => now(),
        ]);

        // Update inventory quantity
        $item->decrement('quantity_on_hand', $validated['quantity']);

        DB::commit();

        // Audit log: stock out recorded
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Record Stock Out',
                'description' => 'Recorded stock out for ' . ($item->sku ?? 'n/a') . ' qty: ' . ($validated['quantity'] ?? 0) . ' reason: ' . ($validated['reason'] ?? 'n/a'),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock out recorded successfully',
            'item' => $item->fresh(),
            'movement' => $movement
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Error recording stock out: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Get date range based on preset or custom dates
     */
    private function getDateRange(Request $request)
    {
        $preset = $request->input('preset', 'month');

        $startDate = null;
        $endDate = now()->endOfDay();

        switch ($preset) {
            case 'year':
                $startDate = now()->startOfYear();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                break;
            case 'custom':
                $startDate = $request->input('start_date')
                    ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay()
                    : now()->startOfMonth();
                $endDate = $request->input('end_date')
                    ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay()
                    : now()->endOfDay();
                break;
            case 'month':
            default:
                $startDate = now()->startOfMonth();
                break;
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

}