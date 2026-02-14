@extends('layouts.admin')

@section('title', 'New Booking')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/bookings/booking-create.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/sweetalert-custom.css') }}">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection

@section('content')
    <div class="main-content">
        <div class="page-header">
            <button class="btn-back" onclick="window.location.href='{{ route('admin.bookings.index') }}'"
                title="Back to Bookings">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h1 class="page-title">New Booking</h1>
        </div>

        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="step active" id="step-indicator-1">
                <div class="step-number">1</div>
                <div class="step-label">Booking Details</div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step-indicator-2">
                <div class="step-number">2</div>
                <div class="step-label">Payment</div>
            </div>
        </div>

        <!-- Step Content -->
        <div class="step-content">
            <!-- Step 1: Booking Details -->
            <div id="step-1" class="step-panel active">
                <form id="bookingDetailsForm">
                    <!-- Guest Information Section -->
                    <div class="form-section">
                        <h3 class="section-title">Guest Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">First Name <span class="required-indicator">*</span></label>
                                <input type="text" class="form-input" id="firstName" name="first_name"
                                    placeholder="Enter First Name" required oninput="capitalizeProper(this)">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name <span class="required-indicator">*</span></label>
                                <input type="text" class="form-input" id="lastName" name="last_name"
                                    placeholder="Enter Last Name" required oninput="capitalizeProper(this)">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-input" id="middleName" name="middle_name"
                                    placeholder="Enter Middle Name (Optional)" oninput="capitalizeProper(this)">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email <span class="required-indicator">*</span></label>
                                <input type="email" class="form-input" id="email" name="email"
                                    placeholder="Enter email address" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone <span class="required-indicator">*</span></label>
                                <input type="tel" class="form-input" id="phone" name="phone"
                                    placeholder="Enter phone number" required oninput="this.value=this.value.replace(/[^0-9+]/g,'')">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-input" id="address" name="address"
                                    placeholder="Enter complete address" oninput="capitalizeProper(this)">
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details Section -->
                    <div class="form-section">
                        <h3 class="section-title">Booking Details</h3>
                        <div class="form-grid form-grid-3">
                            <div class="form-group">
                                <label class="form-label">Check In Date <span class="required-indicator">*</span></label>
                                <input type="date" class="form-input" id="checkInDate" name="check_in" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Check Out Date <span class="required-indicator">*</span></label>
                                <input type="date" class="form-input" id="checkOutDate" name="check_out" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Days of Stay</label>
                                <input type="number" class="form-input readonly-input" id="daysOfStay" placeholder="0"
                                    readonly>
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
                    <div class="form-section">
                        <h3 class="section-title">Guest Count</h3>
                        <div class="form-grid form-grid-4">
                            <div class="form-group">
                                <label class="form-label">Regular Guests <span class="required-indicator">*</span></label>
                                <input type="number" class="form-input" id="regularGuests" name="regular_guests"
                                    placeholder="0" min="0" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Senior Citizens</label>
                                <input type="number" class="form-input" id="seniorGuests" name="num_of_seniors"
                                    placeholder="0" min="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Children (6 yrs and below)</label>
                                <input type="number" class="form-input" id="children" name="children" placeholder="0"
                                    min="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Total Guests</label>
                                <input type="number" class="form-input readonly-input" id="totalGuests" placeholder="0"
                                    readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Package Selection Section -->
                    <div class="form-section">
                        <h3 class="section-title">Select a Package <span class="required-indicator">*</span></h3>
                        <div class="package-grid">
                            @foreach($packages as $package)
                                <div class="package-card" data-package-id="{{ $package->PackageID }}"
                                    data-package-price="{{ $package->Price }}"
                                    data-package-max-guests="{{ $package->max_guests }}"
                                    onclick="selectPackage('{{ $package->PackageID }}', '{{ $package->Name }}', {{ $package->Price }}, {{ $package->max_guests }})">
                                    <div class="package-header">
                                        <div class="package-title">{{ $package->Name }}</div>
                                        <div class="package-price">₱{{ number_format($package->Price, 0) }}</div>
                                    </div>
                                    <div class="package-max-guests">
                                        <i class="fas fa-users"></i> Max: {{ $package->max_guests ?? 30 }} guests
                                    </div>
                                    <div class="package-features">
                                        @if(!empty($package->amenities_array))
                                            @foreach($package->amenities_array as $amenity)
                                                <div class="amenity-item-container">
                                                    <i class="fas fa-check amenity-check-icon"></i>
                                                    <span class="amenity-text">{{ $amenity }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="no-amenities-text">No amenities listed</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </form>

                <!-- Step Navigation -->
                <div class="step-navigation">
                    <button type="button" class="btn-secondary"
                        onclick="window.location.href='{{ route('admin.bookings.index') }}'">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn-primary" onclick="goToStep2()">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: Payment -->
            <div id="step-2" class="step-panel">
                <form id="paymentForm">
                    <!-- Booking Summary Section -->
                    <div class="form-section">
                        <h3 class="section-title">Booking Summary</h3>
                        <div class="booking-summary-info">
                            <i class="fas fa-info-circle"></i>
                            System automatically applies correct downpayment based on your booking time.
                        </div>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">Package:</span>
                                <span class="summary-value" id="summaryPackage">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Days Staying:</span>
                                <span class="summary-value" id="summaryDays">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Adults:</span>
                                <span class="summary-value" id="summaryAdults">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Senior Citizens:</span>
                                <span class="summary-value" id="summarySeniors">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Children:</span>
                                <span class="summary-value" id="summaryChildren">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Package Total:</span>
                                <span class="summary-value" id="summaryPackageTotal">₱0.00</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Excess Fee:</span>
                                <span class="summary-value" id="summaryExcessFee">₱0.00</span>
                            </div>
                            <div class="summary-item summary-total">
                                <span class="summary-label">Total Amount:</span>
                                <span class="summary-value" id="summaryTotal">₱0.00</span>
                            </div>
                            <div class="summary-item summary-downpayment">
                                <span class="summary-label">Required Payment:</span>
                                <span class="summary-value" id="summaryDownpayment">₱0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="form-section">
                        <h3 class="section-title">Select Payment Method <span class="required-indicator">*</span></h3>
                        <div class="payment-methods-grid">
                            <div class="payment-method-card" data-method="cash" onclick="selectPaymentMethod('cash')">
                                <div class="payment-method-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="payment-method-title">Cash</div>
                                <div class="payment-method-desc">Direct cash payment</div>
                            </div>
                            <div class="payment-method-card" data-method="paymongo"
                                onclick="selectPaymentMethod('paymongo')">
                                <div class="payment-method-icon">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="payment-method-title">PayMongo (Online)</div>
                                <div class="payment-method-desc">GCash / Online Banking via PayMongo</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="form-section payment-details-section" id="paymentDetailsSection">
                        <h3 class="section-title">Payment Details</h3>
                        <div class="form-grid form-grid-3">
                            <div class="form-group">
                                <label class="form-label">Payment Method</label>
                                <input type="text" class="form-input readonly-input" id="selectedPaymentMethod" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Purpose <span class="required-indicator">*</span></label>
                                <select class="form-select" id="paymentPurpose" name="payment_purpose" required>
                                    <option value="" disabled selected>Select Purpose</option>
                                    <option value="downpayment">Downpayment</option>
                                    <option value="full_payment">Full Payment</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Amount</label>
                                <input type="number" class="form-input readonly-input-disabled" id="paymentAmount"
                                    placeholder="₱0.00" step="0.01" readonly>
                            </div>
                        </div>
                        <!-- Cash-only fields: Amount Received & Change -->
                        <div class="form-grid form-grid-2" id="cashFieldsCreateBooking" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">Amount Received</label>
                                <input type="number" class="form-input" id="amountReceivedCreateBooking" placeholder="₱0.00"
                                    step="0.01" oninput="calculateChangeCreateBooking()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Change</label>
                                <input type="number" class="form-input" id="changeAmountCreateBooking" placeholder="₱0.00"
                                    step="0.01" readonly style="background-color: #f3f4f6; font-weight: 600;">
                            </div>
                        </div>
                        <div class="form-grid form-grid-3 account-details-row" id="accountDetailsRow">
                            <div class="form-group">
                                <label class="form-label">Account Name</label>
                                <input type="text" class="form-input" id="paymentAccountName" name="account_name"
                                    placeholder="Account Name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Account Number</label>
                                <input type="text" class="form-input" id="paymentAccountNumber" name="account_number"
                                    placeholder="Account Number">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-input" id="paymentReference" name="reference_number"
                                    placeholder="Reference/Transaction ID">
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Step Navigation -->
                <div class="step-navigation">
                    <button type="button" class="btn-secondary" onclick="goToStep1()">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn-primary" onclick="confirmBooking()">
                        Confirm Booking <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

        let selectedPackageId = null;
        let selectedPackage = null;
        let selectedPaymentMethod = null;
        let totalAmount = 0;
        let bookedDateRanges = [];
        let closedDates = [];
        let checkInPicker = null;
        let checkOutPicker = null;

        // Helper function to format date without timezone conversion
        function formatDateLocal(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            loadClosedDates();
            loadBookedDates();

            // Add event listeners
            document.getElementById('regularGuests').addEventListener('input', calculateTotal);
            document.getElementById('seniorGuests')?.addEventListener('input', calculateTotal);
            document.getElementById('children').addEventListener('input', calculateTotal);
            document.getElementById('paymentPurpose').addEventListener('change', handlePaymentPurposeChange);
        });

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
                }
            } catch (error) {
                console.error('Error loading closed dates:', error);
            }
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
                    initializeDatePickers();
                }
            } catch (error) {
                console.error('Error loading booked dates:', error);
                initializeDatePickers();
            }
        }

        // Initialize Flatpickr date pickers
        function initializeDatePickers() {
            if (checkInPicker) checkInPicker.destroy();
            if (checkOutPicker) checkOutPicker.destroy();

            const today = new Date();

            function isDateBooked(date) {
                const dateStr = formatDateLocal(date);

                if (closedDates.includes(dateStr)) {
                    return true;
                }

                return bookedDateRanges.some(range => {
                    return dateStr >= range.start && dateStr <= range.end;
                });
            }

            checkInPicker = flatpickr("#checkInDate", {
                minDate: "today",
                dateFormat: "Y-m-d",
                disable: [
                    function (date) {
                        return isDateBooked(date);
                    }
                ],
                onChange: function (selectedDates, dateStr, instance) {
                    if (checkOutPicker && selectedDates[0]) {
                        const nextDay = new Date(selectedDates[0]);
                        nextDay.setDate(nextDay.getDate() + 1);
                        checkOutPicker.set('minDate', nextDay);
                    }
                    calculateDays();
                    checkDateConflict();
                }
            });

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
                }
            });

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

        // Calculate days of stay
        function calculateDays() {
            const checkIn = document.getElementById('checkInDate').value;
            const checkOut = document.getElementById('checkOutDate').value;

            if (checkIn && checkOut) {
                const startDate = new Date(checkIn);
                const endDate = new Date(checkOut);
                const diffTime = Math.abs(endDate - startDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                document.getElementById('daysOfStay').value = diffDays;
                calculateTotal();
            }
        }

        // Calculate total
        function calculateTotal() {
            const regular = parseInt(document.getElementById('regularGuests').value || 0);
            const seniors = parseInt(document.getElementById('seniorGuests')?.value || 0);
            const children = parseInt(document.getElementById('children').value || 0);
            const totalGuests = regular + children + seniors;

            document.getElementById('totalGuests').value = totalGuests;

            if (selectedPackage) {
                const days = parseInt(document.getElementById('daysOfStay').value || 1);
                const maxGuests = selectedPackage.max_guests || 30;
                // Adults + seniors count toward max guest limit; children are free
                const excessGuests = Math.max(0, (regular + seniors) - maxGuests);
                const excessFee = excessGuests * 100;

                const packageTotal = selectedPackage.price * days;

                // NOTE: Senior discounts are NOT applied automatically online. They are
                // processed at the front desk during bill out to prevent misuse.
                // Compute total as package total + excess fee only.

                totalAmount = packageTotal + excessFee;

                // Update summary
                updateSummary();
            }
        }

        function capitalizeProper(element) {
            const start = element.selectionStart;
            const end = element.selectionEnd;
            let value = element.value;

            // Only capitalize lowercase letters at word boundaries
            // This allows manual capitalization (e.g., Region XI stays as XI)
            value = value.replace(/\b[a-z]/g, char => char.toUpperCase());

            element.value = value;
            element.setSelectionRange(start, end);
        }

        // Update summary display
        function updateSummary() {
            const regular = parseInt(document.getElementById('regularGuests').value || 0);
            const seniors = parseInt(document.getElementById('seniorGuests')?.value || 0);
            const children = parseInt(document.getElementById('children').value || 0);
            const days = parseInt(document.getElementById('daysOfStay').value || 1);
            const maxGuests = selectedPackage.max_guests || 30;
            const excessGuests = Math.max(0, (regular + seniors) - maxGuests);
            const excessFee = excessGuests * 100;
            const packageTotal = selectedPackage.price * days;


            // NOTE: Senior discounts are NOT applied automatically online. They are
            // processed at the front desk during bill out to prevent misuse.

            const adultCount = regular + seniors; // kept for informational purposes
            totalAmount = packageTotal + excessFee;

            document.getElementById('summaryPackage').textContent = `${selectedPackage.name} (₱${selectedPackage.price.toLocaleString()})`;
            document.getElementById('summaryDays').textContent = `${days} day${days > 1 ? 's' : ''} (${days} × ₱${selectedPackage.price.toLocaleString()})`;
            document.getElementById('summaryAdults').textContent = `${regular} adult${regular > 1 ? 's' : ''}`;
            document.getElementById('summarySeniors').textContent = `${seniors} senior${seniors !== 1 ? 's' : ''} (discount processed at front desk)`;
            document.getElementById('summaryChildren').textContent = `${children} ${children === 1 ? 'child' : 'children'} (FREE)`;
            document.getElementById('summaryPackageTotal').textContent = `₱${packageTotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            document.getElementById('summaryExcessFee').textContent = `₱${excessFee.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            document.getElementById('summaryTotal').textContent = `₱${totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            // Calculate downpayment
            const checkInStr = document.getElementById('checkInDate').value;
            if (checkInStr) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const checkIn = new Date(checkInStr);
                checkIn.setHours(0, 0, 0, 0);
                const daysUntilCheckIn = Math.ceil((checkIn - today) / (1000 * 60 * 60 * 24));
                const downpayment = daysUntilCheckIn >= 14 ? 1000 : (totalAmount * 0.5);

                document.getElementById('summaryDownpayment').textContent = `₱${downpayment.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
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
                    document.getElementById('dateConflictError').classList.remove('show');
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
                            document.getElementById('dateConflictError').classList.add('show');
                            document.getElementById('dateConflictMessage').textContent = data.message;
                        } else {
                            document.getElementById('dateConflictError').classList.remove('show');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking date conflict:', error);
                    });
            }, 500);
        }

        // Select package
        function selectPackage(packageId, packageName, packagePrice, maxGuests) {
            document.querySelectorAll('.package-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelector(`[data-package-id="${packageId}"]`).classList.add('selected');

            selectedPackageId = packageId;
            selectedPackage = {
                id: packageId,
                name: packageName,
                price: packagePrice,
                max_guests: maxGuests
            };

            calculateTotal();
        }

        // Select payment method
        function selectPaymentMethod(method) {
            document.querySelectorAll('.payment-method-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelector(`[data-method="${method}"]`).classList.add('selected');
            selectedPaymentMethod = method;

            document.getElementById('paymentDetailsSection').style.display = 'block';

            const methodNames = {
                'cash': 'Cash',
                'paymongo': 'PayMongo (Online)'
            };
            document.getElementById('selectedPaymentMethod').value = methodNames[method];

            const accountRow = document.getElementById('accountDetailsRow');
            if (method === 'cash' || method === 'paymongo') {
                accountRow.style.display = 'none';
            } else {
                accountRow.style.display = 'grid';
            }

            // Toggle cash-specific fields
            toggleCashFieldsCreateBooking();

            handlePaymentPurposeChange();
        }

        // Handle payment purpose change
        function handlePaymentPurposeChange() {
            const purpose = document.getElementById('paymentPurpose').value;
            const amountField = document.getElementById('paymentAmount');

            if (!totalAmount || totalAmount === 0) {
                return;
            }

            const checkInStr = document.getElementById('checkInDate').value;
            if (!checkInStr) {
                return;
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const checkIn = new Date(checkInStr);
            checkIn.setHours(0, 0, 0, 0);
            const daysUntilCheckIn = Math.ceil((checkIn - today) / (1000 * 60 * 60 * 24));

            let downpayment = daysUntilCheckIn >= 14 ? 1000 : (totalAmount * 0.5);

            if (purpose === 'downpayment') {
                amountField.value = downpayment.toFixed(2);
            } else if (purpose === 'full_payment') {
                amountField.value = totalAmount.toFixed(2);
            }
        }

        // Navigation functions
        function goToStep2() {
            const form = document.getElementById('bookingDetailsForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (!selectedPackageId) {
                Swal.fire({
                    ...SwalConfig,
                    icon: 'warning',
                    title: 'No Package Selected',
                    text: 'Please select a package.'
                });
                return;
            }

            // Update summary
            updateSummary();

            // Switch steps
            document.getElementById('step-1').classList.remove('active');
            document.getElementById('step-2').classList.add('active');
            document.getElementById('step-indicator-1').classList.remove('active');
            document.getElementById('step-indicator-2').classList.add('active');

            // Scroll to top
            window.scrollTo(0, 0);
        }

        function goToStep1() {
            document.getElementById('step-2').classList.remove('active');
            document.getElementById('step-1').classList.add('active');
            document.getElementById('step-indicator-2').classList.remove('active');
            document.getElementById('step-indicator-1').classList.add('active');

            // Scroll to top
            window.scrollTo(0, 0);
        }

        // Confirm booking
        function confirmBooking() {
            if (!selectedPaymentMethod) {
                Swal.fire({
                    ...SwalConfig,
                    icon: 'warning',
                    title: 'No Payment Method',
                    text: 'Please select a payment method'
                });
                return;
            }

            const paymentPurpose = document.getElementById('paymentPurpose').value;
            if (!paymentPurpose) {
                Swal.fire({
                    ...SwalConfig,
                    icon: 'warning',
                    title: 'No Payment Purpose',
                    text: 'Please select a payment purpose'
                });
                return;
            }

            const amountPaid = parseFloat(document.getElementById('paymentAmount').value) || 0;
            const totalAmountValue = totalAmount;

            const checkInStr = document.getElementById('checkInDate').value;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const checkIn = new Date(checkInStr);
            checkIn.setHours(0, 0, 0, 0);
            const daysUntilCheckIn = Math.ceil((checkIn - today) / (1000 * 60 * 60 * 24));

            const requiredDownpayment = daysUntilCheckIn >= 14 ? 1000 : (totalAmountValue * 0.5);

            if (paymentPurpose === 'downpayment') {
                if (Math.abs(amountPaid - requiredDownpayment) > 0.01) {
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'error',
                        title: 'Invalid Downpayment',
                        html: `Downpayment must be exactly <strong>₱${requiredDownpayment.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`
                    });
                    return;
                }
            } else if (paymentPurpose === 'full_payment') {
                if (Math.abs(amountPaid - totalAmountValue) > 0.01) {
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'error',
                        title: 'Invalid Full Payment',
                        html: `Full payment must be exactly <strong>₱${totalAmountValue.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`
                    });
                    return;
                }
            }

            const formData = {
                guest_fname: document.getElementById('firstName').value.trim(),
                guest_lname: document.getElementById('lastName').value.trim(),
                guest_email: document.getElementById('email').value.trim(),
                guest_phone: document.getElementById('phone').value.trim(),
                guest_address: document.getElementById('address').value.trim(),
                check_in: document.getElementById('checkInDate').value,
                check_out: document.getElementById('checkOutDate').value,
                regular_guests: parseInt(document.getElementById('regularGuests').value) || 0,
                num_of_seniors: parseInt(document.getElementById('seniorGuests')?.value) || 0,
                children: parseInt(document.getElementById('children').value) || 0,
                package_id: selectedPackageId,
                payment_method: selectedPaymentMethod,
                payment_purpose: paymentPurpose,
                amount_paid: amountPaid,
                reference_number: document.getElementById('paymentReference')?.value?.trim() || ''
            };

            fetch('/admin/bookings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }

                    if (!data.success) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'error',
                            title: 'Booking Failed',
                            text: 'Error creating booking: ' + (data.message || 'Unknown error')
                        });
                        return;
                    }

                    if (data.booking && data.booking.BookingID) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'success',
                            title: 'Booking Created!',
                            html: `<strong>Booking ID:</strong> ${data.booking.BookingID}`,
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            window.location.href = '{{ route("admin.bookings.index") }}';
                        });
                        return;
                    }

                    window.location.href = '{{ route("admin.bookings.index") }}';
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'error',
                        title: 'Error',
                        text: 'Error creating booking. Please try again.'
                    });
                });
        }
    </script>
@endsection