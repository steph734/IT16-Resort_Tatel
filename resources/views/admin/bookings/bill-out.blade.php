@extends('layouts.admin')

@section('title', 'Bill Out')

@section('styles')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/admin/bookings/bill-out.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/sweetalert-custom.css') }}">
@endsection

@section('content')
        <div class="page-header">
            <a href="{{ route('admin.currently-staying') }}" class="btn-back" title="Back to Currently Staying">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="page-title">Bill Out - {{ $booking->guest->FName }}@if($booking->guest->MName) {{ $booking->guest->MName }}@endif {{ $booking->guest->LName }}</h1>
        </div>

        <form id="billOutForm" method="POST" action="{{ route('admin.currently-staying.bill-out.process') }}">
            @csrf
            <input type="hidden" name="booking_id" value="{{ $booking->BookingID }}">

            <!-- Outstanding Balance Banner -->
            <div class="outstanding-banner">
                <div class="outstanding-label">
                    <i class="fas fa-exclamation-triangle"></i>
                    TOTAL OUTSTANDING BALANCE
                </div>
                <div class="outstanding-amount" id="outstandingBannerAmount">₱{{ number_format($totalOutstanding, 2) }}</div>
            </div>

            <div class="bill-out-container">
                <!-- Left Column - Senior Discount -->
                <div class="discount-section">
                    <!-- Senior Citizen Discount -->
                    @if($booking->NumOfSeniors > 0)
                        <div class="senior-discount-card">
                            <div class="senior-header">
                                <i class="fas fa-user-clock"></i>
                                <h3>Senior Citizen Discount</h3>
                            </div>
                            <div class="senior-body">
                                <div class="senior-info-row">
                                    <span class="label">Seniors Listed:</span>
                                    <span class="value">{{ $booking->NumOfSeniors }}</span>
                                </div>
                                <div class="form-group">
                                    <label for="actualSeniors">Actual Seniors Present <span class="required">*</span></label>
                                    <input type="number" 
                                           class="form-input" 
                                           id="actualSeniors" 
                                           name="actual_seniors" 
                                           min="0" 
                                           max="{{ $booking->NumOfSeniors }}" 
                                           placeholder="0"
                                           required>
                                    <small class="help-text">Enter the number of seniors who actually showed up (0-{{ $booking->NumOfSeniors }})</small>
                                </div>
                                <div class="discount-toggle">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="applySeniorDiscount" name="apply_senior_discount" value="1" disabled>
                                        <span>Apply 20% Senior Citizen Discount</span>
                                    </label>
                                </div>
                                <div class="discount-summary" id="discountSummary" style="display: none;">
                                    <div class="discount-breakdown" style="margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #86efac;">
                                        <div style="font-size: 0.75rem; color: #065f46; margin-bottom: 0.5rem;">
                                            <strong>Calculation:</strong>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #047857; margin-bottom: 0.25rem;">
                                            Package Total: <span id="packageTotalDisplay">₱0.00</span>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #047857; margin-bottom: 0.25rem;">
                                            Total Guests: <span id="totalGuestsDisplay">0</span> ({{ $booking->NumOfAdults ?? 0 }} adults + <span id="actualSeniorsDisplay">0</span> seniors)
                                        </div>
                                        <div style="font-size: 0.75rem; color: #047857; margin-bottom: 0.25rem;">
                                            Cost per Person: <span id="costPerPersonDisplay">₱0.00</span>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #047857;">
                                            Senior Portion: <span id="seniorPortionDisplay">₱0.00</span> × 20%
                                        </div>
                                    </div>
                                    <div class="discount-row">
                                        <span>Discount Amount:</span>
                                        <span class="discount-amount" id="discountAmount">₱0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="no-discount-card">
                            <div class="no-discount-header">
                                <i class="fas fa-info-circle"></i>
                                <h3>No Senior Discount Available</h3>
                            </div>
                            <div class="no-discount-body">
                                <p>No senior citizens listed in this booking.</p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right Column - Payment Information -->
                <div class="payment-section">
                    <div class="payment-info-card">
                        <div class="payment-header">
                            <i class="fas fa-credit-card"></i>
                            <h3>Payment Information</h3>
                        </div>
                        <div class="payment-body">
                            <div class="form-group">
                                <label for="paymentMethod">Payment Method <span class="required">*</span></label>
                                <input type="text" 
                                       class="form-input" 
                                       id="paymentMethod" 
                                       name="payment_method" 
                                       value="Cash" 
                                       readonly>
                            </div>

                            <div class="form-group">
                                <label for="totalOutstandingField">Total Outstanding Balance <span class="required">*</span></label>
                                <input type="text" 
                                       class="form-input" 
                                       id="totalOutstandingField" 
                                       name="total_outstanding"
                                       value="₱{{ number_format($totalOutstanding, 2) }}" 
                                       readonly>
                            </div>

                            <div class="form-group">
                                <label for="amountReceived">Amount Received (₱) <span class="required">*</span></label>
                                <input type="number" 
                                       class="form-input" 
                                       id="amountReceived" 
                                       name="amount_received"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="changeAmount">Change (₱)</label>
                                <input type="text" 
                                       class="form-input" 
                                       id="changeAmount" 
                                       name="change_amount"
                                       value="₱0.00" 
                                       readonly>
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes (Optional)</label>
                                <textarea class="form-input" id="notes" name="notes" rows="3" placeholder="Add any additional notes..."></textarea>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn-primary btn-submit">
                                <i class="fas fa-check-circle"></i> Process Bill Out
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History Section (Full Width) -->
            @if($booking->payments && count($booking->payments) > 0)
                <div class="payment-history-section">
                    <div class="payment-history-header">
                        <i class="fas fa-history"></i>
                        <h3>Payment History</h3>
                    </div>
                    <div class="payment-history-table">
                        <table>
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
                            <tbody>
                                @foreach($booking->payments as $p)
                                    <tr>
                                        <td>{{ $p->PaymentID }}</td>
                                        <td class="amount">₱{{ number_format($p->Amount, 2) }}</td>
                                        <td>{{ $p->PaymentPurpose }}</td>
                                        <td class="muted">{{ $p->ReferenceNumber ?? 'N/A' }}</td>
                                        <td class="muted">{{ \Carbon\Carbon::parse($p->PaymentDate)->format('F d, Y') }}</td>
                                        <td>
                                            <span class="status-badge status-{{ strtolower($p->PaymentStatus ?? 'completed') }}">
                                                {{ $p->PaymentStatus ?? 'Completed' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </form>
   
@endsection

@section('scripts')
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

        const totalOutstanding = {{ $totalOutstanding }};
        const hasSeniors = {{ $booking->NumOfSeniors > 0 ? 'true' : 'false' }};
        
        // Booking details for proportional discount calculation
        const numAdults = {{ $booking->NumOfAdults ?? 0 }};
        const numSeniors = {{ $booking->NumOfSeniors ?? 0 }};
        const numChildren = {{ $booking->NumOfChildren ?? 0 }};
        const packagePrice = {{ $booking->package->Price ?? 0 }};
        const checkInDate = new Date('{{ $booking->CheckInDate }}');
        const checkOutDate = new Date('{{ $booking->CheckOutDate }}');
        const days = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
        const excessFee = {{ $booking->ExcessFee ?? 0 }};
        
        // Calculate package total (excluding other charges like rentals and unpaid items)
        const packageTotal = (packagePrice * days) + excessFee;
        const totalPayingGuests = numAdults + numSeniors; // Children are free
        const costPerPerson = totalPayingGuests > 0 ? packageTotal / totalPayingGuests : 0;
        
        let currentSettlement = totalOutstanding;

        document.addEventListener('DOMContentLoaded', function() {
            const totalOutstandingField = document.getElementById('totalOutstandingField');
            const amountReceivedInput = document.getElementById('amountReceived');
            const changeAmountField = document.getElementById('changeAmount');

            // Calculate change when amount received changes
            amountReceivedInput.addEventListener('input', function() {
                const amountReceived = parseFloat(this.value) || 0;
                const change = amountReceived - currentSettlement;
                changeAmountField.value = `₱${change.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
            });

            // Senior discount logic
            if (hasSeniors) {
                const actualSeniorsInput = document.getElementById('actualSeniors');
                const applySeniorDiscountCheckbox = document.getElementById('applySeniorDiscount');
                const discountSummary = document.getElementById('discountSummary');
                const discountAmount = document.getElementById('discountAmount');

                function updateDiscount() {
                    const actualSeniors = parseInt(actualSeniorsInput.value) || 0;
                    const applyDiscount = applySeniorDiscountCheckbox.checked;

                    if (actualSeniors > 0 && applyDiscount) {
                        // Calculate proportional discount: 20% of (cost per person × actual seniors)
                        const seniorPortion = costPerPerson * actualSeniors;
                        const discount = seniorPortion * 0.20;
                        currentSettlement = totalOutstanding - discount;

                        // Update breakdown display
                        document.getElementById('packageTotalDisplay').textContent = `₱${packageTotal.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        document.getElementById('totalGuestsDisplay').textContent = numAdults + actualSeniors;
                        document.getElementById('actualSeniorsDisplay').textContent = actualSeniors;
                        document.getElementById('costPerPersonDisplay').textContent = `₱${costPerPerson.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        document.getElementById('seniorPortionDisplay').textContent = `₱${seniorPortion.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        
                        discountAmount.textContent = `₱${discount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        totalOutstandingField.value = `₱${currentSettlement.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        
                        // Update the banner as well
                        document.getElementById('outstandingBannerAmount').textContent = `₱${currentSettlement.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;

                        discountSummary.style.display = 'block';
                    } else {
                        currentSettlement = totalOutstanding;
                        discountSummary.style.display = 'none';
                        totalOutstandingField.value = `₱${totalOutstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                        
                        // Reset the banner to original amount
                        document.getElementById('outstandingBannerAmount').textContent = `₱${totalOutstanding.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                    }

                    // Recalculate change if amount received is entered
                    const amountReceived = parseFloat(amountReceivedInput.value) || 0;
                    if (amountReceived > 0) {
                        const change = amountReceived - currentSettlement;
                        changeAmountField.value = `₱${change.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
                    }
                }

                // Enable discount checkbox only when actual seniors > 0
                actualSeniorsInput.addEventListener('input', function() {
                    const actualSeniors = parseInt(this.value) || 0;
                    applySeniorDiscountCheckbox.disabled = actualSeniors === 0;
                    if (actualSeniors === 0) {
                        applySeniorDiscountCheckbox.checked = false;
                    }
                    updateDiscount();
                });

                applySeniorDiscountCheckbox.addEventListener('change', updateDiscount);
            }

            // Form submission
            document.getElementById('billOutForm').addEventListener('submit', function(e) {
                e.preventDefault();

                if (hasSeniors) {
                    const actualSeniors = parseInt(document.getElementById('actualSeniors').value);
                    if (isNaN(actualSeniors) || actualSeniors < 0) {
                        Swal.fire({
                            ...SwalConfig,
                            icon: 'warning',
                            title: 'Missing Information',
                            text: 'Please enter the number of actual seniors present'
                        });
                        return;
                    }
                }

                const amountReceived = parseFloat(document.getElementById('amountReceived').value) || 0;
                if (amountReceived < currentSettlement) {
                    Swal.fire({
                        ...SwalConfig,
                        icon: 'error',
                        title: 'Insufficient Amount',
                        html: `Amount received <strong>₱${amountReceived.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong> is less than total outstanding <strong>₱${currentSettlement.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</strong>`
                    });
                    return;
                }

                const change = amountReceived - currentSettlement;
                
                Swal.fire({
                    ...SwalConfig,
                    icon: 'question',
                    title: 'Process Bill Out?',
                    html: `
                        <div style="text-align: left; font-size: 15px; line-height: 1.8;">
                            <div style="margin-bottom: 8px;"><strong>Total Outstanding:</strong> ₱${currentSettlement.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
                            <div style="margin-bottom: 8px;"><strong>Amount Received:</strong> ₱${amountReceived.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
                            <div><strong>Change:</strong> ₱${change.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Process Bill Out',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                    // Add settlement amount to form
                    const settlementInput = document.createElement('input');
                    settlementInput.type = 'hidden';
                    settlementInput.name = 'settlement_amount';
                    settlementInput.value = currentSettlement.toFixed(2);
                        this.appendChild(settlementInput);

                        this.submit();
                    }
                });
            });
        });
    </script>
@endsection
