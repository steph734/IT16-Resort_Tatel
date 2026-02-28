<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PublicKeyController;
use App\Http\Controllers\AccompanyingGuestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CurrentlyStayingController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\RentalsController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\AdminPaymongoController;
use App\Http\Controllers\Admin\AuditLogController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Admin Authentication Routes (for guests)
Route::prefix('admin')->name('admin.')->group(function () {
    // Redirect /admin to /admin/login
    Route::get('/', function () {
        return redirect()->route('admin.login');
    });

    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->name('login');

        // Admin public key endpoint for client-side encryption
        Route::get('public-key', [PublicKeyController::class, 'show'])->name('public_key');

        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
            ->name('password.request');

        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
            ->name('password.reset');

        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->name('password.store');
    });

    // Admin Protected Routes
    // Add server-side inactivity check to admin protected routes. Uses
    // App\Http\Middleware\SessionInactivity to enforce auto-logout on idle.
    Route::middleware(['admin', \App\Http\Middleware\SessionInactivity::class])->group(function () {
        // Heartbeat endpoint: keeps server-side session last_activity_time updated
        Route::post('keep-alive', function (Request $request) {
            $request->session()->put('last_activity_time', time());
            return response()->noContent();
        })->name('admin.keep-alive');

        // Unload beacon endpoint (optional): receives navigator.sendBeacon on unload
        Route::post('unload-beacon', function (Request $request) {
            $request->session()->put('last_activity_time', time());
            return response()->noContent();
        })->name('admin.unload-beacon');

    // Routes that should NOT be accessible to owner (they can only access Sales and Settings)
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
            // Test toggle functionality (remove in production)
            Route::get('test-toggle', function () {
                return view('admin.test-toggle');
            })->name('test-toggle');

            // Logout page (for easy logout)
            Route::get('logout-page', function () {
                return view('admin.logout');
            })->name('logout-page');

            // Dashboard Calendar Routes
            Route::get('dashboard/calendar-data', [DashboardController::class, 'getCalendarData'])
                ->name('dashboard.calendar-data');
            Route::post('dashboard/toggle-closed-date', [DashboardController::class, 'toggleClosedDate'])
                ->name('dashboard.toggle-closed-date');

            // Currently Staying Routes
            Route::get('currently-staying', [CurrentlyStayingController::class, 'index'])
                ->name('currently-staying');
            Route::get('currently-staying/search', [CurrentlyStayingController::class, 'search'])
                ->name('currently-staying.search');
            Route::get('currently-staying/guest-details', [CurrentlyStayingController::class, 'getGuestDetails'])
                ->name('currently-staying.guest-details');
            Route::put('currently-staying/{booking}/status', [CurrentlyStayingController::class, 'updateStatus'])
                ->name('currently-staying.update-status');
            Route::post('currently-staying/add-item', [CurrentlyStayingController::class, 'addUnpaidItem'])
                ->name('currently-staying.add-item');
            Route::get('currently-staying/bill-out/{bookingId}', [CurrentlyStayingController::class, 'showBillOut'])
                ->name('currently-staying.bill-out.show');
            Route::post('currently-staying/bill-out/process', [CurrentlyStayingController::class, 'processBillOut'])
                ->name('currently-staying.bill-out.process');
            Route::post('currently-staying/return-rentals', [CurrentlyStayingController::class, 'returnRentals'])
                ->name('currently-staying.return-rentals');
            Route::get('currently-staying/accompanying-guests', [CurrentlyStayingController::class, 'getAccompanyingGuests']);
            Route::post('currently-staying/accompanying-guests', [CurrentlyStayingController::class, 'saveAccompanyingGuests']);
            // Bookings Routes
            Route::get('bookings', [BookingController::class, 'index'])
                ->name('bookings.index');
            Route::get('bookings/create', [BookingController::class, 'create'])
                ->name('bookings.create');
            Route::get('bookings/data', [BookingController::class, 'getData'])
                ->name('bookings.data');
            Route::get('bookings/closed-dates', [BookingController::class, 'getClosedDates'])
                ->name('bookings.closed-dates');
            Route::post('bookings', [BookingController::class, 'store'])
                ->name('bookings.store');
            // Booking draft session endpoints
            Route::post('bookings/drafts/{draftId}/commit', [BookingController::class, 'commitDraft'])
                ->name('bookings.commit-draft');
            Route::delete('bookings/drafts/{draftId}', [BookingController::class, 'discardDraft'])
                ->name('bookings.discard-draft');
            Route::get('bookings/{id}', [BookingController::class, 'show'])
                ->name('bookings.show');
            Route::put('bookings/{id}', [BookingController::class, 'update'])
                ->name('bookings.update');
            Route::delete('bookings/{id}', [BookingController::class, 'destroy'])
                ->name('bookings.destroy');
            Route::put('bookings/{id}/status', [BookingController::class, 'updateStatus'])
                ->name('bookings.update-status');
            Route::put('bookings/{id}/payment', [BookingController::class, 'updatePayment'])
                ->name('bookings.update-payment');
            Route::get('bookings/{id}/payment-history', [BookingController::class, 'getPaymentHistory'])
                ->name('bookings.payment-history');
            Route::post('bookings/{id}/add-payment', [BookingController::class, 'addPayment'])
                ->name('bookings.add-payment');
            Route::post('bookings/{id}/verify-payment', [BookingController::class, 'verifyPayment'])
                ->name('bookings.verify-payment');
            Route::post('bookings/check-date-conflict', [BookingController::class, 'checkDateConflict'])
                ->name('bookings.check-date-conflict');
            Route::get('bookings/booked-dates/all', [BookingController::class, 'getBookedDates'])
                ->name('bookings.booked-dates');
            Route::get('bookings/calendar', [BookingController::class, 'getCalendarData'])
                ->name('bookings.calendar');
            Route::get('bookings/statistics', [BookingController::class, 'getStatistics'])
                ->name('bookings.statistics');

            // Other Admin Pages (placeholder for now)
            Route::get('checkin-checkout', function () {
                return view('admin.checkin-checkout');
            })->name('checkin-checkout');

            // Admin PayMongo routes (employee-side online checkout)
            Route::post('payments/paymongo/link', [AdminPaymongoController::class, 'createLink'])
                ->name('payments.paymongo.link');
            Route::get('payments/paymongo/status/{bookingId}', [AdminPaymongoController::class, 'status'])
                ->name('payments.paymongo.status');
            Route::get('payments/paymongo/success', [AdminPaymongoController::class, 'success'])
                ->name('payments.paymongo.success');
            Route::get('payments/paymongo/cancel', [AdminPaymongoController::class, 'cancel'])
                ->name('payments.paymongo.cancel');

            // Subsystem Routes
            Route::prefix('rentals')->name('rentals.')->group(function () {
                Route::get('/', [RentalsController::class, 'dashboard'])->name('dashboard');
                Route::get('/list', [RentalsController::class, 'index'])->name('index');
                Route::get('/catalog', [RentalsController::class, 'catalog'])->name('catalog');
                Route::get('/{id}', [RentalsController::class, 'show'])->name('show');

                // Rental Items (Catalog) Management
                Route::post('/items', [RentalsController::class, 'storeItem'])->name('items.store');
                Route::put('/items/{id}', [RentalsController::class, 'updateItem'])->name('items.update');
                Route::post('/items/{id}/toggle-status', [RentalsController::class, 'toggleItemStatus'])->name('items.toggle-status');

                // Rental Transactions
                Route::post('/issue', [RentalsController::class, 'issueRental'])->name('issue');
                Route::post('/{id}/return', [RentalsController::class, 'processReturn'])->name('return');
                Route::post('/{id}/add-fee', [RentalsController::class, 'addFee'])->name('add-fee');
                Route::delete('/{id}', [RentalsController::class, 'destroy'])->name('destroy');

                // API Endpoints
                Route::get('/api/available-items', [RentalsController::class, 'getAvailableItems'])->name('api.available-items');
                Route::get('/api/active-rentals', [RentalsController::class, 'getActiveRentals'])->name('api.active-rentals');
                Route::get('/api/booking/{bookingId}/rentals', [RentalsController::class, 'getBookingRentals'])->name('api.booking-rentals');
                Route::get('/api/list', [RentalsController::class, 'getRentalsList'])->name('api.list');
                Route::get('/issue', [RentalsController::class, 'create'])
                    ->name('issue-modal'); // → /admin/rentals/issue
            });

        }); // end not.owner group

        // Sales Subsystem Routes
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('/', [SalesController::class, 'dashboard'])->name('dashboard');
            Route::get('/ledger', [SalesController::class, 'ledger'])->name('ledger');
            Route::get('/reports', [SalesController::class, 'reports'])->name('reports');

            // API Endpoints
            Route::get('/api/dashboard-data', [SalesController::class, 'getDashboardData'])->name('api.dashboard-data');
            Route::get('/api/transactions', [SalesController::class, 'getTransactions'])->name('api.transactions');
            Route::get('/transactions/{id}', [SalesController::class, 'getTransactionById'])->name('transactions.show');
            Route::post('/transactions/{referenceId}/void', [SalesController::class, 'voidTransaction'])->name('transactions.void');

            // Report Generation Endpoints
            Route::post('/reports/per-booking', [SalesController::class, 'generatePerBookingReport'])->name('reports.per-booking');
            Route::post('/reports/monthly', [SalesController::class, 'generateMonthlyReport'])->name('reports.monthly');
            Route::post('/reports/annual', [SalesController::class, 'generateAnnualReport'])->name('reports.annual');
            Route::post('/reports/custom', [SalesController::class, 'generateCustomReport'])->name('reports.custom');
        });
        Route::get('/admin/accompanying-guests/{bookingId}', [AccompanyingGuestController::class, 'index']);
        Route::post('/admin/accompanying-guests/store', [AccompanyingGuestController::class, 'store']);
    // Inventory Subsystem Routes (restricted from owner)
    Route::middleware('not.owner')->group(function () {
            Route::prefix('inventory')->name('inventory.')->group(function () {
                // Main Pages
                Route::get('/', [InventoryController::class, 'index'])->name('index'); // Dashboard
                Route::get('/list', [InventoryController::class, 'inventoryList'])->name('list'); // Inventory List
                Route::get('/purchases', [InventoryController::class, 'purchases'])->name('purchases'); // Purchase Entries List
                Route::get('/purchase-entry', [InventoryController::class, 'purchaseEntry'])->name('purchase-entry'); // New Purchase Entry Page
                Route::get('/stock-movements', [InventoryController::class, 'stockMovements'])->name('stock-movements');
                Route::get('/admin/inventory/filter', [InventoryController::class, 'filterStockMovements'])
                    ->name('inventory.filter');
                Route::post('/adjust', [InventoryController::class, 'adjustStock'])
                    ->name('adjust');

                // Deprecated routes (keep for backward compatibility, remove later)
                // Route::get('/purchase-orders', [InventoryController::class, 'purchaseOrders'])->name('purchase-orders');
                // Route::get('/purchase-orders/{id}', [InventoryController::class, 'showPurchaseOrder'])->name('purchase-orders.show');
                // Route::get('/suppliers', [InventoryController::class, 'suppliers'])->name('suppliers');
                // Route::get('/suppliers/{id}', [InventoryController::class, 'showSupplier'])->name('suppliers.show');

                // Inventory Items
                Route::get('/items', [InventoryController::class, 'getItems'])->name('items.get');
                Route::post('/items', [InventoryController::class, 'storeItem'])->name('items.store');
                Route::put('/items/{id}', [InventoryController::class, 'updateItem'])->name('items.update');
                Route::get('/items/{id}/movements', [InventoryController::class, 'getItemMovements'])->name('items.movements');
                Route::get('/items/{id}/purchases', [InventoryController::class, 'getItemPurchaseHistory'])->name('items.purchases');
                Route::post('/items/adjust-stock', [InventoryController::class, 'adjustStock'])->name('items.adjust-stock');

                // Rental Items Integration
                Route::post('/sync-rentals', [InventoryController::class, 'syncRentalItems'])->name('sync-rentals');
                Route::get('/rental-items/{id}/stock', [InventoryController::class, 'getRentalItemStock'])->name('rental-items.stock');

                // Deprecated supplier routes
                // Route::post('/suppliers', [InventoryController::class, 'storeSupplier'])->name('suppliers.store');
                // Route::put('/suppliers/{id}', [InventoryController::class, 'updateSupplier'])->name('suppliers.update');

                // Purchase Entries (replaces Purchase Orders)
                Route::get('/purchases/{id}', [InventoryController::class, 'showPurchase'])->name('purchases.show');
                Route::post('/purchases', [InventoryController::class, 'storePurchase'])->name('purchases.store');
                Route::put('/purchases/{id}', [InventoryController::class, 'updatePurchase'])->name('purchases.update');
                Route::delete('/purchases/{id}', [InventoryController::class, 'deletePurchase'])->name('purchases.delete');

                // Deprecated purchase order routes
                // Route::post('/purchase-orders', [InventoryController::class, 'storePurchaseOrder'])->name('purchase-orders.store');
                // Route::put('/purchase-orders/{id}', [InventoryController::class, 'updatePurchaseOrder'])->name('purchase-orders.update');
                // Route::post('/purchase-orders/{id}/mark-received', [InventoryController::class, 'markPOReceived'])->name('purchase-orders.mark-received');
                // Route::post('/purchase-orders/{id}/close', [InventoryController::class, 'closePurchaseOrder'])->name('purchase-orders.close');
                // Route::post('/items/receive-stock', [InventoryController::class, 'receiveStock'])->name('items.receive-stock');

                // Stock Movements
                Route::get('/stock-movements/data', [InventoryController::class, 'getStockMovements'])->name('stock-movements.data');
                Route::get('/stock-movements/{id}', [InventoryController::class, 'getMovementDetails'])->name('stock-movements.details');
                Route::post('/stock-out', [InventoryController::class, 'recordStockOut'])->name('stock-out');

                // Export
                Route::post('/export-pdf', [InventoryController::class, 'exportPDF'])->name('export-pdf');
                Route::post('/purchases-export-pdf', [InventoryController::class, 'purchasesExportPDF'])->name('purchases-export-pdf');

                // Chart Data
                Route::get('/charts/stock-by-category', [InventoryController::class, 'getStockByCategoryData'])->name('charts.category');
                Route::get('/charts/purchasing-trend', [InventoryController::class, 'getPurchasingTrendData'])->name('charts.trend');
            });
        });

            // Settings routes (Admin only) - now split into Accounts and List Management
        Route::middleware('admin.only')->group(function () {
            // Old settings route (can be removed if no longer needed)
            Route::get('settings', [SettingsController::class, 'index'])->name('settings');
            
            // New separate pages for Accounts and List Management
            Route::get('accounts', [SettingsController::class, 'accounts'])->name('accounts');
            Route::get('list-management', [SettingsController::class, 'listManagement'])->name('list-management');
            // Audit logs (owner only)
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs');
            
            Route::post('settings/accounts', [SettingsController::class, 'storeAccount'])->name('settings.store-account');
            Route::put('settings/accounts/{userId}', [SettingsController::class, 'updateAccount'])->name('settings.update-account');
            Route::put('settings/accounts/{userId}/status', [SettingsController::class, 'updateAccountStatus'])->name('settings.update-account-status');

            // Package management routes
            Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
            Route::get('packages/{id}', [PackageController::class, 'show'])->name('packages.show');
            Route::post('packages', [PackageController::class, 'store'])->name('packages.store');
            Route::put('packages/{id}', [PackageController::class, 'update'])->name('packages.update');
            Route::delete('packages/{id}', [PackageController::class, 'destroy'])->name('packages.destroy');

            // Amenity management routes
            Route::post('amenities/add', [SettingsController::class, 'addAmenity'])->name('amenities.add');
            Route::post('amenities/delete', [SettingsController::class, 'deleteAmenity'])->name('amenities.delete');
        });

        Route::get('logout', function () {
            \Illuminate\Support\Facades\Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('admin.login')->with('message', 'You have been logged out.');
        })->name('logout.get');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });

    Route::prefix('admin.rentals')->name('rentals.')->group(function () {
        Route::get('/', [RentalsController::class, 'index'])->name('dashboard');
        Route::get('/rentals/fetch-booking-ids', [RentalsController::class, 'fetchBookingIds'])
            ->name('rentals.fetchBookingIds');
        Route::get('/rentals/booking-ids', [RentalsController::class, 'getBookingIds'])->name('admin.rentals.booking-ids');
        Route::get('/admin/current-booking-id', [RentalsController::class, 'getCurrentlyStayingBooking'])
            ->name('admin.current-booking-id');
        // routes/web.php
        Route::get('/admin/rental-fees', [RentalsController::class, 'getFees']);
        // routes/web.php
        Route::get('/admin/rentals/{id}/issued-quantity', [RentalsController::class, 'getIssuedQuantity']);
        Route::get('/admin/rentals/{id}/quantity', [RentalsController::class, 'getQuantity'])
            ->name('admin.rentals.quantity');
        Route::get('/admin/rentals/booking-ids', [RentalsController::class, 'getStayingBookingIds'])
            ->name('admin.rentals.staying-bookings');
        Route::get('/api/staying-booking', [RentalsController::class, 'getActiveStayingBooking'])
            ->name('api.staying-booking');
        // routes/web.php or routes/api.php
        Route::get('/admin/rentals/booking-ids', [RentalsController::class, 'getAllBookingsForDropdown'])
            ->name('admin.rentals.bookings.dropdown');
        Route::get('/api/currently-staying', [BookingController::class, 'fetchCurrentlyStaying']);
        Route::post('/admin/inventory/adjust', [InventoryController::class, 'adjustStock'])->name('admin.inventory.adjust');
    });Route::get('/admin/bookings/{booking}/outstanding', [CurrentlyStayingController::class, 'getOutstanding'])
        ->name('admin.booking.outstanding');
        
