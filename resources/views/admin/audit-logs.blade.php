@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/settings.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/audit-logs.css') }}">
@endsection

@section('content')
    <div class="main-content">
        <div class="section-header">
            <div>
                <h1 class="section-title">Audit Logs</h1>
                <p class="section-subtitle">System activity tracking for all users</p>
            </div>
            <div class="header-actions">
                {{-- Export Logs button removed per request --}}
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-card">
            <div class="filters-form">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-search"></i>
                            Search
                        </label>
                        <input
                            type="text"
                            id="searchInput"
                            value="{{ request('search') }}"
                            placeholder="Action, description, or IP..."
                            class="filter-input"
                        >
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-user"></i>
                            User ID
                        </label>
                        <input
                            type="text"
                            id="userIdFilter"
                            value="{{ request('user_id') }}"
                            placeholder="Enter user ID"
                            class="filter-input"
                        >
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-calendar-alt"></i>
                            Date From
                        </label>
                        <input
                            type="date"
                            id="dateFromFilter"
                            value="{{ request('date_from') }}"
                            class="filter-input"
                        >
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">
                            <i class="fas fa-calendar-alt"></i>
                            Date To
                        </label>
                        <input
                            type="date"
                            id="dateToFilter"
                            value="{{ request('date_to') }}"
                            class="filter-input"
                        >
                    </div>
                </div>

            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary" id="resultsSummary" style="display: none;">
            <i class="fas fa-info-circle"></i>
            <span id="resultsText">Showing results</span>
        </div>

        <!-- Audit Logs Table -->
        <div class="table-container">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th class="col-timestamp">
                            <i class="fas fa-clock"></i>
                            Timestamp
                        </th>
                        <th class="col-user">
                            <i class="fas fa-user"></i>
                            User
                        </th>
                        <th class="col-action">
                            <i class="fas fa-bolt"></i>
                            Action
                        </th>
                        <th class="col-description">
                            <i class="fas fa-align-left"></i>
                            Description
                        </th>
                        <th class="col-ip">
                            <i class="fas fa-network-wired"></i>
                            IP Address
                        </th>
                    </tr>
                </thead>
                <tbody id="auditLogsTableBody">
                    @forelse($logs as $log)
                        <tr class="log-row"
                            data-log-id="{{ $log->id }}"
                            data-user-id="{{ $log->user_id ?? '' }}"
                            data-user-name="{{ optional($log->user)->name ?? '' }}"
                            data-action="{{ $log->action }}"
                            data-description="{{ $log->description }}"
                            data-ip="{{ $log->ip_address ?? '' }}"
                            data-created-at="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                            <td class="timestamp-cell">
                                <div class="timestamp-wrapper">
                                    <span class="timestamp-date">{{ $log->created_at->format('M d, Y') }}</span>
                                    <span class="timestamp-time">{{ $log->created_at->format('g:i A') }}</span>
                                </div>
                            </td>
                            <td class="user-cell">
                                @if($log->user)
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            {{ strtoupper(substr($log->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div class="user-details">
                                            <span class="user-name">{{ $log->user->name }}</span>
                                            <span class="user-id">ID: {{ $log->user_id }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="user-info">
                                        <div class="user-avatar system">
                                            <i class="fas fa-robot"></i>
                                        </div>
                                        <div class="user-details">
                                            <span class="user-name">System</span>
                                            <span class="user-id">{{ $log->user_id ?? '—' }}</span>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="action-cell">
                                <span class="action-badge action-{{ strtolower(str_replace(' ', '-', $log->action)) }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="description-cell">
                                <div class="description-text">{{ $log->description }}</div>
                            </td>
                            <td class="ip-cell">
                                <span class="ip-address">
                                    <i class="fas fa-globe"></i>
                                    {{ $log->ip_address ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">
                                <div class="empty-state-content">
                                    <i class="fas fa-clipboard-list"></i>
                                    <h3>No Audit Logs Found</h3>
                                    <p>There are no logs matching your criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
        <div class="pagination-wrapper">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        // Store all logs data for client-side filtering
        let allLogs = [];

        // Initialize logs data from server
        document.addEventListener('DOMContentLoaded', function() {
            // Collect all log data from table rows
            document.querySelectorAll('.log-row').forEach(row => {
                allLogs.push({
                    id: row.getAttribute('data-log-id'),
                    userId: row.getAttribute('data-user-id'),
                    userName: row.getAttribute('data-user-name'),
                    action: row.getAttribute('data-action'),
                    description: row.getAttribute('data-description'),
                    ip: row.getAttribute('data-ip'),
                    createdAt: row.getAttribute('data-created-at'),
                    element: row
                });
            });

            // Setup real-time search with debounce
            setupFilterListeners();

            // Apply initial filters if any query params exist
            if (hasActiveFilters()) {
                applyClientSideFilters();
                updateResultsSummary();
            }
        });

        // Setup event listeners for filters
        function setupFilterListeners() {
            let searchTimeout;

            const searchInput = document.getElementById('searchInput');
            const userIdFilter = document.getElementById('userIdFilter');
            const dateFromFilter = document.getElementById('dateFromFilter');
            const dateToFilter = document.getElementById('dateToFilter');

            // Real-time search with debounce
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        applyClientSideFilters();
                        updateResultsSummary();
                    }, 300);
                });
            }

            // Real-time filter on user ID
            if (userIdFilter) {
                userIdFilter.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        applyClientSideFilters();
                        updateResultsSummary();
                    }, 300);
                });
            }

            // Date filters
            if (dateFromFilter) {
                dateFromFilter.addEventListener('change', function() {
                    applyClientSideFilters();
                    updateResultsSummary();
                });
            }

            if (dateToFilter) {
                dateToFilter.addEventListener('change', function() {
                    applyClientSideFilters();
                    updateResultsSummary();
                });
            }
        }

        // Helper: normalize string for comparison
        function normStr(value) {
            if (value === undefined || value === null) return '';
            return String(value).toLowerCase().trim();
        }

        // Check if any filters are active
        function hasActiveFilters() {
            const search = document.getElementById('searchInput')?.value || '';
            const userId = document.getElementById('userIdFilter')?.value || '';
            const dateFrom = document.getElementById('dateFromFilter')?.value || '';
            const dateTo = document.getElementById('dateToFilter')?.value || '';

            return search || userId || dateFrom || dateTo;
        }

        // Apply client-side filters
        function applyClientSideFilters() {
            const search = normStr(document.getElementById('searchInput')?.value || '');
            const userId = normStr(document.getElementById('userIdFilter')?.value || '');
            const dateFrom = document.getElementById('dateFromFilter')?.value || '';
            const dateTo = document.getElementById('dateToFilter')?.value || '';

            let visibleCount = 0;

            allLogs.forEach(log => {
                let isVisible = true;

                // Search filter (action, description, IP)
                if (search) {
                    const matchAction = normStr(log.action).includes(search);
                    const matchDescription = normStr(log.description).includes(search);
                    const matchIp = normStr(log.ip).includes(search);
                    const matchUserName = normStr(log.userName).includes(search);

                    isVisible = isVisible && (matchAction || matchDescription || matchIp || matchUserName);
                }

                // User ID filter
                if (userId) {
                    isVisible = isVisible && normStr(log.userId).includes(userId);
                }

                // Date from filter
                if (dateFrom) {
                    const logDate = log.createdAt.split(' ')[0]; // Get YYYY-MM-DD part
                    isVisible = isVisible && (logDate >= dateFrom);
                }

                // Date to filter
                if (dateTo) {
                    const logDate = log.createdAt.split(' ')[0]; // Get YYYY-MM-DD part
                    isVisible = isVisible && (logDate <= dateTo);
                }

                // Show/hide row
                if (isVisible) {
                    log.element.style.display = '';
                    log.element.dataset.visible = 'true';
                    visibleCount++;
                } else {
                    log.element.style.display = 'none';
                    log.element.dataset.visible = 'false';
                }
            });

            // Show/hide empty state
            const tbody = document.getElementById('auditLogsTableBody');
            const existingEmpty = tbody.querySelector('.empty-state');

            if (visibleCount === 0 && !existingEmpty) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `
                    <td colspan="5" class="empty-state">
                        <div class="empty-state-content">
                            <i class="fas fa-search"></i>
                            <h3>No Results Found</h3>
                            <p>No logs match your search criteria. Try adjusting your filters.</p>
                        </div>
                    </td>
                `;
                tbody.appendChild(emptyRow);
            } else if (visibleCount > 0 && existingEmpty) {
                existingEmpty.remove();
            }
        }

        // Update results summary
        function updateResultsSummary() {
            const summary = document.getElementById('resultsSummary');
            const resultsText = document.getElementById('resultsText');

            if (hasActiveFilters()) {
                const visibleCount = allLogs.filter(log => log.element.dataset.visible === 'true').length;
                summary.style.display = 'flex';
                resultsText.textContent = `Showing ${visibleCount} result${visibleCount !== 1 ? 's' : ''}`;
            } else {
                summary.style.display = 'none';
            }
        }

        // Apply filters (server-side - for URL update)
        function applyFilters() {
            const search = document.getElementById('searchInput')?.value || '';
            const userId = document.getElementById('userIdFilter')?.value || '';
            const dateFrom = document.getElementById('dateFromFilter')?.value || '';
            const dateTo = document.getElementById('dateToFilter')?.value || '';

            const url = new URL(window.location.href);

            // Set or delete query parameters
            if (search) {
                url.searchParams.set('search', search);
            } else {
                url.searchParams.delete('search');
            }

            if (userId) {
                url.searchParams.set('user_id', userId);
            } else {
                url.searchParams.delete('user_id');
            }

            if (dateFrom) {
                url.searchParams.set('date_from', dateFrom);
            } else {
                url.searchParams.delete('date_from');
            }

            if (dateTo) {
                url.searchParams.set('date_to', dateTo);
            } else {
                url.searchParams.delete('date_to');
            }

            // Redirect to update URL and trigger server-side filtering
            window.location.href = url.toString();
        }

        // Reset all filters
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('userIdFilter').value = '';
            document.getElementById('dateFromFilter').value = '';
            document.getElementById('dateToFilter').value = '';

            // Reset URL to base route
            window.location.href = '{{ route("admin.audit-logs") }}';
        }

        // Export functionality removed from UI (server-side export still available via direct request)
    </script>
@endsection
