@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/booking-details.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')
<div class="booking-form-container" x-data="bookingDetailsForm()">

    <!-- Internal Topbar -->
    <header class="w-full">
        @include('partials.topbar-internal')
    </header>

    <!-- Main Content -->
    <main class="container mx-auto max-w-7xl px-6 lg:px-24 py-10 pt-8">

        <!-- Page Title -->
        <h2 class="booking-title">Booking Form</h2>

        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-wrapper">

                <!-- Step 1: Personal Details (completed) -->
                <div class="step-item">
                    <div class="step-indicator completed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <p class="step-label">Personal<br>Details</p>
                </div>
                <div class="step-line completed"></div>

                <!-- Step 2: Booking Details (active) -->
                <div class="step-item">
                    <div class="step-indicator active">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <p class="step-label">Booking<br>Details</p>
                </div>
                <div class="step-line"></div>

                <!-- Step 3: Payment -->
                <div class="step-item">
                    <div class="step-indicator inactive">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="5" width="20" height="14" rx="2" ry="2"/>
                            <line x1="2" y1="10" x2="22" y2="10"/>
                        </svg>
                    </div>
                    <p class="step-label">Payment</p>
                </div>
                <div class="step-line"></div>

                <!-- Step 4: Confirmation -->
                <div class="step-item">
                    <div class="step-indicator inactive">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <p class="step-label">Confirmation</p>
                </div>
            </div>
        </div>

        <!-- Booking Form -->
        <form method="POST" action="{{ route('bookings.details.store') }}" @submit.prevent="validateAndSubmit" x-ref="form">
            @csrf
            <input type="hidden" name="total_amount" x-model="totalAmount">
            <input type="hidden" name="downpayment_amount" x-model="downpaymentAmount">

            <!-- Date and Guests Section -->
            <div class="form-card">
                <h3 class="section-header">Date & Guest Information</h3>

                <div class="form-row two-cols">
                    <div class="form-field">
                        <label class="form-label">Check In Date</label>
                        <input type="date" name="check_in" value="{{ $bookingData['check_in'] ?? old('check_in') }}"
                            readonly class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-label">Check Out Date</label>
                        <input type="date" name="check_out" value="{{ $bookingData['check_out'] ?? old('check_out') }}"
                            readonly class="form-input">
                    </div>
                </div>

                <div class="form-row four-cols">
                    <div class="form-field">
                        <label class="form-label">Adults</label>
                        <input type="number" name="num_of_adults" id="num_of_adults" min="0"
                            x-model="regularGuests" x-ref="regularGuests"
                            value="{{ $bookingData['regular_guests'] ?? old('num_of_adults', 0) }}"
                            readonly class="form-input">
                    </div>

                    <div class="form-field">
                        <label class="form-label">Senior Citizens</label>
                        <input type="number" name="num_of_seniors" id="num_of_seniors" min="0"
                            x-model="seniors" x-ref="seniors"
                            value="{{ $bookingData['seniors'] ?? old('num_of_seniors', 0) }}"
                            readonly class="form-input">
                    </div>

                    <div class="form-field">
                        <label class="form-label">Children (6 yrs old and below)</label>
                        <input type="number" name="num_of_child" id="num_of_child" min="0"
                            x-model="children" x-ref="children"
                            value="{{ $bookingData['children'] ?? old('num_of_child', 0) }}"
                            readonly class="form-input">
                    </div>

                    <div class="form-field">
                        <label class="form-label">Total Guests</label>
                        <input type="number" name="total_guests" readonly x-model="totalGuests"
                            class="form-input" :value="totalGuests">
                    </div>
                </div>
            </div>

            <!-- Package Selection Section -->
            <div class="form-card">
                <h3 class="section-header">Select a Package</h3>
                <div class="package-grid">
                    @foreach($packages as $package)
                        <label class="package-card" :class="{ 'selected': selectedPackage == '{{ $package->PackageID }}' }">
                            <input type="radio" name="package_id" value="{{ $package->PackageID }}"
                                x-model="selectedPackage" :data-price="{{ $package->Price }}"
                                data-max-guests="{{ $package->max_guests }}"
                                data-excess-rate="{{ $package->excess_rate }}" required
                                class="package-radio">
                            <div class="package-content">
                                <div class="package-header">
                                    <span class="package-name">{{ $package->Name }}</span>
                                    <span class="package-price">₱{{ number_format($package->Price, 0) }}</span>
                                </div>
                                <div class="package-max-guests">
                                    <i class="fas fa-users"></i> Max: {{ $package->max_guests ?? 30 }} guests
                                </div>
                                <div class="package-amenities">
                                    @if(!empty($package->amenities_array))
                                        @foreach($package->amenities_array as $amenity)
                                            <div class="amenity-item">
                                                <i class="fas fa-check"></i>
                                                <span>{{ $amenity }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="no-amenities">No amenities listed</span>
                                    @endif
                                </div>
                                <div class="package-note">
                                    Add ₱100 per excess person
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- System Note -->
            <div class="info-box">
                <div class="info-text">

                    <p class="text-sm text-amber-700">
                          <i class="fa-solid fa-info-circle mr-2"></i>
                        Your booking is <strong>{{ $bookingData['days_until_checkin'] }} days</strong> before check-in.
                        @if($bookingData['is_reservation_fee_eligible'])
                            A <strong>₱1,000 downpayment</strong> is required to secure your booking.
                        @else
                            A <strong>{{ $bookingData['payment_label'] }}</strong> is required to secure your booking.
                        @endif
                    </p>
                </div>
            </div>

            <!-- Summary Section (white card with totals stacked) -->
            <div class="form-card summary-card">
                <h3 class="section-header">Booking Summary</h3>

                <div class="summary-grid">
                    <!-- LEFT COLUMN -->
                    <div class="summary-column-left">
                        <div class="summary-item">
                            <label class="summary-label">Package:</label>
                            <p class="summary-value" x-text="selectedPackageName ? (selectedPackageName + ' – ₱' + selectedPackagePrice.toLocaleString() + '/day') : 'Select a package'">Select a package</p>
                        </div>
                        <div class="summary-item">
                            <label class="summary-label">Adults:</label>
                            <p class="summary-value" x-text="regularGuests + ' adult' + (regularGuests !== 1 ? 's' : '') + ' (Max: ' + (selectedPackageMaxGuests || 30) + ')'">0 adult(s)</p>
                        </div>
                        <div class="summary-item">
                            <label class="summary-label">Senior Citizens:</label>
                            <p class="summary-value" x-text="seniors + ' senior' + (seniors !== 1 ? 's' : '') + ' (discount processed at front desk)'">0 senior(s)</p>
                        </div>
                        <div class="summary-item">
                            <label class="summary-label">Children:</label>
                            <p class="summary-value" x-text="children + ' children (FREE)'">0 children (FREE)</p>
                        </div>
                    </div>
                    <!-- RIGHT COLUMN -->
                    <div class="summary-column-right">
                        <div class="summary-item">
                            <label class="summary-label">Days Staying:</label>
                            <p class="summary-value" x-text="daysStayingText">0 days</p>
                        </div>
                        <div class="summary-item">
                            <label class="summary-label">Package Total:</label>
                            <p class="summary-value" x-text="packageTotalText">₱0.00</p>
                        </div>
                        <div class="summary-item">
                            <label class="summary-label">Excess Fee:</label>
                            <p class="summary-value" x-text="excessFeeText">₱0.00 (No excess)</p>
                        </div>
                    </div>
                </div>

                <div class="summary-totals">
                    <div class="total-row">
                        <span class="summary-label">Total Amount:</span>
                        <span class="summary-value highlight total-value" x-text="totalAmountText">₱0.00</span>
                    </div>

                    <div class="total-row reservation-fee">
                        <span class="summary-label" x-text="requiredPaymentLabel">Required Payment:</span>
                        <span class="summary-value total-value" x-text="requiredPaymentText">₱0.00</span>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="form-navigation">
                <a href="{{ route('bookings.personal-details') }}" class="btn-previous">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Previous</span>
                </a>
                <button type="submit" x-on:click.prevent="showPolicyModal = true" class="btn-next">
                    <span>Next</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Policy Modal -->
        <div x-show="showPolicyModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="modal-overlay" @click.self="showPolicyModal = false">
            <div class="modal-content">
                <!-- Logo and Header -->
                <div class="modal-header">
                    <h3 class="modal-title">Booking Policy</h3>
                </div>

                <!-- Policy Content -->
                <div class="modal-body">
                    <p class="modal-intro">
                        Before proceeding with your booking, please read and agree to the following terms:
                    </p>

                    <!-- Policy Points -->
                    <div class="policy-point">
                        <h4 class="policy-title">1. Reservations & Payments</h4>
                        <ul class="policy-list">
                            <li>• If you book <strong>earlier than 2 weeks</strong> before your check-in date, you only need to pay a <strong>₱1,000 reservation fee</strong> to secure your booking.</li>
                            <li>• You must then pay <strong>50% of the total package price</strong> at least 2 weeks before your check-in date.</li>
                            <li>• If you book <strong>within 2 weeks</strong> before your check-in date, a <strong>50% downpayment</strong> is required upon booking.</li>
                            <li>• The <strong>remaining balance</strong> must be paid directly at the resort upon check-in.</li>
                        </ul>
                    </div>

                    <div class="policy-point">
                        <h4 class="policy-title">2. Cancellations & Changes</h4>
                        <ul class="policy-list">
                            <li>• <strong>No refunds</strong> will be given for cancellations or no-shows.</li>
                            <li>• <strong>Rebooking or changing dates</strong> is not allowed.</li>
                        </ul>
                    </div>

                    <div class="policy-point">
                        <h4 class="policy-title">3. Add-ons & Final Billing</h4>
                        <ul class="policy-list">
                            <li>• An <strong>excess person</strong> will be charged <strong>₱100 each</strong>.</li>
                            <li>• Any <strong>add-ons or rental items</strong> availed during your stay will be added to your bill.</li>
                            <li>• The <strong>final bill</strong> must be settled before check-out.</li>
                        </ul>
                    </div>

                    <div class="policy-point">
                        <h4 class="policy-title">4. Senior Citizen Discounts</h4>
                        <ul class="policy-list">
                            <li>• If a senior citizen discount is applicable, it will not be applied online. Senior discounts will be verified and processed at the resort front desk when settling the final bill. Please present a valid senior citizen ID.</li>
                        </ul>
                    </div>

                    <!-- Agreement Checkbox -->
                    <div class="agreement-box">
                        <label class="agreement-label">
                            <input type="checkbox" id="agreePolicy" x-model="agreed" class="agreement-checkbox">
                            <span class="agreement-text">
                                By clicking <strong>"I Agree"</strong>, you acknowledge that you have read, understood,
                                and accepted the above booking policy.
                            </span>
                        </label>
                    </div>

                    <!-- Action Buttons -->
                    <div class="modal-actions">
                        <button x-on:click="showPolicyModal = false" class="btn-modal-back">
                            < Back
                        </button>
                        <button x-on:click="acceptPolicy" :disabled="!agreed" class="btn-modal-proceed"
                            :class="{ 'opacity-50 cursor-not-allowed': !agreed }">
                            Proceed to Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

    <script>
        function bookingDetailsForm() {
            return {
                selectedPackage: null,
                selectedPackageName: '',
                selectedPackagePrice: 0,
                selectedPackageMaxGuests: 0,
                selectedPackageExcessRate: 0,
                regularGuests: {{ $bookingData['regular_guests'] ?? 0 }},
                children: {{ $bookingData['children'] ?? 0 }},
                seniors: {{ $bookingData['seniors'] ?? 0 }},
                totalGuests: 0,
                daysOfStay: 0,
                checkInDate: '{{ $bookingData["check_in"] ?? "" }}',
                checkOutDate: '{{ $bookingData["check_out"] ?? "" }}',
                showPolicyModal: false,
                agreed: false,
                totalAmount: 0,
                downpaymentAmount: 0,
                reservationFee: 1000,
                daysUntilCheckIn: 0,
                isReservationFeeEligible: false,
                // display text properties for summary
                daysStayingText: '0 days',
                packageTotalText: '₱0.00',
                excessFeeText: '₱0.00 (No excess)',
                totalAmountText: '₱0.00',
                downpaymentText: '₱0.00',
                requiredPaymentText: '₱0.00',
                requiredPaymentLabel: 'Required Payment:',
                paymentPolicyText: 'System automatically applies payment based on booking time.',

                init() {
                    this.calculateDaysOfStay();
                    this.calculateDaysUntilCheckIn();
                    this.updateCalculations();
                    this.$watch('selectedPackage', () => this.updateSelectedPackage());
                },

                calculateDaysOfStay() {
                    if (this.checkInDate && this.checkOutDate) {
                        const checkIn = new Date(this.checkInDate);
                        const checkOut = new Date(this.checkOutDate);
                        this.daysOfStay = Math.max(0, Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24)));
                    } else {
                        this.daysOfStay = 0;
                    }
                    console.log('Days of Stay:', this.daysOfStay);
                },

                calculateDaysUntilCheckIn() {
                    if (this.checkInDate) {
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        const checkIn = new Date(this.checkInDate);
                        checkIn.setHours(0, 0, 0, 0);
                        this.daysUntilCheckIn = Math.max(0, Math.ceil((checkIn - today) / (1000 * 60 * 60 * 24)));
                        this.isReservationFeeEligible = this.daysUntilCheckIn >= 14;

                        // Update payment policy text
                        if (this.isReservationFeeEligible) {
                            this.paymentPolicyText = 'Booking made 14+ days before check-in. Reservation fee of ₱1,000 required.';
                        } else {
                            this.paymentPolicyText = 'Booking made less than 14 days before check-in. 50% downpayment required.';
                        }

                        console.log('Days Until Check-In:', this.daysUntilCheckIn, 'Reservation Fee Eligible:', this.isReservationFeeEligible);
                    } else {
                        this.daysUntilCheckIn = 0;
                        this.isReservationFeeEligible = false;
                    }
                },

                updateSelectedPackage() {
                    const selectedRadio = document.querySelector(`input[name="package_id"]:checked`);
                    if (selectedRadio) {
                        const price = parseFloat(selectedRadio.dataset.price) || 0;
                        this.selectedPackagePrice = price;
                        this.selectedPackageName = selectedRadio.parentElement.querySelector('.package-name')?.textContent.trim() || '';
                        this.selectedPackageMaxGuests = parseInt(selectedRadio.dataset.maxGuests) || 0;
                        this.selectedPackageExcessRate = parseInt(selectedRadio.dataset.excessRate) || 0;

                        console.log('Package Selected:', {
                            name: this.selectedPackageName,
                            price: this.selectedPackagePrice,
                            maxGuests: this.selectedPackageMaxGuests,
                            excessRate: this.selectedPackageExcessRate
                        });

                        this.updateCalculations();
                    } else {
                        this.selectedPackagePrice = 0;
                        this.selectedPackageName = '';
                        this.selectedPackageMaxGuests = 0;
                        this.selectedPackageExcessRate = 0;
                        this.totalAmount = 0;
                        this.updateCalculations();
                    }
                },

                updateCalculations() {
                    // Only read from refs if they exist (after DOM is ready), otherwise use initial values
                    if (this.$refs.regularGuests) {
                        this.regularGuests = parseInt(this.$refs.regularGuests.value) || 0;
                    }
                    if (this.$refs.children) {
                        this.children = parseInt(this.$refs.children.value) || 0;
                    }
                    if (this.$refs.seniors) {
                        this.seniors = parseInt(this.$refs.seniors.value) || 0;
                    }
                    this.totalGuests = this.regularGuests + this.children + this.seniors;

                    const baseAmount = this.selectedPackagePrice * this.daysOfStay;
                    const maxGuests = this.selectedPackageMaxGuests || 0;
                    const excessRate = this.selectedPackageExcessRate || 0;

                    // Only adults count toward excess (children are FREE)
                    // Only calculate excess if a package is selected
                    const excess = (this.selectedPackage && maxGuests > 0) ? Math.max(0, (this.regularGuests + this.seniors) - maxGuests) : 0;
                    const excessCharge = excess * excessRate;
                    const adultCount = this.regularGuests + this.seniors;
                    this.totalAmount = baseAmount + excessCharge;

                    // Calculate required payment based on check-in time
                    if (this.isReservationFeeEligible) {
                        this.downpaymentAmount = this.reservationFee;
                        this.requiredPaymentLabel = 'Reservation Fee:';
                        this.requiredPaymentText = `₱${this.reservationFee.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    } else {
                        this.downpaymentAmount = this.totalAmount * 0.5;
                        this.requiredPaymentLabel = 'Required Downpayment (50%):';
                        this.requiredPaymentText = `₱${this.downpaymentAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    }

                    this.daysStayingText = `${this.daysOfStay} day${this.daysOfStay !== 1 ? 's' : ''} (₱${(this.selectedPackagePrice * this.daysOfStay).toLocaleString()})`;
                    this.packageTotalText = `₱${baseAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    this.excessFeeText = excess > 0 ? `₱${excessCharge.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${excess} guests)` : '₱0.00 (No excess)';
                    this.totalAmountText = `₱${this.totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    this.downpaymentText = `₱${this.downpaymentAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

                    console.log('Calculations:', {
                        regularGuests: this.regularGuests,
                        children: this.children,
                        totalGuests: this.totalGuests,
                        selectedPackage: this.selectedPackage,
                        maxGuests: maxGuests,
                        excessRate: excessRate,
                        baseAmount: baseAmount,
                        excess: excess,
                        excessCharge: excessCharge,
                        totalAmount: this.totalAmount,
                        downpaymentAmount: this.downpaymentAmount,
                        isReservationFeeEligible: this.isReservationFeeEligible,
                        daysUntilCheckIn: this.daysUntilCheckIn
                    });
                },

                validateAndSubmit(event) {
                    if (this.showPolicyModal) {
                        event.preventDefault();
                        return;
                    }
                    if (!this.selectedPackage) {
                        alert('Please select a package.');
                        event.preventDefault();
                    } else if (this.totalGuests <= 0) {
                        alert('Please specify at least one guest.');
                        event.preventDefault();
                    } else if (this.daysOfStay <= 0) {
                        alert('Invalid check-in or check-out date.');
                        event.preventDefault();
                    } else if (this.totalAmount <= 0) {
                        alert('Total amount must be greater than zero. Please check your selection.');
                        event.preventDefault();
                    } else {
                        console.log('Submitting Form with Total Amount:', this.totalAmount, 'Downpayment:', this.downpaymentAmount);
                        this.$refs.form.submit();
                    }
                },

                acceptPolicy() {
                    if (this.agreed) {
                        if (this.totalAmount <= 0) {
                            alert('Total amount must be greater than zero. Please check your selection.');
                            this.showPolicyModal = false;
                            return;
                        }
                        console.log('Policy Accepted, Submitting with Total Amount:', this.totalAmount, 'Downpayment:', this.downpaymentAmount);
                        this.showPolicyModal = false;
                        this.$refs.form.submit();
                    }
                }
            }
        }
    </script>

@endsection
