@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/payment.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="booking-form-container">

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
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <p class="step-label">Personal<br>Details</p>
                    </div>
                    <div class="step-line completed"></div>

                    <!-- Step 2: Booking Details (completed) -->
                    <div class="step-item">
                        <div class="step-indicator completed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="step-label">Booking<br>Details</p>
                    </div>
                    <div class="step-line active"></div>

                    <!-- Step 3: Payment (active) -->
                    <div class="step-item">
                        <div class="step-indicator active">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <p class="step-label">Payment</p>
                    </div>
                    <div class="step-line"></div>

                    <!-- Step 4: Confirmation -->
                    <div class="step-item">
                        <div class="step-indicator inactive">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p class="step-label">Confirmation</p>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="form-card">
                <!-- Payment Policy Notice -->
                <div class="mb-6 p-4 rounded-lg border-l-4 bg-amber-50 border-amber-500">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1">
                            <h4 class="font-semibold text-amber-800 mb-1">
                                Payment Policy
                            </h4>
                            <p class="text-sm text-amber-700">
                                Your booking is <strong>{{ $bookingData['days_until_checkin'] }} days</strong> before check-in.
                                @if($bookingData['is_reservation_fee_eligible'])
                                    A <strong>₱1,000 downpayment</strong> is required to secure your booking.
                                @else
                                    A <strong>{{ $bookingData['payment_label'] }}</strong> is required to secure your booking.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

<form method="POST" action="{{ isset($booking) ? route('bookings.payment.store', ['booking_id' => $booking->BookingID]) : route('bookings.payment.store') }}" class="max-w-5xl mx-auto"
    x-data="paymentForm()" x-ref="form" enctype="multipart/form-data">
    @include('alerts.error')
    @include('alerts.success')
    @csrf

    <!-- Hidden field for booking_id -->
    <input type="hidden" name="booking_id" value="{{ $booking->BookingID ?? '' }}">

                    <!-- Single dynamic payment method field -->
                    <input type="hidden" name="payment_method" x-model="currentPaymentMethod">

                    <!-- Hidden field to pass payment mode (account or qr) -->
                    <input type="hidden" name="payment_mode" x-model="paymentMode">

                    <!-- Hidden fields to pass booking data -->
                    <!-- Prefer compiled total from booking-details; fallback to (package_total * days) + excess_fee -->
                    <input type="hidden" name="total_amount" value="{{ $bookingData['total_amount'] ?? (($bookingData['package_total'] ?? 0) + ($bookingData['excess_fee'] ?? 0)) }}">
                    <input type="hidden" name="downpayment_amount" value="{{ $bookingData['downpayment_amount'] ?? 0 }}">
                    <input type="hidden" name="package_id" value="{{ $bookingData['package_id'] ?? '' }}">
                    <input type="hidden" name="check_in" value="{{ $bookingData['check_in'] ?? '' }}">
                    <input type="hidden" name="check_out" value="{{ $bookingData['check_out'] ?? '' }}">
                    <input type="hidden" name="num_of_adults" value="{{ $bookingData['num_of_adults'] ?? 0 }}">
                    <input type="hidden" name="num_of_child" value="{{ $bookingData['num_of_child'] ?? 0 }}">
                    <input type="hidden" name="num_of_seniors" value="{{ $bookingData['num_of_seniors'] ?? 0 }}">
                    <input type="hidden" name="total_guests" value="{{ $bookingData['total_guests'] ?? 0 }}">

                    <div class="bg-blue-50 p-8 rounded-lg border border-blue-200">
                        <!-- Purpose and Amount Fields (2 Columns) -->
                        <div class="grid grid-cols-2 gap-6 mb-8">
                            <div>
                                <label class="block text-sm font-medium mb-2 text-resort-primary flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Purpose
                                </label>
                                <select name="purpose" x-model="purpose" x-on:change="updateAmount()"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-600">
                                    <option value="" disabled>Select purpose</option>
                                    <option value="Downpayment">Downpayment</option>
                                    <option value="Full Payment">Full Payment</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-resort-primary flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Amount
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-gray-500 font-medium">₱</span>
                                    <input type="number" name="amount" x-model="amount" step="0.01" readonly
                                        class="w-full p-3 pl-8 border border-gray-300 rounded-lg bg-gray-100 focus:ring-2 focus:ring-teal-600"
                                        placeholder="0.000">
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mb-8">Amount automatically set based on payment purpose</p>

                        <!-- Booking Summary (Full Width Below) -->
                        <div class="bg-white p-8 rounded-lg border border-gray-300 shadow-sm w-full mb-8">
                                    <h3 class="text-xl font-semibold mb-6 text-resort-primary">Booking Summary</h3>

                                    <!-- Booking Details -->
                                    <div class="space-y-4 text-sm">
                                        <div class="flex justify-between items-start">
                                            <span class="text-gray-600 font-medium">Package:</span>
                                            <span class="font-semibold text-resort-primary text-right max-w-[60%]">
                                                {{ $bookingData['package_name'] ?? 'N/A' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600 font-medium">Days Staying:</span>
                                            <span class="font-semibold text-resort-primary">
                                                {{ $bookingData['days_of_stay'] ?? 0 }} day(s)
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600 font-medium">Adults:</span>
                                            <span class="font-semibold text-resort-primary">
                                                {{ $bookingData['num_of_adults'] ?? 0 }} adult(s)
                                                @if(isset($bookingData['max_guests']) && $bookingData['max_guests'] > 0)
                                                    (Max: {{ $bookingData['max_guests'] }})
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600 font-medium">Senior Citizens:</span>
                                            <span class="font-semibold text-resort-primary">
                                                {{ $bookingData['num_of_seniors'] ?? 0 }} senior(s)
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600 font-medium">Children:</span>
                                            <span class="font-semibold text-resort-primary">
                                                {{ $bookingData['num_of_child'] ?? 0 }} children (FREE)
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                            <span class="text-gray-600 font-medium">Package Total:</span>
                                            <span class="font-semibold text-resort-primary">
                                                ₱{{ number_format($bookingData['package_total'] ?? 0, 2) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600 font-medium">Excess Fee:</span>
                                            <span class="font-semibold text-resort-primary">
                                                @if(isset($bookingData['excess_guests']) && $bookingData['excess_guests'] > 0)
                                                    ₱{{ number_format($bookingData['excess_fee'] ?? 0, 2) }} ({{ $bookingData['excess_guests'] }} guests)
                                                @else
                                                    ₱0.00 (No excess)
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center pt-3 border-t-2 border-gray-300">
                                            <span class="text-gray-700 font-bold text-base">Total Amount:</span>
                                            <span class="text-resort-primary font-bold text-xl">
                                                ₱{{ number_format(($bookingData['total_amount'] ?? (($bookingData['package_total'] ?? 0) + ($bookingData['excess_fee'] ?? 0))), 2) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center pt-3 border-t border-gray-200 bg-yellow-50 -mx-4 px-4 py-3 rounded">
                                            <span class="text-gray-700 font-bold text-base">{{ $bookingData['payment_label'] ?? 'Required Payment' }}:</span>
                                            <span class="text-yellow-700 font-bold text-xl">
                                                ₱{{ number_format($bookingData['downpayment_amount'] ?? 0, 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Divider -->
                                    <div class="border-t-2 border-gray-400 my-6"></div>

                                    <!-- Payment Summary -->
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600 font-medium">Purpose:</span>
                                            <span class="font-semibold text-resort-primary" x-text="purpose || 'Not selected'">Not selected</span>
                                        </div>
                                        <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                            <span class="text-gray-700 font-bold text-base">Amount:</span>
                                            <span class="font-bold text-resort-primary text-lg">
                                                ₱<span x-text="amount ? parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00'">0.00</span>
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center" x-show="referenceNumber">
                                            <span class="text-gray-600 font-medium">Reference Number:</span>
                                            <span class="font-semibold text-resort-primary break-all" x-text="referenceNumber">N/A</span>
                                        </div>
                                    </div>
                                </div>

                        <!-- Online Payment via PayMongo -->
                        <div class="mt-8 p-4 border rounded-lg bg-white">
                            <h4 class="text-lg font-semibold text-resort-primary mb-3">Pay Online (PayMongo)</h4>
                            <div class="flex items-start gap-2 mb-3">
                                <input id="agree_terms" type="checkbox" x-model="agreeTerms" class="mt-1 h-4 w-4 text-teal-800 border-gray-300 rounded">
                                <label for="agree_terms" class="text-sm text-gray-700">I have read and agree to the <a href="{{ route('booking-policy') }}" target="_blank" class="text-teal-700 underline">Booking Policy (Terms & Conditions)</a>.</label>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button type="button" x-on:click="createPaymongoLink"
                                        :disabled="!agreeTerms || !purpose || !amount || amount <= 0"
                                        class="px-6 py-3 rounded-lg font-semibold text-white shadow-sm transition"
                                        :class="(!agreeTerms || !purpose || !amount || amount <= 0) ? 'bg-gray-300 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700'">
                                    Pay Online via PayMongo
                                </button>
                                <p class="text-xs text-gray-500 mt-2">Select purpose to set the exact amount, then agree to the policy to proceed.</p>
                                <template x-if="isPolling">
                                    <span class="text-xs text-emerald-700 font-medium">Waiting for payment confirmation...</span>
                                </template>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="flex justify-between items-center mt-6">
                            <a href="{{ route('bookings.details') }}"
                                class="text-sm font-semibold text-resort-gray-dark hover:text-resort-primary transition flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                                Previous
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </main>

    </div>

    <script>
        function paymentForm() {
            return {
                purpose: '',
                amount: 0,
                agreeTerms: false,
                referenceNumber: '',
                // Prefer compiled values from booking-details; fallback to recomputation
                totalAmount: {{ $bookingData['total_amount'] ?? (($bookingData['package_total'] ?? 0) + ($bookingData['excess_fee'] ?? 0)) }},
                downpaymentAmount: {{ $bookingData['downpayment_amount'] ?? 0 }},
                // polling
                isPolling: false,
                pollHandle: null,
                pollTries: 0,
                // Make polling faster so confirmation shows almost immediately on close
                maxPollTries: 300, // ~5 minutes at 1s interval
                checkoutWindow: null,

                init() {
                    console.log('Payment Form Initialized:', {
                        totalAmount: this.totalAmount,
                        downpaymentAmount: this.downpaymentAmount,
                        initialAmount: this.amount
                    });
                },

                async createPaymongoLink() {
                    if (!this.agreeTerms) {
                        return Swal.fire({ icon: 'warning', title: 'Please agree to the policy first.' });
                    }
                    if (!this.purpose || !this.amount || this.amount <= 0) {
                        return Swal.fire({ icon: 'warning', title: 'Please select a payment purpose.' });
                    }
                    try {
                        const resp = await fetch(`{{ route('payments.paymongo.link') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': `{{ csrf_token() }}`,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                purpose: this.purpose,
                                amount: this.amount,
                                agree: true,
                                booking_id: `{{ $booking->BookingID ?? '' }}` || undefined,
                            }),
                        });
                        if (!resp.ok) {
                            const data = await resp.json().catch(() => ({}));
                            throw new Error(data.message || 'Failed to create payment link');
                        }
                        const data = await resp.json();
                        const checkoutUrl = data.checkout_url;
                        const bookingId = data.booking_id; // may be null for deferred session flow
                        if (!checkoutUrl) throw new Error('Missing checkout URL');
                        if (data.reference) {
                            this.referenceNumber = data.reference;
                        }

                        // Redirect to PayMongo (booking will be committed on success callback)
                        window.location.href = checkoutUrl;
                    } catch (e) {
                        console.error(e);
                        Swal.fire({ icon: 'error', title: 'PayMongo Error', text: e.message || 'Unable to proceed.' });
                    }
                },

                // Polling disabled for deferred session flow; success/cancel endpoints handle commit/discard.

                updateAmount() {
                    if (this.purpose === 'Downpayment') {
                        this.amount = this.downpaymentAmount;
                    } else if (this.purpose === 'Full Payment') {
                        this.amount = this.totalAmount;
                    } else {
                        this.amount = 0;
                    }
                },

                // confirmNow path removed per request; use PayMongo checkout instead
            };
        }
    </script>
@endsection
