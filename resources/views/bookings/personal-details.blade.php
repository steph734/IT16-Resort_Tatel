{{-- resources/views/bookings/personal-details.blade.php --}}
@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/booking-details.css') }}" rel="stylesheet">
    <link href="{{ asset('css/personal-details.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="booking-form-container" x-data="personalDetailsForm()">

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

                <!-- Step 1: Personal Details (active) -->
                <div class="step-item">
                    <div class="step-indicator active">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <p class="step-label">Personal<br>Details</p>
                </div>
                <div class="step-line active"></div>

                <!-- Step 2: Booking Details -->
                <div class="step-item">
                    <div class="step-indicator inactive">
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

        <!-- Personal Details Form -->
        <form action="{{ route('bookings.personal-details.store') }}" method="POST" class="form-card">
            @csrf

            <!-- Name Fields Row (First, Last, Middle) -->
            <div class="form-row three-cols">
                <div class="form-field">
                    <label class="form-label">First Name</label>
                    <div class="input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" class="input-icon w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <input type="text" name="first_name" value="{{ $personalDetails['first_name'] ?? old('first_name') }}" class="form-input" placeholder="Enter First Name" required pattern="^[A-Za-zÀ-ÿ' -]+$" title="Letters, spaces, hyphens, and apostrophes only" oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿ' -]/g,'');capitalizeProperInline(this);">
                    </div>
                    @error('first_name') <p class="error-message">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label class="form-label">Last Name</label>
                    <div class="input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" class="input-icon w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <input type="text" name="last_name" value="{{ $personalDetails['last_name'] ?? old('last_name') }}" class="form-input" placeholder="Enter Last Name" required pattern="^[A-Za-zÀ-ÿ' -]+$" title="Letters, spaces, hyphens, and apostrophes only" oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿ' -]/g,'');capitalizeProperInline(this);">
                    </div>
                    @error('last_name') <p class="error-message">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label class="form-label">Middle Name <span class="optional">(Optional)</span></label>
                    <div class="input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" class="input-icon w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <input type="text" name="middle_name" value="{{ $personalDetails['middle_name'] ?? old('middle_name') }}" class="form-input" placeholder="Enter Middle Name" pattern="^[A-Za-zÀ-ÿ' -]+$" title="Letters, spaces, hyphens, and apostrophes only" oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿ' -]/g,'');capitalizeProperInline(this);">
                    </div>
                    @error('middle_name') <p class="error-message">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Contact Fields Row (Email and Phone) -->
            <div class="form-row two-cols">
                <div class="form-field">
                    <label class="form-label">Email</label>
                    <div class="input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" class="input-icon w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
               <input type="email" name="email" value="{{ $personalDetails['email'] ?? old('email') }}" class="form-input" placeholder="Enter Email" required
                   onblur="this.value=this.value.toLowerCase();"
                   onpaste="(function(e){setTimeout(()=>{e.target.value=e.target.value.toLowerCase();},0)})(event)">
                    </div>
                    @error('email') <p class="error-message">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label class="form-label">Phone</label>
                    <div class="input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" class="input-icon w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <input type="text" name="phone" value="{{ $personalDetails['phone'] ?? old('phone') }}" class="form-input" placeholder="e.g., +639XXXXXXXXX" required inputmode="numeric" pattern="^\+639\d{9}$" title="Format: +639XXXXXXXXX (Philippines mobile)"
                               onfocus="if(!this.value){this.value='+63';}"
                               oninput="
                                   // Ensure prefix +63 is present and fixed
                                   if(!this.value.startsWith('+63')){ this.value = '+63' + this.value.replace(/[^0-9]/g,''); }
                                   // Keep only digits after +63
                                   const after = this.value.slice(3).replace(/[^0-9]/g,'');
                                   // Limit to max 10 digits after +63
                                   const limited = after.slice(0,10);
                                   this.value = '+63' + limited;
                               "
                               maxlength="13">
                    </div>
                    @error('phone') <p class="error-message">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Address Field Row (Full width) -->
            <div class="form-row one-col">
                <div class="form-field">
                    <label class="form-label">Address</label>
                    <div class="input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" class="input-icon w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <input type="text" name="address" value="{{ $personalDetails['address'] ?? old('address') }}" class="form-input" placeholder="Enter Address" oninput="capitalizeProperInline(this);">
                    </div>
                    @error('address') <p class="error-message">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="form-navigation">
                <a href="{{ route('bookings.check-availability', ['from' => 'booking']) }}" class="btn-previous">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Previous</span>
                </a>
                <button type="submit" class="btn-next">
                    <span>Next</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Privacy & Data Collection Notice Modal -->
        <div x-show="showPrivacyModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="modal-overlay" @click.self="showPrivacyModal = false">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h3 class="modal-title">Privacy & Data Collection Notice</h3>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <p class="modal-intro">
                        We value your privacy. Before providing your personal details, please review how we collect, use, and protect your information in accordance with the <strong>Philippine Data Privacy Act of 2012 (RA 10173)</strong> and <strong>GDPR principles</strong>.
                    </p>

                    <!-- Section 1: What We Collect -->
                    <div class="policy-point">
                        <h4 class="policy-title">1. Personal Information We Collect</h4>
                        <ul class="policy-list">
                            <li>• <strong>Identity:</strong> First Name, Last Name, Middle Name</li>
                            <li>• <strong>Contact:</strong> Email Address, Phone Number</li>
                            <li>• <strong>Location:</strong> Address (optional)</li>
                            <li>• <strong>Booking Data:</strong> Check-in/check-out dates, package, guests</li>
                        </ul>
                    </div>

                    <!-- Section 2: Purpose -->
                    <div class="policy-point">
                        <h4 class="policy-title">2. Why We Collect Your Data</h4>
                        <ul class="policy-list">
                            <li>• <strong>Booking Management:</strong> To process and manage your reservation</li>
                            <li>• <strong>Communication:</strong> Send confirmations and service notices</li>
                            <li>• <strong>Legal Compliance:</strong> Tax, accounting, and regulatory requirements</li>
                            <li>• <strong>Service Improvement:</strong> Enhance booking experience</li>
                        </ul>
                    </div>

                    <!-- Section 3: Legal Basis -->
                    <div class="policy-point">
                        <h4 class="policy-title">3. Legal Basis for Processing</h4>
                        <ul class="policy-list">
                            <li>• <strong>Contract Performance:</strong> Fulfilling your booking</li>
                            <li>• <strong>Legitimate Interests:</strong> Fraud prevention & security</li>
                            <li>• <strong>Legal Obligation:</strong> Tax and financial regulations</li>
                            <li>• <strong>Consent:</strong> Marketing communications (optional)</li>
                        </ul>
                    </div>

                    <!-- Section 4: Data Retention -->
                    <div class="policy-point">
                        <h4 class="policy-title">4. How Long We Keep Your Data</h4>
                        <p class="policy-list">
                            • Booking records retained for <strong>up to 7 years</strong> per BIR regulations<br>
                            • Data securely deleted or anonymized after retention period<br>
                            • Earlier deletion available subject to legal requirements
                        </p>
                    </div>

                    <!-- Section 5: Your Rights -->
                    <div class="policy-point">
                        <h4 class="policy-title">5. Your Data Privacy Rights</h4>
                        <ul class="policy-list">
                            <li>• <strong>Access:</strong> Request a copy of your data</li>
                            <li>• <strong>Rectification:</strong> Correct inaccurate information</li>
                            <li>• <strong>Erasure:</strong> Request deletion (subject to legal retention)</li>
                            <li>• <strong>Object:</strong> Withdraw marketing consent anytime</li>
                            <li>• <strong>Portability:</strong> Receive data in machine-readable format</li>
                            <li>• <strong>Complaint:</strong> Lodge concerns with National Privacy Commission</li>
                        </ul>
                    </div>

                    <!-- Section 6: Security -->
                    <div class="policy-point">
                        <h4 class="policy-title">6. Data Security & Sharing</h4>
                        <p class="policy-list">
                            • <strong>Security:</strong> HTTPS encryption, secure databases, access controls<br>
                            • <strong>Third Parties:</strong> Payment processors, hosting (under strict agreements)<br>
                            • <strong>No Selling:</strong> We never sell your data for marketing
                        </p>
                    </div>

                    <!-- Important Notice -->
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 text-xs text-gray-700" style="background-color: #fefce8; border-color: #fde047; border-width: 1px; border-radius: 0.5rem; padding: 1rem; font-size: 0.75rem; color: #374151;">
                        <p><strong>⚠️ Important:</strong> Required fields (name, email, phone) are necessary for booking. Optional fields and marketing are voluntary.</p>
                    </div>

                    <!-- Contact -->
                    <div class="text-xs text-gray-600 mt-4 text-center" style="font-size: 0.75rem; color: #4b5563; margin-top: 1rem; text-align: center;">
                        <p>For privacy requests, contact: <a href="mailto:privacy@jbrb-resort.com" class="underline" style="text-decoration: underline; color: #2563eb;">privacy@jbrb-resort.com</a></p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="modal-actions">
                        <button type="button" x-on:click="declinePrivacy()" class="btn-modal-back">
                            ← Go Back
                        </button>
                        <button type="button" x-on:click="acceptPrivacy()" class="btn-modal-proceed">
                            I Understand & Agree
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

    <script>
        // Capitalize proper function - allows manual capitalization (e.g., Region XI stays as XI)
        function capitalizeProperInline(element) {
            const start = element.selectionStart;
            const end = element.selectionEnd;
            let value = element.value;

            // Only capitalize lowercase letters at word boundaries
            value = value.replace(/\b[a-z]/g, char => char.toUpperCase());

            element.value = value;
            element.setSelectionRange(start, end);
        }

        function personalDetailsForm() {
            return {
                showPrivacyModal: true, // Always show on page load

                init() {
                    // Modal always shows on page load - no localStorage check
                    this.showPrivacyModal = true;
                },

                acceptPrivacy() {
                    // Simply close the modal - user can proceed with form
                    this.showPrivacyModal = false;
                },

                declinePrivacy() {
                    // Redirect back if user declines
                    window.location.href = '{{ route("bookings.check-availability") }}';
                }
            }
        }
    </script>

@endsection
