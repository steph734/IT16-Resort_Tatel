@extends('layouts.admin')

@section('title', 'Inventory Dashboard')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin/inventory/inventory-dashboard.css?v=2.5') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="main-content">
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">Inventory Dashboard</h1>
        </div>
        <div class="page-header-actions">
            <button type="button" class="btn btn-outline" id="dateRangeBtn">
                <i class="fas fa-calendar-alt"></i>
                <span id="dateRangeText">This Month</span>
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <a href="{{ route('admin.inventory.list') }}" class="kpi-card kpi-card-link">
            <div class="kpi-icon icon-green">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Total Inventory Value</div>
                <div class="kpi-change positive"><i class="fas fa-arrow-up"></i> Current stock worth</div>
                <div class="kpi-value">₱{{ number_format($stats['total_inventory_value'] ?? 0, 2) }}</div>
            </div>
        </a>

        <a href="{{ route('admin.inventory.list') }}?filter=low_stock" class="kpi-card kpi-card-link">
            <div class="kpi-icon icon-amber">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Items Needing Restock</div>
                <div class="kpi-change {{ $stats['low_stock_count'] > 0 ? 'negative' : 'positive' }}"><i class="fas fa-{{ $stats['low_stock_count'] > 0 ? 'exclamation-triangle' : 'check-circle' }}"></i> {{ $stats['low_stock_count'] > 0 ? 'Action required' : 'All stocked well' }}</div>
                <div class="kpi-value">{{ $stats['low_stock_count'] }}</div>
            </div>
        </a>

        <a href="{{ route('admin.inventory.stock-movements') }}" class="kpi-card kpi-card-link">
            <div class="kpi-icon icon-blue">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-label">Stock Turnover Rate</div>
                <div class="kpi-change {{ ($stats['turnover_rate'] ?? 0) > 50 ? 'positive' : 'negative' }}"><i class="fas fa-{{ ($stats['turnover_rate'] ?? 0) > 50 ? 'arrow-up' : 'arrow-down' }}"></i> {{ ($stats['turnover_rate'] ?? 0) > 50 ? 'Healthy movement' : 'Review slow items' }}</div>
                <div class="kpi-value">{{ number_format($stats['turnover_rate'] ?? 0, 1) }}%</div>
            </div>
        </a>

        <a href="{{ route('admin.inventory.purchases') }}" class="kpi-card kpi-card-link">
            <div class="kpi-icon icon-purple">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="kpi-content">
                @php
                    $preset = request('preset', 'month');
                    $purchaseLabel = match($preset) {
                        'year' => 'Spent This Year',
                        'week' => 'Spent This Week',
                        'custom' => 'Spent This Period',
                        default => 'Spent This Month'
                    };
                    $percentChange = $stats['purchase_change_percent'] ?? 0;
                @endphp
                <div class="kpi-label">{{ $purchaseLabel }}</div>
                <div class="kpi-change {{ $percentChange >= 0 ? 'positive' : 'negative' }}"><i class="fas fa-arrow-{{ $percentChange >= 0 ? 'up' : 'down' }}"></i> @if($percentChange != 0){{ number_format(abs($percentChange), 1) }}% vs previous period @else No change @endif</div>
                <div class="kpi-value">₱{{ number_format($stats['monthly_purchases'], 2) }}</div>
            </div>
        </a>
    </div>

    <!-- Low Stock Items Section -->
    <div class="low-stock-section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fas fa-exclamation-triangle"></i>
                Low Stock Alert
            </h2>
            <a href="{{ route('admin.inventory.list') }}?filter=low_stock" class="view-all-link">
                View All →
            </a>
        </div>
        @if($lowStockItems->count() > 0)
            <div class="table-container">
                <table class="info-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>On Hand</th>
                            <th>Reorder Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lowStockItems as $item)
                        @php
                            $categoryValue = is_object($item->category) ? $item->category->value : $item->category;
                            $categoryLabel = match($categoryValue) {
                                'cleaning' => 'Cleaning',
                                'kitchen' => 'Kitchen',
                                'amenity' => 'Amenity',
                                'rental_item' => 'Rental Item',
                                default => ucfirst(str_replace('_', ' ', $categoryValue))
                            };
                        @endphp
                        <tr class="clickable-row" onclick="window.location='{{ route('admin.inventory.list') }}?item={{ $item->sku }}'">
                            <td>
                                <div class="item-name-cell">
                                    <span class="item-name">{{ $item->name }}</span>
                                    @if($item->sku)
                                        <span class="item-sku">SKU: {{ $item->sku }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ $categoryLabel }}</span>
                            </td>
                            <td>
                                <span class="qty-value low">{{ $item->quantity_on_hand }}</span>
                            </td>
                            <td>
                                <span class="qty-value">{{ $item->reorder_level }}</span>
                            </td>
                            <td>
                                <span class="badge badge-warning">
                                    <i class="fas fa-exclamation-circle"></i> Low Stock
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <p>All items are adequately stocked</p>
            </div>
        @endif
    </div>

    <!-- Charts and Recent Activity Row -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h3 class="chart-title">Inventory Distribution</h3>
                    <span class="chart-subtitle">Stock allocation across categories</span>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="stockByCategoryChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="chart-card">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Activity
                </h2>
                <a href="{{ route('admin.inventory.stock-movements') }}" class="view-all-link">
                    View All →
                </a>
            </div>
            @if($recentActivity->count() > 0)
                <div class="activity-list">
                    @foreach($recentActivity as $activity)
                    @php
                        $movementType = is_object($activity->movement_type) ? $activity->movement_type->value : $activity->movement_type;
                        $reasonText = is_object($activity->reason) ? $activity->reason->label() : ucfirst(str_replace('_', ' ', $activity->reason));
                    @endphp
                    <div class="activity-item">
                        <div class="activity-icon {{ $movementType }}">
                            <i class="fas fa-{{ $movementType == 'in' ? 'arrow-down' : 'arrow-up' }}"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <span class="movement-badge {{ $movementType }}">
                                    {{ $movementType == 'in' ? '+' : '-' }}{{ $activity->quantity }}
                                </span>
                                {{ $activity->inventoryItem->name }}
                            </div>
                            <div class="activity-meta">
                                <span class="reason-text">{{ $reasonText }}</span>
                                @if($activity->purchaseEntry)
                                    · <a href="{{ route('admin.inventory.purchases.show', $activity->entry_number) }}" class="po-link">{{ $activity->purchaseEntry->entry_number }}</a>
                                @endif
                                · <span class="time-ago">{{ $activity->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>No recent activity</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Date Range Modal -->
    <div class="modal" id="dateRangeModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Select Date Range</h3>
                    <button type="button" class="modal-close" data-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="date-range-presets">
                        <button type="button" class="preset-btn" data-preset="year">This Year</button>
                        <button type="button" class="preset-btn active" data-preset="month">This Month</button>
                        <button type="button" class="preset-btn" data-preset="week">This Week</button>
                    </div>
                    <div class="form-divider">
                        <span>Or select custom range</span>
                    </div>
                    <div class="form-row-custom">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-input" id="customStartDate">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-input" id="customEndDate">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="applyDateRange">Apply</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/admin/inventory-dashboard.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Stock by Category Chart - Doughnut Chart (Pie Chart)
    const categoryData = @json($chartData['categories']);
    const ctxCategory = document.getElementById('stockByCategoryChart').getContext('2d');
    
    // Define diverse color palette for categories
    const colorPalette = [
        '#3b82f6',  // Blue - for Cleaning Supplies
        '#22c55e',  // Green - for Kitchen Supplies  
        '#f97316',  // Orange - for Amenity Supplies
        '#a855f7',  // Purple - for Rental Items
        '#53A9B5',  // Teal - fallback
    ];
    
    // Create a mapping function that handles various label formats
    function getColorForCategory(label, index) {
        const normalizedLabel = label.toLowerCase().trim();
        
        // Check for specific category keywords
        if (normalizedLabel.includes('clean')) return '#3b82f6';      // Blue
        if (normalizedLabel.includes('kitchen')) return '#22c55e';    // Green
        if (normalizedLabel.includes('amenity')) return '#f97316';    // Orange
        if (normalizedLabel.includes('rental')) return '#a855f7';     // Purple
        if (normalizedLabel.includes('supply') || normalizedLabel.includes('supplies')) {
            // Check if it's a specific supply type
            if (normalizedLabel.includes('resort')) return '#53A9B5';
        }
        
        // Fallback to palette by index
        return colorPalette[index % colorPalette.length];
    }
    
    // Map colors to data based on labels
    const backgroundColors = categoryData.labels.map((label, index) => {
        return getColorForCategory(label, index);
    });
    
    new Chart(ctxCategory, {
        type: 'doughnut',
        data: {
            labels: categoryData.labels,
            datasets: [{
                data: categoryData.values,
                backgroundColor: backgroundColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            family: 'Poppins',
                            size: 13
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            return label + ': ' + value + ' items';
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });
    
    // Debug: Log the data to see what labels are being used
    console.log('Category Labels:', categoryData.labels);
    console.log('Category Values:', categoryData.values);
    console.log('Colors Applied:', backgroundColors);
});
</script>
@endsection
