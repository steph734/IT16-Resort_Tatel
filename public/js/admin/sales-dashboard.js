/**
 * Sales Dashboard - Protected KPI Cards + Data Loading
 * Only U001 can view detailed ledger reports
 */

const SwalConfig = {
    confirmButtonColor: '#284B53',
    cancelButtonColor: '#6b7280',
    customClass: {
        popup: 'swal-custom-popup',
        confirmButton: 'swal-custom-confirm',
        cancelButton: 'swal-custom-cancel'
    }
};

const AUTHORIZED_USER_ID = 'U001';

let currentDateFilter = {
    preset: 'month',
    startDate: null,
    endDate: null
};

let dailyRevenueChartInstance   = null;
let revenueBySourceChartInstance = null;
let paymentMethodChartInstance   = null;

document.addEventListener('DOMContentLoaded', () => {
    initCharts();
    loadAllData();
    setupEventListeners();
});

// ────────────────────────────────────────────────
// Core Data Fetch & Load
// ────────────────────────────────────────────────

function loadAllData() {
    fetchDashboardData();
}

function fetchDashboardData() {
    const params = new URLSearchParams({
        preset: currentDateFilter.preset,
        ...(currentDateFilter.startDate && { start_date: currentDateFilter.startDate }),
        ...(currentDateFilter.endDate   && { end_date:   currentDateFilter.endDate })
    });

    fetch(`/admin/sales/api/dashboard-data?${params}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            console.log('Dashboard data:', data); // ← debug tip
            updateKPIs(data.kpis || {});
            updateCharts(data);
            updateTopLists(data);
        })
        .catch(err => {
            console.error('Dashboard fetch failed:', err);
            Swal.fire({
                icon: 'error',
                title: 'Failed to load data',
                text: 'Please check your connection or try refreshing.',
                ...SwalConfig
            });
        });
}

// ────────────────────────────────────────────────
// KPI Display Logic
// ────────────────────────────────────────────────

function updateKPIs(kpis) {
    const comparison = getComparisonLabel();

    const bs = Number(kpis.booking_sales)     || 0;
    const rs = Number(kpis.rental_sales)      || 0;
    const sd = Number(kpis.sales_difference)  || 0;
    const gr = Number(kpis.growth_rate)       || 0;

    const pbs = Number(kpis.previous_booking_sales) || 0;
    const prs = Number(kpis.previous_rental_sales)  || 0;

    const bc = pbs ? ((bs - pbs) / pbs * 100) : 0;
    const rc = prs ? ((rs - prs) / prs * 100) : 0;

    // Booking Sales
    document.getElementById('kpi1Value').textContent = bs.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('kpi1Change').textContent = `${bc >= 0 ? '+' : ''}${Math.abs(bc).toFixed(1)}%`;
    document.getElementById('kpi1ChangeText').textContent = comparison;
    setChangeStyle('#kpi1Value', bc);

    // Rental Sales
    document.getElementById('kpi2Value').textContent = rs.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('kpi2Change').textContent = `${rc >= 0 ? '+' : ''}${Math.abs(rc).toFixed(1)}%`;
    document.getElementById('kpi2ChangeText').textContent = comparison;
    setChangeStyle('#kpi2Value', rc);

    // Sales Difference
    document.getElementById('kpi3Value').textContent = Math.abs(sd).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('kpi3ChangeText').textContent = comparison;
    setChangeStyle('#kpi3Value', sd, true);

    // Growth Rate
    document.getElementById('kpi4Value').textContent = `${gr >= 0 ? '+' : ''}${Math.abs(gr).toFixed(1)}`;
    document.getElementById('kpi4ChangeText').textContent = comparison;
    setChangeStyle('#kpi4Value', gr);
}

function setChangeStyle(selector, value, absolute = false) {
    const el = document.querySelector(selector)?.closest('.kpi-card')?.querySelector('.kpi-change');
    if (!el) return;
    const icon = el.querySelector('i');
    const v = absolute ? Math.abs(value) : value;

    el.classList.toggle('positive', v >= 0);
    el.classList.toggle('negative', v < 0);
    icon.className = v >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
}

function getComparisonLabel() {
    const { preset } = currentDateFilter;
    const now = new Date();

    if (preset === 'week') {
        const week = Math.ceil((now.getDate() + new Date(now.getFullYear(), now.getMonth(), 1).getDay()) / 7);
        return `vs Week ${week-1 || 4} of ${week > 1 ? now.toLocaleString('en-US',{month:'long'}) : getPrevMonth(now)}`;
    }
    if (preset === 'month') return `vs ${getPrevMonth(now)}`;
    if (preset === 'year')  return `vs ${now.getFullYear()-1}`;
    return 'vs previous period';
}

function getPrevMonth(d) {
    return new Date(d.getFullYear(), d.getMonth()-1, 1).toLocaleString('en-US', { month: 'long' });
}

// ────────────────────────────────────────────────
// Chart Initialization (placeholders — fill with your real config)
// ────────────────────────────────────────────────

function initCharts() {
    const dailyCtx   = document.getElementById('dailyRevenueChart')?.getContext('2d');
    const sourceCtx  = document.getElementById('revenueBySourceChart')?.getContext('2d');
    const paymentCtx = document.getElementById('paymentMethodChart')?.getContext('2d');

    if (dailyCtx) {
        dailyRevenueChartInstance = new Chart(dailyCtx, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Revenue', data: [], borderColor: '#53A9B5', fill: true }] },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    if (sourceCtx) {
        revenueBySourceChartInstance = new Chart(sourceCtx, {
            type: 'bar',
            data: { labels: ['Bookings', 'Rentals'], datasets: [{ data: [0,0], backgroundColor: ['#53A9B5','#22c55e'] }] },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    if (paymentCtx) {
        paymentMethodChartInstance = new Chart(paymentCtx, {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#53A9B5','#22c55e','#3b82f6','#f59e0b','#a855f7'] }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }
}

function updateCharts(data) {
    // Daily Revenue
    if (dailyRevenueChartInstance && data.revenue_trend) {
        dailyRevenueChartInstance.data.labels = data.revenue_trend.map(i => i.period);
        dailyRevenueChartInstance.data.datasets[0].data = data.revenue_trend.map(i => i.total);
        dailyRevenueChartInstance.update();
    }

    // Revenue by Source
    if (revenueBySourceChartInstance && data.revenue_by_source) {
        revenueBySourceChartInstance.data.datasets[0].data = [
            Number(data.revenue_by_source.booking) || 0,
            Number(data.revenue_by_source.rental)  || 0
        ];
        revenueBySourceChartInstance.update();
    }

    // Payment Methods
    if (paymentMethodChartInstance && data.payment_methods) {
        const methods = data.payment_methods;
        const labels = Object.keys(methods);
        const values = Object.values(methods).map(Number);
        paymentMethodChartInstance.data.labels = labels;
        paymentMethodChartInstance.data.datasets[0].data = values;
        paymentMethodChartInstance.update();
    }
}

// ────────────────────────────────────────────────
// Top Lists
// ────────────────────────────────────────────────

function updateTopLists(data) {
    renderTopList('topPackagesList', data.top_packages || [], {
        nameKey: 'PackageName', countKey: 'bookings', valueKey: 'revenue', label: 'bookings'
    });
    renderTopList('topRentalsList', data.top_rentals || [], {
        nameKey: 'ItemName', countKey: 'quantity', valueKey: 'revenue', label: 'rentals'
    });
}

function renderTopList(id, items, { nameKey, countKey, valueKey, label }) {
    const container = document.getElementById(id);
    if (!container) return;

    if (!items.length) {
        container.innerHTML = `<div class="top-item-placeholder"><i class="fas fa-chart-bar"></i><span>No data available</span></div>`;
        return;
    }

    container.innerHTML = items.map((item, i) => `
        <div class="top-item">
            <div class="top-item-rank ${i<3 ? ['gold','silver','bronze'][i] : ''}">${i+1}</div>
            <div class="top-item-info">
                <div class="top-item-name">${item[nameKey] || 'Unknown'}</div>
                <div class="top-item-stats">${item[countKey] || 0} ${label}</div>
            </div>
            <div class="top-item-value">₱${Number(item[valueKey]||0).toLocaleString('en-PH',{minimumFractionDigits:2})}</div>
        </div>
    `).join('');
}

// ────────────────────────────────────────────────
// KPI Card Click → User ID Protection
// ────────────────────────────────────────────────

function setupEventListeners() {
    // Date range modal
    document.getElementById('dateRangeBtn')?.addEventListener('click', () => {
        document.getElementById('dateRangeModal')?.classList.add('show');
    });

    document.getElementById('applyDateRange')?.addEventListener('click', applyDateRange);

    document.querySelectorAll('[data-dismiss="modal"]').forEach(el => {
        el.addEventListener('click', () => el.closest('.modal')?.classList.remove('show'));
    });

    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('customStartDate').value = '';
            document.getElementById('customEndDate').value = '';
        });
    });

    // KPI protection
    document.querySelectorAll('.kpi-card-clickable').forEach((card, idx) => {
        card.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            Swal.fire({
                title: 'Enter User ID',
                input: 'text',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                allowOutsideClick: false,
                inputValidator: v => !v?.trim() && 'User ID is required',
                ...SwalConfig
            }).then(result => {
                if (!result.isConfirmed) return;

                const id = (result.value || '').trim().toUpperCase();

                if (id === AUTHORIZED_USER_ID) {
                    redirectToLedger(idx);
                } else if (['A001','S001'].includes(id)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Access Denied',
                        text: 'Staff and admin cannot view detailed reports',
                        timer: 3800,
                        showConfirmButton: false,
                        ...SwalConfig
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid',
                        text: 'Only owner is allowed to view detailed reports',
                        timer: 3200,
                        showConfirmButton: false,
                        ...SwalConfig
                    });
                }
            });
        });
    });
}

function redirectToLedger(index) {
    const params = new URLSearchParams();

    if (currentDateFilter.startDate) params.set('start_date', currentDateFilter.startDate);
    if (currentDateFilter.endDate)   params.set('end_date',   currentDateFilter.endDate);
    if (currentDateFilter.preset && currentDateFilter.preset !== 'custom') {
        params.set('preset', currentDateFilter.preset);
    }

    if (index === 0) params.set('source', 'booking');
    if (index === 1) params.set('source', 'rental');
    // index 2 & 3 → no source filter (all)

    const query = params.toString();
    window.location.href = '/admin/sales/ledger' + (query ? `?${query}` : '');
}

function applyDateRange() {
    const presetBtn = document.querySelector('.preset-btn.active');
    const start = document.getElementById('customStartDate')?.value;
    const end   = document.getElementById('customEndDate')?.value;

    if (start && end) {
        if (start > end) return alert('Start date must be before end date');
        currentDateFilter = { preset: 'custom', startDate: start, endDate: end };
        document.getElementById('dateRangeText').textContent =
            `${new Date(start).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})} – ` +
            `${new Date(end).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})}`;
    } else if (presetBtn) {
        currentDateFilter = {
            preset: presetBtn.dataset.preset,
            startDate: null,
            endDate: null
        };
        document.getElementById('dateRangeText').textContent = presetBtn.textContent;
    }

    loadAllData();
    document.getElementById('dateRangeModal')?.classList.remove('show');
}