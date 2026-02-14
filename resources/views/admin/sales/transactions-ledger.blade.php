@extends('layouts.admin')

@section('title', 'Transactions Ledger')

@section('content')

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">Transactions Ledger</h1>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-outline" id="filterBtn">
            <i class="fas fa-filter"></i>
            Filters
            <span class="filter-badge" id="filterCount" style="display: none;">0</span>
        </button>
    </div>
</div>

<!-- Active Filters Display -->
<div class="active-filters" id="activeFilters" style="display: none;">
    <div class="active-filters-label">Active Filters:</div>
    <div class="filter-tags" id="filterTags"></div>
    <button type="button" class="btn-clear-filters" id="clearFilters">
        <i class="fas fa-times"></i>
        Clear All
    </button>
</div>

<!-- Summary Cards -->
<div class="ledger-summary">
    <div class="summary-card">
        <div class="summary-icon icon-blue">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="summary-content">
            <div class="summary-label">Total Transactions</div>
            <div class="summary-value" id="totalTransactions">0</div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon icon-green">
            <i class="fas fa-peso-sign"></i>
        </div>
        <div class="summary-content">
            <div class="summary-label">Total Amount</div>
            <div class="summary-value">₱<span id="totalAmount">0.00</span></div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon icon-purple">
            <i class="fas fa-bed"></i>
        </div>
        <div class="summary-content">
            <div class="summary-label">Booking Transactions</div>
            <div class="summary-value" id="bookingCount">0</div>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon icon-orange">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="summary-content">
            <div class="summary-label">Rental Transactions</div>
            <div class="summary-value" id="rentalCount">0</div>
        </div>
    </div>
</div>

<!-- Ledger Table -->
<div class="ledger-container">
    <div class="ledger-controls">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" class="search-input" id="searchInput" placeholder="Search by transaction ID, guest name...">
        </div>
        <div class="filter-dropdowns-inline">
            <select class="filter-select" id="filterSource">
                <option value="">All Sources</option>
                <option value="booking">Booking</option>
                <option value="rental">Rental</option>
            </select>
            <select class="filter-select" id="filterMethod">
                <option value="">All Methods</option>
                <option value="cash">Cash</option>
                <option value="gcash">GCash</option>
            </select>
        </div>
        @if(auth()->user()->role !== 'staff')
            <button type="button" class="btn btn-outline btn-sm" id="viewVoidedBtn">
                <i class="fas fa-ban"></i>
                View Voided
            </button>
        @endif
    </div>

    <div class="table-wrapper scrollable-table">
        <table class="ledger-table">
            <thead>
                <tr>
                    <th class="col-datetime sortable" data-sort="date">
                        Date & Time
                        <i class="fas fa-sort"></i>
                    </th>
                    <th class="col-source sortable" data-sort="source">
                        Source
                        <i class="fas fa-sort"></i>
                    </th>
                    <th class="col-transaction-id">Transaction ID</th>
                    <th class="col-guest-name">Guest Name</th>
                    <th class="col-purpose">Purpose</th>
                    <th class="col-amount sortable" data-sort="amount">
                        Amount
                        <i class="fas fa-sort"></i>
                    </th>
                    <th class="col-method">Method</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody id="ledgerTableBody">
                <tr class="loading-row">
                    <td colspan="8" class="text-center">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Loading transactions...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal" id="filterModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Select Date Range</h3>
                <button type="button" class="modal-close" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="date-range-presets">
                    <button type="button" class="preset-btn active" data-preset="year">This Year</button>
                    <button type="button" class="preset-btn" data-preset="month">This Month</button>
                    <button type="button" class="preset-btn" data-preset="week">This Week</button>
                </div>
                <div class="form-divider">
                    <span>Or select custom range</span>
                </div>
                <div class="form-row-custom">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-input" id="filterStartDate">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-input" id="filterEndDate">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="resetFilters">Cancel</button>
                <button type="button" class="btn btn-primary" id="applyFilters">Apply</button>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Detail Modal -->
<div class="modal" id="transactionDetailModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Transaction Details</h3>
                <button type="button" class="modal-close" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="transactionDetailBody">
                <!-- Dynamically populated with transaction details including Processed By -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="voidTransactionBtn" style="display: none;">
                    <i class="fas fa-ban"></i>
                    Void Transaction
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal" id="exportLedgerModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Export Transactions</h3>
                <button type="button" class="modal-close" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="export-info">
                    <i class="fas fa-info-circle"></i>
                    <span id="exportInfo">Exporting 0 selected transactions</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Export Format</label>
                    <select class="form-input" id="ledgerExportFormat">
                        <option value="csv">CSV (Excel Compatible)</option>
                        <option value="pdf">PDF Document</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">File Name</label>
                    <input type="text" class="form-input" id="exportFileName" value="transactions-ledger">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmLedgerExport">
                    <i class="fas fa-download"></i>
                    Export
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Voided Transactions Modal -->
<div class="modal" id="voidedTransactionsModal">
    <div class="modal-dialog modal-xlg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Voided Transactions</h3>
                <button type="button" class="modal-close" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-wrapper scrollable-table">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th class="col-datetime">Date & Time</th>
                                <th class="col-source">Source</th>
                                <th class="col-transaction-id">Transaction ID</th>
                                <th class="col-guest-name">Guest Name</th>
                                <th class="col-purpose">Purpose</th>
                                <th class="col-amount">Amount</th>
                                <th class="col-method">Method</th>
                                <th class="col-processed">Processed By</th>
                            </tr>
                        </thead>
                        <tbody id="voidedTableBody">
                            <tr class="loading-row">
                                <td colspan="8" class="text-center">
                                    <div class="loading-spinner">
                                        <i class="fas fa-inbox"></i>
                                        <span>No voided transactions</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Void Confirmation Modal -->
<div class="modal" id="voidConfirmModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Void Transaction</h3>
                <button type="button" class="modal-close" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="void-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><strong>Warning:</strong> This action cannot be undone.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Enter your admin credentials to confirm:</label>
                    <input type="text" class="form-input" id="voidAdminUsername" placeholder="User ID, name, or email">
                    <small class="form-help">Enter your User ID (e.g., A001), name, or email address</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason for voiding:</label>
                    <textarea class="form-input" id="voidReason" rows="3" placeholder="Enter reason..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmVoid">
                    <i class="fas fa-ban"></i>
                    Void Transaction
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/sweetalert-custom.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/sales/sales-transactions-ledger.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/admin/transactions-ledger.js') }}"></script>
<script>
    // Inject user role for access control
    window.userRole = '{{ auth()->user()->role }}';
</script>
@endpush
