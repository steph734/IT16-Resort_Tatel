@extends('layouts.admin')

@section('title', 'Bookings')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/bookings/bookings.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/sweetalert-custom.css') }}">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection

@section('content')
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Booking Management</h1>
        </div>

        <div class="booking-management">
            <!-- Booking Lists Section -->
            <div class="booking-lists-section">
                <!-- Search and Filter Bar -->
                <div class="search-filter-bar">
                    <input type="text" class="search-input" placeholder="Search ID, guests, tags, etc." id="searchInput"
                        value="{{ request('search') }}">
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}
                            </option>
                        @endforeach
                    </select>
                    <select class="filter-select" id="paymentFilter">
                        <option value="">All Payments</option>
                        <option value="Fully Paid" {{ request('payment') == 'Fully Paid' ? 'selected' : '' }}>Fully Paid
                        </option>
                        <option value="Partial" {{ request('payment') == 'Partial' ? 'selected' : '' }}>Partial</option>
                        <option value="Downpayment" {{ request('payment') == 'Downpayment' ? 'selected' : '' }}>Downpayment
                        </option>
                    </select>
                    <button class="new-booking-btn" onclick="window.location.href='{{ route('admin.bookings.create') }}'">
                        <i class="fas fa-plus"></i>
                        New Booking
                    </button>
                </div>

                <!-- Bookings Table -->
                <div class="bookings-table-container">
                    <table class="bookings-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Guest Name</th>
                                <th>Check-In Date</th>
                                <th>Status</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTableBody">
                            @foreach($bookings as $booking)
                                <tr class="booking-row" data-booking-id="{{ $booking->BookingID }}"
                                    data-checkin-date="{{ $booking->CheckInDate }}" data-status="{{ $booking->BookingStatus }}"
                                    data-guest-name="{{ $booking->guest->GuestName ?? 'Unknown Guest' }}"
                                    data-created-at="{{ $booking->created_at }}">
                                    <td>
                                        <strong class="booking-id-link">{{ $booking->BookingID }}</strong>
                                    </td>
                                    <td>{{ $booking->guest->GuestName ?? 'Unknown Guest' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($booking->CheckInDate)->format('M d, Y') }}</td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'Staying' => '#166534',
                                                'Completed' => '#6b7280',
                                                'Cancelled' => '#dc2626',

                                            ];
                                            $statusColor = $statusColors[$booking->BookingStatus] ?? '#374151';
                                        @endphp
                                        <span class="booking-status-text"
                                            style="color: {{ $statusColor }};">{{ $booking->BookingStatus }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $paymentColors = [
                                                'Fully Paid' => '#166534',
                                                'Partial' => '#1e40af',
                                                'Downpayment' => '#92400e',
                                                'For Verification' => '#ea580c',
                                                'Unpaid' => '#dc2626'
                                            ];
                                            $paymentColor = $paymentColors[$booking->payment_status] ?? '#374151';
                                        @endphp
                                        <span class="booking-payment-text"
                                            style="color: {{ $paymentColor }};">{{ $booking->payment_status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Pagination removed - using scrollable table -->
            </div>

            <!-- Booking Information Section -->
            <div class="booking-info-section">
                <div class="booking-info-content" id="bookingInfoContent">
                    <div class="booking-info-placeholder">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>Select a booking</h3>
                        <p>Click on a booking from the list to view details</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal-overlay" id="paymentModal">
        <div class="payment-modal payment-modal-large">
            <div class="modal-header">
                <h3 class="modal-title">Payments</h3>
                <button class="modal-close" onclick="closePaymentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Toggle Navigation -->
            <div class="payment-toggle">
                <button class="toggle-option active" onclick="switchPaymentMode('history')">
                    <i class="fas fa-history"></i> Payment History
                </button>
                <button class="toggle-option" onclick="switchPaymentMode('add')">
                    <i class="fas fa-plus-circle"></i> Add Payment
                </button>
            </div>

            <div class="modal-body">
                <!-- Balance Summary Section (Always Visible) -->
                <div class="balance-summary-section">
                    <h4 class="balance-summary-header">Balance Summary</h4>
                    <div class="balance-summary-grid">
                        <div class="balance-item">
                            <span class="balance-label">Total Amount:</span>
                            <span class="balance-value" id="totalBookingAmount">₱ 0.00</span>
                        </div>
                        <div class="balance-item">
                            <span class="balance-label">Total Paid:</span>
                            <span class="balance-value" id="totalPaidAmount">₱ 0.00</span>
                        </div>
                        <div class="balance-item balance-remaining">
                            <span class="balance-label">Remaining Balance:</span>
                            <span class="balance-value" id="remainingBalance">₱ 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Payment History Mode -->
                <div id="payment-history-mode" class="payment-mode active">
                    <div class="payment-history-section">
                        <table class="payment-history-table">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Amount</th>
                                    <th>Purpose</th>
                                    <th>Reference</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="paymentHistoryBody">
                                <!-- Payment history will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Payment Mode -->
                <div id="payment-add-mode" class="payment-mode">
                    <div class="payment-info-section">
                        <h4 class="payment-info-header">Record Payment</h4>

                        <div class="payment-form">
                            <!-- First Row: Payment Method, Purpose, Amount -->
                            <div class="payment-row">
                                <div class="form-group">
                                    <label class="form-label">Payment Method <span
                                            class="required-indicator">*</span></label>
                                    <select class="form-select" id="paymentMethod">
                                        <option value="">Select Method</option>
                                        <option value="cash">Cash</option>
                                        <option value="paymongo">PayMongo</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Purpose <span class="required-indicator">*</span></label>
                                    <select class="form-select" id="paymentHistoryPurpose">
                                        <option value="">Select Purpose</option>
                                        <option value="full_payment">Full Payment</option>
                                        <option value="partial_payment">Partial Payment</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Amount <span class="required-indicator">*</span></label>
                                    <input type="number" class="form-input" id="paymentHistoryAmount" placeholder="₱ 0.00"
                                        step="0.01" readonly>
                                </div>
                            </div>

                            <!-- Cash-only fields: Amount Received & Change -->
                            <div class="payment-row-2col" id="cashFieldsPaymentHistory" style="display: none;">
                                <div class="form-group">
                                    <label class="form-label">Amount Received <span
                                            class="required-indicator">*</span></label>
                                    <input type="number" class="form-input" id="amountReceivedPaymentHistory"
                                        placeholder="₱ 0.00" step="0.01" oninput="calculateChangePaymentHistory()">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Change</label>
                                    <input type="number" class="form-input" id="changeAmountPaymentHistory"
                                        placeholder="₱ 0.00" step="0.01" readonly
                                        style="background-color: #f3f4f6; font-weight: 600;">
                                </div>
                            </div>

                            <div class="payment-row">
                                <div class="form-group">
                                    <label class="form-label">Account Name</label>
                                    <input type="text" class="form-input" id="accountName" placeholder="Enter account name">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Account Number</label>
                                    <input type="text" class="form-input" id="accountNumber"
                                        placeholder="Enter account number">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" class="form-input" id="referenceNumber"
                                        placeholder="Transaction ID or Reference">
                                </div>
                            </div>

                            <div class="form-group full-width form-group-end">
                                <button class="btn-pay-now" onclick="handlePaymentAction()">
                                    <i class="fas fa-check-circle"></i>
                                    Add Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Proof Modal removed: payment proofs are no longer stored in payments -->

    <!-- New Booking Modal -->
    <div class="modal-overlay" id="newBookingModal">
        <div class="new-booking-modal">
            <div class="modal-header">
                <h3 class="modal-title">New Booking</h3>
                <button class="modal-close" onclick="closeNewBookingModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Guest Information Section -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Guest Information</h4>
                    <div class="booking-form">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-input" id="firstName" placeholder="First Name" oninput="capitalizeProper(this)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-input" id="lastName" placeholder="Last Name" oninput="capitalizeProper(this)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-input" id="middleName" placeholder="Middle Name (Optional)" oninput="capitalizeProper(this)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" id="email" placeholder="Email Address">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-input" id="phone" placeholder="Phone Number">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-input" id="address" placeholder="Complete Address" oninput="capitalizeProper(this)">
                        </div>
                    </div>
                </div>

                <!-- Booking Details Section -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Booking Details</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Check In Date</label>
                            <input type="date" class="form-input" id="checkInDate" onchange="checkDateConflict()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Check Out Date</label>
                            <input type="date" class="form-input" id="checkOutDate" onchange="checkDateConflict()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Days of Stay</label>
                            <input type="number" class="form-input" id="daysOfStay" placeholder="0" readonly>
                        </div>
                    </div>
                    <div id="dateConflictError" class="date-conflict-error">
                        <p>
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Date Conflict:</strong> <span id="dateConflictMessage"></span>
                        </p>
                    </div>
                </div>

                <!-- Guest Count Section -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Guest Count</h4>
                    <div class="form-row" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label">Adults</label>
                            <input type="number" class="form-input" id="regularGuests" placeholder="0" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Children (6 yrs and below)</label>
                            <input type="number" class="form-input" id="children" placeholder="0" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Senior Citizens</label>
                            <input type="number" class="form-input" id="seniorGuests" placeholder="0" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Total Guests</label>
                            <input type="number" class="form-input" id="totalGuests" placeholder="0" readonly>
                        </div>
                    </div>
                </div>

                <!-- Package Selection Section -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Select a Package</h4>
                    <div class="package-selection">
                        @foreach($packages as $package)
                            <div class="package-card" data-package-id="{{ $package->PackageID }}"
                                data-package-price="{{ $package->Price }}" data-package-max-guests="{{ $package->max_guests }}"
                                onclick="selectPackage('{{ $package->PackageID }}', '{{ $package->Name }}', {{ $package->Price }}, {{ $package->max_guests }})">
                                <div class="package-title">{{ $package->Name }}</div>
                                <div class="package-price">₱ {{ number_format($package->Price, 0) }}</div>
                                <div class="package-max-guests"
                                    style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.75rem;">
                                    <i class="fas fa-users"></i> Max: {{ $package->max_guests ?? 30 }} guests
                                </div>
                                <div class="package-features">
                                    @if(!empty($package->amenities_array))
                                        @foreach($package->amenities_array as $amenity)
                                            <div style="display: flex; align-items: flex-start; margin-bottom: 0.25rem;">
                                                <i class="fas fa-check"
                                                    style="color: #10b981; margin-right: 0.5rem; margin-top: 0.25rem; font-size: 0.875rem;"></i>
                                                <span style="flex: 1;">{{ $amenity }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span style="color: #6b7280; font-size: 0.875rem;">No amenities listed</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeNewBookingModal()">
                    <i class="fas fa-arrow-left"></i>
                    &nbsp; Previous
                </button>
                <button class="btn-primary" onclick="proceedToPayment()">
                    Proceed &nbsp;
                    <i class=" fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Booking Modal -->
    <div class="modal-overlay" id="editBookingModal">
        <div class="new-booking-modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Booking</h3>
                <button class="modal-close" onclick="closeEditBookingModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Guest Information Section -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Guest Information</h4>
                    <div class="booking-form">
                        <div class="form-row" style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                            <div class="form-group" style="flex:1 1 160px; min-width:140px;">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-input" id="editFName" placeholder="First Name" oninput="capitalizeProper(this)">
                            </div>
                            <div class="form-group" style="flex:1 1 140px; min-width:120px;">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-input" id="editMName" placeholder="Middle Name" oninput="capitalizeProper(this)">
                            </div>
                            <div class="form-group" style="flex:1 1 160px; min-width:140px;">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-input" id="editLName" placeholder="Last Name" oninput="capitalizeProper(this)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" id="editEmail" placeholder="Email Address">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-input" id="editPhone" placeholder="Phone Number" oninput="this.value=this.value.replace(/[^0-9+]/g,'')">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-input" id="editAddress" placeholder="Complete Address" oninput="capitalizeProper(this)">
                        </div>
                        <!-- Hidden legacy field kept for backward compatibility with any leftover code -->
                        <input type="hidden" id="editGuestName">
                    </div>
                </div>

                <!-- Booking Details Section -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Booking Details</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Check In Date</label>
                            <input type="text" class="form-input readonly-input" id="editCheckInDate" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Check Out Date</label>
                            <input type="text" class="form-input" id="editCheckOutDate" placeholder="Select check-out date">
                        </div>
                    </div>
                </div>

                <!-- Guest Count Section -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Guest Count</h4>
                    <div class="form-row" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label">Adults</label>
                            <input type="number" class="form-input" id="editAdults" placeholder="0" min="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Children (6 yrs and below)</label>
                            <input type="number" class="form-input" id="editChildren" placeholder="0" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Senior Citizens</label>
                            <input type="number" class="form-input" id="editSeniors" placeholder="0" min="0">
                        </div>
                    </div>
                </div>

                <!-- Package Selection Section -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Select a Package</h4>
                    <div class="package-selection">
                        @foreach($packages as $package)
                            <div class="package-card" data-package-id="{{ $package->PackageID }}"
                                data-package-price="{{ $package->Price }}" data-package-max-guests="{{ $package->max_guests }}"
                                onclick="selectEditPackage('{{ $package->PackageID }}', '{{ $package->Name }}', {{ $package->Price }}, {{ $package->max_guests }})">
                                <div class="package-title">{{ $package->Name }}</div>
                                <div class="package-price">₱ {{ number_format($package->Price, 0) }}</div>
                                <div class="package-max-guests"
                                    style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.75rem;">
                                    <i class="fas fa-users"></i> Max: {{ $package->max_guests ?? 30 }} guests
                                </div>
                                <div class="package-features">
                                    @if(!empty($package->amenities_array))
                                        @foreach($package->amenities_array as $amenity)
                                            <div style="display: flex; align-items: flex-start; margin-bottom: 0.25rem;">
                                                <i class="fas fa-check"
                                                    style="color: #10b981; margin-right: 0.5rem; margin-top: 0.25rem; font-size: 0.875rem;"></i>
                                                <span style="flex: 1;">{{ $amenity }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span style="color: #6b7280; font-size: 0.875rem;">No amenities listed</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeEditBookingModal()">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                <button class="btn-primary" onclick="proceedToEditSummary()">
                    Continue
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Payment Summary Modal -->
    <div class="modal-overlay" id="editSummaryModal">
        <div class="new-booking-modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Booking - Payment Summary</h3>
                <button class="modal-close" onclick="closeEditSummaryModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Booking Summary Section -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Updated Booking Summary</h4>
                    <div class="edit-summary-warning">
                        <p>
                            <i class="fas fa-info-circle"></i>
                            Review the updated booking details and payment summary below.
                        </p>
                    </div>
                    <div class="booking-summary-breakdown">
                        <div class="summary-row">
                            <div class="summary-item">
                                <span class="summary-label">Package:</span>
                                <span class="summary-value" id="editSummaryPackage">Package A (₱15,000)</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Days Staying:</span>
                                <span class="summary-value" id="editSummaryDays">2 days (2 × ₱15,000)</span>
                            </div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-item">
                                <span class="summary-label">Excess Person:</span>
                                <span class="summary-value" id="editSummaryExcess">0 guest (0 × ₱100)</span>
                            </div>
                        </div>
                        <div class="summary-totals">
                            <div class="edit-summary-row">
                                <span class="total-label edit-summary-label">Original Total:</span>
                                <span class="total-value edit-summary-value" id="editSummaryOriginalTotal">₱ 0.00</span>
                            </div>
                            <div class="total-row">
                                <span class="total-label">New Total Amount:</span>
                                <span class="total-value" id="editSummaryTotal">₱ 0.00</span>
                            </div>
                            <div class="total-row downpayment">
                                <span class="total-label">Additional Amount Due:</span>
                                <span class="total-value" id="editSummaryAdditional">₱ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="payment-modal-actions">
                <button class="btn-secondary" onclick="backToEditBooking()">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </button>
                <button class="btn-primary" onclick="confirmBookingChanges()">
                    Confirm Changes
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Payment Method Modal -->
    <div class="modal-overlay" id="paymentMethodModal">
        <div class="new-booking-modal">
            <div class="modal-header">
                <h3 class="modal-title">Payment Method</h3>
                <button class="modal-close" onclick="closePaymentMethodModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <!-- Booking Summary Section -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Booking Summary</h4>
                    <div class="edit-summary-warning">
                        <p>
                            <i class="fas fa-info-circle"></i>
                            System automatically applies correct downpayment based on your booking time.
                        </p>
                    </div>
                    <div class="booking-summary-breakdown">
                        <div class="summary-row">
                            <div class="summary-item">
                                <span class="summary-label">Package:</span>
                                <span class="summary-value" id="paymentSummaryPackage">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Days Staying:</span>
                                <span class="summary-value" id="paymentSummaryDays">-</span>
                            </div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-item">
                                <span class="summary-label">Adults:</span>
                                <span class="summary-value" id="paymentSummaryAdults">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Children:</span>
                                <span class="summary-value" id="paymentSummaryChildren">-</span>
                            </div>
                        </div>
                        <div class="summary-row">
                            <div class="summary-item">
                                <span class="summary-label">Package Total:</span>
                                <span class="summary-value" id="paymentSummaryPackageTotal">₱ 0.00</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Excess Fee:</span>
                                <span class="summary-value" id="paymentSummaryExcess">₱ 0.00</span>
                            </div>
                        </div>
                        <div class="summary-totals">
                            <div class="total-row">
                                <span class="total-label">Total Amount:</span>
                                <span class="total-value" id="paymentSummaryTotal">₱ 0.00</span>
                            </div>
                            <div class="total-row downpayment">
                                <span class="total-label">Required Payment:</span>
                                <span class="total-value" id="paymentSummaryDownpayment">₱ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="booking-form-section">
                    <h4 class="form-section-title">Select Payment Method</h4>
                    <div class="payment-methods-grid">
                        <div class="payment-method-card" data-method="cash" onclick="selectPaymentMethod('cash')">
                            <div class="payment-method-title">
                                <i class="fas fa-money-bill-wave"></i>
                                Cash
                            </div>
                            <div class="payment-method-desc">Direct cash payment</div>
                        </div>
                        <div class="payment-method-card" data-method="paymongo" onclick="selectPaymentMethod('paymongo')">
                            <div class="payment-method-title">
                                <i class="fas fa-credit-card"></i>
                                PayMongo (Online)
                            </div>
                            <div class="payment-method-desc">GCash / Online Banking via PayMongo</div>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="booking-form-section payment-details-section" id="paymentDetailsSection">
                    <h4 class="form-section-title">Payment Details</h4>
                    <div class="payment-details-form">
                        <!-- First Row: Payment Method, Purpose, Amount -->
                        <div class="payment-row">
                            <div class="form-group">
                                <label class="form-label">Payment Method</label>
                                <input type="text" class="form-input" id="selectedPaymentMethod" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Purpose</label>
                                <select class="form-select" id="paymentPurpose" onchange="handlePaymentPurposeChange()">
                                    <option value="" disabled selected>Select Purpose</option>
                                    <option value="downpayment" id="downpaymentOption">Downpayment</option>
                                    <option value="full_payment">Full Payment</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Amount</label>
                                <input type="number" class="form-input readonly-input-disabled" id="paymentAmount"
                                    placeholder="₱ 0.00" step="0.01" readonly>
                                <small id="paymentAmountError" class="payment-amount-error"></small>
                            </div>
                        </div>
                        <!-- Cash-only fields: Amount Received & Change -->
                        <div class="payment-row" id="cashFieldsNewBooking" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">Amount Received</label>
                                <input type="number" class="form-input" id="amountReceivedNewBooking" placeholder="₱ 0.00"
                                    step="0.01" oninput="calculateChangeNewBooking()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Change</label>
                                <input type="number" class="form-input" id="changeAmountNewBooking" placeholder="₱ 0.00"
                                    step="0.01" readonly style="background-color: #f3f4f6; font-weight: 600;">
                            </div>
                        </div>
                        <!-- Second Row: Account Name, Account Number -->
                        <div class="payment-row account-details-row" id="accountDetailsRow">
                            <div class="form-group">
                                <label class="form-label">Account Name</label>
                                <input type="text" class="form-input" id="paymentAccountName" placeholder="Account Name">
                            </div>
                            <br>
                            <div class="form-group">
                                <label class="form-label">Account Number</label>
                                <input type="text" class="form-input" id="paymentAccountNumber"
                                    placeholder="Account Number">
                            </div>
                            <br>
                            <div class="form-group">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-input" id="paymentReference"
                                    placeholder="Reference/Transaction ID">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="payment-modal-actions">
                <button class="btn-secondary" onclick="backToBookingDetails()">
                    <i class="fas fa-arrow-left"></i>
                    Previous
                </button>
                <button class="btn-primary" onclick="confirmBooking()">
                    Confirm Booking &nbsp;
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Cash Payment Handlers -->
    <script src="{{ asset('js/admin/cash-payment-handlers.js') }}"></script>
    <!-- Bookings Management with SweetAlert2 Helpers -->
    <script src="{{ asset('js/admin/bookings-management.js') }}"></script>

    <script>

        let selectedBookingId = null;
        let currentBookings = @json($bookings);
        let selectedPackage = null;
        let selectedPaymentMethod = null;
        let selectedPackageId = null;
        let totalAmount = 0;
        let selectedEditPackage = null;
        let currentEditBookingId = null;
        let bookedDateRanges = []; // Store booked date ranges for Flatpickr
        let closedDates = []; // Store closed dates from dashboard
        let checkInPicker = null;
        let checkOutPicker = null;
        let editCheckOutPicker = null; // Flatpickr instance for edit booking checkout date
        let currentRemainingBalance = 0; // Store remaining balance for payment history calculations
        let originalBookingData = null; // Store original booking data for comparison
        let currentBookingStatus = null; // Store current booking status for verify button state


        @verbatim
            // Helper function to format date without timezone conversion
            function formatDateLocal(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // Helper: parse YYYY-MM-DD into a local Date fixed at midday (12:00) to avoid
            // off-by-one shifts caused by UTC conversion (midnight values can become previous day
            // when toISOString() is used or when the browser applies timezone offsets).
            function parseYMDToLocalMidday(ymd) {
                if (!ymd || !/^\d{4}-\d{2}-\d{2}$/.test(ymd)) return null;
                const [y, m, d] = ymd.split('-').map(Number);
                return new Date(y, m - 1, d, 12, 0, 0); // Midday local time
            }

            // Helper function to format payment purpose for display
            function formatPaymentPurpose(purpose) {
                if (!purpose) return '–';

                const purposeMap = {
                    'partial_payment': 'Partial Payment',
                    'full_payment': 'Full Payment',
                    'downpayment': 'Downpayment'
                };

                // Check if it's a known underscore format
                if (purposeMap[purpose.toLowerCase()]) {
                    return purposeMap[purpose.toLowerCase()];
                }

                // Return as-is if already formatted
                return purpose;
            }

            // Load closed dates from server
            async function loadClosedDates() {
                try {
                    const response = await fetch('/admin/bookings/closed-dates', {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        closedDates = data.closed_dates || [];
                        console.log('Closed dates loaded:', closedDates);
                    } else {
                        console.error('Failed to load closed dates');
                    }
                } catch (error) {
                    console.error('Error loading closed dates:', error);
                }
            }

            // Generic modal functions
            function openModal(modalId) {
                document.getElementById(modalId).classList.add('show');
            }

            function closeModal(modalId) {
                document.getElementById(modalId).classList.remove('show');
            }

            function backToBookingDetails() {
                document.getElementById('paymentMethodModal').style.display = 'none';
                document.getElementById('newBookingModal').style.display = 'block';
            }

            function proceedToPayment() {
                // Validate required fields first
                const firstName = document.getElementById('firstName').value.trim();
                const lastName = document.getElementById('lastName').value.trim();
                const email = document.getElementById('email').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const checkInDate = document.getElementById('checkInDate').value;
                const checkOutDate = document.getElementById('checkOutDate').value;

                if (!firstName || !lastName || !email || !phone || !checkInDate || !checkOutDate) {
                    showWarning('Missing Information', 'Please fill in all guest details and dates.');
                    return;
                }

                if (!selectedPackageId) {
                    showWarning('No Package Selected', 'Please select a package.');
                    return;
                }

                // Proceed to payment modal
                document.getElementById('newBookingModal').classList.remove('show');
                document.getElementById('paymentMethodModal').classList.add('show');

                // Calculate payment and update summary AFTER modal is shown
                calculatePayment();
            }

            function confirmBooking() {
                if (!selectedPaymentMethod) {
                    showWarning('No Payment Method', 'Please select a payment method');
                    return;
                }

                const paymentPurpose = document.getElementById('paymentPurpose').value;
                if (!paymentPurpose) {
                    showWarning('No Payment Purpose', 'Please select a payment purpose');
                    return;
                }

                // Calculate required amounts based on booking policy
                const amountPaid = parseFloat(document.getElementById('paymentAmount').value) || 0;
                const totalAmountValue = totalAmount;

                // Get booking dates to determine payment policy
                const checkInStr = document.getElementById('checkInDate').value;
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const checkIn = new Date(checkInStr);
                checkIn.setHours(0, 0, 0, 0);
                const daysUntilCheckIn = Math.ceil((checkIn - today) / (1000 * 60 * 60 * 24));

                // Simplified downpayment calculation
                const requiredDownpayment = daysUntilCheckIn >= 14 ? 1000 : (totalAmountValue * 0.5);

                // Validate payment amount based on purpose
                if (paymentPurpose === 'downpayment') {
                    if (Math.abs(amountPaid - requiredDownpayment) > 0.01) {
                        showError('Invalid Downpayment', `Downpayment must be exactly <strong>₱${requiredDownpayment.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);
                        return;
                    }
                } else if (paymentPurpose === 'full_payment') {
                    if (Math.abs(amountPaid - totalAmountValue) > 0.01) {
                        showError('Invalid Full Payment', `Full payment must be exactly <strong>₱${totalAmountValue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);
                        return;
                    }
                }

                // Gather form data
                const formData = {
                    guest_fname: document.getElementById('firstName').value.trim(),
                    guest_lname: document.getElementById('lastName').value.trim(),
                    guest_email: document.getElementById('email').value.trim(),
                    guest_phone: document.getElementById('phone').value.trim(),
                    guest_address: document.getElementById('address').value.trim(),
                    check_in: document.getElementById('checkInDate').value,
                    check_out: document.getElementById('checkOutDate').value,
                    regular_guests: parseInt(document.getElementById('regularGuests').value) || 0,
                    children: parseInt(document.getElementById('children').value) || 0,
                    num_of_seniors: parseInt(document.getElementById('seniorGuests').value) || 0,
                    package_id: selectedPackageId,
                    payment_method: selectedPaymentMethod,
                    payment_purpose: paymentPurpose,
                    amount_paid: amountPaid,
                    reference_number: document.getElementById('paymentReference')?.value?.trim() || ''
                };

                console.log('Submitting booking data:', formData);

                // Create booking on server; if PayMongo is selected the API returns redirect_url
                fetch('/admin/bookings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(formData)
                })
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.redirect_url) {
                            // PayMongo flow: redirect to hosted checkout
                            window.location.href = data.redirect_url;
                            return;
                        }

                        if (!data.success) {
                            showError('Booking Failed', 'Error creating booking draft: ' + (data.message || 'Unknown error'));
                            return;
                        }
                        // If booking returned directly (non-PayMongo flow)
                        if (data.booking && data.booking.BookingID) {
                            showSuccess('Booking Created!', `Booking ID: ${data.booking.BookingID}`, 2000).then(() => {
                                closePaymentMethodModal();
                                location.reload();
                            });
                            return;
                        }

                        // Generic fallback for success true
                        closePaymentMethodModal();
                        location.reload();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Error', 'Error creating booking draft. Please try again.');
                    });
            }

            // Search functionality with debounce
            // ...existing code...
            // Search functionality with debounce
            let searchTimeout;
            const setupFilterListeners = () => {
                const searchInput = document.getElementById('searchInput');
                const statusFilter = document.getElementById('statusFilter');
                const paymentFilter = document.getElementById('paymentFilter');

                if (searchInput) {
                    searchInput.addEventListener('input', function () {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => {
                            applyFilters();
                        }, 300);
                    });
                }

                if (statusFilter) {
                    statusFilter.addEventListener('change', function () {
                        applyFilters();
                    });
                }

                if (paymentFilter) {
                    paymentFilter.addEventListener('change', function () {
                        applyFilters();
                    });
                }
            };

            // Call setup when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setupFilterListeners);
            } else {
                setupFilterListeners();
            }

            // Helper: normalize value to lower-case string safely
            function normStr(value) {
                if (value === undefined || value === null) return '';
                return String(value).toLowerCase().trim();
            }

            /**
             * Robust helper that determines whether a booking matches the selected payment filter.
             * Accepts booking objects from currentBookings; handles multiple field names and numeric fallback.
             */
            function paymentMatchesFilter(booking, selectedPayment) {
                // 1. If no filter is selected, show all bookings.
                if (!selectedPayment || String(selectedPayment).trim() === '') return true;
                const sel = normStr(selectedPayment);

                // Standardize data access and normalization (assuming normStr exists and lowercases/trims)
                const status = normStr(booking.payment_status ?? booking.PaymentStatus ?? booking.payment?.PaymentStatus ?? '');
                const amountPaid = Number(booking.AmountPaid ?? booking.amount_paid ?? booking.payment?.AmountPaid ?? 0) || 0;
                const totalAmount = Number(booking.TotalAmount ?? booking.total_amount ?? booking.payment?.TotalAmount ?? booking.total_booking_amount ?? 0) || 0;
                const remaining = Number(booking.RemainingBalance ?? booking.remaining ?? (totalAmount - amountPaid)) || 0;

                // --- Filtering Logic ---

                // A. Filter: Fully Paid
                if (sel === 'fully paid' || sel === 'fullypaid') {
                    // Match explicit 'fully paid' status OR calculated full payment
                    return status === 'fully paid' || status === 'fullypaid' || (totalAmount > 0 && amountPaid >= totalAmount) || remaining <= 0;
                }

                // B. Filter: Downpayment
                if (sel === 'downpayment') {
                    // Match explicit 'downpayment' status.
                    // OR derive it as a partial payment that is typically a deposit (e.g., less than 50% of total)
                    const isDownpaymentByAmount = totalAmount > 0 ? (amountPaid > 0 && amountPaid < totalAmount && amountPaid < (totalAmount * 0.5)) : false;

                    return status === 'downpayment' || isDownpaymentByAmount;
                }

                // C. Filter: Partial (Covers any remaining partial payments not classified as downpayment)
                // NOTE: If you need 'Partial' to include 'Downpayment' too, combine the logic from 'B' and 'C'.
                if (sel === 'partial') {
                    // Match explicit 'partial' status.
                    // OR derive it as a partial payment (AmountPaid > 0 but less than TotalAmount)
                    const isPartialByAmount = amountPaid > 0 && amountPaid < totalAmount && remaining > 0;

                    return status === 'partial' || isPartialByAmount;
                }

                // D. Filter: Other explicit statuses (e.g., 'For Verification', 'Unpaid')
                // This covers any statuses that were added to the dropdown but don't have special logic above.
                return status === sel;
            }

            // Status matching helper (case-insensitive)
            function statusMatchesFilter(booking, selectedStatus) {
                if (!selectedStatus || String(selectedStatus).trim() === '') return true;
                const sel = normStr(selectedStatus);
                const status = normStr(booking.BookingStatus ?? booking.booking_status ?? '');
                return status === sel;
            }

            // Helper function used by the paymentFilter change listener
            function paymentFilter() {
                const paymentSelect = document.getElementById('paymentFilter');
                const selectedPayment = paymentSelect.value;

                // Get the current URL parameters
                const url = new URL(window.location.href);

                // Set or delete the 'payment' query parameter
                if (selectedPayment) {
                    url.searchParams.set('payment', selectedPayment);
                } else {
                    url.searchParams.delete('payment');
                }

                // --- (Logic to preserve other filters: search, status, sort) ---
                // (This part should carry over your existing code)
                const searchInput = document.getElementById('searchInput');
                if (searchInput && searchInput.value) {
                    url.searchParams.set('search', searchInput.value);
                } else {
                    url.searchParams.delete('search');
                }

                const statusSelect = document.getElementById('statusFilter');
                if (statusSelect && statusSelect.value) {
                    url.searchParams.set('status', statusSelect.value);
                } else {
                    url.searchParams.delete('status');
                }

                // Go to the new URL to trigger the filtered search on the server
                window.location.href = url.toString();
            }

            // 🚀 Add the event listener after the DOM is loaded
            document.addEventListener('DOMContentLoaded', function () {
                const paymentSelect = document.getElementById('paymentFilter');
                if (paymentSelect) {
                    // Attach the function to the 'change' event of the select element
                    paymentSelect.addEventListener('change', paymentFilter);
                }
            });

            // Search matching helper — checks BookingID, guest name, email, and phone number ONLY
            function searchMatchesFilter(booking, row, query) {
                if (!query) return true;
                const q = normStr(query);

                // BookingID
                if (booking && booking.BookingID && normStr(booking.BookingID).includes(q)) return true;

                // Guest name from DOM cell (safer when guest relationship not included in currentBookings)
                const guestCellText = row.querySelector('td:nth-child(2)')?.textContent || '';
                if (normStr(guestCellText).includes(q)) return true;

                // Guest object in booking - only check name, email, and phone
                const guest = booking.guest ?? booking.Guest ?? {};
                const guestSearchFields = [
                    guest.GuestName, guest.FName, guest.LName, guest.FullName, guest.name,
                    guest.email, guest.Email,
                    guest.Phone, guest.phone
                ];
                for (const v of guestSearchFields) {
                    if (v && normStr(v).includes(q)) return true;
                }

                return false;
            }

            // Main filter + sort + re-append function
            function applyFilters() {
                const search = document.getElementById('searchInput')?.value || '';
                const status = document.getElementById('statusFilter')?.value || '';
                const payment = document.getElementById('paymentFilter')?.value || '';
                // Always use the smart sort order: Staying > Booked > Completed
                const sort = 'checkin_nearest';

                let bookingRows = Array.from(document.querySelectorAll('.booking-row'));

                // Filter rows
                bookingRows.forEach(row => {
                    const bookingId = row.getAttribute('data-booking-id');
                    const booking = currentBookings.find(b => String(b.BookingID) === String(bookingId));
                    if (!booking) {
                        row.style.display = 'none';
                        row.dataset.visible = 'false';
                        return;
                    }

                    const okSearch = searchMatchesFilter(booking, row, search);
                    const okStatus = statusMatchesFilter(booking, status);
                    const okPayment = paymentMatchesFilter(booking, payment);

                    if (okSearch && okStatus && okPayment) {
                        row.style.display = '';
                        row.dataset.visible = 'true';
                    } else {
                        row.style.display = 'none';
                        row.dataset.visible = 'false';
                    }
                });

                // Sort visible rows
                let visibleRows = bookingRows.filter(r => r.dataset.visible === 'true');

                const today = Date.now();

                visibleRows.sort((a, b) => {
                    const statusA = normStr(a.getAttribute('data-status') || '');
                    const statusB = normStr(b.getAttribute('data-status') || '');

                    // Priority order: Staying > Booked/Upcoming > Completed > Cancelled
                    const getPriorityOrder = (status) => {
                        if (status === 'staying') return 1;
                        if (status === 'booked' || status === 'upcoming') return 2;
                        if (status === 'completed') return 3;
                        if (status === 'cancelled') return 4;
                        return 5;
                    };

                    const priorityA = getPriorityOrder(statusA);
                    const priorityB = getPriorityOrder(statusB);

                    // For default sort (checkin_nearest), always prioritize by status first
                    if (sort === 'checkin_nearest') {
                        if (priorityA !== priorityB) {
                            return priorityA - priorityB;
                        }

                        // Within same priority group, apply specific sorting
                        const dateA = a.getAttribute('data-checkin-date');
                        const dateB = b.getAttribute('data-checkin-date');
                        const timeA = new Date(dateA).getTime();
                        const timeB = new Date(dateB).getTime();

                        if (statusA === 'completed' || statusB === 'completed') {
                            // For Completed bookings: sort descending (latest first, oldest last)
                            return (isNaN(timeB) ? -Infinity : timeB) - (isNaN(timeA) ? -Infinity : timeA);
                        } else {
                            // For Staying and Booked: sort ascending (nearest check-in first)
                            return (isNaN(timeA) ? Infinity : timeA) - (isNaN(timeB) ? Infinity : timeB);
                        }
                    }

                    // Other sort types
                    switch (sort) {
                        case 'checkin_farthest':
                            const dateAFar = a.getAttribute('data-checkin-date');
                            const dateBFar = b.getAttribute('data-checkin-date');
                            const timeAFar = new Date(dateAFar).getTime();
                            const timeBFar = new Date(dateBFar).getTime();
                            return (isNaN(timeBFar) ? -Infinity : timeBFar) - (isNaN(timeAFar) ? -Infinity : timeAFar);

                        case 'bookingdate_oldest':
                            const createdA = a.getAttribute('data-created-at') || '';
                            const createdB = b.getAttribute('data-created-at') || '';
                            const timeCreatedA = new Date(createdA).getTime();
                            const timeCreatedB = new Date(createdB).getTime();
                            return (isNaN(timeCreatedA) ? Infinity : timeCreatedA) - (isNaN(timeCreatedB) ? Infinity : timeCreatedB);

                        case 'name_asc':
                            const nameA = normStr(a.getAttribute('data-guest-name') || '');
                            const nameB = normStr(b.getAttribute('data-guest-name') || '');
                            return nameA.localeCompare(nameB);

                        case 'name_desc':
                            const nameADesc = normStr(a.getAttribute('data-guest-name') || '');
                            const nameBDesc = normStr(b.getAttribute('data-guest-name') || '');
                            return nameBDesc.localeCompare(nameADesc);

                        default:
                            return 0;
                    }
                });

                const tbody = document.getElementById('bookingsTableBody');
                if (tbody) {
                    visibleRows.forEach(row => tbody.appendChild(row));
                }
            }

            // ensure there is an initial application so the view reflects filters that might have been set by server query params
            document.addEventListener('DOMContentLoaded', function () { applyFilters(); });
            // ...existing code...

            // Date calculations for new booking
            // Note: Date change events are handled by Flatpickr onChange callbacks
            document.getElementById('regularGuests')?.addEventListener('input', calculateTotal);
            document.getElementById('children')?.addEventListener('input', calculateTotal);
            document.getElementById('seniorGuests')?.addEventListener('input', calculateTotal);

            function calculateDays() {
                const checkIn = document.getElementById('checkInDate').value;
                const checkOut = document.getElementById('checkOutDate').value;

                if (checkIn && checkOut) {
                    const startDate = new Date(checkIn);
                    const endDate = new Date(checkOut);
                    const diffTime = Math.abs(endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                    document.getElementById('daysOfStay').value = diffDays;
                    document.getElementById('summaryDays').textContent = diffDays + ' days';
                    calculateTotal();
                }
            }

            function calculateTotal() {
                const regular = parseInt(document.getElementById('regularGuests')?.value || 0);
                const children = parseInt(document.getElementById('children')?.value || 0);
                const seniors = parseInt(document.getElementById('seniorGuests')?.value || 0);
                const totalGuests = regular + children + seniors;

                document.getElementById('totalGuests').value = totalGuests;

                if (selectedPackage) {
                    const packagePrice = selectedPackage === 'A' ? 15000 : 12000;
                    const days = parseInt(document.getElementById('daysOfStay')?.value || 1);
                    const maxGuests = 30;
                    // Only count regular guests for excess calculation (children are free)
                    const excessGuests = Math.max(0, (regular + seniors) - maxGuests);
                    const excessFee = excessGuests * 100; // ₱100 per excess guest
                    const baseAmount = packagePrice * days;
                    // NOTE: Senior discounts are NOT applied automatically online.
                    // They will be handled at the resort front desk during bill out.
                    const totalAmount = baseAmount + excessFee;
                    const downpayment = totalAmount * 0.3; // 30% downpayment

                    document.getElementById('summaryExcess').textContent = excessGuests;
                    document.getElementById('summaryTotal').textContent = '₱ ' + totalAmount.toLocaleString() + '.00';
                    document.getElementById('summaryDownpayment').textContent = '₱ ' + downpayment.toLocaleString() + '.00';
                }
            }
            // Check for date conflicts
            let dateConflictTimeout;
            function checkDateConflict() {
                clearTimeout(dateConflictTimeout);
                dateConflictTimeout = setTimeout(() => {
                    const checkIn = document.getElementById('checkInDate').value;
                    const checkOut = document.getElementById('checkOutDate').value;

                    if (!checkIn || !checkOut) {
                        document.getElementById('dateConflictError').style.display = 'none';
                        return;
                    }

                    fetch('/admin/bookings/check-date-conflict', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            check_in: checkIn,
                            check_out: checkOut
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.conflict) {
                                document.getElementById('dateConflictError').style.display = 'block';
                                document.getElementById('dateConflictMessage').textContent = data.message;
                            } else {
                                document.getElementById('dateConflictError').style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error checking date conflict:', error);
                        });
                }, 500);
            }

            // Handle payment purpose change
            function handlePaymentPurposeChange() {
                const purpose = document.getElementById('paymentPurpose').value;
                const amountField = document.getElementById('paymentAmount');

                if (!totalAmount || totalAmount === 0) {
                    return;
                }

                // Get booking dates to determine payment policy
                const checkInStr = document.getElementById('checkInDate').value;
                if (!checkInStr) {
                    return;
                }

                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const checkIn = new Date(checkInStr);
                checkIn.setHours(0, 0, 0, 0);
                const daysUntilCheckIn = Math.ceil((checkIn - today) / (1000 * 60 * 60 * 24));

                // Simplified: Always use same downpayment logic
                let downpayment = daysUntilCheckIn >= 14 ? 1000 : (totalAmount * 0.5);

                if (purpose === 'downpayment') {
                    amountField.value = downpayment.toFixed(2);
                } else if (purpose === 'full_payment') {
                    amountField.value = totalAmount.toFixed(2);
                }
            }

            // Validate payment amount (not needed since field is readonly, but kept for reference)
            function validatePaymentAmount() {
                // Field is readonly, so validation happens on submit
                return true;
            }

            // New Booking Modal Functions
            async function openNewBookingModal() {
                document.getElementById('newBookingModal').classList.add('show');
                await loadClosedDates(); // Load closed dates first
                loadBookedDates(); // This will initialize the date pickers with default dates
            }

            // Load booked dates to disable them in the date picker
            async function loadBookedDates() {
                try {
                    const response = await fetch('/admin/bookings/booked-dates/all');
                    const data = await response.json();
                    if (data.success && data.booked_dates) {
                        bookedDateRanges = data.booked_dates;
                        initializeDatePickers();
                    } else {
                        // Initialize date pickers anyway even if we couldn't load booked dates
                        initializeDatePickers();
                    }
                } catch (error) {
                    console.error('Error loading booked dates:', error);
                    // Initialize date pickers anyway even if we couldn't load booked dates
                    initializeDatePickers();
                }
            }

            // Initialize Flatpickr date pickers with booked dates disabled
            function initializeDatePickers() {
                // Destroy existing pickers if they exist
                if (checkInPicker) checkInPicker.destroy();
                if (checkOutPicker) checkOutPicker.destroy();

                const today = new Date();

                // Function to check if a date falls within any booked range or is closed
                function isDateBooked(date) {
                    // Format date without timezone conversion
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const dateStr = `${year}-${month}-${day}`;

                    // Check if date is closed
                    if (closedDates.includes(dateStr)) {
                        return true;
                    }

                    // Check if date is booked
                    return bookedDateRanges.some(range => {
                        return dateStr >= range.start && dateStr <= range.end;
                    });
                }

                // Initialize Check-in Date Picker
                checkInPicker = flatpickr("#checkInDate", {
                    minDate: "today",
                    dateFormat: "Y-m-d",
                    disable: [
                        function (date) {
                            return isDateBooked(date);
                        }
                    ],
                    onChange: function (selectedDates, dateStr, instance) {
                        // Update check-out minimum date
                        if (checkOutPicker && selectedDates[0]) {
                            const nextDay = new Date(selectedDates[0]);
                            nextDay.setDate(nextDay.getDate() + 1);
                            checkOutPicker.set('minDate', nextDay);
                        }
                        calculateDays();
                        checkDateConflict();
                    },
                    onDayCreate: function (dObj, dStr, fp, dayElem) {
                        const date = dayElem.dateObj;
                        const dateStr = formatDateLocal(date);

                        // Check if date is closed
                        if (closedDates.includes(dateStr)) {
                            dayElem.style.backgroundColor = '#f3f4f6';
                            dayElem.style.color = '#9ca3af';
                            dayElem.style.textDecoration = 'line-through';
                            dayElem.title = 'Date is closed';
                            // Add red indicator
                            const indicator = document.createElement('div');
                            indicator.style.cssText = 'position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; background-color: #ef4444; border-radius: 50%;';
                            dayElem.style.position = 'relative';
                            dayElem.appendChild(indicator);
                        }
                        // Check if date is booked
                        else if (bookedDateRanges.some(range => dateStr >= range.start && dateStr <= range.end)) {
                            dayElem.style.backgroundColor = '#fee2e2';
                            dayElem.style.color = '#dc2626';
                            dayElem.style.textDecoration = 'line-through';
                            dayElem.title = 'Date not available - already booked';
                        }
                    }
                });

                // Initialize Check-out Date Picker
                checkOutPicker = flatpickr("#checkOutDate", {
                    minDate: "today",
                    dateFormat: "Y-m-d",
                    disable: [
                        function (date) {
                            return isDateBooked(date);
                        }
                    ],
                    onChange: function (selectedDates, dateStr, instance) {
                        calculateDays();
                        checkDateConflict();
                    },
                    onDayCreate: function (dObj, dStr, fp, dayElem) {
                        const date = dayElem.dateObj;
                        const dateStr = formatDateLocal(date);

                        // Check if date is closed
                        if (closedDates.includes(dateStr)) {
                            dayElem.style.backgroundColor = '#f3f4f6';
                            dayElem.style.color = '#9ca3af';
                            dayElem.style.textDecoration = 'line-through';
                            dayElem.title = 'Date is closed';
                            // Add red indicator
                            const indicator = document.createElement('div');
                            indicator.style.cssText = 'position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; background-color: #ef4444; border-radius: 50%;';
                            dayElem.style.position = 'relative';
                            dayElem.appendChild(indicator);
                        }
                        // Check if date is booked
                        else if (bookedDateRanges.some(range => dateStr >= range.start && dateStr <= range.end)) {
                            dayElem.style.backgroundColor = '#fee2e2';
                            dayElem.style.color = '#dc2626';
                            dayElem.style.textDecoration = 'line-through';
                            dayElem.title = 'Date not available - already booked';
                        }
                    }
                });

                // Set default dates
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                if (checkInPicker) {
                    checkInPicker.setDate(today);
                }
                if (checkOutPicker) {
                    checkOutPicker.setDate(tomorrow);
                }
                calculateDays();
            }

            // Initialize Flatpickr for Edit Booking checkout date
            function initializeEditCheckoutPicker(checkInDate, defaultCheckOutDate) {
                // Destroy existing picker if it exists
                if (editCheckOutPicker) editCheckOutPicker.destroy();

                // Function to check if a date falls within any booked range
                function isDateBooked(date) {
                    const dateStr = formatDateLocal(date);
                    return bookedDateRanges.some(range => {
                        return dateStr >= range.start && dateStr <= range.end;
                    });
                }

                // FIX: Prevent off-by-one (previous day) issue when backend sends YYYY-MM-DD strings.
                // If the incoming values are plain date strings, parsing them with new Date("YYYY-MM-DD")
                // treats them as UTC, which can shift to the previous local day for timezones west of UTC.
                // We convert them to local midday using the helper to stabilize the date without TZ drift.
                if (typeof checkInDate === 'string') {
                    const parsedIn = parseYMDToLocalMidday(checkInDate);
                    if (parsedIn) checkInDate = parsedIn;
                }
                if (typeof defaultCheckOutDate === 'string') {
                    const parsedOut = parseYMDToLocalMidday(defaultCheckOutDate);
                    if (parsedOut) defaultCheckOutDate = parsedOut;
                }

                // Normalize default checkout date to midday to avoid timezone shifting earlier
                const normalizedDefault = defaultCheckOutDate ? new Date(defaultCheckOutDate.getFullYear(), defaultCheckOutDate.getMonth(), defaultCheckOutDate.getDate(), 12, 0, 0) : null;

                // Initialize Check-out Date Picker for Edit Booking
                editCheckOutPicker = flatpickr("#editCheckOutDate", {
                    minDate: new Date(checkInDate.getTime() + 24 * 60 * 60 * 1000), // Day after check-in
                    dateFormat: "F d, Y", // Format like "October 10, 2025"
                    defaultDate: normalizedDefault,
                    disable: [
                        function (date) {
                            const dateStr = date.toISOString().split('T')[0];
                            const currentBookingCheckIn = originalBookingData.checkInDate;
                            const currentBookingCheckOut = originalBookingData.checkOutDate;

                            // Don't disable dates within the current booking's range
                            if (dateStr >= currentBookingCheckIn && dateStr <= currentBookingCheckOut) {
                                return false;
                            }

                            // Disable closed dates
                            if (closedDates.includes(dateStr)) {
                                return true;
                            }

                            // Disable other booked dates
                            return isDateBooked(date);
                        }
                    ],
                    onChange: function (selectedDates, dateStr, instance) {
                        // Optional: Add any validation or calculations here
                        console.log('Edit checkout date changed:', dateStr);
                    },
                    onDayCreate: function (dObj, dStr, fp, dayElem) {
                        const date = dayElem.dateObj;
                        const dateStr = formatDateLocal(date);
                        const currentBookingCheckIn = originalBookingData.checkInDate;
                        const currentBookingCheckOut = originalBookingData.checkOutDate;

                        // Don't mark current booking dates as booked
                        if (dateStr >= currentBookingCheckIn && dateStr <= currentBookingCheckOut) {
                            return;
                        }

                        // Check if date is closed
                        if (closedDates.includes(dateStr)) {
                            dayElem.style.backgroundColor = '#f3f4f6';
                            dayElem.style.color = '#9ca3af';
                            dayElem.style.textDecoration = 'line-through';
                            dayElem.title = 'Date is closed';
                            // Add red indicator
                            const indicator = document.createElement('div');
                            indicator.style.cssText = 'position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; background-color: #ef4444; border-radius: 50%;';
                            dayElem.style.position = 'relative';
                            dayElem.appendChild(indicator);
                        }
                        // Check if date is booked
                        else if (bookedDateRanges.some(range => dateStr >= range.start && dateStr <= range.end)) {
                            dayElem.style.backgroundColor = '#fee2e2';
                            dayElem.style.color = '#dc2626';
                            dayElem.style.textDecoration = 'line-through';
                            dayElem.title = 'Date not available - already booked';
                            // Add green indicator for booked dates
                            const indicator = document.createElement('div');
                            indicator.style.cssText = 'position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; background-color: #10b981; border-radius: 50%;';
                            dayElem.style.position = 'relative';
                            dayElem.appendChild(indicator);
                        }
                    }
                });

                // Explicitly set date after initialization (ensures selection even if defaultDate skipped)
                if (normalizedDefault) {
                    editCheckOutPicker.setDate(normalizedDefault, true);
                }
            }

            function closeNewBookingModal() {
                document.getElementById('newBookingModal').classList.remove('show');
                resetNewBookingForm();
            }

            function resetNewBookingForm() {
                document.querySelectorAll('#newBookingModal input').forEach(input => {
                    input.value = '';
                });
                document.querySelectorAll('.package-card').forEach(card => {
                    card.classList.remove('selected');
                });
                selectedPackage = null;
                document.getElementById('summaryPackage').textContent = '-';
                document.getElementById('summaryDays').textContent = '0 days';
                document.getElementById('summaryExcess').textContent = '0';
                document.getElementById('summaryTotal').textContent = '₱ 0.00';
                document.getElementById('summaryDownpayment').textContent = '₱ 0.00';
            }

            function selectPackage(packageId, packageName, packagePrice, maxGuests) {
                document.querySelectorAll('.package-card').forEach(card => {
                    card.classList.remove('selected');
                });
                document.querySelector(`[data-package-id="${packageId}"]`).classList.add('selected');

                // Set package data
                selectedPackageId = packageId;
                selectedPackage = {
                    id: packageId,
                    name: packageName,
                    price: packagePrice,
                    max_guests: maxGuests
                };

                calculatePayment();
            }

            function selectPaymentMethod(method) {
                document.querySelectorAll('.payment-method-card').forEach(card => {
                    card.classList.remove('selected');
                });
                document.querySelector(`[data-method="${method}"]`).classList.add('selected');
                selectedPaymentMethod = method;

                // Show payment details section
                document.getElementById('paymentDetailsSection').style.display = 'block';

                // Update selected payment method display
                const methodNames = {
                    'cash': 'Cash',
                    'paymongo': 'PayMongo (Online)'
                };
                document.getElementById('selectedPaymentMethod').value = methodNames[method];

                // Update fields based on payment method
                const accountRow = document.getElementById('accountDetailsRow');
                if (method === 'cash' || method === 'paymongo') {
                    accountRow.style.display = 'none';
                } else {
                    accountRow.style.display = 'block';
                }

                // Toggle cash-specific fields
                toggleCashFieldsNewBooking();

                // Ensure payment amount is filled based on selected purpose
                handlePaymentPurposeChange();
            }

            function backToBookingDetails() {
                document.getElementById('paymentMethodModal').classList.remove('show');
                document.getElementById('newBookingModal').classList.add('show');
            }

            // Booking row click handler
            document.querySelectorAll('.booking-row').forEach(row => {
                row.addEventListener('click', function () {
                    const bookingId = this.getAttribute('data-booking-id');
                    selectBooking(bookingId);
                });
            });

            function selectBooking(bookingId) {
                // Remove previous selection
                document.querySelectorAll('.booking-row').forEach(row => {
                    row.classList.remove('selected');
                });

                // Add selection to clicked row
                document.querySelector(`[data-booking-id="${bookingId}"]`).classList.add('selected');

                selectedBookingId = bookingId;

                // Fetch booking details (with improved logging and error handling)
                console.log('selectBooking: fetching booking id', bookingId);
                fetch(`/admin/bookings/${bookingId}`)
                    .then(response => {
                        console.log('selectBooking: response status', response.status, response.statusText);
                        if (!response.ok) {
                            // Try to capture response body for debugging
                            return response.text().then(text => {
                                console.error('selectBooking: non-OK response body:', text);
                                throw new Error('Non-OK response: ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('selectBooking: payload', data);
                        displayBookingInfo(data);
                    })
                    .catch(error => {
                        console.error('Error fetching booking details:', error);
                        // Show a small notice in the booking info panel so admin sees something happened
                        const content = document.getElementById('bookingInfoContent');
                        if (content) {
                            content.innerHTML = `<div class="booking-info-error">Unable to load booking details. Check console/network for details.</div>`;
                        }
                    });
            }

            function displayBookingInfo(data) {
                const content = document.getElementById('bookingInfoContent');
                if (!data || !data.booking) { if (content) content.innerHTML = `<div class="booking-info-error">No booking data</div>`; return; }
                const booking = data.booking || {}, guest = data.guest || {}, payment = data.payment || {}, packageInfo = data.package || {};

                // Fetch updated total amount if booking is completed (includes rentals and discounts)
                if (booking.BookingStatus === 'Completed') {
                    fetch(`/admin/bookings/${booking.BookingID}/outstanding`)
                        .then(response => response.json())
                        .then(outstandingData => {
                            if (outstandingData.success) {
                                // Store detailed breakdown
                                payment.breakdown = {
                                    booking_amount: outstandingData.booking_amount || 0,
                                    rental_charges: outstandingData.rental_charges || 0,
                                    senior_discount: outstandingData.senior_discount || 0,
                                    total_amount: outstandingData.total_amount || 0,
                                    total_paid: outstandingData.total_paid || 0,
                                    remaining_balance: outstandingData.remaining_balance || 0
                                };
                                payment.TotalAmount = outstandingData.total_amount || payment.TotalAmount;
                                payment.AmountPaid = outstandingData.total_paid || payment.AmountPaid;
                                payment.RemainingBalance = outstandingData.remaining_balance || payment.RemainingBalance;

                                // Re-render with updated data
                                renderBookingInfo(booking, guest, payment, packageInfo);
                            } else {
                                // Render with original data if fetch fails
                                renderBookingInfo(booking, guest, payment, packageInfo);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching outstanding balance:', error);
                            // Render with original data if fetch fails
                            renderBookingInfo(booking, guest, payment, packageInfo);
                        });
                } else {
                    // For non-completed bookings, render normally
                    renderBookingInfo(booking, guest, payment, packageInfo);
                }

                function renderBookingInfo(booking, guest, payment, packageInfo) {
                    let amenitiesHtml = '';
                    if (packageInfo?.amenities_array?.length > 0) {
                        amenitiesHtml = `<div class="detail-item"><span class="detail-label">Amenities</span><div class="amenities-list">${packageInfo.amenities_array.map(a => `<div class="amenity-item"><i class="fas fa-check" style="color: #10b981;"></i><span>${a}</span></div>`).join('')}</div></div>`;
                    }

                    // Define payment status colors
                    const paymentStatusColors = {
                        'Fully Paid': '#166534',
                        'Partial': '#1e40af',
                        'Downpayment': '#92400e',
                        'For Verification': '#ea580c',
                        'Unpaid': '#dc2626'
                    };
                    const paymentStatusColor = paymentStatusColors[payment?.PaymentStatus] || '#374151';

                    // Build payment information HTML with breakdown if available
                    let paymentInfoHtml = `
                                                                                                                                                                                                                            <div class="detail-item">
                                                                                                                                                                                                                                <span class="detail-label">Payment Status</span>
                                                                                                                                                                                                                                <span class="detail-value" style="color: ${paymentStatusColor}; font-weight: 600;">${payment?.PaymentStatus || 'N/A'}</span>
                                                                                                                                                                                                                            </div>`;

                    // If we have a breakdown (completed bookings), show detailed charges
                    if (payment?.breakdown) {
                        const breakdown = payment.breakdown;
                        paymentInfoHtml += `
                                                                                                                                                                                                                                <div class="detail-item">
                                                                                                                                                                                                                                    <span class="detail-label">Booking Amount</span>
                                                                                                                                                                                                                                    <span class="detail-value">₱${breakdown.booking_amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                                                                                                                                                                                                                                </div>`;

                        if (breakdown.rental_charges > 0) {
                            paymentInfoHtml += `
                                                                                                                                                                                                                                    <div class="detail-item">
                                                                                                                                                                                                                                        <span class="detail-label">Rental Charges</span>
                                                                                                                                                                                                                                        <span class="detail-value">₱${breakdown.rental_charges.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                                                                                                                                                                                                                                    </div>`;
                        }

                        if (breakdown.senior_discount > 0) {
                            paymentInfoHtml += `
                                                                                                                                                                                                                                    <div class="detail-item">
                                                                                                                                                                                                                                        <span class="detail-label">Senior Discount</span>
                                                                                                                                                                                                                                        <span class="detail-value" style="color: #16a34a;">-₱${breakdown.senior_discount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                                                                                                                                                                                                                                    </div>`;
                        }

                        paymentInfoHtml += `
                                                                                                                                                                                                                                <div class="detail-item" style="border-top: 2px solid #e5e7eb; padding-top: 0.75rem; margin-top: 0.5rem;">
                                                                                                                                                                                                                                    <span class="detail-label" style="font-weight: 600;">Total Amount</span>
                                                                                                                                                                                                                                    <span class="detail-value" style="font-weight: 600; font-size: 1.125rem;">₱${breakdown.total_amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                <div class="detail-item">
                                                                                                                                                                                                                                    <span class="detail-label">Amount Paid</span>
                                                                                                                                                                                                                                    <span class="detail-value">₱${breakdown.total_paid.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                <div class="detail-item">
                                                                                                                                                                                                                                    <span class="detail-label">Remaining Balance</span>
                                                                                                                                                                                                                                    <span class="detail-value highlight">₱${breakdown.remaining_balance.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                                                                                                                                                                                                                                </div>`;
                    } else {
                        // For non-completed bookings, show simple view
                        paymentInfoHtml += `
                                                                                                                                                                                                                                <div class="detail-item">
                                                                                                                                                                                                                                    <span class="detail-label">Total Amount</span>
                                                                                                                                                                                                                                    <span class="detail-value">₱${payment?.TotalAmount ? payment.TotalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '0.00'}</span>
                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                <div class="detail-item">
                                                                                                                                                                                                                                    <span class="detail-label">Amount Paid</span>
                                                                                                                                                                                                                                    <span class="detail-value">₱${payment?.AmountPaid ? payment.AmountPaid.toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '0.00'}</span>
                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                <div class="detail-item">
                                                                                                                                                                                                                                    <span class="detail-label">Remaining Balance</span>
                                                                                                                                                                                                                                    <span class="detail-value highlight">₱${payment?.RemainingBalance ? payment.RemainingBalance.toLocaleString('en-PH', { minimumFractionDigits: 2 }) : '0.00'}</span>
                                                                                                                                                                                                                                </div>`;
                    }

                    content.innerHTML = `<div class="booking-info-details"><div class="info-section"><h3 class="info-section-title">Guest Information</h3><div class="guest-info"><div class="guest-name">${guest?.GuestName || 'Unknown'}</div><div class="contact-info"><div class="contact-item"><i class="fas fa-envelope"></i><span>${guest?.Email || 'N/A'}</span></div><div class="contact-item"><i class="fas fa-phone"></i><span>${guest?.Phone || 'N/A'}</span></div><div class="contact-item"><i class="fas fa-map-marker-alt"></i><span>${guest?.Address || 'N/A'}</span></div></div></div><div class="booking-action-buttons">${(() => { const disabled = ['Cancelled', 'Completed'].includes(booking.BookingStatus); let html = `<button class="action-btn btn-edit" onclick="editBooking('${booking.BookingID}')" ${disabled ? 'disabled' : ''}><i class="fas fa-edit"></i> Edit</button>`; if (booking.BookingStatus === 'Cancelled') { html += `<button class="action-btn btn-reopen" onclick="reopenBooking('${booking.BookingID}')"><i class="fas fa-redo"></i> Reopen</button>`; } else { html += `<button class="action-btn btn-cancel" onclick="cancelBooking('${booking.BookingID}')" ${disabled ? 'disabled' : ''}><i class="fas fa-times"></i> Cancel</button>`; } html += `<button class="action-btn btn-payments" onclick="openPaymentModal('${booking.BookingID}')"><i class="fas fa-wallet"></i> Payments</button>`; if (payment?.PaymentStatus === 'For Verification') { html += `<button class="action-btn btn-verify" onclick="verifyPayment('${payment.UnverifiedPaymentID || ''}')"><i class="fas fa-check-circle"></i> Verify</button>`; } return html; })()}</div></div><div class="info-section"><h3 class="info-section-title">Booking Details</h3><div class="booking-details"><div class="detail-item"><span class="detail-label">Booking ID</span><span class="detail-value highlight">${booking.BookingID}</span></div><div class="detail-item"><span class="detail-label">Booking Date</span><span class="detail-value">${booking.BookingDate}</span></div><div class="detail-item"><span class="detail-label">Check In</span><div style="display: flex; flex-direction: column;"><span class="detail-value">${booking.CheckInDate}</span><span style="font-size: 14px; color: #6b7280; font-weight: 500;">${booking.ActualCheckInTime || '–'}</span></div></div><div class="detail-item"><span class="detail-label">Check Out</span><div style="display: flex; flex-direction: column;"><span class="detail-value">${booking.CheckOutDate}</span><span style="font-size: 14px; color: #6b7280; font-weight: 500;">${booking.ActualCheckOutTime || '–'}</span></div></div><div class="detail-item"><span class="detail-label">Days of Stay</span><span class="detail-value highlight">${booking.DaysOfStay} ${booking.DaysOfStay === 1 ? 'day' : 'days'}</span></div></div></div><div class="info-section"><h3 class="info-section-title">Payment Information</h3><div class="booking-details">${paymentInfoHtml}</div></div><div class="info-section"><h3 class="info-section-title">Guest Count</h3><div class="booking-details"><div class="detail-item"><span class="detail-label">Total Pax</span><span class="detail-value highlight">${booking.Pax || 0}</span></div><div class="detail-item"><span class="detail-label">Adults</span><span class="detail-value">${booking.NumOfAdults || 0}</span></div><div class="detail-item"><span class="detail-label">Seniors</span><span class="detail-value">${booking.NumOfSeniors || 0}</span></div><div class="detail-item"><span class="detail-label">Children</span><span class="detail-value">${booking.NumOfChild || 0}</span></div></div></div><div class="info-section"><h3 class="info-section-title">Package</h3><div class="booking-details"><div class="detail-item"><span class="detail-label">Package Name</span><span class="detail-value highlight">${packageInfo?.Name || 'N/A'}</span></div><div class="detail-item"><span class="detail-label">Price per Day</span><span class="detail-value">₱${packageInfo?.Price ? packageInfo.Price.toLocaleString('en-PH', { minimumFractionDigits: 2 }) : 'N/A'}</span></div>${amenitiesHtml}</div></div></div>`;
                }
            }

            async function editBooking(bookingId) {
                currentEditBookingId = bookingId;

                // Load closed dates first
                await loadClosedDates();

                // Fetch booking details first
                fetch(`/admin/bookings/${bookingId}`)
                    .then(response => response.json())
                    .then(data => {
                        const booking = data.booking;
                        const guest = data.guest;
                        const packageInfo = data.package;
                        const payment = data.payment;

                        // Store original booking data for comparison
                        // Previous approach substring(0,10) caused off-by-one if source was ISO UTC (e.g. 2025-11-24T16:00:00.000Z) because it trimmed the UTC date portion.
                        // New logic: detect ISO strings and derive the local date via Date parsing; otherwise accept plain YYYY-MM-DD as-is.
                        function extractLocalYMD(source) {
                            if (!source) return '';
                            const s = source.toString();
                            // ISO pattern with 'T'
                            if (/^\d{4}-\d{2}-\d{2}T/.test(s)) {
                                const dt = new Date(s);
                                if (!isNaN(dt)) return formatDateLocal(dt); // Local date portion (YYYY-MM-DD)
                            }
                            // Plain date already
                            if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
                            // Fallback: substring but ONLY if starts with YYYY-MM-DD
                            return s.substring(0, 10);
                        }
                        const rawCheckIn = extractLocalYMD(booking.CheckInDateRaw || booking.CheckInDate);
                        const rawCheckOut = extractLocalYMD(booking.CheckOutDateRaw || booking.CheckOutDate);
                        console.debug('[editBooking] Raw date strings:', { rawCheckIn, rawCheckOut });
                        originalBookingData = {
                            checkInDate: rawCheckIn,
                            checkOutDate: rawCheckOut,
                            adults: booking.NumOfAdults || 1,
                            children: booking.NumOfChild || 0,
                            seniors: booking.NumOfSeniors || 0,
                            packageId: packageInfo?.PackageID,
                            packagePrice: packageInfo?.Price || 0,
                            packageName: packageInfo?.Name || packageInfo?.PackageName || '',
                            maxGuests: packageInfo?.MaxGuests || 30,
                            totalAmount: payment?.TotalAmount || 0
                        };

                        // Populate form fields (split into F/M/L names)
                        if (guest) {
                            document.getElementById('editFName').value = guest.FName || '';
                            document.getElementById('editMName').value = guest.MName || '';
                            document.getElementById('editLName').value = guest.LName || '';
                            // Maintain hidden legacy combined field
                            const combinedName = [guest.FName, guest.MName, guest.LName].filter(Boolean).join(' ').trim();
                            document.getElementById('editGuestName').value = combinedName;
                        } else {
                            document.getElementById('editFName').value = '';
                            document.getElementById('editMName').value = '';
                            document.getElementById('editLName').value = '';
                            document.getElementById('editGuestName').value = '';
                        }
                        document.getElementById('editEmail').value = guest?.Email || '';
                        document.getElementById('editPhone').value = guest?.Phone || '';
                        document.getElementById('editAddress').value = guest?.Address || '';

                        // Helper: parse a YYYY-MM-DD string into a local Date locked at 12:00 (midday) to avoid
                        // timezone-induced day shifts (midnight values can jump to previous day in some locales).

                        // Create Date objects using midday-safe parser to ensure Flatpickr preselect matches DB date
                        const checkInDate = parseYMDToLocalMidday(originalBookingData.checkInDate);
                        const checkOutDate = parseYMDToLocalMidday(originalBookingData.checkOutDate);
                        console.debug('[editBooking] Parsed local midday dates:', { checkInDate, checkOutDate });

                        document.getElementById('editCheckInDate').value = checkInDate.toLocaleDateString('en-US', {
                            month: 'long',
                            day: 'numeric',
                            year: 'numeric'
                        });

                        document.getElementById('editAdults').value = originalBookingData.adults;
                        document.getElementById('editChildren').value = originalBookingData.children;
                        // Pre-fill seniors if available
                        document.getElementById('editSeniors').value = originalBookingData.seniors || 0;

                        // Select current package using dynamic data
                        if (packageInfo) {
                            selectEditPackage(
                                packageInfo.PackageID,
                                packageInfo.Name || packageInfo.PackageName,
                                packageInfo.Price,
                                packageInfo.MaxGuests
                            );
                        }

                        // Pre-set the checkout input's visible value before Flatpickr to ensure correct day is shown.
                        if (checkOutDate && !isNaN(checkOutDate)) {
                            document.getElementById('editCheckOutDate').value = checkOutDate.toLocaleDateString('en-US', {
                                month: 'long', day: 'numeric', year: 'numeric'
                            });
                        }

                        // Initialize Flatpickr for checkout date (if dates parsed correctly)
                        if (checkInDate && checkOutDate && !isNaN(checkInDate) && !isNaN(checkOutDate)) {
                            initializeEditCheckoutPicker(checkInDate, checkOutDate);
                        } else {
                            console.warn('[editBooking] Invalid date(s) for Flatpickr initialization', originalBookingData);
                        }

                        // Open modal
                        document.getElementById('editBookingModal').classList.add('show');
                    })
                    .catch(error => {
                        console.error('Error fetching booking details:', error);
                        showError('Failed to Load', 'Error loading booking details');
                    });
            }

            function closeEditBookingModal() {
                document.getElementById('editBookingModal').classList.remove('show');
                if (editCheckOutPicker) {
                    editCheckOutPicker.destroy();
                    editCheckOutPicker = null;
                }
                resetEditForm();
            }

            function resetEditForm() {
                document.querySelectorAll('#editBookingModal input').forEach(input => {
                    if (input.type !== 'date' || input.id !== 'editCheckInDate') {
                        input.value = '';
                    }
                });
                document.querySelectorAll('#editBookingModal .package-card').forEach(card => {
                    card.classList.remove('selected');
                });
                selectedEditPackage = null;
            }

            function selectEditPackage(packageId, packageName, packagePrice, maxGuests) {
                document.querySelectorAll('#editBookingModal .package-card').forEach(card => {
                    card.classList.remove('selected');
                });
                document.querySelector(`#editBookingModal [data-package-id="${packageId}"]`).classList.add('selected');

                selectedEditPackage = {
                    id: packageId,
                    name: packageName,
                    price: packagePrice,
                    max_guests: maxGuests
                };
            }

            function proceedToEditSummary() {
                // Build full guest name from separate fields
                const fName = document.getElementById('editFName').value.trim();
                const mName = document.getElementById('editMName').value.trim();
                const lName = document.getElementById('editLName').value.trim();
                const guestName = [fName, mName, lName].filter(Boolean).join(' ').trim();
                // Keep hidden legacy field updated
                document.getElementById('editGuestName').value = guestName;
                const email = document.getElementById('editEmail').value.trim();
                const phone = document.getElementById('editPhone').value.trim();
                const checkOutDate = document.getElementById('editCheckOutDate').value;
                const adults = parseInt(document.getElementById('editAdults').value) || 0;

                if (!guestName || !email || !phone || !checkOutDate || adults < 1) {
                    showWarning('Missing Information', 'Please fill in all required fields and ensure at least 1 adult.');
                    return;
                }

                if (!selectedEditPackage) {
                    showWarning('No Package Selected', 'Please select a package.');
                    return;
                }

                // Calculate new totals
                updateEditSummary();

                // Show summary modal
                document.getElementById('editBookingModal').classList.remove('show');
                document.getElementById('editSummaryModal').classList.add('show');
            }

            function updateEditSummary() {
                const adults = parseInt(document.getElementById('editAdults').value) || 1;
                const children = parseInt(document.getElementById('editChildren').value) || 0;
                const seniors = parseInt(document.getElementById('editSeniors')?.value) || 0;

                // Get the selected date from Flatpickr
                const checkOutDate = editCheckOutPicker.selectedDates[0];
                // Use midday parser for check-in as well to keep day calculations stable.
                const checkInDate = parseYMDToLocalMidday(originalBookingData.checkInDate);

                if (!checkOutDate) {
                    showWarning('Missing Date', 'Please select a check-out date');
                    return;
                }

                const days = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));

                if (days <= 0) {
                    showWarning('Invalid Date Range', 'Check-out date must be after check-in date');
                    return;
                }

                // Calculate total guests (only adults count for excess)
                // Only adults + seniors count toward excess; children are free
                const adultCount = adults + seniors;
                const maxGuests = selectedEditPackage.max_guests || 30;
                const excessGuests = Math.max(0, adultCount - maxGuests);

                // Calculate new amounts
                const packageTotal = selectedEditPackage.price * days;
                const excessTotal = excessGuests * 100; // ₱100 per excess guest
                // NOTE: Senior discounts are NOT applied automatically online.
                // They will be verified and processed at the front desk during bill out.
                const newTotalAmount = packageTotal + excessTotal;

                // Get original total amount
                const originalTotal = originalBookingData.totalAmount || 0;

                // Calculate additional amount due (can be negative if new total is less)
                const additionalDue = newTotalAmount - originalTotal;

                // Update display
                document.getElementById('editSummaryPackage').textContent = `${selectedEditPackage.name} (₱${selectedEditPackage.price.toLocaleString()})`;
                document.getElementById('editSummaryDays').textContent = `${days} day${days > 1 ? 's' : ''} (${days} × ₱${selectedEditPackage.price.toLocaleString()})`;
                document.getElementById('editSummaryExcess').textContent = `${excessGuests} guest (${excessGuests} × ₱100)`;
                document.getElementById('editSummaryOriginalTotal').textContent = `₱${originalTotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                document.getElementById('editSummaryTotal').textContent = `₱${newTotalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

                // Display additional amount with proper formatting
                const additionalElement = document.getElementById('editSummaryAdditional');
                if (additionalDue > 0) {
                    additionalElement.textContent = `₱${additionalDue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    additionalElement.style.color = '#dc2626'; // Red for additional charge
                } else if (additionalDue < 0) {
                    additionalElement.textContent = `-₱${Math.abs(additionalDue).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    additionalElement.style.color = '#16a34a'; // Green for refund/credit
                } else {
                    additionalElement.textContent = `₱0.00`;
                    additionalElement.style.color = '#6b7280'; // Gray for no change
                }
            }

            function closeEditSummaryModal() {
                document.getElementById('editSummaryModal').classList.remove('show');
            }

            function backToEditBooking() {
                document.getElementById('editSummaryModal').classList.remove('show');
                document.getElementById('editBookingModal').classList.add('show');
            }

            function confirmBookingChanges() {
                // Get the selected checkout date from Flatpickr
                const checkOutDate = editCheckOutPicker.selectedDates[0];
                if (!checkOutDate) {
                    showWarning('Missing Date', 'Please select a check-out date');
                    return;
                }

                // Format checkout date as YYYY-MM-DD (without timezone conversion)
                const formattedCheckOut = formatDateLocal(checkOutDate);

                // Prepare data to send to backend
                // Build full guest name again (in case user modified fields after summary)
                const fName = document.getElementById('editFName').value.trim();
                const mName = document.getElementById('editMName').value.trim();
                const lName = document.getElementById('editLName').value.trim();
                const fullGuestName = [fName, mName, lName].filter(Boolean).join(' ').trim();
                const updateData = {
                    guest_name: fullGuestName,
                    email: document.getElementById('editEmail').value.trim(),
                    phone: document.getElementById('editPhone').value.trim(),
                    address: document.getElementById('editAddress').value.trim(),
                    checkout_date: formattedCheckOut,
                    adults: parseInt(document.getElementById('editAdults').value) || 1,
                    children: parseInt(document.getElementById('editChildren').value) || 0,
                    seniors: parseInt(document.getElementById('editSeniors').value) || 0,
                    package_id: selectedEditPackage.id
                };

                console.log('Updating booking with data:', updateData);
                console.log('Booking ID:', currentEditBookingId);

                // Submit to backend
                fetch(`/admin/bookings/${currentEditBookingId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(updateData)
                })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            return response.json().then(err => {
                                console.error('Server error:', err);
                                throw new Error(err.message || 'Server error');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
                            showSuccess('Booking Updated!', 'Booking updated successfully!', 1500).then(() => {
                                closeEditSummaryModal();
                                closeEditBookingModal();
                                location.reload();
                            });
                        } else {
                            showError('Update Failed', 'Error updating booking: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Error', 'Error updating booking: ' + error.message);
                    });
            }

            function cancelBooking(bookingId) {
                showConfirm('Cancel Booking?', 'Are you sure you want to cancel this booking?', 'Yes, Cancel', 'No').then((result) => {
                    if (result.isConfirmed) {
                        showInput('User ID Required', 'Please enter your User ID to confirm cancellation:', 'Enter User ID', 'User ID is required to cancel a booking.').then((result) => {
                            if (result.isConfirmed && result.value) {
                                const userId = result.value.trim();

                                // Update booking status to cancelled
                                fetch(`/admin/bookings/${bookingId}/status`, {
                                    method: 'PUT',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({
                                        status: 'Cancelled',
                                        user_id: userId.trim()
                                    })
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            showSuccess('Cancelled', 'Booking cancelled successfully', 1500).then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            showError('Failed', data.message || 'Error updating booking status');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        showError('Error', 'Error updating booking status');
                                    });
                            }
                        });
                    }
                });
            }

            function reopenBooking(bookingId) {
                showConfirm('Reopen Booking?', 'Are you sure you want to reopen this cancelled booking?', 'Yes, Reopen', 'No').then((result) => {
                    if (result.isConfirmed) {
                        showInput('User ID Required', 'Please enter your User ID to confirm reopening:', 'Enter User ID', 'User ID is required to reopen a booking.').then((result) => {
                            if (result.isConfirmed && result.value) {
                                const userId = result.value.trim();

                                // Update booking status to confirmed
                                fetch(`/admin/bookings/${bookingId}/status`, {
                                    method: 'PUT',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({
                                        status: 'Confirmed',
                                        user_id: userId.trim()
                                    })
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            showSuccess('Reopened', 'Booking reopened successfully', 1500).then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            showError('Failed', data.message || 'Error updating booking status');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        showError('Error', 'Error updating booking status');
                                    });
                            }
                        });
                    }
                });
            }

            function openPaymentModal(bookingId) {
                selectedBookingId = bookingId; // Store the booking ID
                const modal = document.getElementById('paymentModal');
                modal.classList.add('show');

                // Reset to Payment History mode by default
                document.querySelectorAll('.payment-toggle .toggle-option').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector('.payment-toggle .toggle-option:first-child').classList.add('active');
                document.querySelectorAll('.payment-mode').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById('payment-history-mode').classList.add('active');

                // Reset payment form fields
                document.getElementById('paymentMethod').value = '';
                document.getElementById('paymentHistoryPurpose').value = '';
                document.getElementById('paymentHistoryAmount').value = '';
                document.getElementById('accountName').value = '';
                document.getElementById('accountNumber').value = '';
                document.getElementById('referenceNumber').value = '';

                // Update field visibility based on default payment method
                updatePaymentFieldsVisibility();

                // Fetch booking details with payments
                fetch(`/admin/bookings/${bookingId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayPaymentHistory(data.payments || []);
                            updateBalanceSummary(data);

                            // Store booking status
                            currentBookingStatus = data.booking.BookingStatus;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        showError('Failed to Load', 'Error loading payment information');
                    });
            }

            function updateBalanceSummary(data) {
                const payment = data.payment;

                // Use the calculated values from the backend
                const totalAmount = payment?.TotalAmount || 0;
                const totalPaid = payment?.AmountPaid || 0;
                const remainingBalance = payment?.RemainingBalance || 0;

                // Store remaining balance for payment purpose calculations
                currentRemainingBalance = remainingBalance;

                // Update balance summary display
                document.getElementById('totalBookingAmount').textContent = `₱${parseFloat(totalAmount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                document.getElementById('totalPaidAmount').textContent = `₱${parseFloat(totalPaid).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                document.getElementById('remainingBalance').textContent = `₱${parseFloat(remainingBalance).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            }

            function closePaymentModal() {
                const modal = document.getElementById('paymentModal');
                modal.classList.remove('show');
            }

            function displayPaymentHistory(payments) {
                const tbody = document.getElementById('paymentHistoryBody');
                tbody.innerHTML = '';

                if (!payments || payments.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="no-payment-records">No payment records found</td></tr>';
                    return;
                }

                payments.forEach(payment => {
                    const row = document.createElement('tr');

                    // Define color mapping for payment statuses
                    const statusColors = {
                        'Fully Paid': '#166534',
                        'Partial Payment': '#1e40af',
                        'Partial': '#1e40af',
                        'Downpayment': '#92400e',
                        'For Verification': '#ea580c',
                        'Unpaid': '#dc2626'
                    };

                    // Get display text (remove "Payment" from "Partial Payment")
                    let statusDisplay = payment.PaymentStatus;
                    if (statusDisplay === 'Partial Payment') {
                        statusDisplay = 'Partial';
                    }

                    const statusColor = statusColors[payment.PaymentStatus] || '#374151';

                    row.innerHTML = `
                                                                                                                                                                                                                            <td>${payment.PaymentID}</td>
                                                                                                                                                                                                                            <td>₱${parseFloat(payment.Amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                                                                                                                                                                                            <td>${formatPaymentPurpose(payment.PaymentPurpose)}</td>
                                                                                                                                                                                                                            <td>${payment.ReferenceNumber || 'N/A'}</td>
                                                                                                                                                                                                                            <td>${payment.PaymentDate}</td>
                                                                                                                                                                                                                            <td><span style="color: ${statusColor}; font-weight: 500;">${statusDisplay}</span></td>
                                                                                                                                                                                                                        `;
                    tbody.appendChild(row);
                });
            }

            // Switch between Payment History and Add Payment modes
            function switchPaymentMode(mode) {
                // Update toggle buttons
                document.querySelectorAll('.payment-toggle .toggle-option').forEach(btn => {
                    btn.classList.remove('active');
                });
                event.target.closest('.toggle-option').classList.add('active');

                // Update payment modes
                document.querySelectorAll('.payment-mode').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById('payment-' + mode + '-mode').classList.add('active');
            }

            function addPayment() {
                const paymentData = {
                    payment_method: document.getElementById('paymentMethod').value,
                    purpose: document.getElementById('paymentHistoryPurpose').value,
                    amount: document.getElementById('paymentHistoryAmount').value,
                    account_name: document.getElementById('accountName').value,
                    account_number: document.getElementById('accountNumber').value,
                    reference_number: document.getElementById('referenceNumber').value
                };

                if (!paymentData.payment_method || !paymentData.purpose || !paymentData.amount) {
                    showWarning('Missing Information', 'Please fill in all required fields');
                    return;
                }

                // Validate payment amount based on purpose
                const amountPaid = parseFloat(paymentData.amount);
                const halfBalance = currentRemainingBalance * 0.5;

                if (paymentData.purpose === 'full_payment') {
                    if (Math.abs(amountPaid - currentRemainingBalance) > 0.01) {
                        showError('Invalid Full Payment', `Full payment must be exactly <strong>₱${currentRemainingBalance.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);
                        return;
                    }
                } else if (paymentData.purpose === 'partial_payment') {
                    if (Math.abs(amountPaid - halfBalance) > 0.01) {
                        showError('Invalid Partial Payment', `Partial payment must be exactly <strong>₱${halfBalance.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);
                        return;
                    }
                }

                fetch(`/admin/bookings/${selectedBookingId}/add-payment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(paymentData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess('Payment Added!', 'Payment added successfully!', 1500).then(() => {
                                closePaymentModal();
                                // Refresh payment history
                                openPaymentModal(selectedBookingId);
                            });
                        } else {
                            showError('Failed', 'Error adding payment: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showError('Error', 'Error adding payment');
                    });
            }

            // Close modal when clicking outside
            document.getElementById('paymentModal').addEventListener('click', function (e) {
                if (e.target === this) {
                    closePaymentModal();
                }
            });

            // Payment proof modal and image handling removed (payment images are no longer stored)

            // Update verify button state based on booking status
            function updateVerifyButtonState() {
                // This function is no longer needed since verify buttons are now per-payment in the table
                // Keeping it empty for backward compatibility
            }

            // Verify payment function - now accepts paymentID parameter
            function verifyPayment(paymentId) {
                showInput('User ID Required', 'Please enter your User ID to confirm payment verification:', 'Enter User ID', 'User ID is required to verify payment.').then((result) => {
                    if (result.isConfirmed && result.value) {
                        const userId = result.value.trim();

                        // Send verification request to backend
                        fetch(`/admin/bookings/${selectedBookingId}/verify-payment`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                user_id: userId.trim(),
                                payment_id: paymentId
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showSuccess('Payment Verified!', data.message || 'Payment verified successfully!', 1500).then(() => {
                                        closePaymentModal();
                                        location.reload(); // Refresh the page to show updated status
                                    });
                                } else {
                                    showError('Verification Failed', 'Error verifying payment: ' + (data.message || 'Unknown error'));
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showError('Error', 'Error verifying payment. Please try again.');
                            });
                    }
                });
            }

            // Handle payment method change in payment history modal
            document.getElementById('paymentMethod').addEventListener('change', function () {
                updatePaymentFieldsVisibility();
                toggleCashFieldsPaymentHistory();
            });

            // Handle payment purpose change in payment history modal
            document.getElementById('paymentHistoryPurpose').addEventListener('change', function () {
                handlePaymentHistoryPurposeChange();
            });

            function handlePaymentHistoryPurposeChange() {
                const purpose = document.getElementById('paymentHistoryPurpose').value;
                const amountField = document.getElementById('paymentHistoryAmount');

                if (!currentRemainingBalance || currentRemainingBalance === 0) {
                    amountField.value = '';
                    return;
                }

                if (purpose === 'full_payment') {
                    // Full Payment: Set to remaining balance
                    amountField.value = currentRemainingBalance.toFixed(2);
                } else if (purpose === 'partial_payment') {
                    // Partial Payment: Set to 50% of remaining balance
                    const halfBalance = currentRemainingBalance * 0.5;
                    amountField.value = halfBalance.toFixed(2);
                } else {
                    // No purpose selected: Clear the field
                    amountField.value = '';
                }
            }

            // Unified payment action for existing booking (cash or paymongo)
            async function handlePaymentAction() {
                const method = document.getElementById('paymentMethod').value;
                if (!method) { showWarning('No Payment Method', 'Please select a payment method'); return; }
                if (method === 'cash') {
                    return addPayment();
                }
                if (!selectedBookingId) { showWarning('No Booking Selected', 'No booking selected.'); return; }
                const purposeRaw = document.getElementById('paymentHistoryPurpose').value;
                const amountStr = document.getElementById('paymentHistoryAmount').value;
                if (!purposeRaw) { showWarning('No Purpose Selected', 'Please select a purpose.'); return; }
                const amount = parseFloat(amountStr || '0');
                if (!amount || amount <= 0) { showWarning('Invalid Amount', 'Invalid amount.'); return; }
                const purpose = purposeRaw === 'full_payment' ? 'Full Payment' : 'Partial Payment';
                try {
                    const resp = await fetch('/admin/payments/paymongo/link', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ purpose, amount, agree: true, booking_id: selectedBookingId })
                    });
                    if (!resp.ok) {
                        const data = await resp.json().catch(() => ({}));
                        throw new Error(data.message || 'Failed to create payment link');
                    }
                    const data = await resp.json();
                    if (!data.checkout_url) throw new Error('Missing checkout URL');
                    window.location.href = data.checkout_url;
                } catch (e) {
                    console.error('PayMongo error:', e);
                    showError('PayMongo Error', e.message || 'Unable to proceed with PayMongo.');
                }
            }

            function updatePaymentFieldsVisibility() {
                const paymentMethod = document.getElementById('paymentMethod').value;

                if (paymentMethod === 'cash' || paymentMethod === 'paymongo' || paymentMethod === '') {
                    // Hide account-related form groups for cash payments or when no method is selected
                    document.querySelectorAll('#accountName, #accountNumber, #referenceNumber').forEach(input => {
                        const formGroup = input.closest('.form-group');
                        if (formGroup) {
                            formGroup.style.display = 'none';
                            input.value = ''; // Clear the value
                        }
                    });
                } else {
                    // Show account-related form groups for non-cash payments
                    document.querySelectorAll('#accountName, #accountNumber, #referenceNumber').forEach(input => {
                        const formGroup = input.closest('.form-group');
                        if (formGroup) {
                            formGroup.style.display = 'block';
                        }
                    });
                }
            }

            function closeNewBookingModal() {
                document.getElementById('newBookingModal').classList.remove('show');
                resetNewBookingForm();
            }

            function closePaymentMethodModal() {
                document.getElementById('paymentMethodModal').classList.remove('show');
                resetBookingForm();
            }

            function resetBookingForm() {
                // Reset guest details
                document.getElementById('firstName').value = '';
                document.getElementById('lastName').value = '';
                document.getElementById('middleName').value = '';
                document.getElementById('email').value = '';
                document.getElementById('phone').value = '';
                document.getElementById('address').value = '';
                document.getElementById('checkInDate').value = '';
                document.getElementById('checkOutDate').value = '';
                document.getElementById('regularGuests').value = '1';
                document.getElementById('children').value = '0';

                // Reset package selection
                document.querySelectorAll('.package-card').forEach(card => {
                    card.classList.remove('selected');
                });
                selectedPackageId = null;
                selectedPackage = null;

                // Reset payment method
                document.querySelectorAll('.payment-method-card').forEach(card => {
                    card.classList.remove('selected');
                });
                selectedPaymentMethod = null;

                // Reset payment details
                document.getElementById('paymentDetailsSection').style.display = 'none';
                document.getElementById('selectedPaymentMethod').value = '';
                document.getElementById('paymentPurpose').value = '';
                document.getElementById('paymentAmount').value = '';
                document.getElementById('paymentAccountName').value = '';
                document.getElementById('paymentAccountNumber').value = '';
                document.getElementById('paymentReference').value = '';

                // Reset totals
                totalAmount = 0;
                updateSummaryDisplay();
            }

            function updateSummaryDisplay() {
                // Update the payment summary in the payment method modal
                const packageElement = document.getElementById('paymentSummaryPackage');
                const daysElement = document.getElementById('paymentSummaryDays');
                const adultsElement = document.getElementById('paymentSummaryAdults');
                const childrenElement = document.getElementById('paymentSummaryChildren');
                const packageTotalElement = document.getElementById('paymentSummaryPackageTotal');
                const excessElement = document.getElementById('paymentSummaryExcess');
                const totalElement = document.getElementById('paymentSummaryTotal');
                const downpaymentElement = document.getElementById('paymentSummaryDownpayment');

                if (selectedPackage) {
                    const adults = parseInt(document.getElementById('regularGuests').value) || 1;
                    const children = parseInt(document.getElementById('children').value) || 0;
                    const checkInStr = document.getElementById('checkInDate').value;
                    const checkOutStr = document.getElementById('checkOutDate').value;

                    if (!checkInStr || !checkOutStr) {
                        return; // Exit if dates not set
                    }

                    const checkIn = new Date(checkInStr);
                    const checkOut = new Date(checkOutStr);
                    const days = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));

                    // Calculate total guests (only adults count for excess)
                    const maxGuests = selectedPackage.max_guests || 30;
                    const excessGuests = Math.max(0, adults - maxGuests);

                    // Calculate amounts
                    const packageTotal = selectedPackage.price * days;
                    const excessFee = excessGuests * 100; // ₱100 per excess guest
                    totalAmount = packageTotal + excessFee;

                    // Calculate required payment based on booking policy
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const checkInDate = new Date(checkIn);
                    checkInDate.setHours(0, 0, 0, 0);

                    // Calculate days until check-in
                    const daysUntilCheckIn = Math.ceil((checkInDate - today) / (1000 * 60 * 60 * 24));

                    // Simplified downpayment calculation
                    let requiredPayment = daysUntilCheckIn >= 14 ? 1000 : (totalAmount * 0.5);
                    let paymentLabel = daysUntilCheckIn >= 14 ? 'Downpayment (₱1,000):' : 'Downpayment (50%):';

                    // Update display
                    packageElement.textContent = `${selectedPackage.name} - ₱${selectedPackage.price.toLocaleString()}/day`;
                    daysElement.textContent = `${days} ${days === 1 ? 'day' : 'days'} × ₱${selectedPackage.price.toLocaleString()} = ₱${packageTotal.toLocaleString()}`;
                    adultsElement.textContent = `${adults} ${adults === 1 ? 'adult' : 'adults'} (Max: ${maxGuests})`;
                    childrenElement.textContent = `${children} ${children === 1 ? 'child' : 'children'} (FREE)`;
                    packageTotalElement.textContent = `₱${packageTotal.toLocaleString()}`;

                    if (excessGuests > 0) {
                        excessElement.textContent = `${excessGuests} ${excessGuests === 1 ? 'guest' : 'guests'} × ₱100 = ₱${excessFee.toLocaleString()}`;
                    } else {
                        excessElement.textContent = '₱0.00 (No excess)';
                    }

                    totalElement.textContent = `₱${totalAmount.toLocaleString()}`;
                    downpaymentElement.textContent = `₱${requiredPayment.toLocaleString()}`;

                    // Update the payment label in booking summary
                    const paymentLabelElement = downpaymentElement.parentElement.querySelector('.total-label');
                    if (paymentLabelElement) {
                        paymentLabelElement.textContent = paymentLabel;
                    }

                    // Update payment purpose options based on booking policy
                    const purposeField = document.getElementById('paymentPurpose');
                    const reservationFeeOption = document.getElementById('reservationFeeOption');
                    const amountPaidField = document.getElementById('paymentAmount');

                    if (purposeField && downpaymentOption) {
                        // Always show downpayment option (no conditional display needed)
                        downpaymentOption.style.display = 'block';

                        // Default to downpayment if no purpose selected
                        if (!purposeField.value || purposeField.value === '') {
                            purposeField.value = 'downpayment';
                        }
                    }

                    // Always update the payment amount field based on current purpose
                    if (amountPaidField && purposeField) {
                        const currentPurpose = purposeField.value;
                        const downpaymentAmount = daysUntilCheckIn >= 14 ? 1000 : (totalAmount * 0.5);

                        if (currentPurpose === 'downpayment') {
                            amountPaidField.value = downpaymentAmount.toFixed(2);
                        } else if (currentPurpose === 'full_payment') {
                            amountPaidField.value = totalAmount.toFixed(2);
                        } else {
                            // Default to downpayment
                            purposeField.value = 'downpayment';
                            amountPaidField.value = downpaymentAmount.toFixed(2);
                        }
                    }
                }
            }

            // Update summary when form values change
            function calculatePayment() {
                updateSummaryDisplay();
            }

            // Add event listeners for real-time calculation
            document.addEventListener('DOMContentLoaded', function () {
                const calculationFields = ['regularGuests', 'children', 'checkInDate', 'checkOutDate'];
                calculationFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.addEventListener('change', calculatePayment);
                        field.addEventListener('input', calculatePayment);
                    }
                });

                // Add event listeners for edit modal calculation
                const editCalculationFields = ['editAdults', 'editChildren', 'editSeniors', 'editCheckOutDate'];
                editCalculationFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.addEventListener('change', updateEditSummary);
                        field.addEventListener('input', updateEditSummary);
                    }
                });
            });

            // Close new booking modal when clicking outside
            document.getElementById('newBookingModal')?.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeNewBookingModal();
                }
            });

            // Close payment method modal when clicking outside
            document.getElementById('paymentMethodModal')?.addEventListener('click', function (e) {
                if (e.target === this) {
                    closePaymentMethodModal();
                }
            });

            // Close edit booking modal when clicking outside
            document.getElementById('editBookingModal')?.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeEditBookingModal();
                }
            });

            // Close edit summary modal when clicking outside
            document.getElementById('editSummaryModal')?.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeEditSummaryModal();
                }
            });

            // Capitalize proper function - allows manual capitalization (e.g., Region XI stays as XI)
            function capitalizeProper(element) {
                const start = element.selectionStart;
                const end = element.selectionEnd;
                let value = element.value;

                // Only capitalize lowercase letters at word boundaries
                value = value.replace(/\b[a-z]/g, char => char.toUpperCase());

                element.value = value;
                element.setSelectionRange(start, end);
            }
        @endverbatim
    </script>
@endsection