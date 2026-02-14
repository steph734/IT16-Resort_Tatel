@extends('layouts.admin')

@section('title', 'Sales Reports')

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">Sales Reports</h1>
        </div>
    </div>

    <!-- Report Presets -->
    <div class="reports-grid">
        <!-- Per Booking Sales Report -->
        <div class="report-card">
            <div class="report-icon icon-green">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="report-content">
                <h3 class="report-title">Per Booking Sales Report</h3>
                <p class="report-description">Comprehensive breakdown of completed booking with all payments, rentals, fees, and discounts
                </p>
                <div class="report-meta">
                    <span class="report-tag">Per Booking</span>
                    <span class="report-tag">PDF</span>
                </div>
            </div>
            @if(auth()->user()->role === 'staff')
                <button type="button" class="btn btn-secondary btn-block" disabled
                        title="Report generation is restricted to Admin and Owner roles only">
                    <i class="fas fa-lock"></i>
                    Generate Report (Admin Only)
                </button>
            @else
                <button type="button" class="btn btn-primary btn-block" data-report="per-booking">
                    <i class="fas fa-plus"></i>
                    Generate Report
                </button>
            @endif
        </div>

        <!-- Monthly Sales Report -->
        <div class="report-card">
            <div class="report-icon icon-blue">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="report-content">
                <h3 class="report-title">Monthly Sales Report</h3>
                <p class="report-description">Monthly revenue summary with daily breakdown, package performance, and rental statistics</p>
                <div class="report-meta">
                    <span class="report-tag">Monthly</span>
                    <span class="report-tag">PDF</span>
                </div>
            </div>
            @if(auth()->user()->role === 'staff')
                <button type="button" class="btn btn-secondary btn-block" disabled
                        title="Report generation is restricted to Admin and Owner roles only">
                    <i class="fas fa-lock"></i>
                    Generate Report (Admin Only)
                </button>
            @else
                <button type="button" class="btn btn-primary btn-block" data-report="monthly">
                    <i class="fas fa-plus"></i>
                    Generate Report
                </button>
            @endif
        </div>

        <!-- Annual Sales Report -->
        <div class="report-card">
            <div class="report-icon icon-purple">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="report-content">
                <h3 class="report-title">Annual Sales Report</h3>
                <p class="report-description">Year-end summary with monthly revenue trends, payment method distribution, and growth analysis</p>
                <div class="report-meta">
                    <span class="report-tag">Yearly</span>
                    <span class="report-tag">PDF</span>
                </div>
            </div>
            @if(auth()->user()->role === 'staff')
                <button type="button" class="btn btn-secondary btn-block" disabled
                        title="Report generation is restricted to Admin and Owner roles only">
                    <i class="fas fa-lock"></i>
                    Generate Report (Admin Only)
                </button>
            @else
                <button type="button" class="btn btn-primary btn-block" data-report="annual">
                    <i class="fas fa-plus"></i>
                    Generate Report
                </button>
            @endif
        </div>

        <!-- Custom Sales Report -->
        <div class="report-card">
            <div class="report-icon icon-orange">
                <i class="fas fa-sliders-h"></i>
            </div>
            <div class="report-content">
                <h3 class="report-title">Custom Sales Report</h3>
                <p class="report-description">Custom date range with detailed transaction history, booking analysis, and rental performance metrics</p>
                <div class="report-meta">
                    <span class="report-tag">Custom Range</span>
                    <span class="report-tag">PDF</span>
                </div>
            </div>
            @if(auth()->user()->role === 'staff')
                <button type="button" class="btn btn-secondary btn-block" disabled
                        title="Report generation is restricted to Admin and Owner roles only">
                    <i class="fas fa-lock"></i>
                    Generate Report (Admin Only)
                </button>
            @else
                <button type="button" class="btn btn-primary btn-block" data-report="custom">
                    <i class="fas fa-plus"></i>
                    Generate Report
                </button>
            @endif
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="recent-reports-section">
        <div class="section-header">
            <h2 class="section-title">Recent Reports</h2>
            <button type="button" class="btn btn-outline-sm" id="clearHistoryBtn">
                <i class="fas fa-trash"></i>
                Clear History
            </button>
        </div>
        <div class="recent-reports-list" id="recentReportsList">
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <p>No reports generated yet</p>
                <span>Your generated reports will appear here</span>
            </div>
        </div>
    </div>

    <!-- Report Generator Modal -->
    <div class="modal" id="reportGeneratorModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="reportModalTitle">Generate Report</h3>
                    <button type="button" class="modal-close" data-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="reportGeneratorForm">
                        <input type="hidden" id="reportType" name="report_type">

                        <!-- Booking Selector Section (for Per Booking report only) -->
                        <div class="form-section" id="bookingSelectSection">
                            <h4 class="form-section-title">Select Booking</h4>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Completed Booking</label>
                                    <select class="form-input" id="bookingSelect" name="booking_id">
                                        <option value="">-- Select a completed booking --</option>
                                        @foreach($completedBookings as $booking)
                                            <option value="{{ $booking['BookingID'] }}" 
                                                data-checkin="{{ \Carbon\Carbon::parse($booking['CheckInDate'])->format('M d, Y') }}" 
                                                data-checkout="{{ \Carbon\Carbon::parse($booking['CheckOutDate'])->format('M d, Y') }}" 
                                                data-package="{{ $booking['PackageName'] }}">
                                                Booking #{{ $booking['BookingID'] }} - {{ $booking['GuestName'] }} ({{ $booking['PackageName'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">Date Range</label>
                                    <input type="text" class="form-input form-input-readonly" id="bookingDateRange" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Date Range Section -->
                        <div class="form-section" id="dateRangeSection">
                            <h4 class="form-section-title">Date Range</h4>
                            <div class="form-row">
                                <div class="form-group col-md-6" id="periodGroup">
                                    <label class="form-label">Period</label>
                                    <input type="text" class="form-input" id="reportPeriodDisplay" readonly>
                                </div>
                                <div class="form-group col-md-6" id="specificDateGroup" style="display: none;">
                                    <label class="form-label">Select Date</label>
                                    <input type="date" class="form-input" id="reportSpecificDate" name="specific_date">
                                </div>
                            </div>
                            <div class="form-row" id="customDateRange" style="display: none;">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-input" id="reportStartDate" name="start_date">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-input" id="reportEndDate" name="end_date">
                                </div>
                            </div>
                            <div class="form-row" id="monthYearSelectors" style="display: none;">
                                <div class="form-group col-md-6" id="monthGroup">
                                    <label class="form-label">Month</label>
                                    <select class="form-input" id="reportMonth" name="month">
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6" id="yearGroup">
                                    <label class="form-label">Year</label>
                                    <select class="form-input" id="reportYear" name="year">
                                        <!-- Populated by JavaScript -->
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Export Options -->
                        <div class="form-section">
                            <h4 class="form-section-title">Export Options</h4>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label">Format</label>
                                    <input type="text" class="form-input form-input-readonly" id="reportFormatDisplay" value="PDF Document" readonly>
                                    <input type="hidden" id="reportFormat" name="format" value="pdf">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label">File Name</label>
                                    <input type="text" class="form-input" id="reportFileName" name="file_name"
                                        placeholder="report-name">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="previewReportBtn">
                        <i class="fas fa-eye"></i>
                        Preview Report
                    </button>
                    <button type="button" class="btn btn-success" id="downloadReportBtn">
                        <i class="fas fa-download"></i>
                        Download Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Preview Modal -->
    <div class="modal" id="reportPreviewModal">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Report Preview</h3>
                    <button type="button" class="modal-close" data-dismiss="modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" id="reportPreviewBody">
                    <div class="preview-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Generating preview...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="downloadFromPreviewBtn">
                        <i class="fas fa-download"></i>
                        Download Report
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/sweetalert-custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/sales/sales-reports.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/admin/sales-reports.js') }}"></script>
@endpush