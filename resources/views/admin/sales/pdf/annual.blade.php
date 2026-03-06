<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Annual Sales Report - {{ $period['year'] ?? date('Y') }}</title>
    @if(isset($isPdf) && $isPdf)
        <link rel="stylesheet" href="{{ public_path('css/pdf/annual.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('css/pdf/annual.css') }}">
    @endif
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
        <div class="summary-box" style="margin-left: 10px;">
            <div class="summary-label">Total Bookings</div>
            <div class="summary-value">{{ $totals['bookings'] ?? 0 }}</div>
        </div>
    </div>

    <!-- Monthly Sales Trend -->
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
            @if(!empty($monthly_sales))
                @foreach($monthly_sales as $month)
                <tr>
                    <td>{{ $month['month'] ?? 'Unknown' }}</td>
                    <td class="text-right">PHP {{ number_format($month['sales'] ?? 0, 2) }}</td>
                    <td class="text-right {{ ($month['growth'] ?? 0) >= 0 ? 'growth-positive' : 'growth-negative' }}">
                        {{ ($month['growth'] ?? 0) > 0 ? '+' : '' }}{{ number_format($month['growth'] ?? 0, 2) }}%
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="3" class="text-center">No sales data for this period</td></tr>
            @endif
        </tbody>
    </table>

    <!-- Do the same for other sections: $package_performance, $rental_performance, $rental_charges -->
    <!-- Example for one more: -->
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
            <tr style="background: #f3f4f6; font-weight: bold;">
                <td>Total Charges</td>
                <td class="text-right">PHP {{ number_format($rental_charges['total'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- ... rest unchanged ... -->
</body>
</html>