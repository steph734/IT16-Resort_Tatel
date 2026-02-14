<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - JB Resort</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .success-box {
            background-color: #d1fae5;
            border-left: 4px solid #059669;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success-box p {
            margin: 0;
            color: #065f46;
            font-weight: 600;
        }
        .booking-details {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .booking-details h3 {
            margin-top: 0;
            color: #111827;
            font-size: 18px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
        }
        .detail-value {
            color: #111827;
            text-align: right;
        }
        .payment-summary {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #93c5fd;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .payment-summary h3 {
            margin-top: 0;
            color: #1e40af;
            font-size: 18px;
            border-bottom: 2px solid #93c5fd;
            padding-bottom: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 18px;
            font-weight: 700;
            color: #1e40af;
            border-top: 2px solid #93c5fd;
            margin-top: 10px;
        }
        .policy-section {
            background-color: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .policy-section h3 {
            margin-top: 0;
            color: #92400e;
            font-size: 18px;
        }
        .policy-section h4 {
            color: #b45309;
            font-size: 14px;
            margin: 15px 0 8px 0;
        }
        .policy-section ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .policy-section li {
            color: #78350f;
            margin: 5px 0;
        }
        .important-note {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .important-note p {
            margin: 5px 0;
            color: #991b1b;
            font-size: 13px;
        }
        .contact-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }
        .contact-info h4 {
            margin-top: 0;
            color: #1e40af;
        }
        .contact-info p {
            margin: 5px 0;
            color: #1e3a8a;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .message-section {
            margin: 20px 0;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🎉 Booking Confirmed!</h1>
            <p>Thank you for choosing JB Resort</p>
        </div>
        
        <div class="content">
            <div class="success-box">
                <p>✅ Your booking has been successfully confirmed!</p>
            </div>

            <p>Dear {{ $booking->guest->FName }} {{ $booking->guest->LName }},</p>
            
            <div class="message-section">
                <p>Thank you for choosing <strong>JB Resort</strong> for your upcoming getaway! We are delighted to confirm your reservation and look forward to providing you with an exceptional experience.</p>
                
                <p>Below are the details of your booking. Please review them carefully and contact us immediately if you notice any discrepancies.</p>
            </div>

            <div class="booking-details">
                <h3>📋 Booking Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Booking Reference:</span>
                    <span class="detail-value" style="font-weight: 700; color: #2563eb;">{{ strtoupper($booking->BookingID) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Guest Name:</span>
                    <span class="detail-value">{{ $booking->guest->FName }} {{ $booking->guest->MName ? $booking->guest->MName . ' ' : '' }}{{ $booking->guest->LName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $booking->guest->Email }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">{{ $booking->guest->Phone }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($booking->CheckInDate)->format('F d, Y (l)') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-out Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($booking->CheckOutDate)->format('F d, Y (l)') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Package:</span>
                    <span class="detail-value">{{ $booking->package->Name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Number of Guests:</span>
                    <span class="detail-value">{{ $booking->Pax }} ({{ $booking->NumOfAdults ?? 0 }} adults, {{ $booking->NumOfSeniors ?? 0 }} seniors, {{ $booking->NumOfChild ?? 0 }} children)</span>
                </div>
            </div>

            <div class="payment-summary">
                <h3>💰 Payment Summary</h3>
                @php
                    // Calculate total booking amount (constant)
                    $package = $booking->package;
                    $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
                    $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
                    $daysOfStay = $checkIn->diffInDays($checkOut);
                    $packageTotal = $package->Price * $daysOfStay;
                    $excessFee = $booking->ExcessFee ?? 0;
                    $seniorDiscount = $booking->senior_discount ?? 0;
                    $totalBookingAmount = $packageTotal + $excessFee - $seniorDiscount;
                    
                    // Get amount paid (first payment)
                    $amountPaid = $payment->Amount ?? 0;
                    $remainingBalance = max(0, $totalBookingAmount - $amountPaid);
                @endphp
                <div class="detail-row">
                    <span class="detail-label">Total Booking Amount:</span>
                    <span class="detail-value">₱{{ number_format($totalBookingAmount, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Purpose:</span>
                    <span class="detail-value">{{ $payment->PaymentPurpose ?? 'Downpayment' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount Paid:</span>
                    <span class="detail-value" style="color: #059669; font-weight: 600;">₱{{ number_format($amountPaid, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Status:</span>
                    <span class="detail-value">{{ $payment->PaymentStatus ?? 'For Verification' }}</span>
                </div>
                <div class="total-row">
                    <span>Remaining Balance:</span>
                    <span>₱{{ number_format($remainingBalance, 2) }}</span>
                </div>
            </div>

            <div class="policy-section">
                <h3>📜 Important Booking Policy</h3>
                
                <h4>1. Reservations & Payments</h4>
                <ul>
                    <li>Bookings made <strong>14+ days before check-in</strong> require a <strong>₱1,000 reservation fee</strong> to secure your booking.</li>
                    <li>Bookings made <strong>less than 14 days before check-in</strong> require a <strong>50% downpayment</strong> of the total amount.</li>
                    <li>The <strong>remaining balance</strong> must be paid directly to the resort upon check-in.</li>
                </ul>

                <h4>2. Cancellations & Changes</h4>
                <ul>
                    <li>Cancellations made <strong>more than 2 weeks</strong> before check-in may be eligible for refunds (minus processing fees).</li>
                    <li>Cancellations made <strong>less than 2 weeks</strong> before check-in will result in forfeiture of the downpayment.</li>
                    <li>No refunds will be issued for no-shows.</li>
                </ul>

                <h4>3. Check-in & Check-out</h4>
                <ul>
                    <li><strong>Check-in time:</strong> 2:00 PM</li>
                    <li><strong>Check-out time:</strong> 12:00 PM (noon)</li>
                    <li>Early check-in or late check-out may be requested subject to availability.</li>
                </ul>

                <h4>4. Senior Citizen Discounts</h4>
                <ul>
                    <li>Senior citizen discounts will be applied at check-in upon presentation of valid ID.</li>
                    <li>Discounts are subject to government regulations and resort policies.</li>
                </ul>
            </div>

            <div class="important-note">
                <p><strong>⚠️ Important:</strong> By confirming your booking, you acknowledge that you have read, understood, and accepted the above booking policy.</p>
            </div>

            <div class="message-section">
                <p><strong>What to bring on your check-in:</strong></p>
                <ul>
                    <li>Valid government-issued ID</li>
                    <li>This booking confirmation (printed or digital)</li>
                    <li>Remaining balance payment (if applicable)</li>
                    <li>Senior citizen ID (if applicable for discount)</li>
                </ul>
            </div>

            <div class="contact-info">
                <h4>📞 Need Assistance?</h4>
                <p>If you have any questions or need to modify your booking, please contact us:</p>
                <p><strong>Email:</strong> {{ config('mail.from.address', 'jbresortbohol@gmail.com') }}</p>
                <p><strong>Phone:</strong> +63 123 456 7890</p>
                <p><strong>Operating Hours:</strong> 8:00 AM - 6:00 PM (Daily)</p>
            </div>

            <p style="margin-top: 30px;">We're excited to welcome you to JB Resort!</p>
            
            <p>Best regards,<br>
            <strong>JB Resort Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated confirmation from JB Resort Booking System.</p>
            <p>&copy; {{ date('Y') }} JB Resort. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
