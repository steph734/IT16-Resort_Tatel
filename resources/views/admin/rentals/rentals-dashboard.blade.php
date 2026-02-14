@extends('layouts.admin')

@section('title', 'Rentals Dashboard')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/rentals/rentals-dashboard.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
    <div class="main-content">
        <div class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">Rentals Dashboard</h1>
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
            <!-- Total Revenue Card - Links to Rental List -->
            <a href="{{ route('admin.rentals.index') }}" class="kpi-card kpi-card-link">
                <div class="kpi-icon icon-cyan">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Total Sales</div>
                    <div class="kpi-change positive"><i class="fas fa-arrow-up"></i> Total Earnings</div>
                    <div class="kpi-value">₱{{ number_format($kpis['total_revenue'], 2) }}</div>
                </div>
            </a>

            <!-- Average Booking Revenue Card - Links to Rental List -->
            <a href="{{ route('admin.rentals.index') }}" class="kpi-card kpi-card-link">
                <div class="kpi-icon icon-dark">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Avg Booking Sales</div>
                    <div class="kpi-change positive"><i class="fas fa-arrow-up"></i> Avg rentals per booking</div>
                    <div class="kpi-value">₱{{ number_format($kpis['avg_revenue_per_booking'], 2) }}</div>
                </div>
            </a>

            <!-- Total Rentable Items Card - Links to Item Catalog -->
            <a href="{{ route('admin.rentals.catalog') }}" class="kpi-card kpi-card-link">
                <div class="kpi-icon icon-purple">
                    <i class="fas fa-box"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Total Rentable Items</div>
                    <div class="kpi-change positive"><i class="fas fa-arrow-up"></i> Active items in catalog</div>
                    <div class="kpi-value">{{ $kpis['total_rentable_items'] }}</div>
                </div>
            </a>

            <!-- Damage Rate Card - Links to Rental List filtered for Damaged/Lost -->
            <a href="{{ route('admin.rentals.index', ['status' => 'issues']) }}" class="kpi-card kpi-card-link">
                <div class="kpi-icon icon-red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Damage Rate</div>
                    <div class="kpi-change negative"><i class="fas fa-arrow-down"></i> Items lost or damaged</div>
                    <div class="kpi-value">{{ number_format($kpis['damage_rate'], 1) }}%</div>
                </div>
            </a>
        </div>

        <!-- Popular Items Table -->
        <div class="popular-items-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-star"></i>
                    Most Popular Items
                </h2>
            </div>
            <div class="table-container">
                <table class="info-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Times Rented</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($popularItems as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->name }}</strong>
                                    <small class="text-muted">{{ $item->code }}</small>
                                </td>
                                <td>{{ $item->rentals_count }}</td>
                                <td>₱{{ number_format($item->total_revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty-state">No rental data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-grid">
            <!-- Revenue Trend Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h2 class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Sales Trend
                    </h2>
                </div>
                <div class="chart-container">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>

            <!-- Revenue by Item Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <h2 class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Sales by Item Category
                    </h2>
                </div>
                <div class="chart-container">
                    <canvas id="revenueByItemChart"></canvas>
                </div>
            </div>
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
@endsection

@section('scripts')
    <script src="{{ asset('js/admin/rentals-dashboard.js') }}"></script>
    <script>
        // Revenue Trend Chart
        const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
        new Chart(revenueTrendCtx, {
            type: 'line',
            data: {
                labels: @json($revenueChart['labels']),
                datasets: [{
                    label: 'Revenue (₱)',
                    data: @json($revenueChart['data']),
                    borderColor: '#53A9B5',
                    backgroundColor: 'rgba(83, 169, 181, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Revenue by Item Chart
        const revenueByItemCtx = document.getElementById('revenueByItemChart').getContext('2d');
        new Chart(revenueByItemCtx, {
            type: 'doughnut',
            data: {
                labels: @json($revenueByItem->pluck('name')),
                datasets: [{
                    data: @json($revenueByItem->pluck('revenue')),
                    backgroundColor: [
                        '#284B53',
                        '#53A9B5',
                        '#F59E0B',
                        '#EF4444',
                        '#A855F7'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += '₱' + context.parsed.toLocaleString();
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
@endsection