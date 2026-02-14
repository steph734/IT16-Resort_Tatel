<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Restored - No Show Reversed</title>
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
            background-color: #059669;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
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
        }
        .message-section {
            margin: 20px 0;
            line-height: 1.8;
        }
        .important-note {
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .important-note h4 {
            margin-top: 0;
            color: #c2410c;
        }
        .important-note p {
            margin: 5px 0;
            color: #7c2d12;
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
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>✅ Booking Restored Successfully</h1>
        </div>
        
        <div class="content">
            <div class="success-box">
                <p>✅ Your booking has been restored and is now active again!</p>
            </div>

            <p>Dear {{ $booking->guest->FName }} {{ $booking->guest->LName }},</p>
            
            <div class="message-section">
                <p>Great news! We are pleased to inform you that the <strong>NO SHOW</strong> status on your booking has been reversed. Your reservation is now <strong>CONFIRMED</strong> and active.</p>
                
                <p>We apologize for any confusion or inconvenience this may have caused. We look forward to welcoming you to JB Resort!</p>
            </div>

            <div class="booking-details">
                <h3>📋 Booking Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Booking ID:</span>
                    <span class="detail-value">{{ $booking->BookingID }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Guest Name:</span>
                    <span class="detail-value">{{ $booking->guest->FName }} {{ $booking->guest->LName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($booking->CheckInDate)->format('F d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in Time:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($booking->CheckInDate)->format('g:i A') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-out Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($booking->CheckOutDate)->format('F d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Package:</span>
                    <span class="detail-value">{{ $booking->package->Name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Number of Guests:</span>
                    <span class="detail-value">{{ $booking->Pax ?? ($booking->NumOfAdults + $booking->NumOfChild) }} pax</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Booking Status:</span>
                    <span class="detail-value" style="color: #059669; font-weight: 700;">CONFIRMED</span>
                </div>
            </div>

            <div class="important-note">
                <h4>📌 Important Reminders</h4>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Please arrive on time for your scheduled check-in</li>
                    <li>If you anticipate any delays, please notify us in advance</li>
                    <li>Bring a valid ID and your booking confirmation</li>
                    <li>Check-in time starts at the scheduled time on your booking</li>
                    <li>For any changes to your booking, contact us at least 24 hours before check-in</li>
                </ul>
            </div>

            <div class="message-section">
                <p><strong>What to bring:</strong></p>
                <ul>
                    <li>Valid government-issued ID</li>
                    <li>This booking confirmation (printed or digital)</li>
                    <li>Payment balance (if applicable)</li>
                </ul>
            </div>

            <div class="contact-info">
                <h4>📞 Need Assistance?</h4>
                <p>If you have any questions or need to make changes to your booking, please contact us:</p>
                <p><strong>Email:</strong> jbresortbohol@gmail.com</p>
                <p><strong>Phone:</strong> +63 123 456 7890</p>
                <p><strong>Operating Hours:</strong> 8:00 AM - 6:00 PM (Daily)</p>
            </div>

            <p style="margin-top: 30px;">We're excited to host you at JB Resort!</p>
            
            <p>Best regards,<br>
            <strong>JB Resort Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from JB Resort Booking System.</p>
            <p>&copy; {{ date('Y') }} JB Resort. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
