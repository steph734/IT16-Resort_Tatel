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
            <h2 class="booking-title">Payment for Booking #{{ $booking->BookingID }}</h2>

            <!-- Payment Form -->
            <div class="form-card">
                @php
                    // Calculate payment amounts from database
                    $totalAmount = $bookingData['total_amount'];
                    $payments = $booking->payments ?? collect();
                    $totalPaid = $payments->sum('Amount');
                    $remainingBalance = max(0, $totalAmount - $totalPaid);
                @endphp

                <!-- Payment Status Notice -->
                <div class="mb-6 p-4 rounded-lg border-l-4 {{ $remainingBalance > 0 ? 'bg-amber-50 border-amber-500' : 'bg-green-50 border-green-500' }}">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ $remainingBalance > 0 ? 'text-amber-600' : 'text-green-600' }} flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            @if($remainingBalance > 0)
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            @endif
                        </svg>
                        <div class="flex-1">
                            <h4 class="font-semibold {{ $remainingBalance > 0 ? 'text-amber-800' : 'text-green-800' }} mb-1">
                                @if($remainingBalance > 0)
                                    Payment Required
                                @else
                                    Fully Paid
                                @endif
                            </h4>
                            <p class="text-sm {{ $remainingBalance > 0 ? 'text-amber-700' : 'text-green-700' }}">
                                @if($remainingBalance > 0)
                                    You have a remaining balance of <strong>₱{{ number_format($remainingBalance, 2) }}</strong> for this booking.
                                @else
                                    This booking has been fully paid. Thank you!
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('bookings.payment.store', ['booking_id' => $booking->BookingID]) }}" 
                    class="max-w-5xl mx-auto" x-data="existingPaymentForm()" x-ref="form" enctype="multipart/form-data">
                    @include('alerts.error')
                    @include('alerts.success')
                    @csrf

                    <!-- Hidden field for booking_id -->
                    <input type="hidden" name="booking_id" value="{{ $booking->BookingID }}">

                    <!-- Single dynamic payment method field -->
                    <input type="hidden" name="payment_method" x-model="currentPaymentMethod">

                    <!-- Hidden field to pass payment mode -->
                    <input type="hidden" name="payment_mode" x-model="paymentMode">

                    <!-- Hidden fields to pass booking data from database -->
                    <input type="hidden" name="total_amount" value="{{ $totalAmount }}">
                    <input type="hidden" name="package_id" value="{{ $booking->PackageID }}">
                    <input type="hidden" name="check_in" value="{{ $booking->CheckInDate }}">
                    <input type="hidden" name="check_out" value="{{ $booking->CheckOutDate }}">
                    <input type="hidden" name="num_of_adults" value="{{ $booking->NumOfAdults ?? 0 }}">
                    <input type="hidden" name="num_of_child" value="{{ $booking->NumOfChild ?? 0 }}">
                    <input type="hidden" name="num_of_seniors" value="{{ $booking->NumOfSeniors ?? 0 }}">
                    <input type="hidden" name="total_guests" value="{{ $booking->Pax ?? 0 }}">

                    <div class="bg-blue-50 p-8 rounded-lg border border-blue-200">
                        
                        @if($remainingBalance > 0)
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
                                        <option value="Partial Payment">Partial Payment</option>
                                        <option value="Full Payment (Balance)">Full Payment (Balance)</option>
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
                                        <input type="number" name="amount" x-model="amount" step="0.01" 
                                            :readonly="purpose === 'Full Payment (Balance)'"
                                            :class="purpose === 'Full Payment (Balance)' ? 'bg-gray-100' : ''"
                                            class="w-full p-3 pl-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-600"
                                            placeholder="0.00"
                                            :max="remainingBalance">
                                    </div>
                                    <template x-if="purpose === 'Partial Payment'">
                                        <p class="text-xs text-gray-500 mt-1">Enter amount (max: ₱{{ number_format($remainingBalance, 2) }})</p>
                                    </template>
                                </div>
                            </div>

                            <p class="text-xs text-gray-500 mb-8">
                                <template x-if="purpose === 'Full Payment (Balance)'">
                                    <span>Amount automatically set to remaining balance</span>
                                </template>
                                <template x-if="purpose === 'Partial Payment'">
                                    <span>Enter the amount you wish to pay</span>
                                </template>
                                <template x-if="!purpose">
                                    <span>Select payment purpose to continue</span>
                                </template>
                            </p>
                        @endif

                        <!-- Booking Summary (Full Width Below) -->
                        <div class="bg-white p-8 rounded-lg border border-gray-300 shadow-sm w-full mb-8">
                            <h3 class="text-xl font-semibold mb-6 text-resort-primary">Booking Summary</h3>

                            <!-- Booking Details -->
                            <div class="space-y-4 text-sm">
                                <div class="flex justify-between items-start">
                                    <span class="text-gray-600 font-medium">Package:</span>
                                    <span class="font-semibold text-resort-primary text-right max-w-[60%]">
                                        {{ $package->Name ?? 'N/A' }} - ₱{{ number_format($package->Price ?? 0, 2) }}/day
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
                                        {{ $booking->NumOfAdults ?? 0 }} adult(s)
                                        @if(isset($bookingData['max_guests']) && $bookingData['max_guests'] > 0)
                                            (Max: {{ $bookingData['max_guests'] }})
                                        @endif
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 font-medium">Senior Citizens:</span>
                                    <span class="font-semibold text-resort-primary">
                                        {{ $booking->NumOfSeniors ?? 0 }} senior(s)
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 font-medium">Children:</span>
                                    <span class="font-semibold text-resort-primary">
                                        {{ $booking->NumOfChild ?? 0 }} children (FREE)
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
                                    <span class="text-gray-700 font-bold text-base">Total Booking Amount:</span>
                                    <span class="text-resort-primary font-bold text-xl">
                                        ₱{{ number_format($totalAmount, 2) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="border-t-2 border-gray-400 my-6"></div>

                            <!-- Payment History -->
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200 mb-4">
                                <h4 class="text-lg font-semibold text-green-800 mb-3">Payment History</h4>
                                @if($payments->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($payments as $pmt)
                                            <div class="flex justify-between items-center text-sm">
                                                <span class="text-gray-700">
                                                    {{ $pmt->Purpose ?? 'Payment' }} 
                                                    <span class="text-xs text-gray-500">({{ \Carbon\Carbon::parse($pmt->created_at)->format('M d, Y') }})</span>
                                                </span>
                                                <span class="font-semibold text-green-700">₱{{ number_format($pmt->Amount, 2) }}</span>
                                            </div>
                                        @endforeach
                                        <div class="flex justify-between items-center pt-2 border-t border-green-300">
                                            <span class="font-bold text-green-800">Total Paid:</span>
                                            <span class="font-bold text-green-800 text-lg">₱{{ number_format($totalPaid, 2) }}</span>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-600">No payments recorded yet.</p>
                                @endif
                            </div>

                            <!-- Remaining Balance -->
                            <div class="flex justify-between items-center pt-3 border-t-2 border-gray-300 {{ $remainingBalance > 0 ? 'bg-amber-50' : 'bg-green-50' }} -mx-4 px-4 py-3 rounded">
                                <span class="text-gray-700 font-bold text-base">Remaining Balance:</span>
                                <span class="{{ $remainingBalance > 0 ? 'text-amber-700' : 'text-green-700' }} font-bold text-xl">
                                    ₱{{ number_format($remainingBalance, 2) }}
                                </span>
                            </div>

                            @if($remainingBalance <= 0)
                                <!-- Divider -->
                                <div class="border-t-2 border-gray-400 my-6"></div>

                                <!-- Status -->
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700 font-bold text-base">Status:</span>
                                    <span class="text-green-700 font-bold text-lg flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        FULLY PAID
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if($remainingBalance > 0)
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
                                    <p class="text-xs text-gray-500 mt-2">Select purpose to set the amount, then agree to the policy to proceed.</p>
                                    <template x-if="isPolling">
                                        <span class="text-xs text-emerald-700 font-medium">Waiting for payment confirmation...</span>
                                    </template>
                                </div>
                            </div>
                        @else
                            <!-- Fully Paid Message -->
                            <div class="mt-8 p-6 border-2 border-green-500 rounded-lg bg-green-50 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="text-2xl font-bold text-green-800 mb-2">Booking Fully Paid!</h3>
                                <p class="text-green-700">Thank you for your payment. We look forward to welcoming you!</p>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </main>

    </div>

    <script>
        function existingPaymentForm() {
            return {
                purpose: '',
                amount: 0,
                agreeTerms: false,
                referenceNumber: '',
                remainingBalance: {{ $remainingBalance }},
                totalAmount: {{ $totalAmount }},
                // polling
                isPolling: false,
                pollHandle: null,
                pollTries: 0,
                maxPollTries: 300, // ~5 minutes at 1s interval
                checkoutWindow: null,

                init() {
                    console.log('Existing Payment Form Initialized:', {
                        remainingBalance: this.remainingBalance,
                        totalAmount: this.totalAmount,
                        bookingId: '{{ $booking->BookingID }}'
                    });
                },

                async createPaymongoLink() {
                    if (!this.agreeTerms) {
                        return Swal.fire({ icon: 'warning', title: 'Please agree to the policy first.' });
                    }
                    if (!this.purpose || !this.amount || this.amount <= 0) {
                        return Swal.fire({ icon: 'warning', title: 'Please select a payment purpose.' });
                    }
                    if (this.amount > this.remainingBalance) {
                        return Swal.fire({ icon: 'warning', title: 'Amount exceeds remaining balance.' });
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
                                booking_id: '{{ $booking->BookingID }}',
                            }),
                        });
                        if (!resp.ok) {
                            const data = await resp.json().catch(() => ({}));
                            throw new Error(data.message || 'Failed to create payment link');
                        }
                        const data = await resp.json();
                        const checkoutUrl = data.checkout_url;
                        if (!checkoutUrl) throw new Error('Missing checkout URL');
                        if (data.reference) {
                            this.referenceNumber = data.reference;
                        }

                        // Redirect to PayMongo
                        window.location.href = checkoutUrl;
                    } catch (e) {
                        console.error(e);
                        Swal.fire({ icon: 'error', title: 'PayMongo Error', text: e.message || 'Unable to proceed.' });
                    }
                },

                updateAmount() {
                    if (this.purpose === 'Full Payment (Balance)') {
                        this.amount = this.remainingBalance;
                    } else if (this.purpose === 'Partial Payment') {
                        this.amount = 0; // User will input
                    } else {
                        this.amount = 0;
                    }
                },
            };
        }
    </script>
@endsection
