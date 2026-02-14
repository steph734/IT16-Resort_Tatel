/**
 * Sales Dashboard JavaScript
 * Handles KPIs, charts, and export functionality for sales overview
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

// Store current filter state
let currentDateFilter = {
    preset: 'month', // Default to "This Month"
    startDate: null,
    endDate: null
};

// Store chart instances for updates
let dailyRevenueChartInstance = null;
let revenueBySourceChartInstance = null;
let paymentMethodChartInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    initializeDailyRevenueChart();
    initializeRevenueBySourceChart();
    initializePaymentMethodChart();
    
    // Load initial data based on default filter (This Month)
    loadAllData();
    
    // Setup event listeners
    setupEventListeners();
});

// KPI Data Loading
function loadKPIData() {
    fetchDashboardData();
}

function fetchDashboardData() {
    // Build query parameters
    const params = new URLSearchParams();
    params.append('preset', currentDateFilter.preset);
    
    if (currentDateFilter.startDate) {
        params.append('start_date', currentDateFilter.startDate);
    }
    if (currentDateFilter.endDate) {
        params.append('end_date', currentDateFilter.endDate);
    }
    
    // Show loading state
    console.log('=== FETCHING DASHBOARD DATA ===');
    console.log('Preset:', currentDateFilter.preset);
    console.log('Start Date:', currentDateFilter.startDate);
    console.log('End Date:', currentDateFilter.endDate);
    console.log('Full URL:', `/admin/sales/api/dashboard-data?${params.toString()}`);
    
    // Call API endpoint
    fetch(`/admin/sales/api/dashboard-data?${params.toString()}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Dashboard data received:', data);
            updateKPIWithData(data.kpis);
            updateChartsWithData(data);
            updateTopPerformersWithData(data);
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
            Swal.fire({
                icon: 'error',
                title: 'Data Load Failed',
                text: 'Failed to load dashboard data. Please refresh the page or try again later.',
                confirmButtonColor: SwalConfig.confirmButtonColor,
                customClass: SwalConfig.customClass,
                footer: '<a href="javascript:location.reload()">Refresh Page</a>'
            });
        });
}

function updateKPIWithData(kpis) {
    const preset = currentDateFilter.preset;
    const today = new Date();
    let comparisonLabel;
    
    // Determine comparison labels based on filter
    switch(preset) {
        case 'week':
            const weekNumber = getWeekOfMonth(today);
            const monthName = today.toLocaleString('en-US', { month: 'long' });
            comparisonLabel = `vs Week ${weekNumber - 1 > 0 ? weekNumber - 1 : 4} of ${weekNumber - 1 > 0 ? monthName : getPreviousMonth(today)}`;
            break;
        case 'month':
            const previousMonth = getPreviousMonth(today);
            comparisonLabel = `vs ${previousMonth}`;
            break;
        case 'year':
            const previousYear = today.getFullYear() - 1;
            comparisonLabel = `vs ${previousYear}`;
            break;
        case 'custom':
            comparisonLabel = 'vs previous period';
            break;
        default:
            comparisonLabel = `vs ${getPreviousMonth(today)}`;
    }
    
    // Parse KPI values
    const bookingSales = parseFloat(kpis.booking_sales);
    const rentalSales = parseFloat(kpis.rental_sales);
    const salesDifference = parseFloat(kpis.sales_difference);
    const growthRate = parseFloat(kpis.growth_rate);
    
    const previousBookingSales = parseFloat(kpis.previous_booking_sales);
    const previousRentalSales = parseFloat(kpis.previous_rental_sales);
    
    // Calculate percentage changes
    const bookingChange = previousBookingSales > 0 ? ((bookingSales - previousBookingSales) / previousBookingSales) * 100 : 0;
    const rentalChange = previousRentalSales > 0 ? ((rentalSales - previousRentalSales) / previousRentalSales) * 100 : 0;
    
    // Update KPI 1: Booking Sales
    document.getElementById('kpi1Label').textContent = 'Booking Sales';
    document.getElementById('kpi1Value').textContent = bookingSales.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('kpi1Change').textContent = `${bookingChange >= 0 ? '+' : ''}${Math.abs(bookingChange).toFixed(1)}%`;
    document.getElementById('kpi1ChangeText').textContent = comparisonLabel;
    
    const kpi1Container = document.querySelector('#kpi1Value').closest('.kpi-card').querySelector('.kpi-change');
    if (bookingChange >= 0) {
        kpi1Container.classList.remove('negative');
        kpi1Container.classList.add('positive');
        kpi1Container.querySelector('i').className = 'fas fa-arrow-up';
    } else {
        kpi1Container.classList.remove('positive');
        kpi1Container.classList.add('negative');
        kpi1Container.querySelector('i').className = 'fas fa-arrow-down';
    }
    
    // Update KPI 2: Rental Sales
    document.getElementById('kpi2Label').textContent = 'Rental Sales';
    document.getElementById('kpi2Value').textContent = rentalSales.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('kpi2Change').textContent = `${rentalChange >= 0 ? '+' : ''}${Math.abs(rentalChange).toFixed(1)}%`;
    document.getElementById('kpi2ChangeText').textContent = comparisonLabel;
    
    const kpi2Container = document.querySelector('#kpi2Value').closest('.kpi-card').querySelector('.kpi-change');
    if (rentalChange >= 0) {
        kpi2Container.classList.remove('negative');
        kpi2Container.classList.add('positive');
        kpi2Container.querySelector('i').className = 'fas fa-arrow-up';
    } else {
        kpi2Container.classList.remove('positive');
        kpi2Container.classList.add('negative');
        kpi2Container.querySelector('i').className = 'fas fa-arrow-down';
    }
    
    // Update KPI 3: Sales Difference
    document.getElementById('kpi3Label').textContent = 'Sales Difference';
    document.getElementById('kpi3Value').textContent = Math.abs(salesDifference).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('kpi3ChangeText').textContent = comparisonLabel;
    
    const kpi3Container = document.getElementById('kpi3ChangeContainer');
    const kpi3Icon = document.getElementById('kpi3Icon');
    if (salesDifference >= 0) {
        kpi3Container.classList.remove('negative');
        kpi3Container.classList.add('positive');
        kpi3Icon.className = 'fas fa-arrow-up';
    } else {
        kpi3Container.classList.remove('positive');
        kpi3Container.classList.add('negative');
        kpi3Icon.className = 'fas fa-arrow-down';
    }
    
    // Update KPI 4: Growth Rate
    document.getElementById('kpi4Label').textContent = 'Growth Rate';
    document.getElementById('kpi4Value').textContent = `${growthRate >= 0 ? '+' : ''}${Math.abs(growthRate).toFixed(1)}`;
    document.getElementById('kpi4ChangeText').textContent = comparisonLabel;
    
    const kpi4Container = document.getElementById('kpi4ChangeContainer');
    const kpi4Icon = document.getElementById('kpi4Icon');
    if (growthRate >= 0) {
        kpi4Container.classList.remove('negative');
        kpi4Container.classList.add('positive');
        kpi4Icon.className = 'fas fa-arrow-up';
    } else {
        kpi4Container.classList.remove('positive');
        kpi4Container.classList.add('negative');
        kpi4Icon.className = 'fas fa-arrow-down';
    }
}

// Helper function to get week number of the month
function getWeekOfMonth(date) {
    const firstDayOfMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    const dayOfMonth = date.getDate();
    const firstDayWeekday = firstDayOfMonth.getDay();
    
    // Calculate which week of the month we're in
    const weekNumber = Math.ceil((dayOfMonth + firstDayWeekday) / 7);
    return weekNumber;
}

// Helper function to get previous month name
function getPreviousMonth(date) {
    const prevMonthDate = new Date(date.getFullYear(), date.getMonth() - 1, 1);
    return prevMonthDate.toLocaleString('en-US', { month: 'long' });
}

// Load all data based on current filter
function loadAllData() {
    loadKPIData();
}

function updateChartsWithData(data) {
    // Update Daily Revenue Chart
    if (dailyRevenueChartInstance && data.revenue_trend) {
        const labels = data.revenue_trend.map(item => item.period);
        const revenueData = data.revenue_trend.map(item => item.total);
        
        dailyRevenueChartInstance.data.labels = labels;
        dailyRevenueChartInstance.data.datasets[0].data = revenueData;
        
        // Make y-axis dynamic based on data
        if (revenueData.length > 0) {
            const maxValue = Math.max(...revenueData);
            const minValue = Math.min(...revenueData);
            dailyRevenueChartInstance.options.scales.y.suggestedMax = maxValue * 1.1;
            dailyRevenueChartInstance.options.scales.y.suggestedMin = Math.max(0, minValue * 0.9);
        }
        
        dailyRevenueChartInstance.update();
    }
    
    // Update Revenue by Source Chart
    if (revenueBySourceChartInstance && data.revenue_by_source) {
        const bookingsRevenue = parseFloat(data.revenue_by_source.booking) || 0;
        const rentalsRevenue = parseFloat(data.revenue_by_source.rental) || 0;
        
        revenueBySourceChartInstance.data.datasets[0].data = [bookingsRevenue, rentalsRevenue];
        
        // Make y-axis dynamic
        const maxRevenue = Math.max(bookingsRevenue, rentalsRevenue);
        revenueBySourceChartInstance.options.scales.y.suggestedMax = maxRevenue * 1.1;
        
        revenueBySourceChartInstance.update();
    }
    
    // Update Payment Methods Chart
    if (paymentMethodChartInstance && data.payment_methods) {
        // Get actual payment methods from the data
        const paymentData = data.payment_methods;
        const labels = [];
        const amounts = [];
        const percentages = [];
        
        // Calculate total
        let totalAmount = 0;
        Object.values(paymentData).forEach(amount => {
            totalAmount += parseFloat(amount) || 0;
        });
        
        // Build arrays from actual data
        Object.entries(paymentData).forEach(([method, amount]) => {
            const numAmount = parseFloat(amount) || 0;
            if (numAmount > 0) { // Only include methods with actual transactions
                labels.push(method);
                amounts.push(numAmount);
                percentages.push(totalAmount > 0 ? Math.round((numAmount / totalAmount) * 100) : 0);
            }
        });
        
        // If no data, show message
        if (labels.length === 0) {
            labels.push('No Data');
            percentages.push(100);
            amounts.push(0);
        }
        
        // Update chart data
        paymentMethodChartInstance.data.labels = labels;
        paymentMethodChartInstance.data.datasets[0].data = percentages;
        
        // Update tooltip to use real amounts
        paymentMethodChartInstance.options.plugins.tooltip.callbacks.label = function(context) {
            const percentage = context.parsed;
            const amount = amounts[context.dataIndex];
            return [
                context.label + ': ' + percentage + '%',
                '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })
            ];
        };
        
        paymentMethodChartInstance.update();
    }
}

function updateTopPerformersWithData(data) {
    // Update Top Packages
    if (data.top_packages && data.top_packages.length > 0) {
        const topPackages = data.top_packages.map((pkg, index) => ({
            rank: index + 1,
            name: pkg.PackageName,
            stats: `${pkg.bookings} bookings`,
            value: '₱' + parseFloat(pkg.revenue).toLocaleString('en-PH', { minimumFractionDigits: 2 })
        }));
        
        renderTopItems('topPackagesList', topPackages);
    } else {
        // Show empty state
        document.getElementById('topPackagesList').innerHTML = `
            <div class="top-item-placeholder">
                <i class="fas fa-chart-bar"></i>
                <span>No package data for this period</span>
            </div>
        `;
    }
    
    // Update Top Rentals
    if (data.top_rentals && data.top_rentals.length > 0) {
        const topRentals = data.top_rentals.map((rental, index) => ({
            rank: index + 1,
            name: rental.ItemName,
            stats: `${rental.quantity} rentals`,
            value: '₱' + parseFloat(rental.revenue).toLocaleString('en-PH', { minimumFractionDigits: 2 })
        }));
        
        renderTopItems('topRentalsList', topRentals);
    } else {
        // Show empty state
        document.getElementById('topRentalsList').innerHTML = `
            <div class="top-item-placeholder">
                <i class="fas fa-box"></i>
                <span>No rental data for this period</span>
            </div>
        `;
    }
}

// Chart: Daily Revenue Trend
function initializeDailyRevenueChart() {
    const ctx = document.getElementById('dailyRevenueChart');
    if (!ctx) return;
    
    // Default is "This Month" - show weeks
    dailyRevenueChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
            datasets: [{
                label: 'Daily Revenue',
                data: [0, 0, 0, 0, 0],
                borderColor: '#53A9B5',
                backgroundColor: 'rgba(83, 169, 181, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#53A9B5',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#284B53',
                    titleFont: {
                        family: 'Poppins',
                        size: 14,
                        weight: '600'
                    },
                    bodyFont: {
                        family: 'Poppins',
                        size: 13
                    },
                    padding: 12,
                    borderRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            family: 'Poppins',
                            size: 12
                        },
                        callback: function(value) {
                            return '₱' + (value / 1000) + 'k';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Poppins',
                            size: 11
                        },
                        maxTicksLimit: 10,
                        autoSkip: true,
                        maxRotation: 0,
                        minRotation: 0
                    }
                }
            }
        }
    });
}

// Chart: Revenue by Source
function initializeRevenueBySourceChart() {
    const ctx = document.getElementById('revenueBySourceChart');
    if (!ctx) return;
    
    revenueBySourceChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Bookings', 'Rentals'],
            datasets: [{
                label: 'Revenue',
                data: [285000, 85000],
                backgroundColor: [
                    '#53A9B5',
                    '#22c55e'
                ],
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#284B53',
                    titleFont: {
                        family: 'Poppins',
                        size: 14,
                        weight: '600'
                    },
                    bodyFont: {
                        family: 'Poppins',
                        size: 13
                    },
                    padding: 12,
                    borderRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            family: 'Poppins',
                            size: 12
                        },
                        callback: function(value) {
                            return '₱' + (value / 1000) + 'k';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Poppins',
                            size: 12,
                            weight: '500'
                        }
                    }
                }
            }
        }
    });
}

// Chart: Payment Methods
function initializePaymentMethodChart() {
    const ctx = document.getElementById('paymentMethodChart');
    if (!ctx) return;
    
    const data = {
        labels: ['Cash', 'GCash', 'BDO', 'BPI', 'GoTyme'],
        amounts: [173250, 96250, 57750, 38500, 19250],
        percentages: [45, 25, 15, 10, 5]
    };
    
    paymentMethodChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.percentages,
                backgroundColor: [
                    '#53A9B5',
                    '#22c55e',
                    '#3b82f6',
                    '#f59e0b',
                    '#a855f7'
                ],
                borderWidth: 3,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 13,
                            family: 'Poppins',
                            weight: '500'
                        },
                        color: '#284B53',
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#284B53',
                    titleFont: {
                        family: 'Poppins',
                        size: 14,
                        weight: '600'
                    },
                    bodyFont: {
                        family: 'Poppins',
                        size: 13
                    },
                    padding: 12,
                    borderRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const percentage = context.parsed;
                            const amount = data.amounts[context.dataIndex];
                            return [
                                context.label + ': ' + percentage + '%',
                                '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })
                            ];
                        }
                    }
                }
            }
        }
    });
}



function renderTopItems(containerId, items) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const rankClasses = ['gold', 'silver', 'bronze'];
    
    container.innerHTML = items.map((item, index) => `
        <div class="top-item">
            <div class="top-item-rank ${rankClasses[index] || ''}">${item.rank}</div>
            <div class="top-item-info">
                <div class="top-item-name">${item.name}</div>
                <div class="top-item-stats">${item.stats}</div>
            </div>
            <div class="top-item-value">${item.value}</div>
        </div>
    `).join('');
}

// Setup KPI Card Click Listeners
function setupKPICardListeners() {
    // Get all KPI cards
    const kpiCards = document.querySelectorAll('.kpi-card');
    
    kpiCards.forEach((card, index) => {
        card.style.cursor = 'pointer';
        card.style.transition = 'transform 0.2s, box-shadow 0.2s';
        
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '';
        });
        
        card.addEventListener('click', function() {
            handleKPICardClick(index);
        });
    });
}

function handleKPICardClick(cardIndex) {
    // Build URL with filters based on current date range and KPI card
    const baseUrl = '/admin/sales/ledger';
    const params = new URLSearchParams();
    
    // Add date range parameters
    if (currentDateFilter.startDate) {
        params.append('start_date', currentDateFilter.startDate);
    }
    if (currentDateFilter.endDate) {
        params.append('end_date', currentDateFilter.endDate);
    }
    if (currentDateFilter.preset && currentDateFilter.preset !== 'custom') {
        params.append('preset', currentDateFilter.preset);
    }
    
    // Add source filter based on KPI card clicked
    switch(cardIndex) {
        case 0: // Booking Sales
            params.append('source', 'booking');
            break;
        case 1: // Rental Sales
            params.append('source', 'rental');
            break;
        case 2: // Sales Difference - show all transactions
            // No source filter, show all
            break;
        case 3: // Growth Rate - show all transactions
            // No source filter, show all
            break;
    }
    
    // Redirect to Transaction Ledger with filters
    const url = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
    window.location.href = url;
}

// Event Listeners
function setupEventListeners() {
    // Date range button
    const dateRangeBtn = document.getElementById('dateRangeBtn');
    if (dateRangeBtn) {
        dateRangeBtn.addEventListener('click', () => openModal('dateRangeModal'));
    }
    
    // Apply date range
    const applyDateRange = document.getElementById('applyDateRange');
    if (applyDateRange) {
        applyDateRange.addEventListener('click', handleDateRangeApply);
    }
    
    // Modal close buttons
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            closeModal(this.closest('.modal').id);
        });
    });
    
    // Preset buttons
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Clear custom dates when preset is selected
            document.getElementById('customStartDate').value = '';
            document.getElementById('customEndDate').value = '';
        });
    });
    
    // Custom date inputs - clear preset when dates are manually selected
    document.getElementById('customStartDate')?.addEventListener('change', () => {
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
    });
    
    document.getElementById('customEndDate')?.addEventListener('change', () => {
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
    });
    
    // Make KPI cards clickable
    setupKPICardListeners();
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Handle Date Range Apply
function handleDateRangeApply() {
    const activePreset = document.querySelector('.preset-btn.active');
    const startDate = document.getElementById('customStartDate').value;
    const endDate = document.getElementById('customEndDate').value;
    
    // Check if custom dates are provided
    if (startDate && endDate) {
        // Validate date range
        if (startDate > endDate) {
            alert('Start date cannot be after end date');
            return;
        }
        
        // Custom date range takes priority
        currentDateFilter.preset = 'custom';
        currentDateFilter.startDate = startDate;
        currentDateFilter.endDate = endDate;
        
        // Format date for display
        const startFormatted = new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const endFormatted = new Date(endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        document.getElementById('dateRangeText').textContent = `${startFormatted} - ${endFormatted}`;
    } else if (activePreset) {
        // Use preset
        const preset = activePreset.dataset.preset;
        const presetText = activePreset.textContent;
        
        // Update current filter
        currentDateFilter.preset = preset;
        currentDateFilter.startDate = null;
        currentDateFilter.endDate = null;
        
        document.getElementById('dateRangeText').textContent = presetText;
    } else {
        alert('Please select a preset or enter custom dates');
        return;
    }
    
    console.log('Date filter updated:', currentDateFilter);
    
    // Reload all data with new date range
    loadAllData();
    
    closeModal('dateRangeModal');
}

// Handle Export
function handleExport() {
    const format = document.getElementById('exportFormat').value;
    console.log('Exporting as:', format);
    
    closeModal('exportModal');
    
    // Placeholder - implement actual export functionality
    Swal.fire({
        icon: 'info',
        title: 'Exporting Dashboard',
        text: `Preparing sales snapshot as ${format.toUpperCase()}...`,
        confirmButtonColor: SwalConfig.confirmButtonColor,
        customClass: SwalConfig.customClass,
        timer: 2500,
        timerProgressBar: true,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    }).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Export Complete!',
            text: `Dashboard snapshot has been exported as ${format.toUpperCase()}.`,
            confirmButtonColor: SwalConfig.confirmButtonColor,
            customClass: SwalConfig.customClass,
            timer: 3000,
            timerProgressBar: true
        });
    });
}

// Helper Functions
function getWeekOfMonth(date) {
    const firstDayOfMonth = new Date(date.getFullYear(), date.getMonth(), 1);
    const dayOfMonth = date.getDate();
    const firstDayWeekday = firstDayOfMonth.getDay();
    
    // Calculate which week of the month we're in
    const weekNumber = Math.ceil((dayOfMonth + firstDayWeekday) / 7);
    return weekNumber;
}
