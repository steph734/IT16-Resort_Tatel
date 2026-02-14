<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: white;
            color: #374151;
            font-size: 12px;
            line-height: 1.5;
            padding: 30px;
        }

        /* Report Header */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #284B53;
        }

        .report-title {
            font-size: 28px;
            font-weight: 700;
            color: #284B53;
            margin-bottom: 8px;
        }

        .report-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .report-meta {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .meta-item {
            font-size: 11px;
            color: #6b7280;
        }

        .meta-label {
            font-weight: 600;
            color: #284B53;
        }

        /* Filter Info */
        .filter-info {
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            border-left: 4px solid #53A9B5;
        }

        .filter-title {
            font-size: 14px;
            font-weight: 700;
            color: #284B53;
            margin-bottom: 10px;
        }

        .filter-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }

        .filter-item {
            font-size: 11px;
        }

        .filter-label {
            font-weight: 600;
            color: #6b7280;
        }

        /* Summary Section */
        .summary-section {
            margin-bottom: 24px;
        }

        .summary-title {
            font-size: 16px;
            font-weight: 700;
            color: #284B53;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }

        .summary-row {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            justify-content: center;
        }

        .summary-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            flex: 1;
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .summary-icon.icon-primary {
            background: rgba(83, 169, 181, 0.1);
            color: #53A9B5;
        }

        .summary-icon.icon-success {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .summary-icon.icon-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .summary-icon.icon-blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .summary-content {
            flex: 1;
        }

        .summary-label {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-value {
            font-size: 22px;
            font-weight: 700;
            color: #284B53;
            line-height: 1;
        }

        /* Table Section */
        .table-section {
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #284B53;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        thead {
            background: #284B53;
        }

        th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            color: white;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        th:last-child {
            border-right: none;
        }

        th.text-right {
            text-align: right;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            font-size: 11px;
            color: #374151;
        }

        td.text-right {
            text-align: right;
        }

        td:last-child {
            border-right: none;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Text Utilities */
        .text-muted {
            color: #9ca3af;
            font-style: italic;
        }

        .vendor-name {
            font-weight: 600;
            color: #284B53;
        }

        .entry-number {
            font-weight: 600;
            color: #53A9B5;
        }

        /* Footer */
        .report-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }

        /* Page break helper */
        .page-break {
            page-break-after: always;
        }

        @media print {
            body {
                padding: 0;
            }
            
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <!-- Report Header -->
    <div class="report-header">
        <div class="report-title">{{ $title }}</div>
        <div class="report-subtitle">Purchase Entries Report</div>
        <div class="report-meta">
            <div class="meta-item">
                <span class="meta-label">Generated:</span> {{ $generated_at }}
            </div>
            <div class="meta-item">
                <span class="meta-label">Generated By:</span> {{ $generated_by }}
            </div>
            <div class="meta-item">
                <span class="meta-label">Total Entries:</span> {{ $summary['total_entries'] }}
            </div>
        </div>
    </div>

    <!-- Filter Information -->
    @if($filters['search'] || $filters['date_from'] || $filters['date_to'])
    <div class="filter-info">
        <div class="filter-title">Applied Filters</div>
        <div class="filter-items">
            <div class="filter-item">
                <span class="filter-label">Range:</span> {{ $filters['range'] }}
            </div>
            @if($filters['search'])
            <div class="filter-item">
                <span class="filter-label">Search:</span> {{ $filters['search'] }}
            </div>
            @endif
            @if($filters['date_from'])
            <div class="filter-item">
                <span class="filter-label">From:</span> {{ $filters['date_from'] }}
            </div>
            @endif
            @if($filters['date_to'])
            <div class="filter-item">
                <span class="filter-label">To:</span> {{ $filters['date_to'] }}
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Summary Statistics -->
    <div class="summary-section">
        <div class="summary-title">Summary Statistics</div>
        
        <div class="summary-row">
            <div class="summary-item">
                <div class="summary-icon icon-primary">
                    {{ $summary['total_entries'] }}
                </div>
                <div class="summary-content">
                    <div class="summary-label">Total Entries</div>
                    <div class="summary-value">{{ $summary['total_entries'] }}</div>
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-icon icon-success">
                    &#8369;
                </div>
                <div class="summary-content">
                    <div class="summary-label">Total Amount</div>
                    <div class="summary-value">&#8369;{{ number_format($summary['total_amount'], 2) }}</div>
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-icon icon-blue">
                    {{ $summary['total_items'] }}
                </div>
                <div class="summary-content">
                    <div class="summary-label">Total Items</div>
                    <div class="summary-value">{{ $summary['total_items'] }}</div>
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-icon icon-warning">
                    {{ $summary['vendor_count'] }}
                </div>
                <div class="summary-content">
                    <div class="summary-label">Unique Vendors</div>
                    <div class="summary-value">{{ $summary['vendor_count'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchases Table -->
    <div class="table-section">
        <div class="section-title">Purchase Entries</div>
        <table>
            <thead>
                <tr>
                    <th>Entry #</th>
                    <th>Date</th>
                    <th>Vendor</th>
                    <th class="text-right">Total Amount</th>
                    <th class="text-right">Items</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr>
                    <td>
                        <span class="entry-number">{{ $purchase->entry_number }}</span>
                    </td>
                    <td>
                        {{ $purchase->purchase_date->format('M d, Y') }}
                    </td>
                    <td>
                        <span class="vendor-name">{{ $purchase->vendor_name }}</span>
                    </td>
                    <td class="text-right">
                        <strong>&#8369;{{ number_format($purchase->total_amount, 2) }}</strong>
                    </td>
                    <td class="text-right">
                        {{ $purchase->items->count() }}
                    </td>
                    <td>
                        {{ $purchase->creator->name ?? 'System' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">
                        No purchase entries found matching the filters
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Report Footer -->
    <div class="report-footer">
        <p>
            This is an auto-generated report from JBRBS Inventory Management System<br>
            Report includes {{ $summary['total_entries'] }} purchase entry(ies) with a total value of &#8369;{{ number_format($summary['total_amount'], 2) }}
        </p>
    </div>
</body>
</html>
