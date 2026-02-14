# JBRBS - Comprehensive Architecture Documentation

> **JBRBS (Jaybee's Resort Booking System)** - A Laravel 12 web application for managing resort bookings, rentals, inventory, payments, and sales reporting.

---

## IMPLEMENTATION DETAILS - Key Features

### 1. **Fetching Booked Dates & Calendar Display** ⭐

**How It Works**:
1. **Backend Retrieval**:
   - **File**: `app/Http/Controllers/CheckAvailabilityController.php` & `app/Http/Controllers/BookingController.php`
   - **Method**: `checkAvailability()` method
   - Queries database for:
     - `Booking::select('CheckInDate', 'CheckOutDate')` - Gets all confirmed, pending, booked, or staying bookings
     - `ClosedDate::pluck('closed_date')` - Gets resort closure dates
   - Builds booked date ranges from check-in to check-out (inclusive)
   - Data passed to blade template as `$bookedDates` and `$closedDates`

2. **Frontend Rendering**:
   - **File**: `resources/views/bookings/check-availability.blade.php`
   - **Libraries**: Flatpickr (date picker), Alpine.js (calendar logic)
   - Uses `window.calendarData` global object containing booked and closed dates
   - `calendar()` Alpine component renders calendar grid with:
     - **getDateStatus()** - Determines if date is booked, closed, or available
     - **isBooked()** - Checks if date falls within any booked range
     - **isPastDate()** - Prevents selecting past dates
   - CSS styling shows visual indicators:
     - 🔴 Red dots for closed dates
     - ⚫ Blue dots for booked dates
     - ✅ Green background for available dates

3. **Date Picker Integration**:
   - **Library**: Flatpickr.js (CDN)
   - **Function**: `initializeDatePickers()` in `bookingForm()` Alpine component
   - Configuration:
     - `dateFormat: "Y-m-d"`
     - `minDate: tomorrow` - Cannot select today or past dates
     - `disable: [isDateBooked]` - Disables booked/closed dates
     - Check-in and check-out pickers are linked (check-out min date updates when check-in selected)

**Files Involved**:
- `app/Http/Controllers/CheckAvailabilityController.php` - Data retrieval
- `app/Http/Controllers/BookingController.php` - Calendar data fetching
- `resources/views/bookings/check-availability.blade.php` - UI rendering
- `public/js/` - Flatpickr & Alpine.js libraries

### 2. **Generating Reports (PDF/CSV/JSON)** ⭐

**Architecture**:
1. **Report Types Supported**:
   - **Per Booking Report** - Single booking breakdown
   - **Monthly Report** - All bookings for a month
   - **Annual Report** - Yearly summary with monthly breakdown
   - **Custom Date Range** - Flexible period analysis

2. **Backend Implementation**:
   - **File**: `app/Http/Controllers/Admin/SalesController.php`
   - **Key Methods**:
     - `generatePerBookingReport()` - Lines 70-190 (calculates booking costs, rentals, store items, payments)
     - `generateMonthlyReport()` - Lines 200-380 (aggregates all sales for selected month)
     - `generateAnnualReport()` - Lines 400-600 (yearly summary with monthly breakdown)
     - `generateCustomReport()` - Lines 700-900 (flexible date range, daily sales)
   
   - **Data Collection Process**:
     ```
     1. Query Payment table for booking payments
     2. Query Rental table for equipment rental charges
     3. Query RentalFee table for damage/loss fees
     4. Query UnpaidItem table for store purchases
     5. Calculate totals: Package Cost + Excess Fees - Senior Discounts
     6. Calculate rental totals: Base Rental + Damage Fees + Loss Fees
     7. Calculate store totals: Sum of all unpaid items marked as paid
     8. Build comprehensive report data structure
     ```

3. **PDF Generation**:
   - **Library**: DomPDF (Barryvdh Laravel package)
   - **Files**: `app/Http/Controllers/Admin/SalesController.php` (methods starting at line 846)
   - **Methods**:
     - `generatePerBookingPDF()` - Generates PDF from view
     - `generateMonthlyPDF()` - Monthly PDF
     - `generateAnnualPDF()` - Annual PDF
     - `generateCustomPDF()` - Custom range PDF
   
   - **PDF Configuration**:
     ```php
     Pdf::loadView('admin.sales.pdf.per-booking', $data)
         ->setPaper('a4', 'portrait')
         ->setOption('margin-top', 5)
         ->setOption('margin-bottom', 5)
         ->setOption('margin-left', 5)
         ->setOption('margin-right', 5)
         ->download($fileName);
     ```

4. **Blade Templates**:
   - **Location**: `resources/views/admin/sales/pdf/`
   - Files: `per-booking.blade.php`, `monthly.blade.php`, `annual.blade.php`, `custom.blade.php`
   - Uses inline CSS for PDF compatibility (no external stylesheets)
   - Displays tables with:
     - Booking information (guest, dates, package)
     - Rental line items (quantity, rate, fees)
     - Store purchases (items, quantities, unit prices)
     - Payment history (method, date, amount, status)
     - Totals breakdown (booking, rental, store, grand total)

5. **Output Formats**:
   - **PDF**: Downloads as `{report-type}-{identifier}.pdf`
   - **CSV**: Returns JSON (converted via frontend)
   - **JSON**: Returns raw JSON response for AJAX handling

**Files Involved**:
- `app/Http/Controllers/Admin/SalesController.php` - Report logic (1618 lines)
- `resources/views/admin/sales/pdf/*.blade.php` - PDF templates
- `vendor/barryvdh/laravel-dompdf/` - DomPDF package
- Route: `POST /admin/sales/reports/per-booking` (and similar for other types)

### 3. **Search Suggestions on Input Fields** ⭐

**Implementations Across Multiple Forms**:

#### A. **Issue Rental - Item Search**:
- **File**: `resources/views/admin/rentals/modals/issue.blade.php` (lines 1-200)
- **Component**: Modal form for issuing rentals to guests
- **Search Field**: `itemSearchInput` - Text input for searching rental items
- **How It Works**:
  1. All rental items loaded into `allRentalItems` array (global JavaScript variable)
  2. User types in `itemSearchInput` field
  3. JavaScript event listener on `input` event triggers filtering
  4. Filters items by: name, SKU, or code (case-insensitive partial match)
  5. Results displayed in dropdown (`itemSearchResults` div)
  
  **Code Structure**:
  ```javascript
  itemSearchInput.addEventListener('input', function () {
      const searchTerm = this.value.toLowerCase().trim();
      
      // Filter items
      const filtered = allRentalItems.filter(item => {
          const name = item.name.toLowerCase();
          const sku = (item.sku || '').toLowerCase();
          return name.includes(searchTerm) || sku.includes(searchTerm);
      });
      
      // Display filtered results in dropdown
      // Each item shows: Name, SKU, and availability count
  });
  ```
  
  **Features**:
  - Dropdown shows up to 150px height with scrollbar
  - Each item displays: Name, SKU, and availability status
  - Click item to select it and populate rental form
  - Displays "Out of stock" warning if quantity unavailable

#### B. **Stock Out - Item Search** (Inventory Management):
- Similar pattern used in inventory stock adjustment forms
- Filters inventory items by name or SKU
- Shows current stock level next to each item

#### C. **New Purchase Entry - Item Search** (Inventory):
- Used when adding items to purchase orders
- Searches inventory items database
- Shows unit cost and last purchase price
- Autocomplete for vendor and supplier information

**Implementation Pattern**:
1. **Data Source**: Items loaded from database into global JavaScript array
2. **Search Method**: JavaScript array `.filter()` with `.includes()` for partial matching
3. **Display**: HTML dropdown with styled result items
4. **Selection**: Click handler populates hidden input and displays selection
5. **UI/UX**: Hover effects, keyboard navigation support, clear X button to reset

**Files Involved**:
- `resources/views/admin/rentals/modals/issue.blade.php` - Rental item search (lines 30-80)
- `resources/views/admin/inventory/**/*.blade.php` - Inventory item searches
- JavaScript: Vanilla JS with event listeners (no external libraries)
- Data: Loaded via PHP Blade templating `@json()` helper

### 4. **Graphical Charts on Dashboards** ⭐

**Chart Library & Implementation**:
- **Library**: Chart.js v4.4.0 (CDN: `https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js`)
- **Language**: Vanilla JavaScript with Chart.js API

**Chart Types & Locations**:

#### A. **Booking Dashboard** - `resources/views/admin/bookings/booking-dashboard.blade.php`:
1. **Line Graph - Booking Trends**:
   - Canvas ID: `bookingTrendsChart`
   - Shows bookings vs cancellations over time
   - Dynamic based on date range preset (Month, Week, Year)
   - Two datasets: Confirmed Bookings (green), Cancellations (red)
   
2. **Sparkline Charts - KPI Cards**:
   - Mini charts in each KPI card
   - Shows trend for: Total Bookings, Confirmed, Pending, Completed

#### B. **Sales Dashboard** - `resources/views/admin/sales/sales-dashboard.blade.php`:
1. **Line Graph - Revenue Trend**:
   - Canvas ID: `dailyRevenueChart`
   - Shows daily revenue over selected period
   - Displays booking sales + rental sales combined

2. **Pie Chart - Revenue by Source**:
   - Canvas ID: `revenueBySourceChart`
   - Shows breakdown: Booking Sales vs Rental Sales
   - Color coded segments

3. **Pie Chart - Payment Methods**:
   - Canvas ID: `paymentMethodChart`
   - Shows breakdown: Cash, GCash, BDO, PayMongo
   - Percentage distribution

#### C. **Rentals Dashboard** - `resources/views/admin/rentals/rentals-dashboard.blade.php`:
1. **Line Graph - Revenue Trend**:
   - Shows rental revenue over time
   - Includes damage/loss fee contributions

2. **Bar Graph - Popular Items**:
   - Top 5 rented items by revenue
   - Shows quantity and revenue for each

#### D. **Inventory Dashboard** - `resources/views/admin/inventory/inventory-dashboard.blade.php`:
1. **Bar Graph - Stock by Category**:
   - Categories: Cleaning, Kitchen, Amenity, Rental Items
   - Shows quantity on hand vs reorder level

**Backend Data Preparation**:
- **File**: `app/Http/Controllers/Admin/SalesController.php` (method `getDashboardData()` at line 1000+)
- **Method**: `getChartData($startDate, $endDate, $preset)` in DashboardController
- Prepares data in Chart.js format:
  ```php
  $chartData = [
      'labels' => ['Jan', 'Feb', 'Mar', ...],  // X-axis
      'datasets' => [
          [
              'label' => 'Bookings',
              'data' => [100, 120, 110, ...],   // Y-axis values
              'borderColor' => 'rgb(75, 192, 192)',
              'backgroundColor' => 'rgba(75, 192, 192, 0.1)',
              'tension' => 0.1
          ]
      ]
  ];
  ```

**Frontend JavaScript**:
- **File**: `public/js/admin/sales-dashboard.js` & Dashboard JavaScript sections
- Initializes Chart.js instances:
  ```javascript
  new Chart(ctx, {
      type: 'line',  // or 'pie', 'bar', 'doughnut'
      data: chartData,
      options: {
          responsive: true,
          plugins: {
              legend: { display: true },
              title: { display: true, text: 'Chart Title' }
          },
          scales: {
              y: { beginAtZero: true }
          }
      }
  });
  ```

**Date Range Filtering**:
- Presets: This Month, This Year, This Week
- Custom date range selector
- Charts re-fetch data via AJAX when date range changes
- Loading state shows spinner until chart renders

**Files Involved**:
- `resources/views/admin/bookings/booking-dashboard.blade.php` - Chart HTML/JS
- `resources/views/admin/sales/sales-dashboard.blade.php` - Sales charts
- `resources/views/admin/rentals/rentals-dashboard.blade.php` - Rental charts
- `resources/views/admin/inventory/inventory-dashboard.blade.php` - Inventory charts
- `app/Http/Controllers/Admin/DashboardController.php` - Chart data generation
- `app/Http/Controllers/Admin/SalesController.php` - Sales chart data

### 5. **Dashboard KPI Card Data Fetching** ⭐

**Data Flow**:

1. **Initial Page Load**:
   - **File**: `resources/views/admin/sales/sales-dashboard.blade.php`
   - KPI cards rendered with placeholder values
   - Four KPI cards: Booking Sales, Rental Sales, Sales Difference, Growth Rate

2. **AJAX Data Fetching**:
   - **File**: `public/js/admin/sales-dashboard.js`
   - On page load, JavaScript makes AJAX fetch to:
     ```
     GET /admin/sales/dashboard/data?preset=month
     ```
   - Response JSON contains:
     ```json
     {
       "kpis": {
         "booking_sales": "25000.00",
         "rental_sales": "5000.00",
         "sales_difference": "3000.00",
         "growth_rate": "12.50",
         "previous_booking_sales": "22000.00",
         "previous_rental_sales": "5000.00"
       },
       "revenue_trend": [...],
       "revenue_by_source": {...},
       "payment_methods": {...},
       "top_packages": [...],
       "top_rentals": [...]
     }
     ```

3. **Backend Calculation**:
   - **Method**: `SalesController::getDashboardData()` (lines 1010-1100)
   - Calculations:
     ```php
     // Booking Sales = Sum of all payments (Fully Paid, Downpayment, Verified status)
     $bookingSales = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
         ->whereIn('PaymentStatus', ['Fully Paid', 'Downpayment', 'Verified'])
         ->sum('Amount');
     
     // Rental Sales = Sum of unpaid items + rentals marked as paid
     $rentalSales = UnpaidItem::where('IsPaid', true)
         ->whereBetween('updated_at', [$startDate, $endDate])
         ->sum('TotalAmount') + Rental::where('is_paid', true)
         ->sum('total_charges');
     
     // Comparison = Current period vs Previous period
     $salesDifference = $totalSales - $previousTotalSales;
     $growthRate = ($totalSales / $previousTotalSales) * 100;
     ```

4. **Data Update on Date Change**:
   - User clicks date range button
   - Modal opens with preset options (This Month, This Year, This Week)
   - User selects preset or custom dates
   - JavaScript re-fetches data:
     ```javascript
     fetch(`/admin/sales/dashboard/data?preset=${preset}&start_date=${start}&end_date=${end}`)
         .then(res => res.json())
         .then(data => updateKPICards(data.kpis));
     ```

5. **UI Update**:
   - KPI values updated in real-time
   - Change indicators (↑↓) updated with percentage
   - Color coding: Green for positive, Red for negative
   - Charts re-render with new data

**KPI Card HTML Structure**:
```html
<div class="kpi-card">
    <div class="kpi-icon icon-green">
        <i class="fas fa-bed"></i>
    </div>
    <div class="kpi-content">
        <div class="kpi-label">Booking Sales</div>
        <div class="kpi-change positive">
            <i class="fas fa-arrow-up"></i> 
            <span id="kpi1Change">0%</span> vs last period
        </div>
        <div class="kpi-value">₱<span id="kpi1Value">0.00</span></div>
    </div>
</div>
```

**Date Range Presets**:
- **This Month**: `Carbon::now()->startOfMonth()` to `Carbon::now()->endOfMonth()`
- **This Year**: `Carbon::now()->startOfYear()` to `Carbon::now()->endOfYear()`
- **This Week**: `Carbon::now()->startOfWeek()` to `Carbon::now()->endOfWeek()`
- **Custom**: User-specified date range

**Files Involved**:
- `resources/views/admin/sales/sales-dashboard.blade.php` - KPI card HTML
- `public/js/admin/sales-dashboard.js` - AJAX fetch & DOM updates
- `app/Http/Controllers/Admin/SalesController.php` - `getDashboardData()` method
- Route: `GET /admin/sales/dashboard/data` (SalesController::getDashboardData)

### 6. **Void Transaction Feature** ⭐

**Void Functionality - Complete Overview**:

1. **Database Support**:
   - **Fields in Payment table**:
     - `is_voided` (boolean) - Flag if payment is voided
     - `voided_at` (timestamp) - When voiding occurred
     - `voided_by` (user ID) - Which staff member voided it
     - `void_reason` (string) - Reason for voiding
   
   - **Fields in Transaction table**:
     - `is_voided` (boolean)
     - `voided_at` (timestamp)
     - `voided_by` (user ID)
     - `void_reason` (string)

2. **Backend Implementation**:
   - **Method**: `SalesController::voidTransaction()` (lines 1286-1400)
   - **Route**: `POST /admin/sales/transactions/{referenceId}/void`
   - **Validation**:
     - Check if transaction exists
     - Check if already voided (prevent double voiding)
     - Validate void reason provided
     - Check user permissions
   
   - **Void Process**:
     ```php
     public function voidTransaction(Request $request, $referenceId) {
         $validated = $request->validate([
             'void_reason' => 'required|string|max:255'
         ]);
         
         // Find transaction/payment
         $transaction = Transaction::where('reference_id', $referenceId)->first();
         
         // Check if already voided
         if ($transaction->is_voided) {
             return error('Transaction already voided');
         }
         
         // Mark as voided
         $transaction->update([
             'is_voided' => true,
             'voided_at' => now(),
             'voided_by' => Auth::id(),
             'void_reason' => $validated['void_reason']
         ]);
         
         // Reverse payment status
         $payment = Payment::find($referenceId);
         $payment->update(['PaymentStatus' => 'Cancelled']);
         
         // Log the voiding
         Log::info("Transaction {$referenceId} voided by " . Auth::user()->name);
         
         return success('Transaction voided successfully');
     }
     ```

3. **Payment ID Reuse Prevention**:
   - **Location**: `app/Models/Payment.php` (generatePaymentID() method)
   - When a payment is voided, its ID is NOT reused
   - New payment IDs are generated sequentially (PY001, PY002, PY003, etc.)
   - Even if PY010 is voided, next payment gets PY011 (not PY010)
   - **Safety Mechanism**: Loop checks if ID exists (including voided ones)
   ```php
   private static function generatePaymentID() {
       $lastPayment = self::orderBy('PaymentID', 'desc')->first();
       $startNumber = $lastPayment ? intval(substr($lastPayment->PaymentID, 2)) : 0;
       
       // Keep incrementing until we find an unused ID
       do {
           $newNumber = $startNumber + 1 + $attempts++;
           $newId = 'PY' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
           $exists = self::where('PaymentID', $newId)->exists();
       } while ($exists && $attempts < 1000);
       
       return $newId;
   }
   ```

4. **UI/UX - Void Button**:
   - **Location**: Transaction ledger page
   - Button appears in action column for each transaction
   - Opens modal with void reason input
   - Confirmation required before voiding
   - Success notification after voiding
   - Voided transactions display with strikethrough or dimmed styling

5. **Audit Trail**:
   - All void actions logged with:
     - Who voided (user name/ID)
     - When voided (timestamp)
     - Why voided (void reason)
     - Original transaction details preserved
   - Query with `is_voided = false` to exclude voided transactions from reports
   - Query with `is_voided = true` to show only voided transactions

6. **Report Exclusion**:
   - Voided transactions excluded from:
     - Sales dashboard KPIs
     - Monthly/annual reports
     - Revenue calculations
     - Dashboard charts
   - Uses scope: `Transaction::notVoided()` or filter: `where('is_voided', false)`

7. **Reversal Effects**:
   - When transaction voided:
     - Payment status changed to 'Cancelled'
     - Transaction marked as voided
     - Related amounts NOT refunded (manual process)
     - Outstanding balance NOT recalculated automatically (manual review)

**Files Involved**:
- `app/Http/Controllers/Admin/SalesController.php` - `voidTransaction()` method
- `app/Models/Payment.php` - Payment ID generation logic
- `app/Models/Transaction.php` - Transaction model with voiding fields
- `routes/admin.php` - Route definition at line 175
- `resources/views/admin/sales/transactions-ledger.blade.php` - UI implementation
- Database migrations - Voiding fields added to payments & transactions tables

---

## Table of Contents

1. [Overview](#overview)
2. [Technology Stack](#technology-stack)
3. [Object-Oriented Programming (OOP) Architecture](#object-oriented-programming-oop-architecture)
4. [Directory Structure](#directory-structure)
5. [Core Application Layers](#core-application-layers)
6. [Key Features & Modules](#key-features--modules)
7. [Database Architecture](#database-architecture)
8. [Configuration Files](#configuration-files)
9. [Public Assets](#public-assets)
10. [Testing & Utilities](#testing--utilities)

---

## Overview

JBRBS is a comprehensive resort management system built on Laravel 12 that handles:
- **Guest Bookings** - Multi-step booking flow with availability checking
- **Payment Processing** - Integration with PayMongo for online payments (GCash, BDO Online Banking)
- **Rental Management** - Track equipment rentals with damage/loss fees
- **Inventory Management** - Purchase entries, stock movements, and item tracking
- **Sales Reporting** - Generate PDF/CSV reports (per booking, monthly, annual, custom)
- **Email Notifications** - SMTP-based booking confirmations, reminders, and alerts
- **Admin Dashboard** - Real-time KPIs, charts, and management interfaces

---

## Technology Stack

### Backend
- **Framework**: Laravel 12.x (PHP 8.2+)
- **Database**: MySQL/MariaDB
- **PDF Generation**: DomPDF (barryvdh/laravel-dompdf)
- **HTTP Client**: Guzzle (for PayMongo API calls)
- **Authentication**: Laravel Breeze

### Frontend
- **CSS Framework**: Tailwind CSS
- **Build Tool**: Vite
- **JavaScript**: Vanilla JS with Alpine.js components

### External Services
- **Payment Gateway**: PayMongo (Checkout Sessions API)
- **Email**: SMTP (configurable via mail.php)

---

## Object-Oriented Programming (OOP) Architecture

JBRBS is built on solid OOP principles using PHP 8.2+ features and Laravel's ecosystem. Here's how OOP is implemented throughout the system:

### 1. **Namespacing & Code Organization**
All classes are properly namespaced by functionality:
- **`App\Models`** - Eloquent ORM models representing database entities
- **`App\Http\Controllers`** - Request handlers organized by domain (Admin, Auth, Public)
- **`App\Services`** - Business logic services (PaymongoService)
- **`App\Mail`** - Mailable classes for email notifications
- **`App\Notifications`** - System notifications
- **`App\Enums`** - Type-safe enumerations (PHP 8.1+ backed enums)
- **`App\Providers`** - Service container providers

### 2. **Inheritance & Polymorphism**

#### **Model Inheritance (Eloquent)**:
All models extend Laravel's `Illuminate\Database\Eloquent\Model` base class:
```php
class Booking extends Model { ... }
class Payment extends Model { ... }
class Transaction extends Model { ... }
class User extends Authenticatable { ... }  // Extends Model + Authentication traits
```

**Benefits**:
- Unified database query interface (all models use Eloquent ORM)
- Built-in timestamps (created_at, updated_at)
- Relationship definitions through base class methods

#### **Controller Inheritance**:
All controllers extend `App\Http\Controllers\Controller` base class:
```php
class PaymongoController extends Controller { ... }
class SalesController extends Controller { ... }
class BookingController extends Controller { ... }
```

**Benefits**:
- Centralized middleware application
- Shared helper methods
- Consistent request/response handling

#### **Notification Inheritance**:
```php
class AdminResetPasswordNotification extends ResetPassword { ... }
```
Extends Laravel's base Notification class, enabling polymorphic notification behavior.

#### **Service Provider Inheritance**:
```php
class AppServiceProvider extends ServiceProvider { ... }
```
Extends Laravel's ServiceProvider for dependency injection container bindings.

### 3. **Dependency Injection (DI)**

The system leverages Laravel's service container for automatic dependency resolution:

#### **Constructor Injection in Controllers**:
```php
class PaymongoController extends Controller
{
    public function __construct(private PaymongoService $paymongo)
    {
        // $paymongo is automatically injected and available as $this->paymongo
    }

    public function createLink(Request $request)
    {
        $this->paymongo->createPaymentLink(...);  // Service method called
    }
}
```

#### **Service Constructor with Type-Hinting**:
```php
class PaymongoService
{
    private Client $client;
    private string $secretKey;

    public function __construct()
    {
        // Initialize Guzzle HTTP client with configuration
        $this->client = new Client([
            'base_uri' => config('paymongo.base_url') . '/',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
            ],
        ]);
    }
}
```

**Benefits**:
- Loose coupling between classes
- Easy testing (mock dependencies)
- Single Responsibility Principle (SRP)

### 4. **Traits (Composition over Inheritance)**

Models use traits to add reusable functionality without creating deep inheritance hierarchies:

```php
class Booking extends Model
{
    use HasFactory;  // Provides factory pattern for testing
}

class User extends Authenticatable
{
    // Inherits from Model which includes:
    // - HasApiTokens (API authentication)
    // - HasFactory (model factory support)
}
```

**Traits Used**:
- **`HasFactory`** - Model factory support for testing (in all models)
- **`Authenticatable`** - User authentication support (User model)
- **`Laravel\Sanctum\HasApiTokens`** - API token authentication (if enabled)

### 5. **Encapsulation & Access Modifiers**

Classes use proper visibility to protect internal state:

```php
class PaymongoService
{
    private Client $client;              // Cannot access outside this class
    private string $secretKey;           // Cannot access outside this class
    private string $baseUrl = '...';     // Cannot access outside this class

    public function createPaymentLink(...) { ... }  // Public API
    
    private function validateResponse(...) { ... }  // Internal helper
}
```

**Types of Encapsulation**:
- **`private`** - Only accessible within the class (e.g., helper methods, sensitive config)
- **`protected`** - Accessible within class and subclasses (used in Eloquent models for relationships)
- **`public`** - Accessible from anywhere (public API methods, endpoints)

### 6. **Type Hinting & Return Types (PHP 8.2+)**

All methods include proper type declarations:

```php
// Service method with full type hints
public function createPaymentLink(
    int $amountCentavos, 
    string $description, 
    array $metadata = []
): array {
    // Method body
}

// Controller method with type hints
public function checkAvailability(Request $request): JsonResponse { ... }

// Model scope with return type
public function scopeNotVoided($query): Builder {
    return $query->where('is_voided', false);
}
```

**Benefits**:
- Compile-time type checking (catches errors early)
- IDE autocompletion
- Self-documenting code
- Runtime type validation

### 7. **Enums (PHP 8.1+ Backed Enums)**

Type-safe alternatives to constants and magic strings:

```php
namespace App\Enums;

enum Role: string
{
    case Staff = 'staff';
    case Admin = 'admin';
}

// Usage in models:
$user->role = Role::Admin;  // Type-safe, no typos possible
```

**Advantages**:
- Cannot use invalid values (e.g., can't accidentally use 'adnin' instead of 'admin')
- IDE autocompletion for enum values
- Type-safe comparisons
- Cleaner than constants

**Enums in JBRBS**:
- `Role` - User roles (Staff, Admin)
- `PaymentMethod` - Payment types (Cash, Online, GCash, BDO)
- `ItemCategory` - Inventory categories (Cleaning, Kitchen, Amenity, Rental)
- `RentalStatus` - Rental states (Issued, Returned, Lost/Damaged)
- `FeeType` - Additional charges (Damage, Loss, Late)
- `StockMovementType` - Inventory movements (In, Out, Adjustment)
- `PurchaseOrderStatus` - PO workflow states
- `ItemCondition` - Item quality states
- `RateType` - Rental pricing models (Per-Day, Flat Rate)

### 8. **Abstract Classes & Interfaces** (When Extended)

Laravel base classes use abstraction to enforce implementation:

```php
// Laravel's Request class (abstract validation interface)
class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array { ... }
    public function authorize(): bool { ... }
}

// All Form Requests must implement rules() and authorize()
```

### 9. **Model Relationships (OOP Pattern)**

Eloquent relationships define object associations:

```php
class Booking extends Model
{
    // One-to-Many: One booking has many rentals
    public function rentals()
    {
        return $this->hasMany(Rental::class, 'BookingID');
    }

    // Belongs-To: Many bookings belong to one guest
    public function guest()
    {
        return $this->belongsTo(Guest::class, 'GuestID');
    }
}

class Transaction extends Model
{
    // Polymorphic relationships: Transaction can link to multiple sources
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'BookingID');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'reference_id', 'PaymentID');
    }
}

// Usage:
$booking = Booking::with('rentals', 'guest')->find($id);  // Eager loading
$totalCharges = $booking->rentals->sum('total_charges');  // Object traversal
```

### 10. **Query Scopes (Encapsulated Queries)**

Models encapsulate common query logic in reusable scopes:

```php
class Transaction extends Model
{
    // Local scope: reusable query logic
    public function scopeNotVoided($query)
    {
        return $query->where('is_voided', false);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }
}

// Usage: Clean, readable query composition
$financialData = Transaction::notVoided()
    ->byDateRange($start, $end)
    ->byType('Booking Payment')
    ->get();
```

### 11. **Boot Method (Model Lifecycle Hooks)**

Models use Laravel's boot lifecycle to enforce business rules:

```php
class Booking extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->BookingID)) {
                // Auto-generate BookingID on creation
                $model->BookingID = self::generateBookingID();
            }
        });
    }

    private static function generateBookingID()
    {
        $lastBooking = self::orderByRaw('CAST(SUBSTRING(BookingID, 2) AS UNSIGNED) DESC')->first();
        $lastNumber = intval(substr($lastBooking->BookingID ?? 'B000', 1));
        return 'B' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }
}
```

**Benefits**:
- Automatic ID generation without controller logic
- Business rule enforcement at model layer
- Single source of truth

### 12. **Property Casting (Type Safety)**

Models automatically cast properties to appropriate types:

```php
class Booking extends Model
{
    protected $casts = [
        'BookingDate' => 'datetime',
        'CheckInDate' => 'datetime',
        'ExcessFee' => 'decimal:2',
        'senior_discount' => 'decimal:2',
        'actual_seniors_at_checkout' => 'integer',
    ];
}

class Transaction extends Model
{
    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
        'metadata' => 'array',        // Automatic JSON encode/decode
        'is_voided' => 'boolean',
        'voided_at' => 'datetime',
    ];
}

// Automatic type conversion on retrieval
$booking->BookingDate instanceof Carbon\Carbon;  // true
$transaction->metadata is an array even if stored as JSON;
```

### 13. **Class Organization Pattern (MVC + Services)**

```
App/
├── Http/Controllers/          (Controllers - Handle requests)
│   ├── BookingController
│   ├── PaymongoController
│   └── Admin/
│       ├── SalesController
│       └── DashboardController
├── Models/                     (Models - Data layer, business rules)
│   ├── Booking
│   ├── Payment
│   ├── Transaction
│   └── [10+ other models]
├── Services/                   (Services - Business logic layer)
│   └── PaymongoService
├── Mail/                       (Mailables - Email notifications)
│   ├── BookingConfirmationMail
│   └── BookingReminderMail
└── Enums/                      (Type-safe enumerations)
    ├── Role
    ├── PaymentMethod
    └── [7+ other enums]
```

**Design Pattern: Service Layer**:
- Controllers remain thin (only request/response)
- Business logic moves to Service classes
- Models handle data relationships
- Easy to test and maintain

### 14. **Polymorphism in Action**

Different payment types handled polymorphically:

```php
// Transaction can represent multiple types
$transaction = Transaction::find($id);

switch ($transaction->transaction_type) {
    case 'Booking Payment':
        $booking = $transaction->booking;
        break;
    case 'Rental':
        $rental = $transaction->rental;
        break;
}

// Or use relationships directly (polymorphic)
$relatedEntity = $transaction->booking ?? $transaction->rental ?? $transaction->payment;
```

### 15. **Summary of OOP Principles Used**

| Principle | Implementation | Example |
|-----------|----------------|---------|
| **Encapsulation** | Private/protected/public modifiers | PaymongoService hides HTTP client implementation |
| **Inheritance** | Model/Controller/Notification base classes | All models extend Eloquent Model |
| **Polymorphism** | Different model relationships, type hints | Transaction links to multiple entities |
| **Abstraction** | Form Requests, Service classes | PaymongoService abstracts API complexity |
| **SRP** | Controllers → Models → Services separation | PaymongoController uses PaymongoService |
| **DI** | Constructor injection via service container | PaymongoService injected into controllers |
| **Composition** | Traits instead of deep inheritance | HasFactory trait added to models |
| **Type Safety** | Type hints, enums, property casting | Role enum, method return types |

---

## Directory Structure

```
JBRBS/
├── app/                    # Application core logic
├── bootstrap/              # Application bootstrapping
├── config/                 # Configuration files
├── database/              # Migrations, seeders, factories
├── public/                # Public web root
├── resources/             # Views, CSS, JS, icons
├── routes/                # Route definitions
├── storage/               # File storage, logs, cache
├── tests/                 # Feature and unit tests
├── vendor/                # Composer dependencies
├── php/                   # Additional PHP utilities/images
└── [root files]           # Composer, package.json, etc.
```

---

## Core Application Layers

### `/app` - Application Logic

The heart of the application containing all business logic, models, controllers, and services.

#### **`/app/Console/`** - Artisan Commands
Custom console commands for scheduled tasks.

- **`Commands/SendBookingReminder.php`**
  - **Purpose**: Automated email reminder system
  - **Feature**: Sends payment reminder emails to guests with outstanding balances
  - **Schedule**: Checks bookings within 14 days of check-in
  - **Email Integration**: Uses `BookingReminderMail` mailable class
  - **Command**: `php artisan reminder:send-booking`

- **`Commands/SendBookingConfirmation.php`**
  - **Purpose**: Send booking confirmation emails
  - **Feature**: Confirms successful bookings with payment details

- **`Commands/ClearGuestData.php`**
  - **Purpose**: Data cleanup utility
  - **Feature**: Removes stale or test guest data

#### **`/app/Enums/`** - Type-Safe Enumerations
Modern PHP 8.2 enums for type safety and code clarity.

- **`Role.php`** - User roles (Admin, Staff)
- **`PaymentMethod.php`** - Payment types (Cash, Online, GCash, BDO, etc.)
- **`ItemCategory.php`** - Inventory categories (Cleaning, Kitchen, Amenity, Rental)
- **`RentalStatus.php`** - Rental states (Issued, Returned, Lost/Damaged)
- **`FeeType.php`** - Additional charge types (Damage, Loss, Late, etc.)
- **`StockMovementType.php`** - Inventory movements (In, Out, Adjustment)
- **`PurchaseOrderStatus.php`** - PO workflow states
- **`ItemCondition.php`** - Item quality states
- **`SubCategory.php`** - Detailed item classifications
- **`RateType.php`** - Rental pricing models (Per-Day, Flat Rate)

#### **`/app/Http/Controllers/`** - Request Handlers

##### **Public Controllers** (Guest-facing)

- **`BookingController.php`**
  - **Purpose**: Multi-step booking flow for guests
  - **Features**:
    - Step 1: Check availability calendar (booked dates, closed dates)
    - Step 2: Personal details form (name, email, phone, address)
    - Step 3: Booking details (package, dates, pax, children, seniors)
    - Session management for booking flow
    - Automatic booking ID generation (`B001`, `B002`, etc.)
  - **Key Methods**:
    - `checkAvailability()` - Display calendar with booked/closed dates
    - `personalDetails()`, `storePersonalDetails()` - Capture guest info
    - `bookingDetails()`, `storeBookingDetails()` - Finalize booking
    - `resetBooking()` - Clear session and restart flow

- **`CheckAvailabilityController.php`**
  - **Purpose**: Real-time availability checking
  - **Features**: Validates date ranges against bookings and closed dates
  - **Prevents**: Double bookings and reservations on closed dates

- **`PaymongoController.php`** ⭐ **PAYMONGO INTEGRATION**
  - **Purpose**: Handle PayMongo checkout sessions and payment callbacks
  - **Features**:
    - Creates PayMongo Checkout Sessions (hosted payment page)
    - Supports GCash and BDO Online Banking
    - Success/cancel URL handling
    - Webhook integration for payment status updates
    - Automatic booking and payment record creation
  - **Key Methods**:
    - `createLink()` - Create checkout session with payment metadata
    - `success()` - Handle successful payment callback, create booking & payment records
    - `cancel()` - Handle payment cancellation, restore session state
    - `webhook()` - Process PayMongo webhook events for status updates
  - **Important**: Uses `PaymongoService` for API communication
  - **Payment Flow**:
    1. Guest fills booking form → session stored
    2. `createLink()` generates PayMongo checkout URL
    3. Guest redirects to PayMongo (GCash/BDO)
    4. Payment success → `success()` creates booking, payment, transaction
    5. Email sent via `BookingConfirmationMail`
  - **Related Files**: `app/Services/PaymongoService.php`, `config/paymongo.php`

- **`PackageController.php`**
  - **Purpose**: Display available booking packages
  - **Features**: Shows resort packages with pricing and amenities

- **`ProfileController.php`**
  - **Purpose**: User profile management
  - **Features**: Update profile info, password changes

##### **Admin Controllers** (`/app/Http/Controllers/Admin/`)

- **`DashboardController.php`**
  - **Purpose**: Admin overview dashboard
  - **Features**:
    - Real-time KPIs (bookings, revenue, guests)
    - Chart data (monthly trends, revenue graphs)
    - Recent bookings and upcoming check-ins
    - Date range filtering with presets

- **`BookingController.php`**
  - **Purpose**: Complete booking management system
  - **Features**:
    - View all bookings with advanced filtering (status, payment, search)
    - Manual booking creation for walk-ins
    - Check-in/check-out processing
    - Senior discount application (20% per senior)
    - No-show marking with email notifications
    - Payment status tracking (Fully Paid, Partial, Downpayment, Unpaid)
    - Booking status workflow (Pending → Booked → Staying → Completed/Cancelled)
    - Outstanding balance calculations
    - Export booking details
  - **Key Methods**:
    - `index()` - List all bookings with filters
    - `create()`, `store()` - Manual booking creation
    - `checkIn()` - Mark guest as staying, record actual check-in time
    - `checkOut()` - Process check-out, apply senior discounts, calculate excess fees
    - `markNoShow()` - Handle no-shows, send email notifications
    - `cancelBooking()` - Cancel bookings with payment handling

- **`CurrentlyStayingController.php`**
  - **Purpose**: Manage guests currently at the resort
  - **Features**:
    - Real-time view of active guests
    - Quick access to rental and unpaid items
    - Check-out initiation from staying page

- **`RentalsController.php`**
  - **Purpose**: Equipment rental management system
  - **Features**:
    - Rental dashboard with KPIs (revenue, damage rate, popular items)
    - Issue rentals to guests (quantity, rate type)
    - Return processing (condition check: Good, Damaged, Lost)
    - Damage/loss fee calculation and application
    - Rate types: Per-Day (auto-calculates days) or Flat Rate
    - Rental catalog management (items, rates, descriptions, images)
    - Revenue tracking including all fees
    - Popular items analysis
  - **Key Methods**:
    - `dashboard()` - KPIs, revenue trends, popular items
    - `issueRental()` - Create new rental record
    - `returnRental()` - Process returns, apply damage/loss fees
    - `catalog()` - Manage rental items (CRUD)
    - `getPopularItems()` - Top rented items by revenue

- **`SalesController.php`** ⭐ **REPORT GENERATION**
  - **Purpose**: Comprehensive sales reporting and analytics
  - **Features**:
    - **Sales Dashboard**: Real-time KPIs, revenue trends, payment breakdown
    - **Transactions Ledger**: Unified view of all transactions (bookings, rentals, store)
    - **Report Generation** (PDF/CSV/JSON formats):
      1. **Per Booking Report** - Detailed breakdown of single booking
      2. **Monthly Report** - All sales for a specific month
      3. **Annual Report** - Yearly sales summary with monthly breakdown
      4. **Custom Report** - Flexible date range with daily sales
    - **PDF Generation**: Uses DomPDF (Barryvdh package)
    - **Transaction Types**: Booking Payment, Rental Fee, Store Purchase
    - **Voiding Support**: Track voided payments with reasons
  - **Key Methods**:
    - `dashboard()` - Sales overview with KPIs and charts
    - `ledger()` - Transaction history view
    - `reports()` - Report selection interface
    - `generatePerBookingReport()` - Single booking detailed report (package, rentals, store items, payments)
    - `generateMonthlyReport()` - Month-based sales aggregation
    - `generateAnnualReport()` - Yearly sales with month-by-month breakdown
    - `generateCustomReport()` - Custom date range analysis
    - `generatePerBookingPDF()` - PDF generation using DomPDF
    - `getLedgerData()` - API endpoint for transaction data
  - **Report Contents**:
    - Booking charges (package cost, excess fees, senior discounts)
    - Rental charges (base rental + damage/loss fees)
    - Store purchases (unpaid items marked as paid)
    - Payment history with methods and status
    - Grand totals and breakdowns
  - **Related Files**: 
    - `resources/views/admin/sales/pdf/*.blade.php` (PDF templates)
    - `vendor/barryvdh/laravel-dompdf/` (PDF library)

- **`InventoryController.php`**
  - **Purpose**: Complete inventory management system
  - **Features**:
    - Inventory dashboard (stock value, turnover rate, low stock alerts)
    - Item management (CRUD) with categories and subcategories
    - Purchase entry system (vendor, items, quantities, costs)
    - Stock movement tracking (in, out, adjustments)
    - Low stock monitoring with configurable thresholds
    - Average cost calculation (FIFO method)
    - Stock by category charts
  - **Categories**: Cleaning, Kitchen, Amenity, Rental Item
  - **Key Methods**:
    - `index()` - Dashboard with KPIs and alerts
    - `items()` - Item listing and management
    - `storePurchaseEntry()` - Record new purchases
    - `adjustStock()` - Manual stock adjustments
    - `getLowStockItems()` - Items below reorder point

- **`PackageController.php`**
  - **Purpose**: Manage resort packages (room types, rates)
  - **Features**: CRUD operations for booking packages

- **`SettingsController.php`**
  - **Purpose**: System configuration management
  - **Features**:
    - Manage closed dates (resort closures)
    - User account management
    - System preferences
    - Email template customization

- **`AdminPaymongoController.php`**
  - **Purpose**: Admin-side PayMongo payment management
  - **Features**: Initiate payments for existing bookings, view payment links

##### **Auth Controllers** (`/app/Http/Controllers/Auth/`)
Laravel Breeze authentication scaffolding:
- `AuthenticatedSessionController.php` - Login/logout
- `RegisteredUserController.php` - User registration
- `PasswordResetLinkController.php` - Password reset requests
- `NewPasswordController.php` - Password reset processing
- `EmailVerificationNotificationController.php` - Email verification
- `ConfirmablePasswordController.php` - Password confirmation
- `VerifyEmailController.php` - Email verification handling
- `PasswordController.php` - Password update

#### **`/app/Http/Middleware/`** - Request Filters

- **`AdminMiddleware.php`**
  - **Purpose**: Protect admin routes
  - **Features**: Checks if authenticated user has admin role

- **`AdminOnlyMiddleware.php`**
  - **Purpose**: Restrict access to admin-only features
  - **Features**: Additional admin permission layer

- **`Authenticate.php`**
  - **Purpose**: Laravel's default authentication guard
  - **Features**: Redirects unauthenticated users to login

#### **`/app/Models/`** - Database Models (Eloquent ORM)

- **`User.php`**
  - **Purpose**: User accounts (admin, staff)
  - **Fields**: name, email, password, role (enum)
  - **Relationships**: Processed payments, processed transactions

- **`Guest.php`**
  - **Purpose**: Customer/guest records
  - **Fields**: GuestID, FName, MName, LName, Email, Phone, Address
  - **Relationships**: bookings (hasMany)
  - **Auto-generation**: GuestID format `G001`, `G002`, etc.

- **`Booking.php`**
  - **Purpose**: Resort reservation records
  - **Fields**: 
    - BookingID (auto: `B001`, `B002`)
    - GuestID, PackageID
    - CheckInDate, CheckOutDate
    - ActualCheckInTime, ActualCheckOutTime
    - BookingStatus (Pending, Booked, Staying, Completed, Cancelled)
    - Pax, NumOfChild, NumOfSeniors, NumOfAdults
    - ExcessFee, senior_discount, actual_seniors_at_checkout
  - **Relationships**: guest, package, payments, rentals, unpaidItems
  - **Methods**: 
    - `generateBookingID()` - Sequential ID generation
    - Status transitions and validations

- **`Payment.php`**
  - **Purpose**: Payment transaction records
  - **Fields**:
    - PaymentID (auto: `PY001`, `PY002`)
    - BookingID, Amount, TotalAmount
    - PaymentMethod (enum: Cash, GCash, BDO, PayMongo)
    - PaymentStatus (Paid, Pending, Cancelled)
    - PaymentPurpose (Downpayment, Partial Payment, Full Payment)
    - ReferenceNumber, NameOnAccount, AccountNumber
    - Voiding fields: is_voided, voided_at, voided_by, void_reason
    - Bill-out fields: amount_received, change_amount, total_outstanding
    - processed_by (user ID)
  - **Relationships**: booking, processedBy (user)
  - **Important**: Never reuses voided payment IDs (incremental generation)

- **`Transaction.php`**
  - **Purpose**: Unified transaction ledger for all payment types
  - **Fields**:
    - transaction_type (booking_payment, rental_fee, store_purchase)
    - reference_id (links to Payment/Rental/UnpaidItem)
    - transaction_date, amount, payment_method
    - booking_id, guest_id, rental_id
    - customer_name, customer_email, customer_phone
    - processed_by, processor_name
    - metadata (JSON for additional data)
    - Voiding: is_voided, voided_at, voided_by, void_reason
  - **Purpose**: Single source of truth for all financial transactions
  - **Scopes**: `notVoided()`, `byDateRange()`, `byType()`

- **`Package.php`**
  - **Purpose**: Resort accommodation packages
  - **Fields**: PackageID, Name, Description, Price, MaxPax, amenities
  - **Relationships**: bookings

- **`Rental.php`**
  - **Purpose**: Equipment rental records
  - **Fields**:
    - rental_item_id, BookingID
    - quantity, rate_snapshot, rate_type_snapshot (Per-Day, Flat Rate)
    - issued_at, returned_at, return_condition
    - status (Issued, Returned, Lost/Damaged)
    - processed_by, is_paid
  - **Relationships**: rentalItem, booking, fees
  - **Methods**: `calculateTotalCharges()` - Sums rental fee + all damage/loss fees

- **`RentalItem.php`**
  - **Purpose**: Rental catalog (kayaks, bikes, etc.)
  - **Fields**: name, description, rate, rate_type, quantity_available, image
  - **Relationships**: rentals

- **`RentalFee.php`**
  - **Purpose**: Additional rental charges (damage, loss, late fees)
  - **Fields**: rental_id, type (enum), amount, reason, assessed_by
  - **Relationships**: rental

- **`InventoryItem.php`**
  - **Purpose**: Stock items (cleaning supplies, kitchen items, amenities)
  - **Fields**:
    - name, sku, category (enum), sub_category
    - quantity_on_hand, reorder_level, average_cost
    - last_purchased_at
  - **Relationships**: stockMovements, purchaseEntryItems
  - **Methods**: `isLowStock()` - Check if below reorder level

- **`PurchaseEntry.php`**
  - **Purpose**: Purchase order records
  - **Fields**: entry_number, vendor, purchase_date, total_amount, status, notes
  - **Relationships**: items (PurchaseEntryItem), stockMovements

- **`PurchaseEntryItem.php`**
  - **Purpose**: Line items in purchase orders
  - **Fields**: purchase_entry_id, inventory_item_id, quantity, unit_cost, total_cost
  - **Relationships**: purchaseEntry, inventoryItem

- **`StockMovement.php`**
  - **Purpose**: Inventory movement audit trail
  - **Fields**: inventory_item_id, movement_type (in/out), quantity, reason, purchase_entry_id
  - **Relationships**: inventoryItem, purchaseEntry

- **`UnpaidItem.php`**
  - **Purpose**: Store purchases by guests (snacks, drinks, etc.)
  - **Fields**: BookingID, ItemName, Quantity, UnitPrice, TotalAmount, IsPaid
  - **Relationships**: booking

- **`ClosedDate.php`**
  - **Purpose**: Resort closure dates (maintenance, holidays)
  - **Fields**: closed_date, reason
  - **Usage**: Blocks bookings on specified dates

- **`Amenity.php`**
  - **Purpose**: Resort amenities/facilities
  - **Fields**: AmenityID, AmenityName, AmenityDescription

- **`Log.php`**
  - **Purpose**: System activity logging
  - **Fields**: action, user_id, details, timestamp

#### **`/app/Services/`** - Business Logic Services

- **`PaymongoService.php`** ⭐ **PAYMONGO API INTEGRATION**
  - **Purpose**: Encapsulates all PayMongo API communication
  - **Features**:
    - **HTTP Client**: Guzzle with Basic Auth (secret key)
    - **Checkout Session Creation**: Single-use hosted payment pages
    - **Payment Link Creation**: Reusable payment links (legacy support)
    - **Session Retrieval**: Get checkout session details by ID
    - **Method Restriction**: Limits payment methods to GCash + BDO Online Banking
    - **Error Handling**: Comprehensive logging and exception throwing
  - **Key Methods**:
    - `createCheckoutSession(amount, description, metadata, methods, successUrl, cancelUrl, refNumber)`
      - Creates PayMongo Checkout Session
      - Returns `['checkout_url' => '...', 'id' => '...']`
      - Automatically retries with fallback method types if BDO unsupported
    - `createPaymentLink(amount, description, metadata)`
      - Creates reusable payment link (older API)
      - Returns checkout URL
    - `getCheckoutSession(sessionId)`
      - Retrieves session details for verification
  - **Configuration**: Uses `config/paymongo.php`
  - **API Endpoint**: `https://api.paymongo.com/v1`
  - **Authentication**: Basic Auth with base64-encoded secret key
  - **Amount Format**: All amounts in centavos (multiply by 100)

#### **`/app/Mail/`** - Email Templates (Mailables) ⭐ **SMTP INTEGRATION**

All email classes use Laravel's Mailable system with SMTP configuration from `config/mail.php`.

- **`BookingConfirmationMail.php`**
  - **Purpose**: Sent after successful booking/payment
  - **Triggers**: When payment is confirmed (online or cash)
  - **Contains**: Booking ID, guest name, check-in/out dates, payment details, package info
  - **Template**: `resources/views/emails/booking-confirmation.blade.php`
  - **Subject**: "Booking Confirmation - JBRB Resort"

- **`BookingReminderMail.php`**
  - **Purpose**: Automated payment reminder for guests with balance
  - **Triggers**: Console command `SendBookingReminder` (scheduled daily)
  - **Condition**: Sent 14 days before check-in if remaining balance > 0
  - **Contains**: Remaining balance, check-in date, payment instructions
  - **Template**: `resources/views/emails/balance-reminder.blade.php`
  - **Subject**: "Payment Reminder: Remaining Balance"

- **`NoShowNotification.php`**
  - **Purpose**: Notify admins when guest is marked as no-show
  - **Triggers**: Admin marks booking as no-show
  - **Contains**: Booking details, guest contact, no-show timestamp
  - **Template**: `resources/views/emails/no-show-notification.blade.php`
  - **Subject**: "Booking No Show Notification - [BookingID]"

- **`UndoNoShowNotification.php`**
  - **Purpose**: Notify admins when no-show status is reversed
  - **Triggers**: Admin undoes no-show marking
  - **Contains**: Booking details, restoration timestamp
  - **Template**: `resources/views/emails/undo-no-show-notification.blade.php`
  - **Subject**: "No Show Status Reversed - [BookingID]"

**Email Configuration**:
- **SMTP Setup**: Configured in `config/mail.php`
- **Environment Variables**: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- **Default Mailer**: Set to 'log' in development, 'smtp' in production
- **Queue Support**: Uses Laravel's queue system for async sending
- **Markdown Templates**: All emails use Laravel's markdown mailable for responsive design

#### **`/app/Notifications/`** - System Notifications

- **`AdminResetPasswordNotification.php`**
  - **Purpose**: Admin-specific password reset notification
  - **Features**: Custom template for admin password resets

#### **`/app/Providers/`** - Service Providers

- **`AppServiceProvider.php`**
  - **Purpose**: Application service container bindings
  - **Features**: Register singletons, bind interfaces, boot services

#### **`/app/View/`** - View Composers
- **Purpose**: Share data across multiple views
- **Features**: Inject common data into blade templates

---

## Key Features & Modules

### 1. **PayMongo Payment Integration** ⭐

**How It Works**:
1. Guest completes booking form (session stored)
2. PaymentController creates PayMongo Checkout Session via PaymongoService
3. Guest redirects to PayMongo hosted page (same tab)
4. Guest pays via GCash or BDO Online Banking
5. PayMongo redirects back to success URL
6. Success callback:
   - Creates Booking record in database
   - Creates Payment record with PayMongo reference
   - Creates Transaction record for ledger
   - Sends BookingConfirmationMail to guest
7. Optional webhook updates payment status

**Files Involved**:
- `app/Http/Controllers/PaymongoController.php` - Payment flow orchestration
- `app/Services/PaymongoService.php` - API communication
- `config/paymongo.php` - Configuration (keys, URLs, methods)
- `routes/web.php` - Payment routes
- `.env` - API credentials

**Payment Methods Supported**:
- GCash (e-wallet)
- BDO Online Banking
- Fallback to GCash-only if BDO unavailable

**Security**:
- Basic Auth with secret key
- Webhook signature verification (optional)
- HTTPS required for production

### 2. **SMTP Email System** ⭐

**Configuration**:
- File: `config/mail.php`
- Environment: `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- Supports: Gmail, SendGrid, Mailgun, Amazon SES, custom SMTP

**Email Types**:
1. **Booking Confirmation** - Instant after payment
2. **Balance Reminder** - Automated 14 days before check-in
3. **No-Show Alert** - Admin notification
4. **Undo No-Show** - Admin notification

**Scheduled Tasks**:
- `SendBookingReminder` command runs daily via Laravel Scheduler
- Add to `app/Console/Kernel.php`: `$schedule->command('reminder:send-booking')->daily();`

**Testing Emails**:
- Development: Set `MAIL_MAILER=log` to write emails to `storage/logs/laravel.log`
- Use Mailtrap or MailHog for local testing

### 3. **Report Generation (PDF/CSV)** ⭐

**Report Types**:

1. **Per Booking Report**
   - Complete breakdown of single booking
   - Package costs, rentals, store items, payments
   - Damage/loss fees included
   - Format: PDF (A4 portrait)

2. **Monthly Report**
   - All bookings for selected month
   - Daily sales breakdown
   - Payment method distribution
   - Package performance
   - Format: PDF/JSON

3. **Annual Report**
   - Yearly summary with monthly breakdown
   - Revenue trends
   - Booking statistics
   - Format: PDF/CSV

4. **Custom Report**
   - Flexible date range
   - Daily sales granularity
   - Rental item sales
   - Transaction ledger
   - Format: PDF/JSON/CSV

**PDF Generation**:
- **Library**: DomPDF (Barryvdh Laravel package)
- **Templates**: Blade files in `resources/views/admin/sales/pdf/`
- **Styling**: Inline CSS for PDF compatibility
- **Paper**: A4 portrait with 5mm margins
- **Method**: `Pdf::loadView($view, $data)->download($filename)`

**Access**:
- Admin → Sales → Reports
- Select report type, date range, format
- Generate and download

### 4. **Multi-Step Booking Flow**

**Steps**:
1. **Check Availability** - Calendar with booked/closed dates
2. **Personal Details** - Guest information form
3. **Booking Details** - Package selection, dates, pax
4. **Payment** - PayMongo checkout
5. **Confirmation** - Email sent, booking created

**Session Management**:
- Data stored in Laravel sessions
- Persists across steps
- Cleared after confirmation or manual reset

### 5. **Rental Management System**

**Features**:
- Issue rentals to guests (quantity, rate)
- Return processing (Good, Damaged, Lost)
- Automatic fee calculation (damage, loss)
- Rate types: Per-Day (calculates days), Flat Rate
- Revenue tracking with all fees
- Popular items analysis

**Fee Application**:
- Admin assesses condition on return
- Damage fee: Configurable per item
- Loss fee: Full item replacement cost
- Fees added to booking total

### 6. **Inventory Management**

**Features**:
- Track stock levels (cleaning, kitchen, amenity, rental items)
- Purchase entry system with vendor tracking
- Stock movements (in, out, adjustments)
- Average cost calculation (FIFO)
- Low stock alerts with reorder levels
- Category-based organization

**Stock Movement Reasons**:
- Purchase, Usage, Damage, Loss, Adjustment, Return

### 7. **Transaction Ledger**

**Purpose**: Unified financial record of all transactions

**Transaction Types**:
1. **Booking Payment** - Package fees, excess fees, discounts
2. **Rental Fee** - Equipment rentals + damage/loss fees
3. **Store Purchase** - Guest purchases (food, drinks, supplies)

**Features**:
- Single source of truth for all revenue
- Voiding support with audit trail
- Links to source records (Payment, Rental, UnpaidItem)
- Processor tracking (which staff member processed)
- Metadata storage for additional context

**Access**: Admin → Sales → Transactions Ledger

---

## Database Architecture

### **`/database/migrations/`** - Schema Definitions

**Core Tables**:
- `users` - Staff/admin accounts
- `guests` - Customer records
- `packages` - Accommodation types
- `bookings` - Reservations
- `payments` - Payment transactions
- `transactions` - Unified financial ledger
- `amenities` - Resort facilities
- `closed_dates` - Unavailable dates

**Rental System**:
- `rental_items` - Rental catalog
- `rentals` - Rental records
- `rental_fees` - Additional charges

**Inventory System**:
- `inventory_items` - Stock items
- `purchase_entries` - Purchase orders
- `purchase_entry_items` - PO line items
- `stock_movements` - Inventory changes

**Additional**:
- `unpaid_items` - Guest store purchases
- `cache`, `jobs` - Laravel infrastructure

**Migration Files**:
- Chronologically ordered with timestamps
- Run with `php artisan migrate`
- Rollback with `php artisan migrate:rollback`

### **`/database/seeders/`** - Sample Data
- Development data population
- Test accounts and packages

### **`/database/factories/`** - Model Factories
- Generate fake data for testing
- Used with PHPUnit tests

---

## Configuration Files

### **`/config/`** - Application Configuration

- **`app.php`**
  - Application name, environment, debug mode
  - Timezone, locale, encryption key
  - Service providers

- **`database.php`**
  - Database connections (MySQL default)
  - Connection pooling, read/write splitting

- **`mail.php`** ⭐ **SMTP CONFIGURATION**
  - Default mailer (smtp, log, mailgun, ses)
  - SMTP host, port, username, password, encryption
  - From address and name
  - Markdown email theme settings

- **`paymongo.php`** ⭐ **PAYMONGO CONFIGURATION**
  - Secret key, public key
  - Base URL: `https://api.paymongo.com/v1`
  - Webhook secret for signature verification
  - BDO method type: `online_banking_bdo`
  - Default payment method types: `['gcash', 'online_banking_bdo']`

- **`auth.php`**
  - Authentication guards and providers
  - Password reset settings

- **`cache.php`**
  - Cache drivers (file, redis, memcached)
  - Cache key prefix

- **`filesystems.php`**
  - Disk configurations (local, public, s3)
  - Default upload disk

- **`logging.php`**
  - Log channels (single, daily, stack)
  - Log level configuration

- **`queue.php`**
  - Queue driver (sync, database, redis)
  - Queue connection settings

- **`services.php`**
  - Third-party service credentials
  - API keys for external services

- **`session.php`**
  - Session driver, lifetime, cookie settings
  - Secure session configuration

---

## Routes

### **`/routes/web.php`** - Public Routes
- Landing page
- Booking flow (check availability, personal details, booking details)
- PayMongo payment routes (create, success, cancel, webhook)
- Public package listing

### **`/routes/admin.php`** - Admin Routes
All routes prefixed with `/admin` and protected by `admin` middleware:
- Dashboard
- Booking management (CRUD, check-in, check-out, no-show)
- Currently staying guests
- Rentals (dashboard, issue, return, catalog)
- Inventory (dashboard, items, purchases, stock movements)
- Sales (dashboard, ledger, reports)
- Settings (closed dates, user management)
- Package management

### **`/routes/auth.php`** - Authentication Routes
Laravel Breeze authentication:
- Login, logout, register
- Password reset, email verification
- Two-factor authentication (if enabled)

### **`/routes/console.php`** - Console Routes
Artisan command definitions and closures

---

## Public Assets

### **`/public/`** - Web Root

- **`index.php`** - Application entry point (Laravel bootstrap)
- **`robots.txt`** - Search engine instructions
- **`.htaccess`** - Apache server configuration

**Asset Directories**:
- **`/public/css/`** - Compiled stylesheets (Vite output)
- **`/public/js/`** - Compiled JavaScript (Vite output)
- **`/public/images/`** - Uploaded images, logos, banners
- **`/public/icons/`** - Favicons, app icons

**Test Files** (remove in production):
- `test_api.html` - PayMongo API testing interface
- `test_api_direct.php` - Direct API testing
- `test_booking.html` - Booking flow testing
- `toggle-test.html` - UI component testing

---

## Resources

### **`/resources/views/`** - Blade Templates

**Structure**:
- **`layouts/`** - Master layouts (guest, admin, app)
- **`components/`** - Reusable UI components
- **`auth/`** - Authentication pages (login, register, password reset)
- **`bookings/`** - Guest booking flow views
- **`admin/`** - Admin panel views
  - `bookings/` - Booking management
  - `sales/` - Sales dashboard, ledger, reports
    - `pdf/` - PDF report templates ⭐
  - `inventory/` - Inventory management
  - `rentals/` - Rental management
  - `settings/` - System settings
- **`emails/`** - Email templates (markdown) ⭐
- **`partials/`** - Reusable view fragments
- **`payments/`** - Payment flow views
- **`profile/`** - User profile pages

---

## Testing & Utilities

### **`/tests/`** - Automated Tests
- **`Feature/`** - Integration tests
- **`Unit/`** - Unit tests
- Run with `php artisan test` or `vendor/bin/phpunit`

### **Root PHP Test Files** (Development/Debugging)
These are utility scripts for testing specific features:

- **`test_api_endpoints.php`** - PayMongo API testing
- **`test_booking.php`** - Booking creation testing
- **`test_payment_methods.php`** - Payment method validation
- **`test_sales_data.php`** - Sales report data verification
- **`test_transactions.php`** - Transaction ledger testing
- **`test_voiding_rules.php`** - Payment voiding logic
- **`verify_*.php`** - Various verification scripts

**Analysis Scripts**:
- **`analyze_voiding_behavior.php`** - Audit voiding patterns
- **`check_*.php`** - Data integrity checks
- **`final_verification.php`** - Comprehensive system check
- **`fix_*.php`** - Data migration/fix scripts

**Database Scripts**:
- **`clear_guest_data.sql`** - SQL script to clear test data
- **`migrate_transactions.php`** - Migrate old transaction format


## Vendor

### **`/vendor/`** - Composer Dependencies

**Key Packages**:
- **`laravel/framework`** - Laravel core
- **`laravel/breeze`** - Authentication scaffolding
- **`barryvdh/laravel-dompdf`** - PDF generation ⭐
- **`guzzlehttp/guzzle`** - HTTP client (PayMongo) ⭐
- **`laravel/tinker`** - Interactive REPL
- **`laravel/pint`** - Code style fixer
- **`phpunit/phpunit`** - Testing framework

**Install/Update**: `composer install` or `composer update`


### **`.env`** - Environment Variables (Not in version control)

**Critical Variables**:

```env
# Application
APP_NAME="JBRBS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jbrbs
DB_USERNAME=root
DB_PASSWORD=your_password

# PayMongo ⭐
PAYMONGO_SECRET_KEY=sk_live_xxxxxxxxxxxxx
PAYMONGO_PUBLIC_KEY=pk_live_xxxxxxxxxxxxx
PAYMONGO_BASE_URL=https://api.paymongo.com/v1
PAYMONGO_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
PAYMONGO_BDO_METHOD_TYPE=online_banking_bdo

# Mail/SMTP ⭐
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@jbrbs.com
MAIL_FROM_NAME="${APP_NAME}"

# Queue (for async emails)
QUEUE_CONNECTION=database

# Cache
CACHE_DRIVER=file
SESSION_DRIVER=file
```

---

### **Setup**:
```bash
# Clone repository
git clone https://github.com/xB14CKx/JBRBS.git
cd JBRBS

# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Build assets
npm run dev  # Development
npm run build  # Production

# Start server
php artisan serve
```

### **Scheduled Tasks**:
Add to crontab for automated email reminders:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### **Queue Worker** (for async emails):
```bash
php artisan queue:work
```

---

## Security Considerations

1. **PayMongo**:
   - Use live keys in production, test keys in development
   - Enable webhook signature verification
   - HTTPS required for production
   - Never expose secret key in frontend

2. **SMTP**:
   - Use app-specific passwords (Gmail)
   - Enable 2FA on email account
   - Use TLS/SSL encryption
   - Verify from address SPF/DKIM records

3. **Database**:
   - Strong passwords
   - Restrict database user permissions
   - Regular backups
   - Parameterized queries (Eloquent handles this)

4. **Application**:
   - Keep `.env` out of version control (`.gitignore`)
   - Set `APP_DEBUG=false` in production
   - Use CSRF protection (Laravel default)
   - Validate all user input
   - Sanitize file uploads
   - Regular security updates (`composer update`)

### **PayMongo Issues**:
- Check API keys in `.env`
- Verify webhook URL is publicly accessible
- Check logs: `storage/logs/laravel.log`
- Test with PayMongo test cards (see PayMongo docs)

### **Email Not Sending**:
- Verify SMTP credentials
- Check firewall/port 587 access
- Test with `MAIL_MAILER=log` and check logs
- Verify "Less secure apps" disabled (use app passwords)
- Check queue worker if using `QUEUE_CONNECTION=database`

### **PDF Generation Errors**:
- Check DomPDF package installed: `composer require barryvdh/laravel-dompdf`
- Verify blade templates exist in `resources/views/admin/sales/pdf/`
- Check for missing images (use absolute paths or base64)
- Increase PHP memory limit if needed

### **Permission Errors**:
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### **Database Issues**:
- Check connection in `.env`
- Run migrations: `php artisan migrate`
- Check database user permissions
- Verify timezone matches between PHP and MySQL

---

## Summary

JBRBS is a comprehensive Laravel 12 resort management system featuring:

✅ **Multi-step booking flow** with availability checking  
✅ **PayMongo integration** for online payments (GCash, BDO)  
✅ **SMTP email system** with automated reminders  
✅ **PDF report generation** for sales analysis  
✅ **Rental management** with damage/loss fee tracking  
✅ **Inventory management** with purchase entries  
✅ **Transaction ledger** for unified financial records  
✅ **Admin dashboard** with real-time KPIs and charts  
✅ **Role-based access** (Admin, Staff)  
✅ **Voiding support** with audit trails  

**Key Integrations**:
- 💳 **PayMongo** - Payment gateway
- 📧 **SMTP** - Email delivery
- 📄 **DomPDF** - Report generation

**Technologies**: Laravel 12, PHP 8.2, MySQL, Tailwind CSS, Vite, Alpine.js

---

## Support & Maintenance

**Logs**: `storage/logs/laravel.log`  
**Documentation**: This file + Laravel docs (https://laravel.com/docs)  
**PayMongo Docs**: https://developers.paymongo.com/docs  
**DomPDF Package**: https://github.com/barryvdh/laravel-dompdf  

---

*Last Updated: December 10, 2025*
*Prepared by Kaye Mayugba*  
*Version: 2.0*  
*Repository: https://github.com/xB14CKx/JBRBS (Branch: v2.0_cont)*
