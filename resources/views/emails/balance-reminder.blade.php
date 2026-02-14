<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reminder - Balance Due</title>
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
        .reminder-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .reminder-box p {
            margin: 0;
            color: #92400e;
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
        .balance-highlight {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .balance-highlight h2 {
            margin: 0 0 10px 0;
            color: #92400e;
            font-size: 16px;
            font-weight: 600;
        }
        .balance-amount {
            font-size: 32px;
            font-weight: 700;
            color: #b45309;
            margin: 10px 0;
        }
        .payment-button {
            display: inline-block;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: #ffffff;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .payment-button:hover {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }
        .payment-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .payment-info h3 {
            margin-top: 0;
            color: #1e40af;
            font-size: 18px;
        }
        .payment-info ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .payment-info li {
            color: #1e3a8a;
            margin: 8px 0;
        }
        .contact-info {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }
        .contact-info h4 {
            margin-top: 0;
            color: #15803d;
        }
        .contact-info p {
            margin: 5px 0;
            color: #166534;
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
            <h1>💳 Payment Reminder</h1>
            <p>Remaining Balance Notice</p>
        </div>
        
        <div class="content">
            <div class="reminder-box">
                <p>⏰ You have a remaining balance for your upcoming stay</p>
            </div>

            <p>Hello {{ $booking->GuestName ?? 'Valued Guest' }},</p>
            
            <div class="message-section">
                <p>This is a friendly reminder that you still have a remaining balance for your upcoming reservation at <strong>JB Resort</strong>.</p>
                
                <p>We're looking forward to welcoming you soon! To ensure a smooth check-in experience, we kindly remind you about your outstanding balance.</p>
            </div>

            <div class="balance-highlight">
                <h2>💰 Remaining Balance</h2>
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
                    
                    // Sum ALL payments made so far
                    $totalPaid = $booking->payments->sum('Amount');
                    
                    // Calculate remaining balance
                    $remainingBalance = max(0, $totalBookingAmount - $totalPaid);
                @endphp
                <div class="balance-amount">₱{{ number_format($remainingBalance, 2) }}</div>
                <p style="color: #92400e; margin: 10px 0 0 0;">Please settle this amount before or upon check-in</p>
            </div>

            <div class="booking-details">
                <h3>📋 Booking Summary</h3>
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value" style="font-weight: 700; color: #2563eb;">{{ $booking->BookingID }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($booking->CheckInDate)->format('F d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-out Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($booking->CheckOutDate)->format('F d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Booking Amount:</span>
                    <span class="detail-value">₱{{ number_format($totalBookingAmount, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Amount Paid:</span>
                    <span class="detail-value" style="color: #059669; font-weight: 600;">₱{{ number_format($totalPaid, 2) }}</span>
                </div>
                <div class="detail-row" style="border-top: 2px solid #f59e0b; padding-top: 12px; margin-top: 8px;">
                    <span class="detail-label" style="font-size: 16px; color: #92400e;">Remaining Balance:</span>
                    <span class="detail-value" style="font-size: 16px; font-weight: 700; color: #b45309;">₱{{ number_format($remainingBalance, 2) }}</span>
                </div>
            </div>

            <!-- Payment History -->
            @if($booking->payments->count() > 0)
            <div class="booking-details" style="margin-top: 20px;">
                <h3>📜 Payment History</h3>
                @foreach($booking->payments as $pmt)
                <div class="detail-row">
                    <span class="detail-label">
                        {{ \Carbon\Carbon::parse($pmt->PaymentDate)->format('M d, Y') }} - {{ $pmt->PaymentPurpose }}
                    </span>
                    <span class="detail-value" style="color: #059669; font-weight: 600;">₱{{ number_format($pmt->Amount, 2) }}</span>
                </div>
                @endforeach
            </div>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('bookings.payment.booking', ['booking' => $booking->BookingID]) }}" class="payment-button">
                    💳 Pay Remaining Balance Online
                </a>
                <p style="color: #6b7280; font-size: 13px; margin-top: 10px;">Click the button above to pay securely online</p>
            </div>

            <div class="payment-info">
                <h3>💳 Payment Options</h3>
                <ul>
                    <li><strong>Online Payment:</strong> Click the button above to pay securely through our payment gateway.</li>
                    <li><strong>Pay at Check-in:</strong> You can also settle the remaining balance directly at the resort upon check-in.</li>
                    <li><strong>Bank Transfer:</strong> Contact us for bank transfer details if you prefer this method.</li>
                </ul>
                
                <h3 style="margin-top: 20px;">📌 Important Notes</h3>
                <ul>
                    <li>The <strong>remaining balance</strong> must be settled before or upon check-in.</li>
                    <li>Paying online in advance can help speed up your check-in process.</li>
                    <li>Keep your payment confirmation for your records.</li>
                    <li>If you have any payment concerns, please contact us immediately.</li>
                </ul>
            </div>

            <div class="message-section">
                <p><strong>What to bring on check-in day:</strong></p>
                <ul>
                    <li>Valid government-issued ID</li>
                    <li>Booking confirmation</li>
                    <li>Payment confirmation (if paid online)</li>
                    <li>Remaining balance (if paying at resort)</li>
                </ul>
            </div>

            <div class="contact-info">
                <h4>📞 Questions or Concerns?</h4>
                <p>If you have any questions about your booking or payment, feel free to reach out:</p>
                <p><strong>Email:</strong> jbresortbohol@gmail.com</p>
                <p><strong>Phone:</strong> +63 123 456 7890</p>
                <p><strong>Operating Hours:</strong> 8:00 AM - 6:00 PM (Daily)</p>
            </div>

            <p style="margin-top: 30px;">Thank you for choosing JB Resort. We can't wait to see you!</p>
            
            <p>Best regards,<br>
            <strong>JB Resort Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated payment reminder from JB Resort Booking System.</p>
            <p>&copy; {{ date('Y') }} JB Resort. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
