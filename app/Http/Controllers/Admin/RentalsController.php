<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\RentalFee;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Audit_Log;

class RentalsController extends Controller
{
    /**
     * Display the rentals dashboard with KPIs and charts
     */
    public function dashboard(Request $request)
    {
        // Handle date filtering
        $dateRange = $this->getDateRange($request);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // Get ALL rentals with fees for revenue calculation
        // Include all statuses (Issued, Returned, Lost/Damaged) because customers pay for everything
        // This includes base rental fees, damage fees, and lost item fees
        $allRentals = Rental::with('fees')
            ->whereBetween('issued_at', [$startDate, $endDate])
            ->get();

        $totalRevenue = $allRentals->sum(function ($rental) {
            return $rental->calculateTotalCharges();
        });

        // Calculate average rental earnings per booking (all rentals)
        $bookingsWithRentals = Rental::whereBetween('issued_at', [$startDate, $endDate])
            ->distinct('BookingID')
            ->count('BookingID');
        $avgRevenuePerBooking = $bookingsWithRentals > 0 ? $totalRevenue / $bookingsWithRentals : 0;


        // Count returned vs damaged/lost rentals
        $returnedCount = Rental::where('status', 'Returned')
            ->whereBetween('issued_at', [$startDate, $endDate])
            ->count();
        // Check for both 'Damaged' and 'Lost' as separate statuses, or 'Lost/Damaged' combined
        $damagedCount = Rental::whereIn('status', ['Damaged', 'Lost', 'Lost/Damaged'])
            ->whereBetween('issued_at', [$startDate, $endDate])
            ->count();

        // Calculate damage rate percentage (damaged/lost out of all processed returns)
        $totalProcessed = $returnedCount + $damagedCount;
        $damageRate = $totalProcessed > 0 ? ($damagedCount / $totalProcessed) * 100 : 0;


        // Total rentable items in catalog (active items only)
        $totalRentableItems = RentalItem::where('status', 'Active')->count();

        // KPIs - More insightful metrics
        $kpis = [
            'total_revenue' => $totalRevenue,
            'avg_revenue_per_booking' => $avgRevenuePerBooking,
            'total_rentable_items' => $totalRentableItems,
            'damage_rate' => $damageRate,
        ];

        // Revenue Trend - dynamic based on selected date range
        $revenueChart = $this->getRevenueTrendData($startDate, $endDate, $request->input('preset', 'month'));

        // Popular Items (Top 5 by rental count)
        // Include ALL rentals (issued, returned, damaged) because customers pay for everything
        $popularItems = RentalItem::withCount([
                'rentals' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('issued_at', [$startDate, $endDate]);
                }
            ])
            ->with([
                'rentals' => function ($query) use ($startDate, $endDate) {
                    $query->with('fees')
                        ->whereBetween('issued_at', [$startDate, $endDate]);
                }
            ])
            ->having('rentals_count', '>', 0)
            ->orderBy('rentals_count', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                // Calculate total revenue from all rentals including fees
                $item->total_revenue = $item->rentals->sum(function ($rental) {
                    return $rental->calculateTotalCharges();
                });
                return $item;
            });

        // Revenue by Item (Top 5 items by revenue for pie chart)
        // Include ALL rentals because customers pay for all rental states
        $revenueByItem = RentalItem::with([
            'rentals' => function ($query) use ($startDate, $endDate) {
                $query->with('fees')
                    ->whereBetween('issued_at', [$startDate, $endDate]);
            }
        ])->get()->map(function ($item) {
            $revenue = $item->rentals->sum(function ($rental) {
                return $rental->calculateTotalCharges();
            });
            return [
                'name' => $item->name,
                'revenue' => $revenue
            ];
        })->filter(function ($item) {
            // Only include items with revenue > 0
            return $item['revenue'] > 0;
        })->sortByDesc('revenue')->take(5)->values();

        return view('admin.rentals.rentals-dashboard', compact(
            'kpis',
            'revenueChart',
            'popularItems',
            'revenueByItem'
        ));
    }
    public function create()
    {
        $today = today()->format('Y-m-d');

        $stayingBooking = DB::table('bookings')
            ->where('BookingStatus', 'Staying')
            ->whereDate('CheckInDate', '<=', $today)
            ->whereDate('CheckOutDate', '>=', $today)
            ->first();

        return view('admin.rentals.issue', compact('stayingBooking'));
    }
    public function getActiveStayingBooking()
    {
        $today = today()->format('Y-m-d');

        $booking = DB::table('bookings')
            ->where('BookingStatus', 'Staying')
            ->whereDate('CheckInDate', '<=', $today)
            ->whereDate('CheckOutDate', '>=', $today)
            ->select('ID as booking_id', 'GuestID') // GuestID to show G001/G002 if needed
            ->first();

        if ($booking) {
            return response()->json([
                'success' => true,
                'booking_id' => $booking->booking_id,
                'guest_code' => $booking->GuestID, // e.g., G002
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No guest currently staying'
        ]);
    }
    /**
     * Get date range based on preset or custom dates
     */
    private function getDateRange(Request $request)
    {
        $preset = $request->input('preset', 'month');
        
        $startDate = null;
        $endDate = Carbon::now()->endOfDay();

        switch ($preset) {
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                break;
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                break;
            case 'custom':
                $startDate = $request->input('start_date') 
                    ? Carbon::parse($request->input('start_date'))->startOfDay()
                    : Carbon::now()->startOfMonth();
                $endDate = $request->input('end_date')
                    ? Carbon::parse($request->input('end_date'))->endOfDay()
                    : Carbon::now()->endOfDay();
                break;
            case 'month':
            default:
                $startDate = Carbon::now()->startOfMonth();
                break;
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * Get revenue trend data based on selected date range
     */
    private function getRevenueTrendData($startDate, $endDate, $preset)
    {
        $labels = [];
        $data = [];

        // Determine granularity based on preset
        switch ($preset) {
            case 'year':
                // Show monthly data for the year
                $start = $startDate->copy()->startOfMonth();
                $end = $endDate->copy();
                
                while ($start <= $end) {
                    $labels[] = $start->format('M Y');
                    
                    $monthlyRentals = Rental::whereMonth('issued_at', $start->month)
                        ->whereYear('issued_at', $start->year)
                        ->with('fees')
                        ->get();
                    
                    $data[] = $monthlyRentals->sum(function ($rental) {
                        return $rental->calculateTotalCharges();
                    });
                    
                    $start->addMonth();
                }
                break;
                
            case 'week':
                // Show daily data for the week
                $start = $startDate->copy();
                $end = $endDate->copy();
                
                while ($start <= $end) {
                    $labels[] = $start->format('M d');
                    
                    $dailyRentals = Rental::whereDate('issued_at', $start->toDateString())
                        ->with('fees')
                        ->get();
                    
                    $data[] = $dailyRentals->sum(function ($rental) {
                        return $rental->calculateTotalCharges();
                    });
                    
                    $start->addDay();
                }
                break;
                
            case 'custom':
                // Determine granularity based on date range length
                $daysDiff = $startDate->diffInDays($endDate);
                
                if ($daysDiff <= 31) {
                    // Daily for ranges up to 31 days
                    $start = $startDate->copy();
                    $end = $endDate->copy();
                    
                    while ($start <= $end) {
                        $labels[] = $start->format('M d');
                        
                        $dailyRentals = Rental::whereDate('issued_at', $start->toDateString())
                            ->with('fees')
                            ->get();
                        
                        $data[] = $dailyRentals->sum(function ($rental) {
                            return $rental->calculateTotalCharges();
                        });
                        
                        $start->addDay();
                    }
                } else {
                    // Monthly for longer ranges
                    $start = $startDate->copy()->startOfMonth();
                    $end = $endDate->copy();
                    
                    while ($start <= $end) {
                        $labels[] = $start->format('M Y');
                        
                        $monthlyRentals = Rental::whereMonth('issued_at', $start->month)
                            ->whereYear('issued_at', $start->year)
                            ->whereBetween('issued_at', [$startDate, $endDate])
                            ->with('fees')
                            ->get();
                        
                        $data[] = $monthlyRentals->sum(function ($rental) {
                            return $rental->calculateTotalCharges();
                        });
                        
                        $start->addMonth();
                    }
                }
                break;
                
            case 'month':
            default:
                // Show weekly data for the month
                $start = $startDate->copy();
                $end = $endDate->copy();
                $weekNum = 1;
                
                while ($start <= $end) {
                    $weekEnd = $start->copy()->endOfWeek();
                    if ($weekEnd > $end) {
                        $weekEnd = $end->copy();
                    }
                    
                    $labels[] = 'Week ' . $weekNum;
                    
                    $weeklyRentals = Rental::whereBetween('issued_at', [
                            $start->startOfDay(),
                            $weekEnd->endOfDay()
                        ])
                        ->with('fees')
                        ->get();
                    
                    $data[] = $weeklyRentals->sum(function ($rental) {
                        return $rental->calculateTotalCharges();
                    });
                    
                    $start = $weekEnd->copy()->addDay()->startOfDay();
                    $weekNum++;
                }
                break;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
    public function getStayingBookingIds()
    {
        // Today’s date (you can adjust logic for timezones if needed)
        $today = now()->format('Y-m-d');

        $bookings = DB::table('bookings as b')
            ->join('guests as g', 'b.GuestID', '=', 'g.GuestID') // assuming you have a guests table
            ->select([
                'b.ID as booking_id',           // this is your primary key shown in phpMyAdmin
                'g.GuestName as guest_name',    // adjust column name if different
            ])
            ->where('b.BookingStatus', 'Staying')
            ->whereDate('b.CheckInDate', '<=', $today)
            ->whereDate('b.CheckOutDate', '>=', $today)
            ->orderBy('b.ID')
            ->get();

        return response()->json([
            'success' => true,
            'bookings' => $bookings
        ]);
    }

    public function getAllBookingsForDropdown(Request $request)
    {
        $bookings = DB::table('bookings as b')
            ->join('guests as g', 'b.GuestID', '=', 'g.GuestID')
            ->leftJoin('rooms as r', 'g.room_id', '=', 'r.id') // adjust if room relation is different
            ->select(
                'b.BookingID as booking_id',
                DB::raw("CONCAT(g.FirstName, ' ', g.LastName) as guest_name"),
                'r.room_number', // adjust column name if needed
                'b.BookingStatus as status',
                'b.CheckInDate as check_in',
                'b.CheckOutDate as check_out'
            )
            ->orderByDesc('b.BookingDate')
            ->get();

        return response()->json([
            'success' => true,
            'bookings' => $bookings
        ]);
    }
    public function fetchCurrentlyStaying()
    {
        $today = now()->toDateString();

        $bookings = Booking::whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>=', $today)
            ->select('BookingID', 'guest_name') // include guest if needed
            ->get();

        return response()->json($bookings);
    }
    /**
     * Display the rentals management page (List View)
     */
    public function index(Request $request)
    {
        $query = Rental::with(['booking.guest', 'rentalItem', 'issuedByUser']);

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'issues') {
                $query->whereIn('status', ['Damaged', 'Lost', 'Lost/Damaged']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by item
        if ($request->filled('item_id')) {
            $query->where('rental_item_id', $request->item_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('issued_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('issued_at', '<=', $request->date_to);
        }

        // Search by booking ID or guest name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('BookingID', 'like', "%{$search}%")
                    ->orWhereHas('booking.guest', function ($guestQuery) use ($search) {
                        $guestQuery->where('FName', 'like', "%{$search}%")
                            ->orWhere('LName', 'like', "%{$search}%");
                    });
            });
        }

        $rentals = $query->orderBy('issued_at', 'desc')->get();
        $rentalItems = RentalItem::active()->get();

        return view('admin.rentals.index', compact('rentals', 'rentalItems'));
    }

    /**
     * Display rental details selection page
     */
    public function details(Request $request)
    {
        $query = Rental::with(['booking.guest', 'rentalItem', 'issuedByUser']);

        // Filter by item if provided
        if ($request->filled('item_id')) {
            $query->where('rental_item_id', $request->item_id);
        }

        $rentals = $query->orderBy('issued_at', 'desc')->get();
        $rentalItems = RentalItem::active()->get();

        return view('admin.rentals.details', compact('rentals', 'rentalItems'));
    }

    /**
     * Display rental detail page
     */
    public function show($id)
    {
        $rental = Rental::with([
            'booking.guest',
            'rentalItem',
            'fees.addedByUser',
            'issuedByUser',
            'returnedByUser'
        ])->findOrFail($id);

        $feeBreakdown = $rental->getFeeBreakdown();

        return view('admin.rentals.detail', compact('rental', 'feeBreakdown'));
    }

    /**
     * Display items catalog page
     */
    public function catalog(Request $request)
    {
        // Always get fresh pending count
        $pendingCount = \App\Models\InventoryItem::where('category', 'rental_item')
            ->where('is_active', true)
            ->whereDoesntHave('rentalItem')
            ->count();

        $viewMode = $request->get('view', 'configured'); // 'configured' or 'pending'

        if ($viewMode === 'pending') {
            // Show items that need setup
            $items = \App\Models\InventoryItem::where('category', 'rental_item')
                ->where('is_active', true)
                ->whereDoesntHave('rentalItem')
                ->get();

            return view('admin.rentals.catalog-pending', compact('items', 'pendingCount'));
        }

        // Default: Show configured rental items
        $items = RentalItem::with(['inventoryItem', 'rentals'])->get();

        return view('admin.rentals.catalog', compact('items', 'pendingCount'));
    }

    /**
     * Store a new rental item (catalog) - Links to existing inventory item
     */
    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,sku|unique:rental_items,sku',
            'rate_type' => 'required|in:Per-Day,Flat',
            'rate' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // Rename key from inventory_item_id to sku
        $validated['sku'] = $validated['inventory_item_id'];
        unset($validated['inventory_item_id']);
        $validated['status'] = 'Active';

        $rentalItem = RentalItem::create($validated);

        // Audit log: rental catalog item created
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Create Rental Item',
                'description' => 'Added rental catalog item ' . ($rentalItem->id ?? 'n/a') . ' sku: ' . ($rentalItem->sku ?? 'n/a'),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json(['success' => true, 'message' => 'Item added successfully']);
    }

    public function getFees()
    {
        $fees = DB::table('rental_fees')->first();

        return response()->json([
            'damage_fee' => $fees->damage_fee,
            'loss_fee' => $fees->loss_fee
        ]);
    }
    public function updateItem(Request $request, $id)
    {
        $item = RentalItem::findOrFail($id);

        $validated = $request->validate([
            'rate_type' => 'required|in:Per-Day,Flat',
            'rate' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Archived',
        ]);

        $item->update($validated);

        // Audit log: rental item updated
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Update Rental Item',
                'description' => 'Updated rental item ' . ($item->id ?? 'n/a'),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json(['success' => true, 'message' => 'Item updated successfully']);
    }

    /**
     * Archive/Restore rental item
     */
    public function toggleItemStatus($id)
    {
        $item = RentalItem::findOrFail($id);
        $item->status = $item->status === 'Active' ? 'Archived' : 'Active';
        $item->save();

        // Audit log: rental item status toggled
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Toggle Item Status',
                'description' => 'Toggled rental item ' . ($item->id ?? 'n/a') . ' status to ' . $item->status,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Item status updated',
            'status' => $item->status
        ]);
    }

    /**
     * Get all booking IDs for dropdown (Currently Staying)
     */
    public function getBookingIds()
    {
        $bookings = Booking::with('guest')
            ->whereIn('BookingStatus', ['Confirmed', 'Staying'])
            ->select('BookingID')
            ->get()
            ->map(function ($booking) {
                return [
                    'booking_id' => $booking->BookingID,
                    'guest_name' => $booking->guest ? $booking->guest->FName . ' ' . $booking->guest->LName : 'N/A'
                ];
            });

        return response()->json([
            'success' => true,
            'bookings' => $bookings
        ]);
    }

    /**
     * Get available items for rental
     */
    public function getAvailableItems()
    {
        $items = RentalItem::active()
            ->with('inventoryItem')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'code' => $item->code,
                    'sku' => $item->sku,
                    'rate' => $item->rate,
                    'rate_type' => $item->rate_type,
                    'available_quantity' => $item->getAvailableQuantity(),
                    'stock_on_hand' => $item->stock_on_hand,
                ];
            });

        return response()->json($items);
    }

    /**
     * Get rentals list as JSON for client-side filtering
     */
    public function getRentalsList()
    {
        $rentals = Rental::with(['booking.guest', 'rentalItem'])
            ->orderBy('issued_at', 'desc')
            ->get()
            ->map(function ($rental) {
                return [
                    'id' => $rental->id,
                    'booking_id' => $rental->BookingID,
                    'guest_name' => $rental->booking->guest 
                        ? $rental->booking->guest->FName . ' ' . $rental->booking->guest->LName 
                        : 'N/A',
                    'item_id' => $rental->rental_item_id,
                    'item_name' => $rental->rentalItem->name,
                    'item_code' => $rental->rentalItem->code,
                    'quantity' => $rental->quantity,
                    'status' => $rental->status,
                    'total_charges' => $rental->calculateTotalCharges(),
                    'issued_at' => $rental->issued_at->toISOString(),
                    'returned_at' => $rental->returned_at ? $rental->returned_at->toISOString() : null,
                ];
            });

        return response()->json([
            'success' => true,
            'rentals' => $rentals
        ]);
    }

    /**
     * Issue/Attach rental to a booking - Store data in rentals table
     */
    public function issueRental(Request $request)
    {
        // 1. Fetch booking with 'Staying' status (not checking CheckInDate)
        $booking = Booking::where('BookingID', $request->booking_id)
            ->where('BookingStatus', 'Staying')
            ->first();

        // 2. If booking not found or not staying
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'No booking found with this Booking ID or guest is not currently staying.'
            ], 404);
        }

        // 4. Protect against spoofed booking_id
        $request->merge([
            'booking_id' => $booking->BookingID
        ]);

        // 5. Validate normal fields
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,BookingID',
            'rental_item_id' => 'required|exists:rental_items,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $item = RentalItem::findOrFail($validated['rental_item_id']);

        // 6. Check availability
        if (!$item->isAvailable($validated['quantity'])) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $item->getAvailableQuantity()
            ], 400);
        }

        DB::beginTransaction();
        try {

            // 7. Create rental
            $rental = Rental::create([
                'BookingID' => $booking->BookingID,
                'rental_item_id' => $validated['rental_item_id'],
                'quantity' => $validated['quantity'],
                'rate_snapshot' => $item->rate,
                'rate_type_snapshot' => $item->rate_type,
                'status' => 'Issued',
                'notes' => $validated['notes'] ?? null,
                'issued_at' => Carbon::now(),
                'issued_by' => Auth::user()->user_id,

                // return defaults
                'returned_quantity' => 0,
                'condition' => null,
                'damage_description' => null,
                'returned_at' => null,
                'returned_by' => null,
            ]);

            DB::commit();

            // Audit log: rental issued
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'Issue Rental',
                    'description' => 'Issued rental ' . ($rental->id ?? 'n/a') . ' booking: ' . ($rental->BookingID ?? 'n/a') . ' item: ' . ($rental->rental_item_id ?? 'n/a') . ' qty: ' . ($rental->quantity ?? 0),
                    'ip_address' => request()->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore
            }

            return response()->json([
                'success' => true,
                'message' => 'Rental issued successfully and stored in database',
                'rental' => $rental->load('rentalItem')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to issue rental: ' . $e->getMessage()
            ], 500);
        }
    }

    public function processReturn(Request $request, $id)
    {
        $rental = Rental::findOrFail($id);

        if ($rental->status !== 'Issued') {
            return response()->json([
                'success' => false,
                'message' => 'Rental is not currently issued'
            ], 400);
        }

        // Normalize input: some frontends send a single 'lost_damage_fee' field.
        // Map it to damage_fee or loss_fee depending on condition so validation works.
        $input = $request->all();

        // If frontend sent lost_damage_fee and no damage_fee/loss_fee, map it:
        if (isset($input['lost_damage_fee'])) {
            // if condition is present in request, use it. Otherwise fallback to existing rental condition.
            $cond = $input['condition'] ?? null;

            // If condition not supplied yet, we still map to both possibilities after validation step below.
            // But prefer mapping now if condition is known.
            if ($cond === 'Damaged') {
                $input['damage_fee'] = $input['lost_damage_fee'];
            } elseif ($cond === 'Lost') {
                $input['loss_fee'] = $input['lost_damage_fee'];
            } else {
                // if condition unknown yet, don't map; we'll map after validating 'condition'
            }
        }

        // Create validator against normalized $input.
        // We use Validator::make rather than $request->validate() so we can pass $input.
        $validator = Validator::make($input, [
            'returned_quantity' => 'required|integer|min:0|max:' . $rental->quantity,
            'condition' => 'required|in:Good,Damaged,Lost',
            'damage_description' => 'nullable|string|required_if:condition,Damaged',
            'damage_fee' => 'nullable|numeric|min:0|required_if:condition,Damaged',
            'loss_fee' => 'nullable|numeric|min:0|required_if:condition,Lost',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:5120', // 5MB max
        ]);

        // If validator fails, return errors
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // After validation, ensure we have correctly mapped fee field if frontend only sent lost_damage_fee
        $validated = $validator->validated();

        if (!isset($validated['damage_fee']) && !isset($validated['loss_fee']) && $request->filled('lost_damage_fee')) {
            // map based on validated condition
            if ($validated['condition'] === 'Damaged') {
                $validated['damage_fee'] = $request->input('lost_damage_fee');
            } elseif ($validated['condition'] === 'Lost') {
                $validated['loss_fee'] = $request->input('lost_damage_fee');
            }
        }

        DB::beginTransaction();
        try {
            // Handle photo upload (use original $request for file)
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('rental_damages', 'public');
            }

            // Update rental record in the rentals table
            $rental->update([
                'returned_quantity' => $validated['returned_quantity'],
                'condition' => $validated['condition'],
                'damage_description' => $validated['damage_description'] ?? null,
                // Set status based on specific condition
                'status' => match ($validated['condition']) {
                    'Good' => 'Returned',
                    'Damaged' => 'Damaged',
                    'Lost' => 'Lost',
                    default => 'Returned'
                },
                'returned_at' => Carbon::now(),
                'returned_by' => Auth::user()->user_id,
                'notes' => ($rental->notes ? $rental->notes . "\n\n" : '') . ($validated['notes'] ?? ''),
            ]);

            // Add damage fee if applicable
            if (isset($validated['damage_fee']) && $validated['damage_fee'] > 0) {
                RentalFee::create([
                    'rental_id' => $rental->id,
                    'type' => 'Damage',
                    'amount' => $validated['damage_fee'],
                    'reason' => $validated['damage_description'] ?? 'Damage assessment',
                    'photo_path' => $photoPath,
                    'added_by' => Auth::user()->user_id,
                ]);
            }

            // Add loss fee if applicable
            if (isset($validated['loss_fee']) && $validated['loss_fee'] > 0) {
                RentalFee::create([
                    'rental_id' => $rental->id,
                    'type' => 'Loss',
                    'amount' => $validated['loss_fee'],
                    'reason' => 'Lost item fee',
                    'photo_path' => $photoPath,
                    'added_by' => Auth::user()->user_id,
                ]);
            }

            DB::commit();

            // Audit log: rental return processed
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'Process Return',
                    'description' => 'Processed return for rental ' . ($rental->id ?? 'n/a') . ' condition: ' . ($rental->condition ?? 'n/a') . ' returned_qty: ' . ($rental->returned_quantity ?? 0),
                    'ip_address' => request()->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore
            }

            return response()->json([
                'success' => true,
                'message' => 'Return processed successfully and rental updated in database',
                'rental' => $rental->fresh()->load('fees')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error processing return: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addFee(Request $request, $id)
    {
        $rental = Rental::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:Adjustment,Damage,Loss',
            'amount' => 'required|numeric',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $fee = RentalFee::create([
            'rental_id' => $rental->id,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'added_by' => Auth::user()->user_id,
        ]);

        // Audit log: rental fee added
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Add Rental Fee',
                'description' => 'Added fee ' . ($fee->id ?? 'n/a') . ' to rental ' . ($rental->id ?? 'n/a') . ' type: ' . ($fee->type ?? 'n/a') . ' amount: ' . ($fee->amount ?? 0),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json([
            'success' => true,
            'message' => 'Fee added successfully and stored in database',
            'fee' => $fee->load('addedByUser')
        ]);
    }

    /**
     * Get rentals for a specific booking
     */
    public function getBookingRentals($bookingId)
    {
        $rentals = Rental::with(['rentalItem', 'fees'])
            ->where('BookingID', $bookingId)
            ->get()
            ->map(function ($rental) {
                return [
                    'id' => $rental->id,
                    'item_name' => $rental->rentalItem->name,
                    'quantity' => $rental->quantity,
                    'status' => $rental->status,
                    'issued_at' => $rental->issued_at->format('M d, Y h:i A'),
                    'total_charges' => $rental->calculateTotalCharges(),
                ];
            });

        return response()->json($rentals);
    }

    /**
     * Get active (issued) rentals for a specific booking
     */
    public function getActiveRentals(Request $request)
    {
        $bookingId = $request->input('booking_id');

        $rentals = Rental::with('rentalItem')
            ->where('BookingID', $bookingId)
            ->where('status', 'Issued')
            ->get()
            ->map(function ($rental) {
                return [
                    'id' => $rental->id,
                    'item_name' => $rental->rentalItem->name,
                    'item_code' => $rental->rentalItem->code,
                    'quantity' => $rental->quantity,
                    'rate_snapshot' => $rental->rate_snapshot,
                    'rate_type_snapshot' => $rental->rate_type_snapshot,
                    'issued_at' => $rental->issued_at->format('M d, Y h:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'rentals' => $rentals
        ]);
    }

    /**
     * Delete/Void rental (Admin only)
     */
    public function destroy($id)
    {
        $rental = Rental::findOrFail($id);

        // Only allow deletion if issued (not yet returned)
        if ($rental->status !== 'Issued') {
            return response()->json([
                'success' => false,
                'message' => 'Can only void issued rentals'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Delete associated fees first
            RentalFee::where('rental_id', $rental->id)->delete();

            // Then delete the rental
            $rental->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rental voided successfully and removed from database'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error voiding rental: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCurrentlyStayingBookings()
    {
        $today = Carbon::today()->toDateString();

        $bookings = Booking::with('guest')
            ->whereDate('CheckInDate', '<=', $today)
            ->whereDate('CheckOutDate', '>=', $today)
            ->whereIn('BookingStatus', ['Confirmed', 'Staying'])
            ->get()
            ->map(function ($booking) {
                return [
                    'booking_id' => $booking->BookingID,
                    'guest_name' => $booking->guest
                        ? $booking->guest->FName . ' ' . $booking->guest->LName
                        : 'Unknown Guest'
                ];
            });

        return response()->json([
            'success' => true,
            'bookings' => $bookings
        ]);
    }

    public function getIssuedQuantity($id)
    {
        $rental = Rental::find($id);

        if (!$rental) {
            return response()->json([
                'success' => false,
                'issued_quantity' => 0
            ]);
        }

        return response()->json([
            'success' => true,
            'issued_quantity' => $rental->quantity  // fetch the quantity from rentals table
        ]);
    }

    public function getQuantity($id)
    {
        $rental = Rental::findOrFail($id);
        return response()->json(['quantity' => $rental->quantity]);
    }

}