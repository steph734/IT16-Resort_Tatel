<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Annual Sales Report - {{ $period['year'] }}</title>
    @if(isset($isPdf) && $isPdf)
        <link rel="stylesheet" href="{{ public_path('css/pdf/annual.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('css/pdf/annual.css') }}">
    @endif
</head>
<body>
    <div class="header">
        <h1>Annual Sales Report</h1>
        <p>Year {{ $period['year'] }}</p>
        <p>Generated: {{ now()->format('M d, Y g:i A') }}</p>
    </div>

    <div class="summary-boxes">
        <div class="summary-box">
            <div class="summary-label">Total Annual Sales</div>
            <div class="summary-value">PHP {{ number_format($totals['sales'], 2) }}</div>
        </div>
        <div class="summary-box" style="margin-left: 10px;">
            <div class="summary-label">Total Bookings</div>
            <div class="summary-value">{{ $totals['bookings'] }}</div>
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
            @if(count($monthly_sales) > 0)
                @foreach($monthly_sales as $month)
                <tr>
                    <td>{{ $month['month'] }}</td>
                    <td class="text-right">PHP {{ number_format($month['sales'], 2) }}</td>
                    <td class="text-right {{ $month['growth'] >= 0 ? 'growth-positive' : 'growth-negative' }}">
                        {{ $month['growth'] > 0 ? '+' : '' }}{{ number_format($month['growth'], 2) }}%
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
            @if(count($package_performance) > 0)
                @foreach($package_performance as $package)
                <tr>
                    <td>{{ $package['name'] }}</td>
                    <td class="text-center">{{ $package['bookings'] }}</td>
                    <td class="text-right">PHP {{ number_format($package['sales'], 2) }}</td>
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
            @if(count($rental_performance) > 0)
                @foreach($rental_performance as $rental)
                <tr>
                    <td>{{ $rental['name'] }}</td>
                    <td class="text-center">{{ $rental['times_rented'] }}</td>
                    <td class="text-right">PHP {{ number_format($rental['sales'], 2) }}</td>
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
                <td class="text-right">PHP {{ number_format($rental_charges['damage_fees'], 2) }}</td>
            </tr>
            <tr>
                <td>Lost/Missing Item Fees</td>
                <td class="text-right">PHP {{ number_format($rental_charges['lost_fees'], 2) }}</td>
            </tr>
            <tr style="background: #f3f4f6; font-weight: bold;">
                <td>Total Charges</td>
                <td class="text-right">PHP {{ number_format($rental_charges['total'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>JBRBS Booking Management System - Annual Sales Report</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>
