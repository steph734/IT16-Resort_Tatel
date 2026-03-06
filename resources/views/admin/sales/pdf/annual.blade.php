<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annual Sales Report - {{ $period['year'] ?? date('Y') }}</title>

    <!-- Always use asset() — works in browser + PDF generation contexts -->
    <link rel="stylesheet" href="{{ asset('css/pdf/annual.css') }}">

</head>
<body>

    <div class="header">
        <h1>Annual Sales Report</h1>
        <p>Year {{ $period['year'] ?? 'N/A' }}</p>
        <p>Generated: {{ now()->format('M d, Y g:i A') }}</p>
    </div>

    <div class="summary-boxes">
        <div class="summary-box">
            <div class="summary-label">Total Annual Sales</div>
            <div class="summary-value">PHP {{ number_format($totals['sales'] ?? 0, 2) }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-label">Total Bookings</div>
            <div class="summary-value">{{ $totals['bookings'] ?? 0 }}</div>
        </div>
    </div>

    <div class="section-title">Monthly Sales Trend</div>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th class="text-right">Sales</th>
                <th class="text-right">Growth</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($monthly_sales) && is_array($monthly_sales))
                @foreach($monthly_sales as $month)
                    <tr>
                        <td>{{ e($month['month'] ?? 'Unknown') }}</td>
                        <td class="text-right">PHP {{ number_format($month['sales'] ?? 0, 2) }}</td>
                        <td class="text-right {{ ($month['growth'] ?? 0) >= 0 ? 'growth-positive' : 'growth-negative' }}">
                            {{ ($month['growth'] ?? 0) > 0 ? '+' : '' }}{{ number_format($month['growth'] ?? 0, 2) }}%
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="3" class="text-center">No sales data for this period</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">Package Performance</div>
    <table>
        <thead>
            <tr>
                <th>Package Name</th>
                <th class="text-center">Total Bookings</th>
                <th class="text-right">Total Sales</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($package_performance) && is_array($package_performance))
                @foreach($package_performance as $package)
                    <tr>
                        <td>{{ e($package['name'] ?? 'N/A') }}</td>
                        <td class="text-center">{{ $package['bookings'] ?? 0 }}</td>
                        <td class="text-right">PHP {{ number_format($package['sales'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="3" class="text-center">No package data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">Rental Performance</div>
    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th class="text-center">Times Rented</th>
                <th class="text-right">Total Sales</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($rental_performance) && is_array($rental_performance))
                @foreach($rental_performance as $rental)
                    <tr>
                        <td>{{ e($rental['name'] ?? 'N/A') }}</td>
                        <td class="text-center">{{ $rental['times_rented'] ?? 0 }}</td>
                        <td class="text-right">PHP {{ number_format($rental['sales'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="3" class="text-center">No rental data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">Rental Charges Summary</div>
    <table>
        <tbody>
            <tr>
                <td>Damage Fees</td>
                <td class="text-right">PHP {{ number_format($rental_charges['damage_fees'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Lost/Missing Item Fees</td>
                <td class="text-right">PHP {{ number_format($rental_charges['lost_fees'] ?? 0, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Charges</td>
                <td class="text-right">PHP {{ number_format($rental_charges['total'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>JBRBS Booking Management System - Annual Sales Report</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>

</body>
</html>