<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Per Booking Sales Report - Booking #{{ $booking['BookingID'] }}</title>
    @if(isset($isPdf) && $isPdf)
        <link rel="stylesheet" href="{{ public_path('css/pdf/per-booking.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('css/pdf/per-booking.css') }}">
    @endif
</head>
<body>
    <div class="header">
        <h1>Per Booking Sales Report</h1>
        <p>Booking #{{ $booking['BookingID'] }} - {{ $booking['GuestName'] }}</p>
        <p>Generated: {{ now()->format('M d, Y g:i A') }}</p>
    </div>

    <div class="section-title">Booking Information</div>
    <div class="info-section">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Booking ID:</div>
                <div class="info-value">#{{ $booking['BookingID'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Guest Name:</div>
                <div class="info-value">{{ $booking['GuestName'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $booking['GuestEmail'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone:</div>
                <div class="info-value">{{ $booking['GuestPhone'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Package:</div>
                <div class="info-value">{{ $booking['PackageName'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Check-In:</div>
                <div class="info-value">{{ $booking['CheckInDate'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Check-Out:</div>
                <div class="info-value">{{ $booking['CheckOutDate'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Duration:</div>
                <div class="info-value">{{ $booking['Days'] }} {{ $booking['Days'] == 1 ? 'day' : 'days' }}</div>
            </div>
        </div>
    </div>

    <div class="section-title">Booking Charges</div>
    <table>
        <thead>
            <tr>
                <th class="col-desc">Description</th>
                <th class="col-amount text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Package Cost ({{ $booking['Days'] }} {{ $booking['Days'] == 1 ? 'day' : 'days' }})</td>
                <td class="text-right">PHP {{ number_format($booking['PackageCost'], 2) }}</td>
            </tr>
            @if($booking['ExcessFee'] > 0)
            <tr>
                <td>Excess Fee</td>
                <td class="text-right">PHP {{ number_format($booking['ExcessFee'], 2) }}</td>
            </tr>
            @endif
            @if($booking['SeniorDiscount'] > 0)
            <tr>
                <td>Senior/PWD Discount</td>
                <td class="text-right">-PHP {{ number_format($booking['SeniorDiscount'], 2) }}</td>
            </tr>
            @endif
            <tr class="subtotal-row">
                <td><strong>Booking Subtotal</strong></td>
                <td class="text-right"><strong>PHP {{ number_format($booking['BookingTotal'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Payment History</div>
    <table>
        <thead>
            <tr>
                <th class="col-id">Payment ID</th>
                <th class="col-date">Date</th>
                <th class="col-method">Method</th>
                <th class="col-purpose">Purpose</th>
                <th class="col-status">Status</th>
                <th class="col-amount text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>#{{ $payment['PaymentID'] }}</td>
                <td>{{ \Carbon\Carbon::parse($payment['PaymentDate'])->format('M d, Y') }}</td>
                <td>{{ $payment['PaymentMethod'] }}</td>
                <td>{{ $payment['PaymentPurpose'] }}</td>
                <td>{{ $payment['PaymentStatus'] }}</td>
                <td class="text-right">PHP {{ number_format($payment['Amount'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
                <td colspan="5"><strong>Total Payments</strong></td>
                <td class="text-right"><strong>PHP {{ number_format(collect($payments)->sum('Amount'), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if(count($rentals) > 0)
    <div class="section-title">Rental Charges</div>
    <table>
        <thead>
            <tr>
                <th class="col-id">Rental ID</th>
                <th class="col-desc">Item</th>
                <th class="col-qty text-center">Qty</th>
                <th class="col-amount text-right">Rental Fee</th>
                <th class="col-amount text-right">Damage Fee</th>
                <th class="col-amount text-right">Lost/Missing Fee</th>
                <th class="col-amount text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rentals as $rental)
            <tr>
                <td>#{{ $rental['rental_id'] }}</td>
                <td>{{ $rental['item_name'] }}</td>
                <td class="text-center">{{ $rental['quantity'] }}</td>
                <td class="text-right">PHP {{ number_format($rental['rental_fee'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($rental['damage_fee'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($rental['lost_fee'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($rental['total'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
                <td colspan="6"><strong>Total Rental Charges</strong></td>
                <td class="text-right"><strong>PHP {{ number_format(collect($rentals)->sum('total'), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    @endif

    @if(count($unpaidItems) > 0)
    <div class="section-title">Store Purchases (Unpaid Items)</div>
    <table>
        <thead>
            <tr>
                <th class="col-id">Item ID</th>
                <th class="col-desc">Item Name</th>
                <th class="col-qty text-center">Quantity</th>
                <th class="col-amount text-right">Unit Price</th>
                <th class="col-amount text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($unpaidItems as $item)
            <tr>
                <td>#{{ $item['UnpaidItemID'] }}</td>
                <td>{{ $item['ItemName'] }}</td>
                <td class="text-center">{{ $item['Quantity'] }}</td>
                <td class="text-right">PHP {{ number_format($item['UnitPrice'], 2) }}</td>
                <td class="text-right">PHP {{ number_format($item['TotalAmount'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="subtotal-row">
                <td colspan="4"><strong>Total Store Purchases</strong></td>
                <td class="text-right"><strong>PHP {{ number_format(collect($unpaidItems)->sum('TotalAmount'), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-label">Booking Amount:</div>
                <div class="summary-value">PHP {{ number_format($totals['BookingAmount'], 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Rental Charges:</div>
                <div class="summary-value">PHP {{ number_format($totals['RentalAmount'], 2) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Grand Total Paid:</div>
                <div class="summary-value grand-total">PHP {{ number_format($totals['GrandTotal'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>JBRBS Booking Management System - Sales Report</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>
