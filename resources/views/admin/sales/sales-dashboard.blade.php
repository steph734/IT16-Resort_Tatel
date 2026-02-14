@extends('layouts.admin')

@section('title', 'Sales Overview')

@section('content')

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">Sales Dashboard</h1>
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
    <div class="kpi-card kpi-card-clickable" title="Click to view booking transactions">
        <div class="kpi-icon icon-green">
            <i class="fas fa-bed"></i>
        </div>
        <div class="kpi-content">
            <div class="kpi-label" id="kpi1Label">Booking Sales</div>
            <div class="kpi-change positive"><i class="fas fa-arrow-up"></i> <span id="kpi1Change">0%</span> <span id="kpi1ChangeText">vs last period</span></div>
            <div class="kpi-value">₱<span id="kpi1Value">0.00</span></div>
        </div>
    </div>

    <div class="kpi-card kpi-card-clickable" title="Click to view rental transactions">
        <div class="kpi-icon icon-blue">
            <i class="fas fa-umbrella-beach"></i>
        </div>
        <div class="kpi-content">
            <div class="kpi-label" id="kpi2Label">Rental Sales</div>
            <div class="kpi-change positive"><i class="fas fa-arrow-up"></i> <span id="kpi2Change">0%</span> <span id="kpi2ChangeText">vs last period</span></div>
            <div class="kpi-value">₱<span id="kpi2Value">0.00</span></div>
        </div>
    </div>

    <div class="kpi-card kpi-card-clickable" title="Click to view all transactions">
        <div class="kpi-icon icon-purple">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="kpi-content">
            <div class="kpi-label" id="kpi3Label">Sales Difference</div>
            <div class="kpi-change positive" id="kpi3ChangeContainer"><i class="fas fa-arrow-up" id="kpi3Icon"></i> <span id="kpi3ChangeText">vs previous period</span></div>
            <div class="kpi-value">₱<span id="kpi3Value">0.00</span></div>
        </div>
    </div>

    <div class="kpi-card kpi-card-clickable" title="Click to view all transactions">
        <div class="kpi-icon icon-orange">
            <i class="fas fa-percentage"></i>
        </div>
        <div class="kpi-content">
            <div class="kpi-label" id="kpi4Label">Growth Rate</div>
            <div class="kpi-change positive" id="kpi4ChangeContainer"><i class="fas fa-arrow-up" id="kpi4Icon"></i> <span id="kpi4ChangeText">vs previous period</span></div>
            <div class="kpi-value"><span id="kpi4Value">0</span>%</div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="charts-row">
    <div class="chart-card large">
        <div class="chart-header">
            <h3 class="chart-title">Revenue Trend</h3>
        </div>
        <div class="chart-body">
            <canvas id="dailyRevenueChart"></canvas>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="charts-row">
    <div class="chart-card medium">
        <div class="chart-header">
            <h3 class="chart-title">Revenue by Source</h3>
        </div>
        <div class="chart-body">
            <canvas id="revenueBySourceChart"></canvas>
        </div>
    </div>

    <div class="chart-card medium">
        <div class="chart-header">
            <h3 class="chart-title">Payment Methods</h3>
        </div>
        <div class="chart-body">
            <canvas id="paymentMethodChart"></canvas>
        </div>
    </div>
</div>

<!-- Highlights Row -->
<div class="charts-row">
    <div class="chart-card medium">
        <div class="chart-header">
            <h3 class="chart-title">Top Performing Packages</h3>
            <span class="chart-subtitle">Highest revenue contributors</span>
        </div>
        <div class="chart-body">
            <div class="top-items-list" id="topPackagesList">
                <div class="top-item-placeholder">
                    <i class="fas fa-chart-bar"></i>
                    <span>Loading data...</span>
                </div>
            </div>
        </div>
    </div>

    <div class="chart-card medium">
        <div class="chart-header">
            <h3 class="chart-title">Top Rental Items</h3>
            <span class="chart-subtitle">Most rented and revenue</span>
        </div>
        <div class="chart-body">
            <div class="top-items-list" id="topRentalsList">
                <div class="top-item-placeholder">
                    <i class="fas fa-box"></i>
                    <span>Loading data...</span>
                </div>
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

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/sweetalert-custom.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/sales/sales-dashboard.css') }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/admin/sales-dashboard.js') }}"></script>
@endpush
