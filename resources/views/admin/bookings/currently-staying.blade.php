@extends('layouts.admin')
@section('title', 'Currently Staying')
@push('styles')
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="{{ asset('css/admin/bookings/currently-staying.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin/sweetalert-custom.css') }}">
@endpush

@section('content')
    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Currently Staying Guest</h1>
        </div>

        <!-- Currently Staying Guests Table (moved to top) -->
        <section class="guest-info-section">
            <div class="status-buttons">
                <button class="status-btn checkin-btn" id="checkedInBtn" data-status="checked-in">
                    <i class="fa-solid fa-door-open"></i>
                    Check In
                </button>
                <button class="status-btn checkout-btn" id="checkedOutBtn" data-status="checked-out" disabled>
                    <i class="fas fa-sign-out-alt"></i>
                    Check Out
                </button>
                <button class="status-btn noshow-btn" id="noShowBtn" data-status="no-show">
                    <i class="fas fa-times-circle"></i>
                    No Show
                </button>
            </div>

            @if($currentGuests->count() > 0)
                <div class="currently-staying-table-wrapper">
                    <table class="currently-staying-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Guest Name</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($currentGuests as $booking)
                                <tr
                                    onclick="selectGuest('{{ $booking->BookingID }}', '{{ addslashes($booking->guest->FName ?? 'Guest') }}@if($booking->guest->MName) {{ addslashes($booking->guest->MName) }}@endif {{ addslashes($booking->guest->LName ?? '') }}')">
                                    <td class="booking-id">{{ $booking->BookingID }}</td>
                                    <td>{{ $booking->guest->FName ?? 'Guest' }}@if($booking->guest->MName) {{ $booking->guest->MName }}@endif {{ $booking->guest->LName ?? '' }}</td>
                                    <td>
                                        <div class="date-main">
                                            {{ $booking->CheckInDate ? $booking->CheckInDate->format('M d, Y') : 'N/A' }}
                                        </div>
                                        <div class="date-time">
                                            @if($booking->ActualCheckInTime)
                                                {{ $booking->ActualCheckInTime->format('g:i A') }}
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-main">
                                            {{ $booking->CheckOutDate ? $booking->CheckOutDate->format('M d, Y') : 'N/A' }}
                                        </div>
                                        <div class="date-time">
                                            @if($booking->ActualCheckOutTime)
                                                {{ $booking->ActualCheckOutTime->format('g:i A') }}
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($booking->BookingStatus === 'Confirmed')
                                            <span class="status-badge status-confirmed">
                                                <i class="fas fa-clock"></i>
                                                Confirmed
                                            </span>
                                        @elseif($booking->BookingStatus === 'Staying')
                                            <span class="status-badge status-staying">
                                                <i class="fas fa-check-circle"></i>
                                                Staying
                                            </span>
                                        @elseif($booking->BookingStatus === 'Completed')
                                            <span class="status-badge status-completed">
                                                <i class="fas fa-flag-checkered"></i>
                                                Checked Out
                                            </span>
                                        @elseif($booking->BookingStatus === 'Cancelled')
                                            <span class="status-badge status-cancelled">
                                                <i class="fas fa-times-circle"></i>
                                                No Show
                                            </span>
                                        @else
                                            <span class="status-badge status-confirmed">
                                                <i class="fas fa-clock"></i>
                                                {{ $booking->BookingStatus }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No guests currently staying</h3>
                    <p>There are no guests checked in at the moment.</p>
                </div>
            @endif
        </section>

        <!-- Content Layout -->
        <div class="content-layout">
            <div class="left-column">
                <div class="booking-details-section">
                    <div class="booking-details-placeholder">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>Booking Information</h3>
                        <p class="booking-details-subtext">Select a guest to view booking details</p>
                    </div>
                </div>
            </div>

            <!-- Right Column - Remaining Balance -->
            <div class="right-column">
                <div class="balance-section">
                    <div class="balance-header-buttons">
                        <button class="balance-action-btn return-rentals-btn" id="returnRentalsBtn" disabled>
                            <i class="fas fa-undo"></i>
                            Return Rented Items
                        </button>
                        <button class="balance-action-btn bill-out-btn" id="billOutBtn" disabled>
                            <i class="fas fa-receipt"></i>
                            Bill Out
                        </button>
                    </div>
                    <div id="balance-content">
                        <div class="balance-placeholder">
                            <i class="fas fa-calculator"></i>
                            <h3>Remaining Balance</h3>
                            <p class="balance-details-subtext">Select a guest to view balance details</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Accompanying Guests Modal - TEMPORARILY HIDDEN FOR DEMO -->
    <!-- <div class="modal-overlay" id="addAccompanyingGuestsModal">
        <div class="new-booking-modal" style="max-width: 900px;">
            <div class="modal-header">
                <h2 id="accompanyingModalTitle">Check In: Record Accompanying Guests</h2>
                <button class="modal-close" onclick="closeAccompanyingGuestsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-section">
                    <div
                        style="background: #ecfdf5; padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #10b981; text-align: center;">
                        <div style="font-weight: 600; color: #065f46; font-size: 1.1rem;">
                            <i class="fas fa-users" style="margin-right: 8px;"></i>
                            Total Guests Expected:
                            <span id="totalExpectedGuestsLabel"
                                style="font-size: 1.8rem; color: #059669; margin-left: 8px;">0</span>
                        </div>
                        <small style="color: #047857; display: block; margin-top: 4px;">
                            (Adults + Seniors + Children from Booking)
                        </small>
                    </div>
                    <div style="margin-bottom: 20px; text-align: center; font-size: 1.1rem; color: #374151;">
                        Guests Recorded: <strong id="currentGuestCount">1</strong> <span style="color: #6b7280;">(Including
                            Main Guest)</span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="guestFirstName" class="form-input" placeholder="Enter First Name"
                                oninput="capitalizeProper(this)">
                        </div>
                        <div class="form-group">
                            <label>Last Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="guestLastName" class="form-input" placeholder="Enter Last Name"
                                oninput="capitalizeProper(this)">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Gender <span style="color: #ef4444;">*</span></label>
                            <select id="guestGender" class="form-input">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Type <span style="color: #ef4444;">*</span></label>
                            <select id="guestType" class="form-input">
                                <option value="">Select Type</option>
                                <option value="Regular">Regular (Adult)</option>
                                <option value="Senior">Senior</option>
                                <option value="Children">Children (6 Years & Below - Free)</option>
                            </select>
                        </div>
                    </div>

                    <div style="text-align: right; margin: 20px 0;">
                        <button class="btn-primary" onclick="addGuestTemp()">
                            <i class="fas fa-plus"></i> Add to List
                        </button>
                    </div>

                    <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                        <table class="currently-staying-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="accompanyingGuestsList"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeAccompanyingGuestsModal()">Cancel Check In</button>
                <button class="btn-warning" id="skipGuestDetailsBtn" style="margin-right: 10px;">
                    <i class="fas fa-forward"></i> Skip Guest Details
                </button>
                <button class="btn-primary" onclick="saveAccompanyingGuests()">
                    <i class="fa-solid fa-door-open"></i> Complete Check In
                </button>
            </div>
        </div>
    </div>
    </div>
    <!-- Add New Item Modal -->
    <div class="modal-overlay" id="addItemModal">
        <div class="new-booking-modal">
            <div class="modal-header">
                <h2>Add New Item</h2>
                <button class="modal-close" onclick="closeAddItemModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-section">
                    <div class="form-group">
                        <label for="itemName">Item Name</label>
                        <input type="text" id="itemName" class="form-input" placeholder="Enter item name">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="itemQuantity">Quantity</label>
                            <input type="number" id="itemQuantity" class="form-input" value="1" min="1">
                        </div>
                        <div class="form-group">
                            <label for="itemPrice">Price (₱)</label>
                            <input type="number" id="itemPrice" class="form-input" placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="item-summary"
                        style="margin-top: 20px; padding: 15px; background-color: #f3f4f6; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-weight: 600;">Total:</span>
                            <span id="itemTotal" style="font-weight: 700; font-size: 18px; color: #10b981;">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeAddItemModal()">Cancel</button>
                <button class="btn-primary" onclick="confirmAddItem()">Add Item</button>
            </div>
        </div>
    </div>

    <!-- Bill Out Modal -->
    <div class="modal-overlay" id="billOutModal">
        <div class="new-booking-modal">
            <div class="modal-header">
                <h2>Bill Out</h2>
                <button class="modal-close" onclick="closeBillOutModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-section">
                    <!-- Total Outstanding Balance Summary -->
                    <div class="bill-out-total-card"
                        style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 2px solid #fbbf24; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div>
                                <div style="font-size: 14px; font-weight: 600; color: #92400e; margin-bottom: 4px;">
                                    <i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i>
                                    TOTAL OUTSTANDING BALANCE
                                </div>
                                <div style="font-size: 12px; color: #a16207; line-height: 1.4;">
                                    All charges must be settled before checkout
                                </div>
                            </div>
                            <div id="billOutTotalOutstanding"
                                style="font-size: 32px; font-weight: 800; color: #92400e; text-shadow: 0 2px 4px rgba(146, 64, 14, 0.1);">
                                ₱0.00
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 16px;">
                        <i class="fas fa-credit-card" style="color: #8b5cf6; margin-right: 8px;"></i>
                        Payment Information
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="billOutPaymentMethod">Payment Method <span style="color: #ef4444;">*</span></label>
                            <select id="billOutPaymentMethod" class="form-input" onchange="toggleBillOutFields()" required>
                                <option value="">Select Payment Method</option>
                                <option value="Cash">Cash</option>
                                <option value="BDO Bank Transfer">BDO Bank Transfer</option>
                                <option value="BPI Bank Transfer">BPI Bank Transfer</option>
                                <option value="GCash">GCash</option>
                                <option value="GoTyme">GoTyme</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="billOutPurpose">Purpose</label>
                            <input type="text" id="billOutPurpose" class="form-input" value="Bill Out - Full Settlement"
                                readonly style="background-color: #f3f4f6; font-weight: 600;">
                        </div>
                    </div>

                    <!-- Amount Field -->
                    <div class="form-group">
                        <label for="billOutAmount">Settlement Amount (₱) <span style="color: #ef4444;">*</span></label>
                        <div style="position: relative;">
                            <input type="number" id="billOutAmount" class="form-input" placeholder="0.00" step="0.01"
                                min="0" required
                                style="background-color: #f8fafc; font-weight: 700; font-size: 1.2rem; color: #059669; border: 2px solid #10b981; padding-right: 40px;">
                            <i class="fas fa-edit"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #10b981; font-size: 14px; pointer-events: none;"></i>
                        </div>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            You can adjust for partial payments, discounts, or waivers
                        </div>
                    </div>

                    <!-- Cash-only fields -->
                    <div id="billOutCashFields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="billOutAmountReceived">Amount Received (₱) <span
                                        style="color: #ef4444;">*</span></label>
                                <input type="number" id="billOutAmountReceived" class="form-input" placeholder="0.00"
                                    step="0.01" min="0" oninput="calculateChangeBillOut()">
                            </div>
                            <div class="form-group">
                                <label for="billOutChange">Change (₱)</label>
                                <input type="number" id="billOutChange" class="form-input" placeholder="0.00" step="0.01"
                                    readonly style="background-color: #f3f4f6; font-weight: 600;">
                            </div>
                        </div>
                    </div>

                    <!-- Non-Cash Fields -->
                    <div id="billOutNonCashFields" style="display: none;">
                        <div class="form-group">
                            <label for="billOutAccountName">Account Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="billOutAccountName" class="form-input" placeholder="Enter account name">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="billOutAccountNumber">Account Number <span
                                        style="color: #ef4444;">*</span></label>
                                <input type="text" id="billOutAccountNumber" class="form-input"
                                    placeholder="Enter account number">
                            </div>
                            <div class="form-group">
                                <label for="billOutReferenceNumber">Reference/Transaction ID <span
                                        style="color: #ef4444;">*</span></label>
                                <input type="text" id="billOutReferenceNumber" class="form-input"
                                    placeholder="Enter transaction ID">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeBillOutModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn-primary" id="confirmBillOutBtn" onclick="confirmBillOut()">
                    <i class="fas fa-receipt"></i>
                    Process Bill Out (₱<span id="billOutConfirmAmount">0.00</span>)
                </button>
            </div>
        </div>
    </div>

    <!-- Issue Rental Modal -->
    <div class="modal-overlay" id="issueRentalModal">
        <div class="new-booking-modal">
            <div class="modal-header">
                <h2>Issue Rental Item</h2>
                <button class="modal-close" onclick="closeIssueRentalModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-section">
                    <div class="form-group">
                        <label for="rentalBookingId">Booking ID</label>
                        <input type="text" id="rentalBookingId" class="form-input" readonly
                            style="background-color: #f3f4f6; font-weight: 600; color: #111827;">
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            <i class="fas fa-info-circle"></i> Currently staying guest auto-selected
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rentalItemSelect">Rental Item <span style="color: #ef4444;">*</span></label>
                        <select id="rentalItemSelect" class="form-input">
                            <option value="">Select an item...</option>
                        </select>
                        <div id="rentalItemAvailability" class="form-hint" style="margin-top: 8px;"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rentalQuantity">Quantity <span style="color: #ef4444;">*</span></label>
                            <input type="number" id="rentalQuantity" class="form-input" min="1" value="1">
                        </div>
                        <div class="form-group">
                            <label>Rate</label>
                            <div id="rentalRateDisplay"
                                style="padding: 12px; background-color: #f9fafb; border: 2px solid #e5e7eb; border-radius: 8px;">
                                <div style="font-size: 20px; font-weight: 700; color: #059669;">—</div>
                                <div style="font-size: 12px; color: #6b7280;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rentalNotes">Notes</label>
                        <textarea id="rentalNotes" class="form-input" rows="3"
                            placeholder="Optional notes about this rental..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeIssueRentalModal()">Cancel</button>
                <button class="btn-primary" onclick="confirmIssueRental()">
                    <i class="fas fa-check"></i>
                    Issue Rental
                </button>
            </div>
        </div>
    </div>

    <!-- Return Rented Items Modal -->
    <div class="modal-overlay" id="returnRentalsModal">
        <div class="new-booking-modal" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Return Rented Items</h2>
                <button class="modal-close" onclick="closeReturnRentalsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-section">
                    <p style="margin-bottom: 1.5rem; color: #6b7280;">
                        Select the items to return and their condition. Damage fees will be added to the remaining balance.
                    </p>
                    <!-- Rentals Checklist -->
                    <div id="rentalsChecklist" style="margin-bottom: 1rem;">
                        <!-- Dynamically populated with JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeReturnRentalsModal()">Cancel</button>
                <button class="btn-primary" onclick="confirmReturnRentals()">
                    <i class="fas fa-check"></i>
                    Process Returns
                </button>
            </div>
        </div>
    </div>

    <!-- Pay Rentals Only Modal -->
    <div class="modal-overlay" id="payRentalsOnlyModal">
        <div class="new-booking-modal">
            <div class="modal-header">
                <h2>Pay Rentals Only</h2>
                <button class="modal-close" onclick="closePayRentalsOnlyModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-section">
                    <!-- Total Rental Charges Summary -->
                    <div class="bill-out-total-card"
                        style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 2px solid #10b981; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 14px; font-weight: 600; color: #065f46; margin-bottom: 4px;">
                                    <i class="fas fa-dolly" style="margin-right: 6px;"></i>
                                    TOTAL RENTAL CHARGES
                                </div>
                                <div style="font-size: 12px; color: #047857;">
                                    Includes issued items and any damage/lost fees
                                </div>
                            </div>
                            <div id="rentalOnlyTotal" style="font-size: 32px; font-weight: 800; color: #065f46;">
                                ₱0.00
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 16px;">
                        <i class="fas fa-credit-card" style="color: #8b5cf6; margin-right: 8px;"></i>
                        Payment Information
                    </h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rentalPaymentMethod">Payment Method <span style="color: #ef4444;">*</span></label>
                            <select id="rentalPaymentMethod" class="form-input" onchange="toggleRentalNonCashFields()"
                                required>
                                <option value="">Select Payment Method</option>
                                <option value="Cash">Cash</option>
                                <option value="BDO Bank Transfer">BDO Bank Transfer</option>
                                <option value="BPI Bank Transfer">BPI Bank Transfer</option>
                                <option value="GCash">GCash</option>
                                <option value="GoTyme">GoTyme</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rentalPurpose">Purpose</label>
                            <input type="text" id="rentalPurpose" class="form-input" value="Payment - Rentals Only" readonly
                                style="background-color: #f3f4f6; font-weight: 600;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rentalAmount">Amount to Pay (₱) <span style="color: #ef4444;">*</span></label>
                        <input type="number" id="rentalAmount" class="form-input" step="0.01" min="0"
                            style="font-weight: 700; font-size: 1.2rem; color: #059669; border: 2px solid #10b981;">
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            You can accept partial payment for rentals
                        </div>
                    </div>
                    <!-- Cash-only fields -->
                    <div id="rentalCashFields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="rentalAmountReceived">Amount Received (₱) <span
                                        style="color: #ef4444;">*</span></label>
                                <input type="number" id="rentalAmountReceived" class="form-input" placeholder="0.00"
                                    step="0.01" min="0" oninput="calculateChangeRental()">
                            </div>
                            <div class="form-group">
                                <label for="rentalChange">Change (₱)</label>
                                <input type="number" id="rentalChange" class="form-input" placeholder="0.00" step="0.01"
                                    readonly style="background-color: #f3f4f6; font-weight: 600;">
                            </div>
                        </div>
                    </div>
                    <!-- Non-Cash Fields -->
                    <div id="rentalNonCashFields" style="display: none;">
                        <div class="form-group">
                            <label for="rentalAccountName">Account Name <span style="color: #ef4444;">*</span></label>
                            <input type="text" id="rentalAccountName" class="form-input">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="rentalAccountNumber">Account Number <span
                                        style="color: #ef4444;">*</span></label>
                                <input type="text" id="rentalAccountNumber" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="rentalReferenceNumber">Reference/Transaction ID <span
                                        style="color: #ef4444;">*</span></label>
                                <input type="text" id="rentalReferenceNumber" class="form-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closePayRentalsOnlyModal()">Cancel</button>
                <button class="btn-primary" onclick="confirmPayRentalsOnly()">
                    <i class="fas fa-check"></i> Process Rental Payment (₱<span id="rentalConfirmAmount">0.00</span>)
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Cash Payment Handlers -->
    <script src="{{ asset('js/admin/cash-payment-handlers.js') }}"></script>
    <script>
        // SweetAlert2 Configuration with Project Colors
        const SwalConfig = {
            confirmButtonColor: '#284B53',
            cancelButtonColor: '#6b7280',
            customClass: {
                popup: 'swal-custom-popup',
                confirmButton: 'swal-custom-confirm',
                cancelButton: 'swal-custom-cancel'
            }
        };

        let currentBookingId = null;
        let currentGuestName = null;
        let currentTotalOutstanding = 0;
        let activeRentals = [];

        // === Accompanying Guests Variables - TEMPORARILY HIDDEN FOR DEMO ===
        /* let tempGuests = [];
        let expectedAdults = 0;
        let expectedSeniors = 0;
        let expectedChildren = 0;

        // === Accompanying Guests Helper Functions ===
        function countCurrentByType(type) {
            return tempGuests.filter(g => g.guest_type === type).length;
        } */

        /* function capitalizeProper(element) {
            let value = element.value;

            // Convert to proper case: first letter of each word capitalized
            value = value
                .toLowerCase()
                .replace(/\b\w/g, char => char.toUpperCase())  // Capitalize first letter of each word
                .replace(/\s+/g, ' ')   // Normalize multiple spaces to single
                .trim();                // Remove leading/trailing spaces

            element.value = value;

            // Keep cursor at the end of the input
            const cursorPos = value.length;
            element.setSelectionRange(cursorPos, cursorPos);
        } */

        /* function updateTypeDropdownDisabled() {
            const adultOption = document.querySelector('#guestType option[value="Regular"]');
            const seniorOption = document.querySelector('#guestType option[value="Senior"]');
            const childOption = document.querySelector('#guestType option[value="Children"]');

            if (adultOption) adultOption.disabled = expectedAdults > 0 && countCurrentByType('Regular') >= expectedAdults;
            if (seniorOption) seniorOption.disabled = expectedSeniors > 0 && countCurrentByType('Senior') >= expectedSeniors;
            if (childOption) childOption.disabled = expectedChildren > 0 && countCurrentByType('Children') >= expectedChildren;
        } */

        /* function renderGuestList() {
            const tbody = document.getElementById('accompanyingGuestsList');
            if (!tbody) return;
            tbody.innerHTML = '';
            tempGuests.forEach((guest, index) => {
                const typeDisplay = guest.guest_type === 'Regular' ? 'Regular (Adult)' :
                    guest.guest_type === 'Senior' ? 'Senior' :
                        'Children (6 yrs & below - FREE)';
                tbody.innerHTML += `
                                                                                                <tr>
                                                                                                    <td>${index + 1}</td>
                                                                                                    <td>${guest.first_name} ${guest.last_name}</td>
                                                                                                    <td>${guest.gender}</td>
                                                                                                    <td>${typeDisplay}</td>
                                                                                                    <td><button class="btn-danger btn-sm" onclick="removeGuest(${index})"><i class="fas fa-trash"></i></button></td>
                                                                                                </tr>`;
            });
            document.getElementById('currentGuestCount').textContent = tempGuests.length + 1;
            updateTypeDropdownDisabled();
        } */

        /* window.removeGuest = function (index) {
            tempGuests.splice(index, 1);
            renderGuestList();
        }; */

        /* window.addGuestTemp = function () {
            const firstName = document.getElementById('guestFirstName').value.trim();
            const lastName = document.getElementById('guestLastName').value.trim();
            const gender = document.getElementById('guestGender').value;
            const guestType = document.getElementById('guestType').value;

            if (!firstName || !lastName || !gender || !guestType) {
                Swal.fire({ ...SwalConfig, icon: 'warning', title: 'Incomplete', text: 'Please fill all fields' });
                return;
            }

            let typeLabel = ''; let expected = 0;
            if (guestType === 'Regular') { typeLabel = 'adult(s)'; expected = expectedAdults; }
            else if (guestType === 'Senior') { typeLabel = 'senior(s)'; expected = expectedSeniors; }
            else if (guestType === 'Children') { typeLabel = 'child(ren)'; expected = expectedChildren; }

            if (expected > 0 && countCurrentByType(guestType) >= expected) {
                Swal.fire({ ...SwalConfig, icon: 'error', title: 'Maximum Reached', text: `Only ${expected} ${typeLabel} allowed.` });
                return;
            }

            tempGuests.push({ first_name: firstName, last_name: lastName, gender, guest_type: guestType });
            document.getElementById('guestFirstName').value = '';
            document.getElementById('guestLastName').value = '';
            document.getElementById('guestGender').value = '';
            document.getElementById('guestType').value = '';
            renderGuestList();
        }; */

        /* window.openAccompanyingGuestsModalForCheckIn = function (bookingId, guestName) {
            currentBookingId = bookingId;
            currentGuestName = guestName;

            fetch(`/admin/currently-staying/guest-details?booking_id=${bookingId}`, {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        Swal.fire({ ...SwalConfig, icon: 'error', title: 'Error', text: 'Failed to load booking details' });
                        return;
                    }

                    expectedAdults = parseInt(data.booking.NumOfAdults) || 0;
                    expectedSeniors = parseInt(data.booking.NumOfSeniors) || 0;
                    expectedChildren = parseInt(data.booking.NumOfChild) || 0;
                    document.getElementById('totalExpectedGuestsLabel').textContent = expectedAdults + expectedSeniors + expectedChildren;

                    // Load existing companions
                    fetch(`/admin/accompanying-guests/${bookingId}`)
                        .then(r => r.json())
                        .then(resp => { tempGuests = resp.guests || []; renderGuestList(); })
                        .catch(() => { tempGuests = []; renderGuestList(); });

                    document.getElementById('accompanyingModalTitle').textContent = `Check In: Record Accompanying Guests for ${guestName}`;
                    document.getElementById('addAccompanyingGuestsModal').classList.add('show');
                });
        };

        window.closeAccompanyingGuestsModal = function () {
            Swal.fire({
                ...SwalConfig,
                icon: 'warning',
                title: 'Cancel Check In?',
                text: 'The guest will NOT be checked in if you close this.',
                showCancelButton: true,
                confirmButtonText: 'Yes, Cancel',
                cancelButtonText: 'Continue Adding'
            }).then(result => {
                if (result.isConfirmed) {
                    document.getElementById('addAccompanyingGuestsModal').classList.remove('show');
                    tempGuests = [];
                }
            });
        };

        window.saveAccompanyingGuests = function () {
            const proceed = () => {
                if (tempGuests.length > 0) {
                    fetch('/admin/accompanying-guests/store', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ booking_id: currentBookingId, guests: tempGuests })
                    })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) performActualCheckIn();
                            else Swal.fire({ ...SwalConfig, icon: 'error', title: 'Error', text: res.message || 'Failed to save' });
                        })
                        .catch(() => Swal.fire({ ...SwalConfig, icon: 'error', title: 'Error', text: 'Network error' }));
                } else {
                    performActualCheckIn();
                }
            };

            if (tempGuests.length === 0) {
                Swal.fire({
                    ...SwalConfig,
                    icon: 'question',
                    title: 'No Accompanying Guests',
                    text: 'Proceed with check-in without adding companions?',
                    showCancelButton: true
                }).then(result => { if (result.isConfirmed) proceed(); });
            } else {
                proceed();
            }
        }; */

        // Direct check-in function (bypasses accompanying guests modal)
        function performActualCheckIn() {
            updateGuestStatus(currentBookingId, 'Staying');
        }

        // === Display Functions ===
        function updateBookingDetailsDisplay(data) {
            const bookingDetailsSection = document.querySelector('.booking-details-section');
            const booking = data.booking;
            const guest = data.guest;
            const packageData = data.package;

            bookingDetailsSection.innerHTML = `
                                                                                            <div class="booking-info-details">
                                                                                                <div class="info-section">
                                                                                                    <h3 class="info-section-title">Guest Information</h3>
                                                                                                    <div class="guest-info">
                                                                                                        <div class="guest-name">${guest.FName} ${guest.MName ? guest.MName + ' ' : ''}${guest.LName}</div>
                                                                                                        <div class="contact-info">
                                                                                                            <div class="contact-item"><i class="fas fa-envelope"></i> <span>${guest.Email || 'N/A'}</span></div>
                                                                                                            <div class="contact-item"><i class="fas fa-phone"></i> <span>${guest.Phone || 'N/A'}</span></div>
                                                                                                            <div class="contact-item"><i class="fas fa-map-marker-alt"></i> <span>${guest.Address || 'N/A'}</span></div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="info-section">
                                                                                                    <h3 class="info-section-title">Booking Details</h3>
                                                                                                    <div class="booking-details">
                                                                                                        <div class="detail-item"><span class="detail-label">Booking ID</span><span class="detail-value highlight">${booking.BookingID}</span></div>
                                                                                                        <div class="detail-item">
                                                                                                            <span class="detail-label">Check In</span>
                                                                                                            <span class="detail-value">
                                                                                                                ${booking.CheckInDate}
                                                                                                                ${booking.ActualCheckInTime ? `- ${new Date(booking.ActualCheckInTime).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })}` : '(Not checked in yet)'}
                                                                                                            </span>
                                                                                                        </div>
                                                                                                        <div class="detail-item">
                                                                                                            <span class="detail-label">Check Out</span>
                                                                                                            <span class="detail-value">
                                                                                                                ${booking.CheckOutDate}
                                                                                                                ${booking.ActualCheckOutTime ? `- ${new Date(booking.ActualCheckOutTime).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })}` : '(Not checked out yet)'}
                                                                                                            </span>
                                                                                                        </div>
                                                                                                        <div class="detail-item"><span class="detail-label">Days of Stay</span><span class="detail-value highlight">${booking.Days} day${booking.Days > 1 ? 's' : ''}</span></div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="info-section">
                                                                                                    <h3 class="info-section-title">Guest Count</h3>
                                                                                                    <div class="booking-details">
                                                                                                        <div class="detail-item"><span class="detail-label">Adults</span><span class="detail-value">${booking.NumOfAdults || 0}</span></div>
                                                                                                        <div class="detail-item"><span class="detail-label">Seniors</span><span class="detail-value">${booking.NumOfSeniors || 0}</span></div>
                                                                                                        <div class="detail-item"><span class="detail-label">Children (6 years and below - FREE)</span><span class="detail-value">${booking.NumOfChild || 0}</span></div>
                                                                                                        ${booking.ExcessGuests > 0 ? `<div class="detail-item"><span class="detail-label">Excess Guests (₱100 per guest)</span><span class="detail-value" style="color: #ef4444;">${booking.ExcessGuests} guest${booking.ExcessGuests > 1 ? 's' : ''} = ₱${booking.ExcessFee.toLocaleString()}</span></div>` : ''}
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="info-section">
                                                                                                    <h3 class="info-section-title">Package Information</h3>
                                                                                                    <div class="booking-details">
                                                                                                        <div class="detail-item"><span class="detail-label">Package Name</span><span class="detail-value">${packageData.Name}</span></div>
                                                                                                        <div class="detail-item"><span class="detail-label">Package Price (per day)</span><span class="detail-value">₱${parseFloat(packageData.Price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span></div>
                                                                                                        <div class="detail-item"><span class="detail-label">Maximum Guests</span><span class="detail-value">${packageData.MaxGuests || 'N/A'}</span></div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="info-section">
                                                                                                    <h3 class="info-section-title">Booking Status</h3>
                                                                                                    <div class="booking-details">
                                                                                                        <div class="detail-item"><span class="detail-label">Status</span><span class="detail-value">${booking.BookingStatus}</span></div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>`;
        }

        function updateBalanceSectionDisplay(data) {
            const balanceContent = document.getElementById('balance-content');
            const payment = data.payment;
            const payments = data.payments || [];
            const unpaidItems = data.unpaidItems || [];
            const rentals = data.rentals || [];
            const bookingStatus = data.booking?.BookingStatus || 'Confirmed';

            console.log('Rentals received:', rentals);
            console.log('Rentals count:', rentals.length);
            console.log('Booking status:', bookingStatus);

            let unpaidItemsTotal = unpaidItems.reduce((sum, item) => sum + parseFloat(item.TotalAmount), 0);
            let rentalChargesTotal = parseFloat(payment.RentalCharges || 0);
            let totalOutstanding = parseFloat(payment.RemainingBalance) + unpaidItemsTotal + rentalChargesTotal;

            let financialSummaryHTML = `<div class="financial-summary-panel">`;

            // Show outstanding charges section if there are any
            if (totalOutstanding > 0 || rentals.length > 0 || unpaidItems.length > 0) {
                financialSummaryHTML += `<div class="section" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem;">`;
                financialSummaryHTML += `<h3 class="outstanding-charges-title"><i class="fas fa-shopping-cart"></i> Outstanding Charges</h3>`;

                // Booking Balance
                if (payment.RemainingBalance > 0) {
                    financialSummaryHTML += `<div class="balance-row">
                                                                                                    <div class="balance-row-label">Booking Balance:</div>
                                                                                                    <div class="highlight-yellow balance-row-amount">₱${parseFloat(payment.RemainingBalance).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
                                                                                                </div>`;
                }

                // Unpaid Items
                if (unpaidItems.length > 0) {
                    financialSummaryHTML += `<div class="unpaid-items-container">
                                                                                                    <div class="unpaid-items-title">Unpaid Items:</div>
                                                                                                    <table class="unpaid-items-table">
                                                                                                        <thead><tr><th>Item</th><th class="rental-table-right">Qty</th><th class="rental-table-right">Price</th><th class="rental-table-right">Total</th></tr></thead>
                                                                                                        <tbody>`;
                    unpaidItems.forEach(item => {
                        financialSummaryHTML += `<tr>
                                                                                                        <td>${item.ItemName}</td>
                                                                                                        <td class="rental-table-right">${item.Quantity}</td>
                                                                                                        <td class="rental-table-right">₱${parseFloat(item.Price).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                                                                                        <td class="rental-table-right unpaid-items-amount">₱${parseFloat(item.TotalAmount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                                                                                    </tr>`;
                    });
                    financialSummaryHTML += `</tbody>
                                                                                                    <tfoot><tr><td colspan="3" class="unpaid-items-subtotal">Items Subtotal:</td>
                                                                                                    <td class="rental-table-right"><span class="highlight-yellow">₱${unpaidItemsTotal.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span></td></tr></tfoot>
                                                                                                    </table>
                                                                                                </div>`;
                }

                // Rental Charges
                if (rentals.length > 0) {
                    let calculatedRentalTotal = 0;
                    financialSummaryHTML += `<div class="rental-charges-container" style="margin-top:20px">
                                                                                                    <div class="rental-charges-title"><i class="fas fa-tools"></i> Rental Charges:</div>
                                                                                                    <table class="rental-table">
                                                                                                        <thead><tr><th>Item</th><th class="rental-table-center">Qty</th><th class="rental-table-center">Status</th><th class="rental-table-right">Charges</th></tr></thead>
                                                                                                        <tbody>`;

                    rentals.forEach(rental => {
                        console.log('Processing rental:', rental);
                        const statusHTML = rental.Status === 'Issued' ? '<span class="rental-status-issued">Issued</span>' :
                            rental.Status === 'Returned' ? '<span class="rental-status-returned">Returned</span>' :
                                rental.Status === 'Damaged' ? '<span class="rental-status-damaged">Damaged</span>' :
                                    rental.Status === 'Lost' ? '<span class="rental-status-lost">Lost</span>' :
                                        '<span class="rental-status-unknown">Unknown</span>';

                        const rentalFee = parseFloat(rental.RentalFee || 0);
                        const additionalFees = parseFloat(rental.AdditionalFees || 0);
                        const totalCharges = parseFloat(rental.TotalCharges || 0);

                        console.log(`${rental.ItemName} - RentalFee: ${rentalFee}, AdditionalFees: ${additionalFees}, TotalCharges: ${totalCharges}`);

                        calculatedRentalTotal += totalCharges;

                        // Main rental row
                        financialSummaryHTML += `<tr>
                                                                                                        <td>${rental.ItemName}</td>
                                                                                                        <td class="rental-table-center">${rental.Quantity}</td>
                                                                                                        <td class="rental-table-center rental-status">${statusHTML}</td>
                                                                                                        <td class="rental-table-right rental-charge">₱${rentalFee.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                                                                                    </tr>`;

                        // Additional fee row
                        if (additionalFees > 0) {
                            const feeLabel = rental.Status === 'Damaged' ? 'Damage Fee:' :
                                rental.Status === 'Lost' ? 'Loss Fee:' : 'Fee:';
                            console.log(`Adding fee row for ${rental.ItemName}: ${feeLabel} ${additionalFees}`);
                            financialSummaryHTML += `<tr class="rental-fee-row">
                                                                                                            <td></td>
                                                                                                            <td class="rental-table-center"></td>
                                                                                                            <td class="rental-fee-label">${feeLabel}</td>
                                                                                                            <td class="rental-fee-amount">₱${additionalFees.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                                                                                                        </tr>`;
                        }
                    });

                    financialSummaryHTML += `</tbody>
                                                                                                    <tfoot><tr><td colspan="3" class="subtotal-label">Rentals Subtotal:</td>
                                                                                                    <td class="subtotal-amount"><span>₱${calculatedRentalTotal.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span></td></tr></tfoot>
                                                                                                    </table>
                                                                                                </div>`;
                }

                // Total Outstanding
                if (totalOutstanding > 0) {
                    financialSummaryHTML += `<div class="outstanding-container" style="margin-top:15px; font-size:18px;">
                                                                                                    <div style="display:flex; justify-content:space-between;">
                                                                                                        <div class="outstanding-label">Total:</div>
                                                                                                        <div id="total-outstanding" class="outstanding-amount">₱${totalOutstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
                                                                                                    </div>
                                                                                                </div>`;
                }
                financialSummaryHTML += `</div>`;
            } else {
                // No outstanding balance message
                financialSummaryHTML += `<div class="no-outstanding-message" style="text-align: center; padding: 3rem; color: #10b981;">
                                                                                                <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                                                                                <div style="font-size: 1.25rem; font-weight: 600;">No outstanding balance</div>
                                                                                                <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">All charges have been settled</div>
                                                                                            </div>`;
            }
            financialSummaryHTML += `</div>`;
            balanceContent.innerHTML = financialSummaryHTML;

            // Button logic
            const hasRentals = rentals.length > 0;
            const hasUnreturnedRentals = rentals.some(r => r.Status === 'Issued');

            console.log('Has rentals:', hasRentals);
            console.log('Has unreturned rentals:', hasUnreturnedRentals);
            console.log('Total outstanding:', totalOutstanding);

            // Enable Return Rentals button
            const returnRentalsBtn = document.getElementById('returnRentalsBtn');
            if (returnRentalsBtn) {
                returnRentalsBtn.disabled = !hasRentals;
                console.log('Return Rentals button disabled:', returnRentalsBtn.disabled);
            }

            // Bill Out button logic
            const billOutBtn = document.getElementById('billOutBtn');
            if (billOutBtn) {
                if (bookingStatus !== 'Staying') {
                    billOutBtn.disabled = true;
                    billOutBtn.title = 'Guest must be checked in first';
                    console.log('Bill Out disabled: guest not checked in');
                } else if (hasUnreturnedRentals) {
                    billOutBtn.disabled = true;
                    billOutBtn.title = 'Please return all rented items first';
                    console.log('Bill Out disabled: unreturned rentals');
                } else if (totalOutstanding <= 0) {
                    billOutBtn.disabled = true;
                    billOutBtn.title = 'No outstanding balance to settle';
                    console.log('Bill Out disabled: no outstanding balance');
                } else {
                    billOutBtn.disabled = false;
                    billOutBtn.title = '';
                    console.log('Bill Out enabled');
                }
            }
        }

        function updateStatusButtons(bookingStatus, totalOutstanding) {
            const checkedInBtn = document.getElementById('checkedInBtn');
            const checkedOutBtn = document.getElementById('checkedOutBtn');
            const noShowBtn = document.getElementById('noShowBtn');

            checkedInBtn.disabled = false;
            checkedOutBtn.disabled = true;
            noShowBtn.disabled = false;
            noShowBtn.innerHTML = '<i class="fas fa-times-circle"></i> No Show';
            noShowBtn.setAttribute('data-status', 'no-show');
            checkedOutBtn.title = '';

            if (bookingStatus === 'Confirmed') {
                checkedOutBtn.title = 'Guest must be checked in first';
            } else if (bookingStatus === 'Staying') {
                checkedInBtn.disabled = true;
                checkedOutBtn.disabled = false;
                if (totalOutstanding > 0) {
                    checkedOutBtn.disabled = true;
                    checkedOutBtn.title = `Cannot check out. Outstanding balance: ₱${parseFloat(totalOutstanding).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
                }
            } else if (bookingStatus === 'Cancelled') {
                checkedInBtn.disabled = true;
                checkedOutBtn.disabled = true;
                checkedOutBtn.title = 'Guest marked as No Show';
                noShowBtn.innerHTML = '<i class="fas fa-undo"></i> Undo No Show';
                noShowBtn.setAttribute('data-status', 'undo-cancel');
                noShowBtn.disabled = false;
            } else if (bookingStatus === 'Completed') {
                checkedInBtn.disabled = true;
                checkedOutBtn.disabled = true;
                noShowBtn.disabled = true;
            }
        }

        // === selectGuest Function ===
        window.selectGuest = function (bookingId, guestName) {
            currentBookingId = bookingId;
            currentGuestName = guestName;

            console.log('Selecting guest:', bookingId, guestName);

            // Fetch guest details via AJAX
            fetch(`/admin/currently-staying/guest-details?booking_id=${bookingId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    if (data.success) {
                        updateBookingDetailsDisplay(data);
                        updateBalanceSectionDisplay(data);
                        updateStatusButtons(data.booking.BookingStatus, data.payment.TotalOutstanding || 0);
                    } else {
                        console.error('Failed to load guest details:', data);
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'error',
                            title: 'Failed to Load',
                            text: 'Failed to load guest details: ' + (data.message || 'Unknown error')
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching guest details:', error);
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while loading guest details: ' + error.message
                    });
                });

            // Highlight the selected row temporarily
            document.querySelectorAll('.currently-staying-table tbody tr').forEach(row => {
                row.style.backgroundColor = '';
                if (row.cells[0].textContent.trim() === bookingId) {
                    row.style.backgroundColor = '#EBC595';
                    row.style.transition = 'background-color 0.3s ease';
                    setTimeout(() => {
                        row.style.backgroundColor = '';
                    }, 1000);
                }
            });
        };

        // === Modal Functions ===
        window.openPayRentalsOnlyModal = function () {
            if (!currentBookingId) {
                document.getElementById('rentalOnlyTotal').textContent = '₱0.00';
                document.getElementById('rentalAmount').value = '0.00';
                document.getElementById('rentalConfirmAmount').textContent = '0.00';
                document.getElementById('rentalPaymentMethod').value = '';
                document.getElementById('rentalNonCashFields').style.display = 'none';
                document.getElementById('payRentalsOnlyModal').classList.add('show');
                return;
            }
            fetch(`/admin/currently-staying/guest-details?booking_id=${currentBookingId}`, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'error',
                            title: 'Failed to Load',
                            text: 'Failed to load guest details'
                        });
                        return;
                    }
                    const rentals = data.rentals || [];
                    const total = rentals.reduce((sum, r) => sum + parseFloat(r.TotalCharges || 0), 0);
                    document.getElementById('rentalOnlyTotal').textContent = '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('rentalAmount').value = total.toFixed(2);
                    document.getElementById('rentalConfirmAmount').textContent = total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('rentalPaymentMethod').value = '';
                    document.getElementById('rentalNonCashFields').style.display = 'none';
                    document.getElementById('payRentalsOnlyModal').classList.add('show');
                })
                .catch(() => {
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'error',
                        title: 'Error',
                        text: 'Error loading rental charges'
                    });
                });
        };

        window.closePayRentalsOnlyModal = function () {
            const modal = document.getElementById('payRentalsOnlyModal');
            if (modal) {
                modal.classList.remove('show');
            }
        };

        window.openReturnRentalsModal = function () {
            if (!currentBookingId) {
                Swal.fire({
                    ...SwalConfig,
                    icon: 'info',
                    title: 'No Guest Selected',
                    text: 'Please select a guest first'
                });
                return;
            }
            fetch(`/admin/rentals/api/active-rentals?booking_id=${currentBookingId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.rentals && data.rentals.length > 0) {
                        activeRentals = data.rentals;
                        const container = document.getElementById('rentalsChecklist');
                        container.innerHTML = '';
                        const headerDiv = document.createElement('div');
                        headerDiv.style.cssText = 'display:grid; grid-template-columns:40px 2fr 1fr 1.2fr 1.2fr; gap:0.75rem; padding:0.5rem 1rem; margin-bottom:0.5rem; font-weight:600; color:#374151; border-bottom:2px solid #e5e7eb;';
                        headerDiv.innerHTML = `
                                                                                                        <div></div>
                                                                                                        <div>Item</div>
                                                                                                        <div>Qty Return</div>
                                                                                                        <div>Status</div>
                                                                                                        <div>Damage/Loss Fee</div>
                                                                                                    `;
                        container.appendChild(headerDiv);
                        activeRentals.forEach(rental => {
                            const div = document.createElement('div');
                            div.className = 'rental-return-row';
                            div.style.cssText = 'display:grid; grid-template-columns:40px 2fr 1fr 1.2fr 1.2fr; gap:0.75rem; padding:1rem; border:2px solid #e5e7eb; border-radius:8px; margin-bottom:1rem; align-items:center; background:#f9fafb;';
                            div.innerHTML = `
                                                                                                            <div style="text-align:center;">
                                                                                                                <input type="checkbox" id="rent_${rental.id}" class="rental-checkbox" style="width:20px;height:20px;cursor:pointer;">
                                                                                                            </div>
                                                                                                            <div>
                                                                                                                <strong style="font-size:1rem;color:#111827;">${rental.item_name}</strong>
                                                                                                                <br>
                                                                                                                <small style="color:#6b7280;">Issued: ${rental.quantity} | Rate: ₱${parseFloat(rental.rate_snapshot).toFixed(2)}</small>
                                                                                                            </div>
                                                                                                            <div>
                                                                                                                <input type="number" id="qty_${rental.id}" class="form-input" min="0" max="${rental.quantity}" value="${rental.quantity}" disabled style="width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                                                                                                            </div>
                                                                                                            <div>
                                                                                                                <select id="status_${rental.id}" class="form-input" disabled style="width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;background:white;">
                                                                                                                    <option value="">Select Status</option>
                                                                                                                    <option value="Returned">Returned (Good)</option>
                                                                                                                    <option value="Damaged">Damaged</option>
                                                                                                                    <option value="Lost">Lost</option>
                                                                                                                </select>
                                                                                                            </div>
                                                                                                            <div>
                                                                                                                <input type="number" id="fee_${rental.id}" class="form-input" placeholder="Enter fee amount" min="0" step="0.01" disabled style="width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;">
                                                                                                            </div>
                                                                                                        `;
                            container.appendChild(div);
                            const checkbox = document.getElementById(`rent_${rental.id}`);
                            const qtyInput = document.getElementById(`qty_${rental.id}`);
                            const statusSelect = document.getElementById(`status_${rental.id}`);
                            const feeInput = document.getElementById(`fee_${rental.id}`);
                            checkbox.addEventListener('change', () => {
                                qtyInput.disabled = !checkbox.checked;
                                statusSelect.disabled = !checkbox.checked;
                                if (!checkbox.checked) {
                                    qtyInput.value = rental.quantity;
                                    statusSelect.value = '';
                                    feeInput.disabled = true;
                                    feeInput.value = '';
                                }
                            });
                            statusSelect.addEventListener('change', () => {
                                const requiresFee = (statusSelect.value === 'Damaged' || statusSelect.value === 'Lost');
                                feeInput.disabled = !requiresFee;
                                if (!requiresFee) {
                                    feeInput.value = '';
                                }
                            });
                        });
                        document.getElementById('returnRentalsModal').classList.add('show');
                    } else {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'info',
                            title: 'No Active Rentals',
                            text: 'No active rentals found for this booking'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading rentals:', error);
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'error',
                        title: 'Error',
                        text: 'Error loading rental items'
                    });
                });
        };

        window.closeReturnRentalsModal = function () {
            document.getElementById('returnRentalsModal')?.classList.remove('show');
            activeRentals = [];
        };

        window.confirmReturnRentals = function () {
            const returns = [];
            let hasError = false;
            activeRentals.forEach(rental => {
                const checkbox = document.getElementById(`rent_${rental.id}`);
                if (checkbox && checkbox.checked) {
                    const qty = parseInt(document.getElementById(`qty_${rental.id}`).value) || 0;
                    const status = document.getElementById(`status_${rental.id}`).value;
                    const fee = parseFloat(document.getElementById(`fee_${rental.id}`).value) || 0;

                    if (status === 'Lost') {
                        if (qty < 0 || qty > rental.quantity) {
                            Swal.fire({
                                ...SwalConfig,
                                icon: 'warning',
                                title: 'Invalid Quantity',
                                text: `Please enter a valid quantity (0-${rental.quantity}) for ${rental.item_name}`
                            });
                            hasError = true;
                            return;
                        }
                    } else {
                        if (qty <= 0 || qty > rental.quantity) {
                            Swal.fire({
                                ...SwalConfig,
                                icon: 'warning',
                                title: 'Invalid Quantity',
                                text: `Please enter a valid quantity (1-${rental.quantity}) for ${rental.item_name}`
                            });
                            hasError = true;
                            return;
                        }
                    }

                    if (!status) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'warning',
                            title: 'Missing Status',
                            text: `Please select a return status for ${rental.item_name}`
                        });
                        hasError = true;
                        return;
                    }

                    if ((status === 'Damaged' || status === 'Lost') && fee <= 0) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'warning',
                            title: 'Missing Fee',
                            text: `Please enter a ${status.toLowerCase()} fee for ${rental.item_name}`
                        });
                        hasError = true;
                        return;
                    }

                    returns.push({
                        rental_id: rental.id,
                        quantity: qty,
                        status: status,
                        damage_fee: (status === 'Damaged' || status === 'Lost') ? fee : 0
                    });
                }
            });

            if (hasError) return;
            if (returns.length === 0) {
                Swal.fire({
                    ...SwalConfig,
                    icon: 'info',
                    title: 'No Items Selected',
                    text: 'Please select at least one item to return'
                });
                return;
            }

            fetch('/admin/currently-staying/return-rentals', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    booking_id: currentBookingId,
                    returns: returns
                })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'success',
                            title: 'Success',
                            text: 'Items returned successfully!',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            closeReturnRentalsModal();
                            selectGuest(currentBookingId, currentGuestName);
                        });
                    } else {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Failed to return items'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing returns'
                    });
                });
        };

        // === Add Item Modal Functions ===
        window.openAddItemModal = function () {
            if (!currentBookingId) {
                Swal.fire({
                    ...SwalConfig,
                    icon: 'info',
                    title: 'No Guest Selected',
                    text: 'Please select a guest first'
                });
                return;
            }
            document.getElementById('addItemModal').classList.add('show');
            document.getElementById('itemName').value = '';
            document.getElementById('itemQuantity').value = '1';
            document.getElementById('itemPrice').value = '';
            updateItemTotal();
        };

        window.closeAddItemModal = function () {
            document.getElementById('addItemModal').classList.remove('show');
        };

        function updateItemTotal() {
            const quantity = parseInt(document.getElementById('itemQuantity').value) || 0;
            const price = parseFloat(document.getElementById('itemPrice').value) || 0;
            const total = quantity * price;
            document.getElementById('itemTotal').textContent = `₱${total.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
        }

        window.confirmAddItem = function () {
            const itemName = document.getElementById('itemName').value.trim();
            const quantity = parseInt(document.getElementById('itemQuantity').value);
            const price = parseFloat(document.getElementById('itemPrice').value);

            if (!itemName || !quantity || quantity < 1 || !price || price < 0) {
                Swal.fire({
                    ...SwalConfig,
                    icon: 'warning',
                    title: 'Invalid Input',
                    text: 'Please fill all fields correctly'
                });
                return;
            }

            fetch('/admin/currently-staying/add-item', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    booking_id: currentBookingId,
                    item_name: itemName,
                    quantity: quantity,
                    price: price
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'success',
                            title: 'Success',
                            text: 'Item added successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            closeAddItemModal();
                            selectGuest(currentBookingId, currentGuestName);
                        });
                    } else {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Failed to add item'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while adding item'
                    });
                });
        };

        // === Bill Out Functions ===
        window.openBillOutModal = function () {
            if (!currentBookingId) {
                Swal.fire({
                    ...SwalConfig,
                    icon: 'info',
                    title: 'No Guest Selected',
                    text: 'Please select a guest first'
                });
                return;
            }
            fetch(`/admin/currently-staying/guest-details?booking_id=${currentBookingId}`, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'error',
                            title: 'Failed to Load',
                            text: 'Failed to load details'
                        });
                        return;
                    }
                    currentTotalOutstanding = calculateTotalOutstanding(data);
                    document.getElementById('billOutTotalOutstanding').textContent = '₱' + currentTotalOutstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('billOutAmount').value = currentTotalOutstanding.toFixed(2);
                    document.getElementById('billOutConfirmAmount').textContent = currentTotalOutstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('billOutModal').classList.add('show');
                });
        };

        function calculateTotalOutstanding(data) {
            const payment = data.payment;
            const unpaidItems = data.unpaidItems || [];
            const rentals = data.rentals || [];

            let unpaidItemsTotal = unpaidItems.reduce((sum, item) => sum + parseFloat(item.TotalAmount), 0);
            let rentalChargesTotal = rentals.reduce((sum, r) => sum + parseFloat(r.TotalCharges || 0), 0);
            return parseFloat(payment.RemainingBalance) + unpaidItemsTotal + rentalChargesTotal;
        }

        window.closeBillOutModal = function () {
            document.getElementById('billOutModal').classList.remove('show');
        };

        function toggleBillOutFields() {
            const paymentMethod = document.getElementById('billOutPaymentMethod').value;
            const nonCashFields = document.getElementById('billOutNonCashFields');
            if (paymentMethod && paymentMethod !== 'Cash') {
                nonCashFields.style.display = 'block';
            } else {
                nonCashFields.style.display = 'none';
            }
            // Toggle cash-specific fields
            const cashFields = document.getElementById('billOutCashFields');
            if (paymentMethod === 'Cash') {
                cashFields.style.display = 'block';
            } else {
                cashFields.style.display = 'none';
            }
        }

        function toggleRentalNonCashFields() {
            const paymentMethod = document.getElementById('rentalPaymentMethod').value;
            const nonCashFields = document.getElementById('rentalNonCashFields');
            if (paymentMethod && paymentMethod !== 'Cash') {
                nonCashFields.style.display = 'block';
            } else {
                nonCashFields.style.display = 'none';
            }
            // Toggle cash-specific fields
            const cashFields = document.getElementById('rentalCashFields');
            if (paymentMethod === 'Cash') {
                cashFields.style.display = 'block';
            } else {
                cashFields.style.display = 'none';
            }
        }

        // === Status Update Function ===
        function updateGuestStatus(bookingId, status, userId = null) {
            const payload = { status: status };
            if (userId) payload.user_id = userId;

            fetch(`/admin/currently-staying/${bookingId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'success',
                            title: 'Success',
                            text: data.message || 'Status updated successfully.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'error',
                            title: 'Failed',
                            text: data.message || 'Failed to update status'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while updating status'
                    });
                });
        }

        // === DOMContentLoaded Initialization ===
        document.addEventListener('DOMContentLoaded', function () {
            // Auto-load first guest on page load
            const firstRow = document.querySelector('.currently-staying-table tbody tr');
            console.log('First row found:', firstRow);
            if (firstRow) {
                const bookingId = firstRow.cells[0].textContent.trim();
                const guestName = firstRow.cells[1].textContent.trim();
                console.log('Auto-loading guest:', bookingId, guestName);
                setTimeout(() => {
                    selectGuest(bookingId, guestName);
                }, 100);
                firstRow.style.backgroundColor = '#f3f4f6';
            } else {
                console.log('No guest found to auto-load');
            }

            // Handle status button clicks
            document.querySelectorAll('.status-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    console.log('Status button clicked:', this.getAttribute('data-status'));
                    
                    if (this.disabled) {
                        console.log('Button is disabled, returning');
                        return;
                    }

                    if (!currentBookingId || !currentGuestName) {
                        console.log('No guest selected');
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'info',
                            title: 'No Guest Selected',
                            text: 'Please select a guest first'
                        });
                        return;
                    }

                    const status = this.getAttribute('data-status');
                    console.log('Processing status:', status, 'for guest:', currentGuestName);
                    let confirmMessage = '';
                    let requiresAdminId = false;

                    if (status === 'checked-in') {
                        // Direct check-in (accompanying guests feature hidden for demo)
                        confirmMessage = `Check in ${currentGuestName}?`;
                        // COMMENTED OUT: openAccompanyingGuestsModalForCheckIn(currentBookingId, currentGuestName);
                        // Continue to show confirmation dialog below
                    } else if (status === 'checked-out') {
                        confirmMessage = `Mark ${currentGuestName} as Checked-Out (Completed)?`;
                    } else if (status === 'no-show') {
                        confirmMessage = `Mark ${currentGuestName} as Cancelled (No Show)?`;
                        requiresAdminId = true;
                    } else if (status === 'undo-cancel') {
                        confirmMessage = `Undo cancellation for ${currentGuestName}?`;
                        requiresAdminId = true;
                    }

                    // Map frontend status values to backend database status values
                    const statusMap = {
                        'checked-in': 'Staying',
                        'checked-out': 'Completed',
                        'no-show': 'Cancelled',
                        'undo-cancel': 'Confirmed'
                    };
                    const backendStatus = statusMap[status] || status;

                    if (requiresAdminId) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'question',
                            title: 'Confirm Action',
                            text: confirmMessage,
                            input: 'text',
                            inputPlaceholder: 'Enter your Admin ID',
                            showCancelButton: true,
                            confirmButtonText: 'Confirm',
                            cancelButtonText: 'Cancel',
                            inputValidator: (value) => {
                                if (!value || !value.trim()) {
                                    return 'Admin ID is required to perform this action';
                                }
                            }
                        }).then((result) => {
                            if (result.isConfirmed && result.value) {
                                updateGuestStatus(currentBookingId, backendStatus, result.value.trim());
                            }
                        });
                    } else {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'question',
                            title: 'Confirm Action',
                            text: confirmMessage,
                            showCancelButton: true,
                            confirmButtonText: 'Confirm',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                updateGuestStatus(currentBookingId, backendStatus);
                            }
                        });
                    }
                });
            });

            // Event listeners for other buttons
            document.getElementById('addItemBtn')?.addEventListener('click', openAddItemModal);
            document.getElementById('billOutBtn')?.addEventListener('click', function () {
                if (!currentBookingId) {
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'info',
                        title: 'No Guest Selected',
                        text: 'Please select a guest first'
                    });
                    return;
                }
                window.location.href = `/admin/currently-staying/bill-out/${currentBookingId}`;
            });
            document.getElementById('returnRentalsBtn')?.addEventListener('click', openReturnRentalsModal);
            document.getElementById('payRentalsOnlyBtn')?.addEventListener('click', openPayRentalsOnlyModal);

            // Item total calculator
            const itemQuantity = document.getElementById('itemQuantity');
            const itemPrice = document.getElementById('itemPrice');
            if (itemQuantity) itemQuantity.addEventListener('input', updateItemTotal);
            if (itemPrice) itemPrice.addEventListener('input', updateItemTotal);

            // Modal close on outside click
            ['addItemModal', 'billOutModal', 'issueRentalModal', 'returnRentalsModal', 'payRentalsOnlyModal' /*, 'addAccompanyingGuestsModal' */].forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.addEventListener('click', function (e) {
                        if (e.target === this) {
                            this.classList.remove('show');
                        }
                    });
                }
            });

            // Skip button click handler - COMMENTED OUT FOR DEMO
            /* document.getElementById('skipGuestDetailsBtn')?.addEventListener('click', function () {
                // Use the ORIGINAL expected counts from the booking (fetched when modal opened)
                const regularCount = expectedAdults;      // Adults = Regular
                const seniorCount = expectedSeniors;
                const childrenCount = expectedChildren;
                const totalCount = regularCount + seniorCount + childrenCount;

                // Optional: warn if no accompanying guests are expected at all
                const hasExpectedGuests = totalCount > 0;

                Swal.fire({
                    ...SwalConfig,
                    icon: 'warning',
                    title: 'Skip Recording Guest Details?',
                    html: `
                                            Skipping will <strong>not record individual names, genders, or types</strong> of the accompanying guests.<br><br>
                                            ${hasExpectedGuests
                            ? `The system will still use the <strong>original booked guest counts</strong> for billing, package validation, and records:<br><br>`
                            : `No accompanying guests were booked, so skipping is safe.<br><br>`
                        }
                                            <strong>Adults (Regular):</strong> ${regularCount}<br>
                                            <strong>Seniors:</strong> ${seniorCount}<br>
                                            <strong>Children (6 yrs & below - FREE):</strong> ${childrenCount}<br>
                                            <strong>Total Accompanying Guests:</strong> ${totalCount} ${totalCount > 0 ? '(excluding main guest)' : ''}
                                        `,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Check In Anyway',
                    cancelButtonText: 'No, Add Details First',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        performActualCheckIn();
                        document.getElementById('addAccompanyingGuestsModal').classList.remove('show');
                        tempGuests = []; // optional: clean up temp list
                    }
                });
            }); */
            // ESC key to close modals
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    ['addItemModal', 'billOutModal', 'issueRentalModal', 'returnRentalsModal', 'payRentalsOnlyModal', 'addAccompanyingGuestsModal'].forEach(modalId => {
                        const modal = document.getElementById(modalId);
                        if (modal && modal.classList.contains('show')) {
                            modal.classList.remove('show');
                        }
                    });
                }
            });
        });
    </script>
@endpush