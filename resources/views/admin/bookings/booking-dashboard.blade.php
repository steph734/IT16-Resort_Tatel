@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
    <!-- Custom CSS -->
    <link href="{{ asset('css/admin/bookings/dashboard.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ asset('css/admin/bookings/calendar.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ asset('css/admin/bookings/calendar-indicators.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ asset('css/admin/rentals/rentals-dashboard.css') }}?v={{ time() }}" rel="stylesheet">
@endpush

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
    <div class="dashboard-wrapper">
        <!-- Welcome Header with Date Range Filter -->
        <div class="welcome-header-row">
            <h1 class="welcome-header">Welcome, {{ Auth::user()->name }}!</h1>
            <button type="button" class="btn btn-outline" id="dateRangeBtn">
                <i class="fas fa-calendar-alt"></i>
                <span id="dateRangeText">This Month</span>
            </button>
        </div>

        <!-- Booking Stats -->
        @php
            // Use the date range from the controller (date filter applied)
            $filterStart = $startDate ?? \Carbon\Carbon::now()->startOfMonth();
            $filterEnd = $endDate ?? \Carbon\Carbon::now()->endOfDay();
            
            // Calculate previous period for comparison (same length as current filter)
            $daysDiff = $filterStart->diffInDays($filterEnd);
            $prevPeriodEnd = $filterStart->copy()->subDay();
            $prevPeriodStart = $prevPeriodEnd->copy()->subDays($daysDiff);

            // Bookings in current period vs previous period
            $bookingsNow = App\Models\Booking::whereBetween('CheckInDate', [$filterStart, $filterEnd])
                ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                ->count();
            $bookingsTotal = App\Models\Booking::whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                ->count();
            $bookingsPrev = App\Models\Booking::whereBetween('CheckInDate', [$prevPeriodStart, $prevPeriodEnd])
                ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                ->count();
            $bookingsChange = $bookingsPrev ? round((($bookingsNow - $bookingsPrev) / $bookingsPrev) * 100, 1) : ($bookingsNow ? 100 : 0);

            // Revenue in current period vs previous period
            $revenueNow = App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')
                ->whereBetween('bookings.CheckInDate', [$filterStart, $filterEnd])
                ->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                ->sum('payments.Amount');
            $revenueTotal = App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')
                ->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                ->sum('payments.Amount');
            $revenuePrev = App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')
                ->whereBetween('bookings.CheckInDate', [$prevPeriodStart, $prevPeriodEnd])
                ->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                ->sum('payments.Amount');
            $revenueChange = $revenuePrev ? round((($revenueNow - $revenuePrev) / $revenuePrev) * 100, 1) : ($revenueNow ? 100 : 0);

            // Average Stay Duration (current period vs previous period)
            $today = \Carbon\Carbon::today();
            $bookingsThisMonth = App\Models\Booking::whereBetween('CheckInDate', [$filterStart, $filterEnd])
                ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                ->get();
            $avgStayNow = 0;
            if ($bookingsThisMonth->count() > 0) {
                $totalNights = $bookingsThisMonth->sum(function ($booking) {
                    return $booking->CheckInDate->diffInDays($booking->CheckOutDate);
                });
                $avgStayNow = round($totalNights / $bookingsThisMonth->count(), 1);
            }

            $bookingsAllTime = App\Models\Booking::whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                ->get();
            $avgStayTotal = 0;
            if ($bookingsAllTime->count() > 0) {
                $totalNightsAll = $bookingsAllTime->sum(function ($booking) {
                    return $booking->CheckInDate->diffInDays($booking->CheckOutDate);
                });
                $avgStayTotal = round($totalNightsAll / $bookingsAllTime->count(), 1);
            }

            $bookingsPrevMonth = App\Models\Booking::whereBetween('CheckInDate', [$prevPeriodStart, $prevPeriodEnd])
                ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                ->get();
            $avgStayPrev = 0;
            if ($bookingsPrevMonth->count() > 0) {
                $totalNightsPrev = $bookingsPrevMonth->sum(function ($booking) {
                    return $booking->CheckInDate->diffInDays($booking->CheckOutDate);
                });
                $avgStayPrev = round($totalNightsPrev / $bookingsPrevMonth->count(), 1);
            }
            $avgStayChange = $avgStayPrev ? round((($avgStayNow - $avgStayPrev) / $avgStayPrev) * 100, 1) : ($avgStayNow ? 100 : 0);

            // Cancellations current period vs previous period
            $cancellationsNow = App\Models\Booking::whereBetween('CheckInDate', [$filterStart, $filterEnd])
                ->where('BookingStatus', 'Cancelled')
                ->count();
            $cancellationsTotal = App\Models\Booking::where('BookingStatus', 'Cancelled')
                ->count();
            $cancellationsPrev = App\Models\Booking::whereBetween('CheckInDate', [$prevPeriodStart, $prevPeriodEnd])
                ->where('BookingStatus', 'Cancelled')
                ->count();
            $cancellationsChange = $cancellationsPrev ? round((($cancellationsNow - $cancellationsPrev) / $cancellationsPrev) * 100, 1) : ($cancellationsNow ? 100 : 0);

            // Sparkline data (last 7 days of bookings)
            $bookingsSeries = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = \Carbon\Carbon::today()->subDays($i);
                $count = App\Models\Booking::whereDate('CheckInDate', $date)
                    ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                    ->count();
                $bookingsSeries[] = $count;
            }

            // Revenue sparkline (last 7 days)
            $revenueSeries = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = \Carbon\Carbon::today()->subDays($i);
                $amount = App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')
                    ->whereDate('bookings.CheckInDate', $date)
                    ->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                    ->sum('payments.Amount');
                $revenueSeries[] = $amount;
            }

            // Average Stay Duration sparkline (last 7 days)
            $avgStaySeries = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = \Carbon\Carbon::today()->subDays($i);
                $dayBookings = App\Models\Booking::whereDate('CheckInDate', $date)
                    ->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])
                    ->get();
                if ($dayBookings->count() > 0) {
                    $totalNightsDay = $dayBookings->sum(function ($booking) {
                        return $booking->CheckInDate->diffInDays($booking->CheckOutDate);
                    });
                    $avgStaySeries[] = round($totalNightsDay / $dayBookings->count(), 1);
                } else {
                    $avgStaySeries[] = 0;
                }
            }

            // Cancellations sparkline (last 7 days)
            $cancelSeries = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = \Carbon\Carbon::today()->subDays($i);
                $count = App\Models\Booking::whereDate('CheckInDate', $date)
                    ->where('BookingStatus', 'Cancelled')
                    ->count();
                $cancelSeries[] = $count;
            }
        @endphp

        <div class="stats-container stats-grid">
            <!-- Bookings KPI Card -->
            <a href="{{ route('admin.bookings.index') }}" class="stat-card kpi-card kpi-card-link">
                <div class="kpi-icon icon-green">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Bookings</div>
                    <div class="kpi-change {{ $bookingsChange >= 0 ? 'positive' : 'negative' }}">
                        <i class="fas fa-arrow-{{ $bookingsChange >= 0 ? 'up' : 'down' }}"></i>
                        {{ abs($bookingsChange) }}% vs previous period
                    </div>
                    <div class="kpi-value">{{ $bookingsNow }}</div>
                </div>
            </a>

            <!-- Revenue KPI Card -->
            <a href="{{ route('admin.sales.ledger', ['source' => 'bookings']) }}" class="stat-card kpi-card kpi-card-link">
                <div class="kpi-icon icon-blue">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Booking Sales</div>
                    <div class="kpi-change {{ $revenueChange >= 0 ? 'positive' : 'negative' }}">
                        <i class="fas fa-arrow-{{ $revenueChange >= 0 ? 'up' : 'down' }}"></i>
                        {{ abs($revenueChange) }}% vs previous period
                    </div>
                    <div class="kpi-value">₱{{ number_format($revenueNow, 0) }}</div>
                </div>
            </a>

            <!-- Average Stay Duration KPI Card -->
            <a href="{{ route('admin.bookings.index', ['status' => 'Completed']) }}" class="stat-card kpi-card kpi-card-link">
                <div class="kpi-icon icon-purple">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Avg Stay</div>
                    <div class="kpi-change {{ $avgStayChange >= 0 ? 'positive' : 'negative' }}">
                        <i class="fas fa-arrow-{{ $avgStayChange >= 0 ? 'up' : 'down' }}"></i>
                        {{ abs($avgStayChange) }}% vs previous period
                    </div>
                    <div class="kpi-value">{{ $avgStayNow > 0 ? $avgStayNow : '0' }} <span style="font-size: 16px; font-weight: 500; color: #6b7280;">nights</span></div>
                </div>
            </a>

            <!-- Cancellations KPI Card -->
            <a href="{{ route('admin.bookings.index', ['status' => 'Cancelled']) }}" class="stat-card kpi-card kpi-card-link">
                <div class="kpi-icon icon-orange">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="kpi-content">
                    <div class="kpi-label">Cancellations</div>
                    <div class="kpi-change {{ $cancellationsChange <= 0 ? 'positive' : 'negative' }}">
                        <i class="fas fa-arrow-{{ $cancellationsChange >= 0 ? 'up' : 'down' }}"></i>
                        {{ abs($cancellationsChange) }}% vs previous period
                    </div>
                    <div class="kpi-value">{{ $cancellationsNow }}</div>
                </div>
            </a>
        </div>

        <!-- Currently Staying Section -->
        <section class="currently-staying">
            <div class="section-header">
                <h2 class="section-title">Currently Staying</h2>
                <a href="{{ route('admin.currently-staying') }}" class="view-btn">View All Guests</a>
            </div>
            <table class="guest-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Guest Name</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $currentGuests = App\Models\Booking::with('guest')
                            ->whereDate('CheckInDate', '<=', today())
                            ->whereDate('CheckOutDate', '>', today())
                            ->whereIn('BookingStatus', ['Confirmed', 'Staying'])
                            ->orderBy('CheckOutDate', 'asc')
                            ->take(5)
                            ->get();
                    @endphp
                    @forelse($currentGuests as $booking)
                        <tr>
                            <td>{{ $booking->BookingID }}</td>
                            <td>{{ $booking->guest->FName ?? 'Guest' }}@if($booking->guest->MName) {{ $booking->guest->MName }}@endif {{ $booking->guest->LName ?? '' }}</td>
                            <td>
                                <div>{{ $booking->CheckInDate ? $booking->CheckInDate->format('m/d/Y') : 'N/A' }}</div>
                                <div style="font-size: 0.75rem; margin-top: 2px;">
                                    @if($booking->ActualCheckInTime)
                                        {{ $booking->ActualCheckInTime->format('g:i A') }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>{{ $booking->CheckOutDate ? $booking->CheckOutDate->format('m/d/Y') : 'N/A' }}</div>
                                <div style="font-size: 0.75rem; margin-top: 2px;">
                                    @if($booking->ActualCheckOutTime)
                                        {{ $booking->ActualCheckOutTime->format('g:i A') }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 3rem 1rem; color: #9ca3af; font-style: italic;">
                                No guests currently staying
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <!-- Calendar Section -->
        <section class="calendar-section">
            <div class="calendar-header">
                <h2 class="month-year" style="font-family: Poppins;">Loading...</h2>
                <div class="calendar-nav">
                    <button class="calendar-nav-btn" id="prevMonth">&lt;</button>
                    <button class="calendar-nav-btn" id="nextMonth">&gt;</button>
                </div>
            </div>
            <div class="calendar-legend">
                <span class="legend-item"><span class="legend-dot available"></span> Available</span>
                <span class="legend-item"><span class="legend-dot booked"></span> Booked</span>
                <span class="legend-item"><span class="legend-dot holiday"></span> Closed</span>
            </div>
            <div class="calendar-grid">
                <!-- Week Days -->
                <div class="calendar-weekday">SU</div>
                <div class="calendar-weekday">MO</div>
                <div class="calendar-weekday">TU</div>
                <div class="calendar-weekday">WE</div>
                <div class="calendar-weekday">TH</div>
                <div class="calendar-weekday">FR</div>
                <div class="calendar-weekday">SA</div>
                <!-- Calendar dates will be generated by JavaScript -->
            </div>
        </section>

        <!-- Booking Trends Chart -->
        <section class="chart-section">
            <div class="chart-header">
                <h2 id="chartTitle" class="chart-title">Booking Trends</h2>
            </div>
            <div class="chart-container">
                <canvas id="bookingTrendsChart"></canvas>
            </div>
            <div id="chartSummary" class="chart-summary">
                <!-- Summary will be dynamically generated -->
            </div>
            <!-- Chart legend will be generated dynamically in the chart-legend div -->
            <div class="chart-legend flex justify-center items-center gap-4 mt-3 mb-4">
            </div>
        </section>

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

    @push('scripts')
        <!-- Date Filter JavaScript -->
        <script src="{{ asset('js/admin/booking-dashboard.js') }}"></script>
        <!-- JavaScript for Charts and Calendar -->
        <script>
            // Render sparkline charts for KPI cards
            function renderSparkline(ctxId, data, color) {
                const el = document.getElementById(ctxId);
                if (!el) {
                    console.warn(`Canvas element ${ctxId} not found`);
                    return;
                }

                if (typeof Chart === 'undefined') {
                    console.error('Chart.js is not loaded');
                    return;
                }

                try {
                    new Chart(el.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: data.map((_, i) => i + 1),
                            datasets: [{
                                data: data,
                                borderColor: 'transparent',
                                backgroundColor: 'rgba(0,0,0,0)',
                                borderWidth: 0,
                                pointRadius: 0,
                                pointHoverRadius: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            elements: {
                                line: {
                                    tension: 0.4
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    enabled: false
                                }
                            },
                            scales: {
                                x: {
                                    display: false
                                },
                                y: {
                                    display: false
                                }
                            }
                        }
                    });
                } catch (error) {
                    console.error(`Error rendering sparkline ${ctxId}:`, error);
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                // Check if Chart.js is loaded
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js is not loaded. Please check your internet connection or CDN link.');
                    return;
                }

                // Render all sparklines
                const bookingsSeries = @json($bookingsSeries ?? []);
                const revenueSeries = @json($revenueSeries ?? []);
                const avgStaySeries = @json($avgStaySeries ?? []);
                const cancelSeries = @json($cancelSeries ?? []);

                console.log('Sparkline data:', { bookingsSeries, revenueSeries, avgStaySeries, cancelSeries });

                renderSparkline('sparkBookings', bookingsSeries, '#10b981');
                renderSparkline('sparkRevenue', revenueSeries, '#eab308');
                renderSparkline('sparkAvgStay', avgStaySeries, '#8b5cf6');
                renderSparkline('sparkCancel', cancelSeries, '#ef4444');

                // Common variables used throughout the dashboard
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'];

                // Use actual current date
                const demoDate = new Date(); // Current date
                const currentMonth = monthNames[demoDate.getMonth()];
                const currentYear = demoDate.getFullYear();

                // Initialize Chart
                const ctx = document.getElementById('bookingTrendsChart');
                if (!ctx) {
                    console.error('Chart canvas not found');
                    return;
                }
                const ctxContext = ctx.getContext('2d');

                // Get dynamic chart data from controller
                const dynamicChartData = {!! json_encode($chartData ?? ['labels' => [], 'bookings' => [], 'cancellations' => [], 'totalBookings' => 0, 'totalCancellations' => 0]) !!};

                // Prepare chart data structure
                const chartLabels = dynamicChartData.labels;
                const chartDatasets = [
                    {
                        label: 'Bookings',
                        data: dynamicChartData.bookings,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Cancellations',
                        data: dynamicChartData.cancellations,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ];

                // Chart data for different views (keeping old structure for reference, but using dynamic data)
                const chartData = {
                    bookings: {
                        weekly: {
                            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                            datasets: [
                                {
                                    label: 'Bookings',
                                    data: [
                                            {{ App\Models\Booking::whereBetween('CheckInDate', ['2025-10-01', '2025-10-07'])->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }}, // Week 1
                                            {{ App\Models\Booking::whereBetween('CheckInDate', ['2025-10-08', '2025-10-14'])->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }}, // Week 2
                                            {{ App\Models\Booking::whereBetween('CheckInDate', ['2025-10-15', '2025-10-21'])->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }}, // Week 3
                                        {{ App\Models\Booking::whereBetween('CheckInDate', ['2025-10-22', '2025-10-31'])->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }}  // Week 4
                                    ],
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                },
                                {
                                    label: 'Cancellations',
                                    data: [
                                            {{ App\Models\Booking::whereBetween('CheckInDate', ['2025-10-01', '2025-10-07'])->where('BookingStatus', 'Cancelled')->count() }}, // Week 1
                                            {{ App\Models\Booking::whereBetween('CheckInDate', ['2025-10-08', '2025-10-14'])->where('BookingStatus', 'Cancelled')->count() }}, // Week 2
                                            {{ App\Models\Booking::whereBetween('CheckInDate', ['2025-10-15', '2025-10-21'])->where('BookingStatus', 'Cancelled')->count() }}, // Week 3
                                        {{ App\Models\Booking::whereBetween('CheckInDate', ['2025-10-22', '2025-10-31'])->where('BookingStatus', 'Cancelled')->count() }}  // Week 4
                                    ],
                                    borderColor: '#ef4444',
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }
                            ],
                            yAxisMax: 10,
                            summary: {
                                totalBookings: {{ App\Models\Booking::whereMonth('CheckInDate', 10)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                totalCancellations: {{ App\Models\Booking::whereMonth('CheckInDate', 10)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }}
                                }
                        },
                        monthly: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                            datasets: [
                                {
                                    label: 'Bookings',
                                    data: [
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 1)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 2)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 3)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 4)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 5)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 6)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 7)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 8)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 9)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                        {{ App\Models\Booking::whereMonth('CheckInDate', 10)->whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }}
                                    ],
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                },
                                {
                                    label: 'Cancellations',
                                    data: [
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 1)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 2)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 3)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 4)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 5)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 6)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 7)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 8)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }},
                                            {{ App\Models\Booking::whereMonth('CheckInDate', 9)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }},
                                        {{ App\Models\Booking::whereMonth('CheckInDate', 10)->whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }}
                                    ],
                                    borderColor: '#ef4444',
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }
                            ],
                            yAxisMax: 10, // Adjusted for realistic scale
                            summary: {
                                totalBookings: {{ App\Models\Booking::whereYear('CheckInDate', 2025)->whereIn('BookingStatus', ['Confirmed', 'Completed', 'Staying'])->count() }},
                                totalCancellations: {{ App\Models\Booking::whereYear('CheckInDate', 2025)->where('BookingStatus', 'Cancelled')->count() }}
                                }
                        }
                    },
                    revenue: {
                        weekly: {
                            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                            datasets: [
                                {
                                    label: 'Revenue (PHP)',
                                    data: [
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereBetween('bookings.CheckInDate', ['2025-10-01', '2025-10-07'])->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }}, // Week 1
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereBetween('bookings.CheckInDate', ['2025-10-08', '2025-10-14'])->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }}, // Week 2
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereBetween('bookings.CheckInDate', ['2025-10-15', '2025-10-21'])->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }}, // Week 3
                                        {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereBetween('bookings.CheckInDate', ['2025-10-22', '2025-10-31'])->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }}  // Week 4
                                    ],
                                    borderColor: '#eab308',
                                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }
                            ],
                            yAxisMax: 50000,
                            summary: {
                                revenue: {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 10)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }}
                                }
                        },
                        monthly: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                            datasets: [
                                {
                                    label: 'Revenue (PHP)',
                                    data: [
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 1)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }},
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 2)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }},
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 3)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }},
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 4)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }},
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 5)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }},
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 6)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }},
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 7)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }},
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 8)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }},
                                            {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 9)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }},
                                        {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereMonth('bookings.CheckInDate', 10)->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }}
                                    ],
                                    borderColor: '#eab308',
                                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }
                            ],
                            yAxisMax: 100000, // Adjusted for realistic scale
                            summary: {
                                revenue: {{ App\Models\Payment::join('bookings', 'payments.BookingID', '=', 'bookings.BookingID')->whereYear('bookings.CheckInDate', 2025)->whereIn('bookings.BookingStatus', ['Confirmed', 'Completed', 'Staying'])->sum('payments.Amount') }}
                                }
                        }
                    }
                };

                // Initialize with default selections
                let currentDataType = 'bookings';
                let currentTimeInterval = 'monthly';

                // Create chart with dynamic data from controller
                const bookingChart = new Chart(ctxContext, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: chartDatasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMin: 0,
                                ticks: {
                                    stepSize: 5,
                                    callback: function (value) {
                                        if (currentDataType === 'revenue') {
                                            return '₱' + value.toLocaleString();
                                        }
                                        return value;
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                // Add event listeners for chart dropdowns
                // Removed: Chart now displays bookings only (no filtering needed)

                // Initialize chart summary with bookings data
                function initializeChartSummary() {
                    const summaryElement = document.getElementById('chartSummary');
                    const chartHeader = document.querySelector('.chart-header');

                    // Add date range subtitle
                    const startDateStr = new Date(@json($startDate->timestamp * 1000)).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const endDateStr = new Date(@json($endDate->timestamp * 1000)).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    
                    // Remove existing subtitle if it exists
                    const existingSubtitle = document.getElementById('chartDateRange');
                    if (existingSubtitle) {
                        existingSubtitle.remove();
                    }

                    // Insert date range subtitle with icon
                    const subtitle = document.createElement('div');
                    subtitle.id = 'chartDateRange';
                    subtitle.className = 'chart-date-range';
                    subtitle.innerHTML = `<i class="fas fa-calendar-alt"></i> ${startDateStr} to ${endDateStr}`;
                    chartHeader.appendChild(subtitle);

                    summaryElement.innerHTML = `
                            <div class="chart-summary-item">
                                <div class="chart-summary-label">Total Bookings</div>
                                <div class="chart-summary-value bookings">${dynamicChartData.totalBookings}</div>
                            </div>
                            <div class="chart-summary-item">
                                <div class="chart-summary-label">Total Cancellations</div>
                                <div class="chart-summary-value cancellations">${dynamicChartData.totalCancellations}</div>
                            </div>
                        `;

                    // Hide the text legend below the chart
                    document.querySelector('.chart-legend').style.display = 'none';
                }

                // Function to update chart based on selection
                function updateChart() {
                    // Update chart data
                    bookingChart.data = chartData[currentDataType][currentTimeInterval];

                    // Update y-axis tick callback for revenue formatting
                    bookingChart.options.scales.y.ticks.callback = function (value) {
                        if (currentDataType === 'revenue') {
                            return '₱' + value.toLocaleString();
                        }
                        return value;
                    };

                    // Update chart title
                    const chartTitle = document.getElementById('chartTitle');
                    if (currentTimeInterval === 'weekly') {
                        chartTitle.textContent = `Booking Trends for ${currentMonth}`;
                    } else {
                        chartTitle.textContent = `Booking Trends for ${currentYear}`;
                    }

                    // Update summary section
                    const summaryData = chartData[currentDataType][currentTimeInterval].summary;
                    const summaryElement = document.getElementById('chartSummary');

                    if (currentDataType === 'revenue') {
                        // Format the revenue with comma separators and 2 decimal places
                        const formattedRevenue = new Intl.NumberFormat('en-PH', {
                            style: 'currency',
                            currency: 'PHP',
                            minimumFractionDigits: 2
                        }).format(summaryData.revenue);

                        summaryElement.innerHTML = `
                                <div class="chart-summary-item">
                                    <div class="chart-summary-label">Total Revenue</div>
                                    <div class="chart-summary-value revenue">${formattedRevenue}</div>
                                </div>
                            `;

                        document.querySelector('.chart-legend').innerHTML = `
                                <div class="flex items-center">
                                    <div class="w-4 h-4 bg-yellow-500 rounded mr-2"></div>
                                    <span>Revenue</span>
                                </div>
                            `;
                    } else {
                        summaryElement.innerHTML = `
                                <div class="chart-summary-item">
                                    <div class="chart-summary-label">Total Bookings</div>
                                    <div class="chart-summary-value bookings">${summaryData.totalBookings}</div>
                                </div>
                                <div class="chart-summary-item">
                                    <div class="chart-summary-label">Total Cancellations</div>
                                    <div class="chart-summary-value cancellations">${summaryData.totalCancellations}</div>
                                </div>
                            `;

                        document.querySelector('.chart-legend').innerHTML = `
                                <div class="flex items-center">
                                    <div class="w-4 h-4 bg-green-500 rounded mr-2"></div>
                                    <span>Bookings</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
                                    <span>Cancellations</span>
                                </div>
                            `;
                    }

                    // Update chart
                    bookingChart.update();
                }

                // Calendar Functionality
                class Calendar {
                    constructor() {
                        // Use actual current date
                        const now = new Date(); // Current date
                        this.currentDate = new Date(now.getFullYear(), now.getMonth(), 1); // First day of current month
                        this.today = now; // Today's actual date

                        // Initialize with empty arrays - will be loaded from server
                        this.bookedDates = [];
                        this.closedDates = [];
                        this.activeDate = null; // No active date by default

                        this.init();
                    }

                    async init() {
                        try {
                            await this.loadCalendarData();
                            this.renderCalendar();
                            this.addEventListeners();
                            console.log("Calendar initialized successfully");
                            console.log("Event listeners added successfully");
                        } catch (error) {
                            console.error("Error in calendar init:", error);
                        }
                    }

                    async loadCalendarData() {
                        try {
                            const year = this.currentDate.getFullYear();
                            const month = this.currentDate.getMonth() + 1;

                            const response = await fetch(`/admin/dashboard/calendar-data?year=${year}&month=${month}`, {
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            });

                            if (response.ok) {
                                const data = await response.json();
                                this.bookedDates = data.booked_dates || [];
                                this.closedDates = data.closed_dates || [];
                            } else {
                                console.error('Failed to load calendar data');
                            }
                        } catch (error) {
                            console.error('Error loading calendar data:', error);
                        }
                    }

                    renderCalendar() {
                        try {
                            // Update month/year display
                            const monthYearElement = document.querySelector('.month-year');
                            if (monthYearElement) {
                                const monthName = monthNames[this.currentDate.getMonth()];
                                const year = this.currentDate.getFullYear();
                                monthYearElement.textContent = `${monthName} ${year}`;
                            }

                            // Clear existing calendar dates
                            const calendarGrid = document.querySelector('.calendar-grid');

                            // Keep the weekday headers
                            const weekdayHeaders = calendarGrid.querySelectorAll('.calendar-weekday');
                            calendarGrid.innerHTML = '';
                            weekdayHeaders.forEach(header => calendarGrid.appendChild(header));

                            // Get first day of month and number of days
                            const firstDay = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
                            const lastDay = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);
                            const daysInMonth = lastDay.getDate();
                            const startingDayOfWeek = firstDay.getDay();

                            // Add empty cells for days before the first day of the month
                            for (let i = 0; i < startingDayOfWeek; i++) {
                                const emptyDay = document.createElement('div');
                                emptyDay.classList.add('calendar-date', 'other-month');
                                calendarGrid.appendChild(emptyDay);
                            }

                            // Add days of the month
                            for (let day = 1; day <= daysInMonth; day++) {
                                const date = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), day);

                                let additionalClass = null;
                                const formattedDate = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                                if (this.bookedDates.includes(formattedDate)) {
                                    additionalClass = 'booked';
                                } else if (this.closedDates.includes(formattedDate)) {
                                    additionalClass = 'holiday';
                                }

                                this.createDayElement(date, additionalClass, calendarGrid);
                            }
                        } catch (error) {
                            console.error("Error rendering calendar:", error);
                        }
                    }

                    createDayElement(date, additionalClass, container) {
                        const day = date.getDate();
                        const month = date.getMonth();
                        const year = date.getFullYear();
                        const formattedDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                        // Create day element
                        const dayElement = document.createElement('div');
                        dayElement.textContent = day;
                        dayElement.classList.add('calendar-date');

                        // Get today without time for comparison
                        const todayNoTime = new Date(this.today.getFullYear(), this.today.getMonth(), this.today.getDate());
                        const dateNoTime = new Date(year, month, day);

                        // Check if date is today
                        if (dateNoTime.getTime() === todayNoTime.getTime()) {
                            dayElement.classList.add('today');
                        }

                        // Determine if this is a past date
                        const isPast = dateNoTime < todayNoTime;
                        const isCurrentMonth = month === this.currentDate.getMonth() && year === this.currentDate.getFullYear();
                        const isClosed = this.closedDates.includes(formattedDate);

                        // Handle past dates
                        if (isPast) {
                            dayElement.classList.add('past-date');
                        }
                        // Handle current month future dates (including closed dates)
                        else if (isCurrentMonth) {
                            // Default to available (yellow background) for future dates
                            dayElement.classList.add('available');

                            // If date is closed, add holiday styling
                            if (isClosed) {
                                dayElement.classList.add('holiday');
                                dayElement.style.color = '#9ca3af'; // Grey out text

                                // Add red indicator dot
                                const indicatorDot = document.createElement('div');
                                indicatorDot.classList.add('indicator-dot', 'holiday');
                                indicatorDot.style.backgroundColor = '#ef4444'; // Red color
                                dayElement.appendChild(indicatorDot);
                            }
                            // Add indicator dots for booked dates
                            else if (additionalClass) {
                                const indicatorDot = document.createElement('div');
                                indicatorDot.classList.add('indicator-dot', additionalClass);
                                dayElement.appendChild(indicatorDot);
                            }

                            // Add click event listener for ALL future dates (including closed ones)
                            dayElement.addEventListener('click', (e) => {
                                e.stopPropagation();

                                // Set as active date first
                                this.setActiveDate(formattedDate);

                                // Always show popup for date management
                                this.showDatePopup(formattedDate, dayElement);
                            });
                        }

                        // Check if this date is the active date (day 25)
                        if (formattedDate === this.activeDate) {
                            dayElement.classList.add('selected');
                        }

                        container.appendChild(dayElement);
                    }

                    setActiveDate(date) {
                        this.activeDate = date;

                        // Remove selected class from all dates
                        document.querySelectorAll('.calendar-date.selected').forEach(el => {
                            el.classList.remove('selected');
                        });

                        // Add active class to the selected date
                        const dateElements = document.querySelectorAll('.calendar-date');
                        dateElements.forEach(el => {
                            const dayText = el.textContent;
                            const targetDay = String(new Date(date).getDate());

                            if (dayText === targetDay && !el.classList.contains('other-month')) {
                                el.classList.add('selected');
                            }
                        });
                    }

                    async prevMonth() {
                        this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                        await this.loadCalendarData();
                        this.renderCalendar();
                    }

                    async nextMonth() {
                        this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                        await this.loadCalendarData();
                        this.renderCalendar();
                    }

                    // Show date popup with Close Date toggle
                    async showDatePopup(date, dayElement) {
                        // Remove any existing popups
                        this.removePopup();

                        // Create popup element
                        const popup = document.createElement('div');
                        popup.id = 'date-popup';
                        popup.className = 'date-popup';

                        // Create toggle element
                        const toggleContainer = document.createElement('div');
                        toggleContainer.className = 'toggle-container';

                        // Check if date is already closed or booked
                        const isClosed = this.closedDates.includes(date);
                        const isBooked = this.bookedDates.includes(date);

                        // Create label
                        const label = document.createElement('label');
                        label.className = 'toggle-label';
                        label.textContent = isBooked ? 'Date is booked' : 'Close date';

                        // Create checkbox input
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.className = 'toggle-checkbox';
                        checkbox.checked = isClosed;
                        if (isBooked) {
                            checkbox.disabled = true;
                        }

                        // Add event listener to checkbox
                        checkbox.addEventListener('change', async () => {
                            if (checkbox.disabled) return; // Guard booked dates
                            try {
                                const response = await fetch('/admin/dashboard/toggle-closed-date', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    body: JSON.stringify({
                                        date: date,
                                        is_closed: checkbox.checked
                                    })
                                });

                                if (response.ok) {
                                    // Update local data
                                    if (checkbox.checked) {
                                        if (!this.closedDates.includes(date)) {
                                            this.closedDates.push(date);
                                        }
                                        // Remove from booked dates if present
                                        const bookedIndex = this.bookedDates.indexOf(date);
                                        if (bookedIndex > -1) {
                                            this.bookedDates.splice(bookedIndex, 1);
                                        }
                                    } else {
                                        // Remove from closed dates
                                        const closedIndex = this.closedDates.indexOf(date);
                                        if (closedIndex > -1) {
                                            this.closedDates.splice(closedIndex, 1);
                                        }
                                    }

                                    // Re-render calendar to show changes
                                    this.renderCalendar();

                                    // Close popup after a short delay
                                    setTimeout(() => this.removePopup(), 300);
                                } else {
                                    console.error('Failed to update closed date');
                                    // Revert checkbox state
                                    checkbox.checked = !checkbox.checked;
                                }
                            } catch (error) {
                                console.error('Error updating closed date:', error);
                                // Revert checkbox state
                                checkbox.checked = !checkbox.checked;
                            }
                        });

                        // Add toggle switch elements
                        const toggleSwitch = document.createElement('div');
                        toggleSwitch.className = 'toggle-switch';

                        // Create a wrapper for checkbox and switch to make CSS sibling selector work
                        const toggleWrapper = document.createElement('div');
                        toggleWrapper.style.display = 'flex';
                        toggleWrapper.style.alignItems = 'center';
                        toggleWrapper.style.gap = '8px';

                        // Make toggle switch clickable
                        toggleSwitch.addEventListener('click', () => {
                            if (checkbox.disabled) return; // Guard booked dates
                            checkbox.checked = !checkbox.checked;
                            checkbox.dispatchEvent(new Event('change'));
                        });

                        // Make label clickable too
                        label.addEventListener('click', () => {
                            if (checkbox.disabled) return; // Guard booked dates
                            checkbox.checked = !checkbox.checked;
                            checkbox.dispatchEvent(new Event('change'));
                        });

                        // Add elements to container in the correct order for CSS
                        toggleWrapper.appendChild(checkbox);
                        toggleWrapper.appendChild(toggleSwitch);

                        toggleContainer.appendChild(label);
                        toggleContainer.appendChild(toggleWrapper);

                        // Add container to popup
                        popup.appendChild(toggleContainer);

                        // Position popup near the day element
                        const rect = dayElement.getBoundingClientRect();
                        popup.style.top = `${rect.bottom + window.scrollY + 5}px`;
                        popup.style.left = `${rect.left + window.scrollX - 30}px`;

                        // Add popup to body
                        document.body.appendChild(popup);

                        // Add click outside listener to close popup
                        setTimeout(() => {
                            document.addEventListener('click', this.handleOutsideClick.bind(this));
                        }, 10);
                    }

                    // Remove popup when clicking outside
                    handleOutsideClick(e) {
                        try {
                            const popup = document.getElementById('date-popup');
                            if (popup && !popup.contains(e.target)) {
                                this.removePopup();
                            }
                        } catch (error) {
                            console.error("Error in handleOutsideClick:", error);
                        }
                    }

                    // Remove popup helper
                    removePopup() {
                        const popup = document.getElementById('date-popup');
                        if (popup) {
                            popup.remove();
                            document.removeEventListener('click', this.handleOutsideClick.bind(this));
                        }
                    }

                    addEventListeners() {
                        document.getElementById('prevMonth').addEventListener('click', async () => {
                            await this.prevMonth();
                        });

                        document.getElementById('nextMonth').addEventListener('click', async () => {
                            await this.nextMonth();
                        });
                    }
                }

                // Initialize calendar with debugging
                try {
                    console.log("Initializing calendar...");
                    const calendar = new Calendar();
                    console.log("Calendar initialized successfully");
                } catch (error) {
                    console.error("Error initializing calendar:", error);
                }

                // Initialize chart summary with bookings data only
                initializeChartSummary();

                // Date Range Modal Functionality
                const dateRangeBtn = document.getElementById('dateRangeBtn');
                const dateRangeModal = document.getElementById('dateRangeModal');
                const modalClose = document.querySelector('[data-dismiss="modal"]');
                const cancelBtn = document.querySelector('.modal-footer .btn-secondary');
                const applyBtn = document.getElementById('applyDateRange');
                const presetBtns = document.querySelectorAll('.preset-btn');
                const customStartDate = document.getElementById('customStartDate');
                const customEndDate = document.getElementById('customEndDate');

                // Open modal
                dateRangeBtn.addEventListener('click', () => {
                    dateRangeModal.classList.add('show');
                });

                // Close modal
                function closeModal() {
                    dateRangeModal.classList.remove('show');
                }

                modalClose.addEventListener('click', closeModal);
                cancelBtn.addEventListener('click', closeModal);

                // Handle preset buttons
                presetBtns.forEach(btn => {
                    btn.addEventListener('click', function () {
                        presetBtns.forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                    });
                });

                // Apply date range
                applyBtn.addEventListener('click', () => {
                    const activePreset = document.querySelector('.preset-btn.active');
                    const dateRangeText = document.getElementById('dateRangeText');

                    if (activePreset.dataset.preset === 'year') {
                        dateRangeText.textContent = 'This Year';
                    } else if (activePreset.dataset.preset === 'month') {
                        dateRangeText.textContent = 'This Month';
                    } else if (activePreset.dataset.preset === 'week') {
                        dateRangeText.textContent = 'This Week';
                    } else if (customStartDate.value && customEndDate.value) {
                        dateRangeText.textContent = `${customStartDate.value} to ${customEndDate.value}`;
                    }

                    closeModal();
                });

                // Close modal when clicking outside
                dateRangeModal.addEventListener('click', (e) => {
                    if (e.target === dateRangeModal) {
                        closeModal();
                    }
                });
            });
        </script>
    @endpush
@endsection