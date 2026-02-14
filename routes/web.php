<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CheckAvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymongoController;
use App\Http\Controllers\Admin\InventoryController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --------------------
// Public Pages
// --------------------
// Landing / Home
Route::get('/', function () {
    return view('landing');
})->name('home');

// --------------------
// Booking Flow
// --------------------

// Step 1: Check Availability
Route::get('/check-availability', [CheckAvailabilityController::class, 'index'])
    ->name('bookings.check-availability');
Route::post('/check-availability', [CheckAvailabilityController::class, 'store'])
    ->name('bookings.check-availability.store');

// Step 2: Personal Details
Route::get('/personal-details', [BookingController::class, 'personalDetails'])
    ->name('bookings.personal-details');
Route::post('/personal-details', [BookingController::class, 'storePersonalDetails'])
    ->name('bookings.personal-details.store');

// Step 3: Booking Details
Route::get('/booking-details', [BookingController::class, 'bookingDetails'])
    ->name('bookings.details');

// Reset booking session and go back to personal details
Route::get('/reset-booking', [BookingController::class, 'resetBooking'])
    ->name('bookings.personal-details.reset');
Route::post('/booking-details', [BookingController::class, 'storeBookingDetails'])
    ->name('bookings.details.store');

// Step 4: Payment
Route::get('/payment', [BookingController::class, 'payment'])
    ->name('bookings.payment');
Route::post('/payment', [BookingController::class, 'storePayment'])
    ->name('bookings.payment.store');

Route::get('/payment/{booking}', [BookingController::class, 'paymentByBooking'])
    ->name('bookings.payment.booking');

// Debug route to check session
Route::get('/debug-session', function() {
    return response()->json([
        'all_session' => Session::all(),
        'booking_details' => Session::get('booking_details'),
        'booking_data' => Session::get('booking_data'),
        'personal_details' => Session::get('personal_details'),
    ]);
})->name('debug.session');

// Step 5: Confirmation
Route::get('/booking-confirmation', [BookingController::class, 'confirmation'])
    ->name('bookings.confirmation');


// Send confirmation email (from confirmation page)
Route::post('/booking-confirmation/send', [BookingController::class, 'sendConfirmationEmail'])
    ->name('bookings.confirmation.send');
Route::view('/booking-policy', 'bookings.booking-policy')->name('booking-policy');

// --------------------
// Payments (Integrations)
// --------------------
Route::post('/payments/paymongo/link', [PaymongoController::class, 'createLink'])
    ->name('payments.paymongo.link');
Route::post('/payments/paymongo/webhook', [PaymongoController::class, 'webhook'])
    ->name('payments.paymongo.webhook');
Route::post('/payments/paymongo/confirm-now', [PaymongoController::class, 'confirmNow'])
    ->name('payments.paymongo.confirm_now');
Route::get('/payments/paymongo/status/{bookingId}', [PaymongoController::class, 'status'])
    ->name('payments.paymongo.status');
Route::get('/payments/paymongo/success', [PaymongoController::class, 'success'])
    ->name('payments.paymongo.success');
Route::get('/payments/paymongo/cancel', [PaymongoController::class, 'cancel'])
    ->name('payments.paymongo.cancel');

// Auth routes are handled in admin.php only

// --------------------
// Public API Routes
// --------------------
Route::get('/api/inventory/item/{itemId}/purchases', [InventoryController::class, 'getItemPurchaseHistory'])
    ->name('api.inventory.item-purchases');

// --------------------
// Admin Routes
// --------------------
require __DIR__.'/admin.php';
