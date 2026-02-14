@extends('layouts.app')

@section('content')
    <!-- Include Topbar -->
    @include('partials.topbar-internal')

    <div class="min-h-screen flex flex-col items-center bg-gray-50 text-gray-900 px-4 sm:px-6 lg:px-24 py-16">

        <!-- Page Title -->
        <h1 class="text-2xl sm:text-3xl font-crimson-pro font-bold text-center text-resort-primary mb-8 tracking-wide">
            Booking Policy
        </h1>

        <!-- Policy Content -->
        <div class="max-w-2xl w-full bg-white rounded-xl shadow p-8 space-y-8">

            <!-- Section 1 -->
            <section>
                <h2 class="font-crimson-pro text-resort-primary text-lg sm:text-xl font-bold mb-3">1. Reservations & Payments</h2>
                <ul class="list-disc ml-6 space-y-3 text-gray-700 leading-normal">
                    <li>If you book <span class="font-semibold text-resort-primary">earlier than 2 weeks before your check-in date</span>, you only need to pay a <span class="font-semibold text-resort-primary">₱1,000 reservation fee</span> to secure your booking.</li>
                    <li>You must then pay <span class="font-semibold text-resort-primary">50% of the total package price</span> at least 2 weeks before your check-in date.</li>
                    <li>If you book <span class="font-semibold text-resort-primary">within 2 weeks before your check-in date</span>, a <span class="font-semibold text-resort-primary">50% downpayment</span> is required upon booking.</li>
                </ul>
                <p class="mt-4 text-gray-700 leading-normal">The <span class="font-semibold text-resort-primary">remaining balance</span> must be paid directly at the resort upon check-in.</p>
            </section>

            <!-- Section 2 -->
            <section>
                <h2 class="font-crimson-pro text-resort-primary text-lg sm:text-xl font-bold mb-3">2. Cancellations & Changes</h2>
                <ul class="list-disc ml-6 space-y-3 text-gray-700 leading-normal">
                    <li><span class="font-semibold text-resort-primary">No refunds</span> will be given for cancellations or no-shows.</li>
                    <li><span class="font-semibold text-resort-primary">Rebooking or changing dates is not allowed.</span></li>
                </ul>
            </section>

            <!-- Section 3 -->
            <section>
                <h2 class="font-crimson-pro text-resort-primary text-lg sm:text-xl font-bold mb-3">3. Add-ons & Final Billing</h2>
                <ul class="list-disc ml-6 space-y-3 text-gray-700 leading-normal">
                    <li>An excess person will be charged <span class="font-semibold text-resort-primary">₱100 each.</span></li>
                    <li>Any add-ons or rental items availed during your stay will be added to your bill.</li>
                    <li>The final bill must be settled before check-out.</li>
                    <li>If a senior citizen discount is applicable, it will <span class="font-semibold text-resort-primary">not</span> be applied online. Senior discounts will be verified and processed at the resort front desk when settling the final bill. Please present a valid senior citizen ID at the front desk to avail the discount.</li>
                </ul>
            </section>

            <!-- Divider -->
            <hr class="border-gray-300 my-6">

            <!-- Book Button -->
            <div class="text-center">
                <a href="{{ url('/check-availability') }}" class="inline-block bg-resort-beige text-resort-primary font-semibold px-8 py-3 rounded-full shadow hover:bg-resort-accent transition-colors tracking-wide">
                    BOOK YOUR STAY!
                </a>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    @include('partials.footer')
@endsection
