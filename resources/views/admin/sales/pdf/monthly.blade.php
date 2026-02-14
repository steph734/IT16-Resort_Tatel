<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Sales Report - {{ $period['month'] }}</title>
    @if(isset($isPdf) && $isPdf)
        <link rel="stylesheet" href="{{ public_path('css/pdf/monthly.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('css/pdf/monthly.css') }}">
    @endif
</head>
<body>
    <div class="header">
        <h1>Monthly Sales Report</h1>
        <p>{{ $period['month'] }}</p>
        <p>Generated: {{ now()->format('M d, Y g:i A') }}</p>
    </div>

    <div class="summary-boxes">
        <div class="summary-box">
            <div class="summary-label">Total Sales</div>
            <div class="summary-value">PHP {{ number_format($totals['sales'], 2) }}</div>
        </div>
        <div class="summary-box" style="margin-left: 10px;">
            <div class="summary-label">Total Bookings</div>
            <div class="summary-value">{{ $totals['bookings'] }}</div>
        </div>
    </div>

    <div class="section-title">Package Sales</div>
    <table>
        <thead>
            <tr>
                <th>Package Name</th>
                <th class="text-center">Bookings</th>
                <th class="text-right">Sales</th>
            </tr>
        </thead>
        <tbody>
            @if(count($package_sales) > 0)
                @foreach($package_sales as $package)
                <tr>
                    <td>{{ $package['name'] }}</td>
                    <td class="text-center">{{ $package['bookings'] }}</td>
                    <td class="text-right">PHP {{ number_format($package['sales'], 2) }}</td>
                </tr>
                @endforeach
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td colspan="2">Total</td>
                    <td class="text-right">PHP {{ number_format($package_sales_total, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td colspan="3" class="text-center">No package data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">Rental Item Sales</div>
    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th class="text-center">Times Rented</th>
                <th class="text-right">Sales</th>
            </tr>
        </thead>
        <tbody>
            @if(count($rental_item_sales) > 0)
                @foreach($rental_item_sales as $rental)
                <tr>
                    <td>{{ $rental['name'] }}</td>
                    <td class="text-center">{{ $rental['times_rented'] }}</td>
                    <td class="text-right">PHP {{ number_format($rental['sales'], 2) }}</td>
                </tr>
                @endforeach
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td colspan="2">Total</td>
                    <td class="text-right">PHP {{ number_format($rental_item_sales_total, 2) }}</td>
                </tr>
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

    <div class="section-title">Daily Sales Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th class="text-right">Sales</th>
            </tr>
        </thead>
        <tbody>
            @if(count($daily_sales) > 0)
                @foreach($daily_sales as $day)
                <tr>
                    <td>{{ $day['date'] }}</td>
                    <td class="text-right">PHP {{ number_format($day['total'], 2) }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="2" class="text-center">No sales data for this period</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">Weekly Sales Summary</div>
    <table>
        <thead>
            <tr>
                <th>Week</th>
                <th>Date Range</th>
                <th class="text-right">Sales</th>
            </tr>
        </thead>
        <tbody>
            @foreach($weekly_summary as $week)
            <tr>
                <td>{{ $week['week'] }}</td>
                <td>{{ $week['date_range'] }}</td>
                <td class="text-right">PHP {{ number_format($week['sales'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>JBRBS Booking Management System - Monthly Sales Report</p>
        <p>This is a computer-generated document. No signature required.</p>
    </div>
</body>
</html>
