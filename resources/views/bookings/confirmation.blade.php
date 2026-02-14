@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/confirmation.css') }}" rel="stylesheet">
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
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <p class="step-label">Personal<br>Details</p>
                </div>
                <div class="step-line completed"></div>

                <!-- Step 2: Booking Details (completed) -->
                <div class="step-item">
                    <div class="step-indicator completed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <p class="step-label">Booking<br>Details</p>
                </div>
                <div class="step-line completed"></div>

                <!-- Step 3: Payment (completed) -->
                <div class="step-item">
                    <div class="step-indicator completed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="5" width="20" height="14" rx="2" ry="2"/>
                            <line x1="2" y1="10" x2="22" y2="10"/>
                        </svg>
                    </div>
                    <p class="step-label">Payment</p>
                </div>
                <div class="step-line active"></div>

                <!-- Step 4: Confirmation (active) -->
                <div class="step-item">
                    <div class="step-indicator active">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <p class="step-label">Confirmation</p>
                </div>
            </div>
        </div>

        <!-- Confirmation Card -->

        <div class="confirmation-card">
            <div class="confirmation-icon">
                🎉
            </div>

            <h3 class="confirmation-title">
                Yay! We've accepted your booking.
            </h3>

            <p class="confirmation-message">
                Thank you for choosing <strong>Jara's Palm Beach Resort</strong>.
            </p>

            <div class="booking-reference-box">
                <p class="reference-label">
                    Your Booking Reference:
                </p>
                <p class="reference-value">
                    {{ strtoupper($booking->BookingID) }}
                </p>
            </div>

            <div class="confirmation-actions" style="display:flex;gap:12px;align-items:center;justify-content:center;">
                <a href="{{ route('home') }}" class="btn-home">
                    Back to Home
                </a>
            </div>
        </div>
    </main>

</div>
@endsection
