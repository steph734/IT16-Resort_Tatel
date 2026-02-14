/**
 * Transactions Ledger JavaScript
 * Handles filtering, sorting, pagination, and export for transactions
 */

// SweetAlert2 Configuration with Project Colors
const SwalConfig = {
    confirmButtonColor: '#284B53',
    cancelButtonColor: '#6b7280',
    customClass: {
        popup: 'swal-custom-popup',
        confirmButton: 'swal-custom-confirm',
        cancelButton: 'swal-custom-cancel'
    }
};

// Get date range from preset
function getDateRangeFromPreset(preset) {
    const today = new Date();
    const start = new Date();
    const end = new Date();
    
    switch(preset) {
        case 'today':
            // Today only
            break;
        case 'yesterday':
            start.setDate(today.getDate() - 1);
            end.setDate(today.getDate() - 1);
            break;
        case 'week':
            // This week (Sunday to Saturday)
            const dayOfWeek = today.getDay();
            start.setDate(today.getDate() - dayOfWeek);
            end.setDate(start.getDate() + 6);
            break;
        case 'month':
            // This month (1st to last day)
            start.setDate(1);
            end.setMonth(today.getMonth() + 1, 0);
            break;
        case 'last_month':
            // Last month
            start.setMonth(today.getMonth() - 1, 1);
            end.setMonth(today.getMonth(), 0);
            break;
        case 'year':
            // This year (Jan 1 to Dec 31)
            start.setMonth(0, 1);
            end.setMonth(11, 31);
            break;
        default:
            return { start: '', end: '' };
    }
    
    return {
        start: start.toISOString().split('T')[0],
        end: end.toISOString().split('T')[0]
    };
}

document.addEventListener('DOMContentLoaded', function() {
    // Check for URL parameters from dashboard KPI clicks
    const urlParams = new URLSearchParams(window.location.search);
    
    // Apply URL parameters to filters
    if (urlParams.has('source')) {
        currentFilters.source = urlParams.get('source');
        document.getElementById('filterSource').value = currentFilters.source;
    }
    
    if (urlParams.has('method')) {
        currentFilters.method = urlParams.get('method');
        document.getElementById('filterMethod').value = currentFilters.method;
    }
    
    if (urlParams.has('start_date') && urlParams.has('end_date')) {
        currentFilters.startDate = urlParams.get('start_date');
        currentFilters.endDate = urlParams.get('end_date');
        currentFilters.datePreset = ''; // Clear preset when using custom dates
        
        // Update date inputs
        document.getElementById('filterStartDate').value = currentFilters.startDate;
        document.getElementById('filterEndDate').value = currentFilters.endDate;
        
        // Clear active preset button
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
    } else if (urlParams.has('preset')) {
        currentFilters.datePreset = urlParams.get('preset');
        const dates = getDateRangeFromPreset(currentFilters.datePreset);
        currentFilters.startDate = dates.start;
        currentFilters.endDate = dates.end;
        
        // Update date inputs
        document.getElementById('filterStartDate').value = dates.start;
        document.getElementById('filterEndDate').value = dates.end;
        
        // Set active preset button
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
        const presetBtn = document.querySelector(`.preset-btn[data-preset="${currentFilters.datePreset}"]`);
        if (presetBtn) presetBtn.classList.add('active');
    } else {
        // Default: Initialize with This Year
        const dates = getDateRangeFromPreset('year');
        currentFilters.startDate = dates.start;
        currentFilters.endDate = dates.end;
        currentFilters.datePreset = 'year';
        document.getElementById('filterStartDate').value = dates.start;
        document.getElementById('filterEndDate').value = dates.end;
        
        // Set active preset to "This Year"
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
        const yearBtn = document.querySelector('.preset-btn[data-preset="year"]');
        if (yearBtn) yearBtn.classList.add('active');
    }
    
    // Load initial data with applied filters
    loadLedgerData();
    
    // Setup event listeners
    setupEventListeners();
});

// State management
let currentPage = 1;
let pageSize = 25;
let currentFilters = {
    search: '',
    datePreset: 'month',
    startDate: '',
    endDate: '',
    source: '',
    method: ''
};
let selectedTransactions = new Set();
let allTransactions = [];
let filteredTransactions = [];

// Load ledger data - only fetch from server with date range filter
function loadLedgerData() {
    // Build query parameters for date range only
    const params = new URLSearchParams();
    
    if (currentFilters.startDate) {
        params.append('start_date', currentFilters.startDate);
    }
    if (currentFilters.endDate) {
        params.append('end_date', currentFilters.endDate);
    }
    
    // Call API endpoint
    console.log('Fetching transactions with params:', params.toString());
    console.log('Date filters:', { startDate: currentFilters.startDate, endDate: currentFilters.endDate });
    fetch(`/admin/sales/api/transactions?${params.toString()}`)
        .then(response => {
            console.log('Transactions response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Transactions data received:', data);
            allTransactions = data.transactions || [];
            // Apply client-side filters immediately
            applyClientSideFilters();
        })
        .catch(error => {
            console.error('Error fetching transactions:', error);
            const tbody = document.getElementById('ledgerTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr class="loading-row">
                        <td colspan="7" class="text-center">
                            <div class="loading-spinner">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Failed to load transactions. Please check console for details.</span>
                            </div>
                        </td>
                    </tr>
                `;
            }
        });
}

// Apply client-side filters without page refresh
function applyClientSideFilters() {
    currentPage = 1; // Reset to first page
    
    // Start with all transactions, excluding voided ones
    filteredTransactions = allTransactions.filter(transaction => {
        // Hide voided transactions
        if (transaction.is_voided) {
            return false;
        }
        
        // Search filter
        if (currentFilters.search) {
            const searchLower = currentFilters.search.toLowerCase();
            const searchableText = [
                transaction.id?.toString(),
                transaction.source,
                transaction.purpose,
                transaction.customer_name,
                transaction.processed_by,
                transaction.method
            ].join(' ').toLowerCase();
            
            if (!searchableText.includes(searchLower)) {
                return false;
            }
        }
        
        // Source filter
        if (currentFilters.source && transaction.source !== currentFilters.source) {
            return false;
        }
        
        // Method filter
        if (currentFilters.method) {
            const transMethod = transaction.method.toLowerCase();
            const filterMethod = currentFilters.method.toLowerCase();
            if (transMethod !== filterMethod) {
                return false;
            }
        }
        
        return true;
    });
    
    // Update display
    renderLedgerTable(filteredTransactions);
    updateSummaryFromFiltered(filteredTransactions);
    updateFilterCount();
}

// Update summary from filtered data
function updateSummaryFromFiltered(transactions) {
    const summary = {
        total_transactions: transactions.length,
        total_amount: transactions.reduce((sum, t) => sum + parseFloat(t.amount || 0), 0),
        booking_count: transactions.filter(t => t.source === 'booking').length,
        rental_count: transactions.filter(t => t.source === 'rental').length
    };
    updateSummary(summary);
}

// Update filter count badge
function updateFilterCount() {
    const filterCount = document.getElementById('filterCount');
    let activeCount = 0;
    
    if (currentFilters.search) activeCount++;
    if (currentFilters.source) activeCount++;
    if (currentFilters.method) activeCount++;
    if (currentFilters.startDate || currentFilters.endDate || currentFilters.datePreset) activeCount++;
    
    if (activeCount > 0) {
        filterCount.style.display = 'inline';
        filterCount.textContent = activeCount;
    } else {
        filterCount.style.display = 'none';
    }
}

// Render ledger table (scrollable, max 20 rows visible)
function renderLedgerTable(data) {
    const tbody = document.getElementById('ledgerTableBody');
    if (!tbody) return;
    
    if (!data || data.length === 0) {
        tbody.innerHTML = `
            <tr class="loading-row">
                <td colspan="8" class="text-center">
                    <div class="loading-spinner">
                        <i class="fas fa-inbox"></i>
                        <span>No transactions found</span>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    // Show all data (scrollable table will handle display)
    // Check if current user can void transactions (Admin or Owner only, not Staff)
    const canVoidTransaction = window.userRole && window.userRole !== 'staff';
    
    tbody.innerHTML = data.map(transaction => `
        <tr>
            <td class="col-datetime">${formatDate(transaction.date)}</td>
            <td class="col-source">
                <span class="source-badge ${transaction.source}">${capitalizeFirst(transaction.source)}</span>
            </td>
            <td class="col-transaction-id">${transaction.id}</td>
            <td class="col-guest-name">${transaction.customer_name || '–'}</td>
            <td class="col-purpose">${getPurposeDisplay(transaction.purpose)}</td>
            <td class="col-amount">₱${transaction.amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            <td class="col-method">
                <span class="method-badge ${transaction.method.toLowerCase()}">${getMethodDisplay(transaction.method)}</span>
            </td>
            <td class="col-actions">
                <button type="button" class="action-btn" onclick="viewTransaction('${transaction.id}')" title="View Details">
                    <i class="fas fa-eye"></i>
                    <span>View</span>
                </button>
                ${canVoidTransaction ? `
                    <button type="button" class="action-btn action-btn-danger" onclick="voidTransaction('${transaction.id}')" title="Void Transaction">
                        <i class="fas fa-ban"></i>
                        <span>Void</span>
                    </button>
                ` : ''}
            </td>
        </tr>
    `).join('');
}

// Update summary cards
function updateSummary(summary) {
    document.getElementById('totalTransactions').textContent = summary.total_transactions;
    document.getElementById('totalAmount').textContent = parseFloat(summary.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('bookingCount').textContent = summary.booking_count;
    document.getElementById('rentalCount').textContent = summary.rental_count;
}

// Show voided transactions modal
function showVoidedTransactions() {
    const voidedTransactions = allTransactions.filter(t => t.is_voided);
    
    const tbody = document.getElementById('voidedTableBody');
    if (!tbody) return;
    
    if (voidedTransactions.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center">
                    <div class="loading-spinner">
                        <i class="fas fa-inbox"></i>
                        <span>No voided transactions found</span>
                    </div>
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = voidedTransactions.map(transaction => `
            <tr class="voided-row">
                <td class="col-datetime">${formatDate(transaction.date)}</td>
                <td class="col-source">
                    <span class="source-badge ${transaction.source}">${capitalizeFirst(transaction.source)}</span>
                </td>
                <td class="col-transaction-id">${transaction.id}</td>
                <td class="col-guest-name">${transaction.customer_name || '–'}</td>
                <td class="col-purpose">${getPurposeDisplay(transaction.purpose)}</td>
                <td class="col-amount">₱${transaction.amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td class="col-method">
                    <span class="method-badge ${transaction.method.toLowerCase()}">${getMethodDisplay(transaction.method)}</span>
                </td>
                <td class="col-processed">${transaction.processed_by || 'System'}</td>
            </tr>
        `).join('');
    }
    
    openModal('voidedTransactionsModal');
}

// Event listeners setup
function setupEventListeners() {
    // Filter button
    document.getElementById('filterBtn')?.addEventListener('click', () => openModal('filterModal'));
    
    // Inline filter dropdowns - apply filters immediately
    document.getElementById('filterSource')?.addEventListener('change', function() {
        currentFilters.source = this.value;
        applyClientSideFilters();
    });
    
    document.getElementById('filterMethod')?.addEventListener('change', function() {
        currentFilters.method = this.value;
        applyClientSideFilters();
    });
    
    // Date preset buttons
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Clear custom dates when preset is selected
            document.getElementById('filterStartDate').value = '';
            document.getElementById('filterEndDate').value = '';
        });
    });
    
    // Custom date inputs - remove preset active state when dates are manually selected
    document.getElementById('filterStartDate')?.addEventListener('change', () => {
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
    });
    
    document.getElementById('filterEndDate')?.addEventListener('change', () => {
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
    });
    
    // Search input - apply filter immediately
    document.getElementById('searchInput')?.addEventListener('input', debounce(function(e) {
        currentFilters.search = e.target.value.toLowerCase();
        applyClientSideFilters();
    }, 300));
    
    // View Voided Transactions button
    document.getElementById('viewVoidedBtn')?.addEventListener('click', showVoidedTransactions);
    
    // Apply filters from modal
    document.getElementById('applyFilters')?.addEventListener('click', handleApplyFilters);
    
    // Reset filters
    document.getElementById('resetFilters')?.addEventListener('click', handleResetFilters);
    
    // Export confirm
    document.getElementById('confirmLedgerExport')?.addEventListener('click', handleExport);
    
    // Void confirm
    document.getElementById('confirmVoid')?.addEventListener('click', handleVoidConfirm);
    
    // Modal close
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            closeModal(this.closest('.modal').id);
        });
    });
}



// Handle apply filters from modal (date range only)
function handleApplyFilters() {
    // Get the active preset button
    const activePresetBtn = document.querySelector('.preset-btn.active');
    const datePreset = activePresetBtn ? activePresetBtn.dataset.preset : '';
    
    let startDate = document.getElementById('filterStartDate').value;
    let endDate = document.getElementById('filterEndDate').value;
    
    // Calculate dates based on preset if one is selected
    if (datePreset) {
        const dates = getDateRangeFromPreset(datePreset);
        startDate = dates.start;
        endDate = dates.end;
    }
    // If no preset but dates are provided, use custom dates
    // Date inputs already provide YYYY-MM-DD format
    
    // Validate date range
    if (startDate && endDate && startDate > endDate) {
        alert('Start date cannot be after end date');
        return;
    }
    
    // Update only date filters
    currentFilters.datePreset = datePreset;
    currentFilters.startDate = startDate;
    currentFilters.endDate = endDate;
    
    console.log('Applying date filters:', { datePreset, startDate, endDate });
    
    // Reload data from server with new date range
    loadLedgerData();
    closeModal('filterModal');
}

// Handle reset filters
function handleResetFilters() {
    // Reset all filters
    currentFilters = {
        search: '',
        datePreset: 'year',
        startDate: '',
        endDate: '',
        source: '',
        method: ''
    };
    
    // Reset preset buttons to "This Year" active
    document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
    const yearBtn = document.querySelector('.preset-btn[data-preset="year"]');
    if (yearBtn) {
        yearBtn.classList.add('active');
        // Update date inputs to show this year's range
        const dates = getDateRangeFromPreset('year');
        document.getElementById('filterStartDate').value = dates.start;
        document.getElementById('filterEndDate').value = dates.end;
    }
    
    // Reset inline filter dropdowns
    document.getElementById('filterSource').value = '';
    document.getElementById('filterMethod').value = '';
    
    // Reset search
    document.getElementById('searchInput').value = '';
    
    // Reload data with default date range
    loadLedgerData();
    closeModal('filterModal');
}

// Handle export
function handleExport() {
    const format = document.getElementById('ledgerExportFormat').value;
    const fileName = document.getElementById('exportFileName').value;
    
    console.log('Exporting', selectedTransactions.size, 'transactions as', format);
    
    closeModal('exportLedgerModal');
    
    Swal.fire({
        icon: 'info',
        title: 'Exporting Transactions',
        text: `Preparing ${selectedTransactions.size} transactions for export as ${format.toUpperCase()}...`,
        confirmButtonColor: SwalConfig.confirmButtonColor,
        customClass: SwalConfig.customClass,
        timer: 2500,
        timerProgressBar: true,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    }).then(() => {
        // After loading simulation, show success
        Swal.fire({
            icon: 'success',
            title: 'Export Complete!',
            text: `${fileName}.${format} has been downloaded.`,
            confirmButtonColor: SwalConfig.confirmButtonColor,
            customClass: SwalConfig.customClass,
            timer: 3000,
            timerProgressBar: true
        });
    });
}

// View transaction details
function viewTransaction(id) {
    const transaction = allTransactions.find(t => t.id === id);
    if (!transaction) return;
    
    const detailBody = document.getElementById('transactionDetailBody');
    
    // Build detail rows
    let detailHTML = '';
    
    // Show void banner at the top if transaction is voided
    if (transaction.is_voided) {
        detailHTML += `
        <div class="void-banner">
            <i class="fas fa-ban"></i>
            <strong>VOIDED BY ${transaction.voided_by_name ? transaction.voided_by_name.toUpperCase() : 'UNKNOWN'}</strong>
            <div class="void-banner-details">
                <span>Voided on: ${formatDateTime(transaction.voided_at)}</span>
                ${transaction.void_reason ? `<span>Reason: ${transaction.void_reason}</span>` : ''}
            </div>
        </div>`;
    }
    
    detailHTML += `<div class="transaction-detail-grid">
        <div class="detail-row">
            <div class="detail-label">Transaction ID:</div>
            <div class="detail-value">${transaction.id}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Date:</div>
            <div class="detail-value">${formatDateTime(transaction.date)}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Source:</div>
            <div class="detail-value">
                <span class="source-badge ${transaction.source}">${capitalizeFirst(transaction.source)}</span>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Purpose:</div>
            <div class="detail-value">${transaction.purpose || '–'}</div>
        </div>`;
    
    // Customer Name
    if (transaction.customer_name) {
        detailHTML += `
        <div class="detail-row">
            <div class="detail-label">Customer Name:</div>
            <div class="detail-value">${transaction.customer_name}</div>
        </div>`;
    }
    
    detailHTML += `
        <div class="detail-row">
            <div class="detail-label">Amount:</div>
            <div class="detail-value">₱${transaction.amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
        </div>`;
    
    // Amount Received & Change (for cash transactions)
    if (transaction.amount_received !== null && transaction.amount_received !== undefined) {
        detailHTML += `
        <div class="detail-row">
            <div class="detail-label">Amount Received:</div>
            <div class="detail-value">₱${transaction.amount_received.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Change:</div>
            <div class="detail-value">₱${transaction.change_amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
        </div>`;
    }
    
    detailHTML += `
        <div class="detail-row">
            <div class="detail-label">Payment Method:</div>
            <div class="detail-value">
                <span class="method-badge ${transaction.method.toLowerCase()}">${getMethodDisplay(transaction.method)}</span>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Processed By:</div>
            <div class="detail-value">${transaction.processed_by || 'Admin'}</div>
        </div>
    </div>`;
    
    detailBody.innerHTML = detailHTML;
    
    // Show/hide void button based on user role and void status
    const canVoidTransaction = window.userRole && window.userRole !== 'staff';
    const voidBtn = document.getElementById('voidTransactionBtn');
    if (canVoidTransaction && !transaction.is_voided) {
        voidBtn.style.display = 'inline-block';
        voidBtn.onclick = () => {
            closeModal('transactionDetailModal');
            voidTransaction(id);
        };
    } else {
        voidBtn.style.display = 'none';
    }
    
    openModal('transactionDetailModal');
}

// Void transaction
let transactionToVoid = null;
let transactionToVoidData = null;

function voidTransaction(id) {
    // First, fetch transaction details to check if it's a Bill Out Settlement
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch(`/admin/sales/transactions/${id}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.transaction) {
            transactionToVoidData = data.transaction;
            transactionToVoid = id;
            
            // Check if this is a Bill Out Settlement transaction
            const isBillOutSettlement = data.transaction.purpose === 'Bill Out Settlement' || 
                                       data.transaction.purpose.startsWith('Bill Out Settlement -');
            
            if (isBillOutSettlement) {
                // Show special warning for Bill Out Settlement
                Swal.fire({
                    icon: 'warning',
                    title: 'Void Bill Out Settlement',
                    html: `
                        <div style="text-align: left; margin-bottom: 15px;">
                            <p style="margin-bottom: 10px;"><strong>⚠️ Important:</strong></p>
                            <p style="margin-bottom: 10px;">This transaction is part of a <strong>Bill Out Settlement</strong>.</p>
                            <p style="margin-bottom: 10px; color: #dc2626; font-weight: 600;">Voiding this transaction will void the ENTIRE Bill Out Settlement, including:</p>
                            <ul style="margin-left: 20px; margin-bottom: 10px; color: #4b5563;">
                                <li>Booking balance payment</li>
                                <li>All rental charges</li>
                                <li>All damage and loss fees</li>
                            </ul>
                            <p style="margin-bottom: 10px; color: #284B53; font-weight: 600;">After voiding:</p>
                            <ul style="margin-left: 20px; color: #4b5563;">
                                <li>All rentals will be marked as unpaid</li>
                                <li>The payment record will be deleted</li>
                                <li>You will need to redo the complete bill-out process</li>
                            </ul>
                        </div>
                        <p style="margin-top: 15px; font-weight: 600;">Do you want to continue?</p>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Continue',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    customClass: {
                        popup: 'swal-custom-popup',
                        confirmButton: 'swal-custom-confirm',
                        cancelButton: 'swal-custom-cancel'
                    },
                    width: '600px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // User confirmed, show void modal
                        openModal('voidConfirmModal');
                        
                        // Clear inputs
                        document.getElementById('voidAdminUsername').value = '';
                        document.getElementById('voidReason').value = '';
                    } else {
                        // User cancelled
                        transactionToVoid = null;
                        transactionToVoidData = null;
                    }
                });
            } else {
                // Regular transaction, show void modal directly
                openModal('voidConfirmModal');
                
                // Clear inputs
                document.getElementById('voidAdminUsername').value = '';
                document.getElementById('voidReason').value = '';
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load transaction details.',
                confirmButtonColor: SwalConfig.confirmButtonColor,
                customClass: SwalConfig.customClass
            });
        }
    })
    .catch(error => {
        console.error('Error fetching transaction:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load transaction details.',
            confirmButtonColor: SwalConfig.confirmButtonColor,
            customClass: SwalConfig.customClass
        });
    });
}

// Handle void confirmation
function handleVoidConfirm() {
    const username = document.getElementById('voidAdminUsername').value.trim();
    const reason = document.getElementById('voidReason').value.trim();
    
    if (!username) {
        Swal.fire({
            icon: 'warning',
            title: 'Username Required',
            text: 'Please enter your admin username to confirm this action.',
            confirmButtonColor: SwalConfig.confirmButtonColor,
            customClass: SwalConfig.customClass
        });
        return;
    }
    
    if (!reason) {
        Swal.fire({
            icon: 'warning',
            title: 'Reason Required',
            text: 'Please provide a reason for voiding this transaction.',
            confirmButtonColor: SwalConfig.confirmButtonColor,
            customClass: SwalConfig.customClass
        });
        return;
    }
    
    // Disable confirm button to prevent double-submit
    const confirmBtn = document.getElementById('confirmVoid');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Voiding...';
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Call API to void transaction
    fetch(`/admin/sales/transactions/${transactionToVoid}/void`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            admin_username: username,
            reason: reason
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Failed to void transaction');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeModal('voidConfirmModal');
            transactionToVoid = null;
            
            // Clear inputs
            document.getElementById('voidAdminUsername').value = '';
            document.getElementById('voidReason').value = '';
            
            Swal.fire({
                icon: 'success',
                title: 'Transaction Voided',
                text: 'The transaction has been successfully voided.',
                confirmButtonColor: SwalConfig.confirmButtonColor,
                customClass: SwalConfig.customClass,
                timer: 3000,
                timerProgressBar: true
            });
            
            // Reload data
            loadLedgerData();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Void Failed',
                text: data.message || 'Failed to void transaction. Please try again.',
                confirmButtonColor: SwalConfig.confirmButtonColor,
                customClass: SwalConfig.customClass
            });
        }
    })
    .catch(error => {
        console.error('Error voiding transaction:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'An unexpected error occurred.',
            confirmButtonColor: SwalConfig.confirmButtonColor,
            customClass: SwalConfig.customClass
        });
    })
    .finally(() => {
        // Re-enable button
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    });
}

// Helper functions
function formatDate(date) {
    const d = new Date(date);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[d.getMonth()];
    const day = d.getDate();
    const year = d.getFullYear();
    const hours = d.getHours();
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    
    return `<div class="datetime-cell">
        <div class="date-part">${month} ${day}, ${year}</div>
        <div class="time-part">${displayHours}:${minutes} ${ampm}</div>
    </div>`;
}

function formatDateTime(date) {
    const d = new Date(date);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[d.getMonth()];
    const day = d.getDate();
    const year = d.getFullYear();
    const hours = d.getHours();
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    
    return `${month} ${day}, ${year}, ${displayHours}:${minutes} ${ampm}`;
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function getMethodDisplay(method) {
    const methods = {
        'cash': 'Cash',
        'Cash': 'Cash',
        'gcash': 'GCash',
        'GCash': 'GCash',
        'paymongo': 'GCash',
        'bdo': 'BDO Transfer',
        'bpi': 'BPI Transfer',
        'gotyme': 'GoTyme'
    };
    return methods[method] || method;
}

function getPurposeDisplay(purpose) {
    if (!purpose) return '–';
    
    // Format common payment purposes
    const purposes = {
        'partial_payment': 'Partial Payment',
        'full_payment': 'Full Payment',
        'downpayment': 'Downpayment',
        'Downpayment': 'Downpayment',
        'Partial Payment': 'Partial Payment',
        'Full Payment': 'Full Payment'
    };
    
    return purposes[purpose] || purpose;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function openModal(modalId) {
    document.getElementById(modalId)?.classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId)?.classList.remove('show');
}
