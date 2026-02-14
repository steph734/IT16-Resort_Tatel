<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking No Show Notification</title>
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
            background-color: #dc2626;
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
        .alert-box {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-box p {
            margin: 0;
            color: #991b1b;
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
            <h1>⚠️ Booking No Show Notification</h1>
        </div>
        
        <div class="content">
            <div class="alert-box">
                <p>⚠️ Your booking has been flagged as a NO SHOW</p>
            </div>

            <p>Dear {{ $booking->guest->FName }} {{ $booking->guest->LName }},</p>
            
            <div class="message-section">
                <p>We regret to inform you that your booking has been marked as a <strong>NO SHOW</strong> as you did not arrive for your scheduled check-in time.</p>
                
                <p><strong>If you believe this is an error or you experienced unexpected circumstances that prevented your arrival, please contact us immediately.</strong></p>
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
                    <span class="detail-value" style="color: #dc2626; font-weight: 700;">CANCELLED (No Show)</span>
                </div>
            </div>

            <div class="message-section">
                <p><strong>What happens next?</strong></p>
                <ul>
                    <li>Your booking has been cancelled due to no-show</li>
                    <li>Any deposits or payments made may be subject to our cancellation policy</li>
                    <li>If this was a mistake, please contact us within 24 hours to resolve the issue</li>
                </ul>
            </div>

            <div class="contact-info">
                <h4>📞 Need Assistance?</h4>
                <p>If you have any questions or concerns about this notification, please contact us:</p>
                <p><strong>Email:</strong> jbresortbohol@gmail.com</p>
                <p><strong>Phone:</strong> +63 123 456 7890</p>
                <p><strong>Operating Hours:</strong> 8:00 AM - 6:00 PM (Daily)</p>
            </div>

            <p style="margin-top: 30px;">We hope to see you on your next visit!</p>
            
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
