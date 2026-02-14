@extends('layouts.admin')

@section('title', 'Rentals')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/rentals/rentals-list.css') }}">
@endsection

@section('content')
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Rentals Management</h1>
        </div>

        <!-- Rentals List Section -->
        <div class="rentals-section">
            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Search by Booking ID or Guest Name..." id="searchInput"
                    value="{{ request('search') }}">

                <select class="filter-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="Issued" {{ request('status') == 'Issued' ? 'selected' : '' }}>Issued</option>
                    <option value="Returned" {{ request('status') == 'Returned' ? 'selected' : '' }}>Returned</option>
                    <option value="Damaged" {{ request('status') == 'Damaged' ? 'selected' : '' }}>Damaged</option>
                    <option value="Lost" {{ request('status') == 'Lost' ? 'selected' : '' }}>Lost</option>
                </select>

                <select class="filter-select" id="itemFilter">
                    <option value="">All Items</option>
                    @foreach($rentalItems as $item)
                        <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>

                <input type="date" class="filter-input" id="dateFrom" value="{{ request('date_from') }}"
                    placeholder="From Date">
                <input type="date" class="filter-input" id="dateTo" value="{{ request('date_to') }}" placeholder="To Date">

                <button class="new-rental-btn" onclick="openIssueRentalModal()">
                    <i class="fas fa-plus"></i>
                    Issue Rental
                </button>
            </div>

            <!-- Rentals Table -->
            <div class="table-container">
                <table class="rentals-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Guest</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Charges</th>
                            <th>Issued At</th>
                            <th>Returned At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rentals as $rental)
                            <tr>
                                <td class="booking-id">{{ $rental->BookingID }}</td>
                                <td>
                                    <div class="guest-name">
                                        {{ $rental->booking->guest->FName ?? 'N/A' }}@if($rental->booking->guest->MName) {{ $rental->booking->guest->MName }}@endif {{ $rental->booking->guest->LName ?? '' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="item-name">{{ $rental->rentalItem->name }}</div>
                                    <div class="item-code">{{ $rental->rentalItem->code }}</div>
                                </td>
                                <td class="text-center">{{ $rental->quantity }}</td>
                                <td>
                                    @if($rental->status === 'Issued')
                                        <span class="status-badge status-issued">
                                            <i class="fas fa-clock"></i> Issued
                                        </span>
                                    @elseif($rental->status === 'Returned')
                                        <span class="status-badge status-returned">
                                            <i class="fas fa-check-circle"></i> Returned
                                        </span>
                                    @elseif($rental->status === 'Damaged')
                                        <span class="status-badge status-damaged">
                                            <i class="fas fa-exclamation-triangle"></i> Damaged
                                        </span>
                                    @elseif($rental->status === 'Lost')
                                        <span class="status-badge status-damaged">
                                            <i class="fas fa-times-circle"></i> Lost
                                        </span>
                                    @else
                                        <span class="status-badge status-damaged">
                                            <i class="fas fa-question-circle"></i> Unknown
                                        </span>
                                    @endif
                                </td>
                                <td class="amount">₱{{ number_format($rental->calculateTotalCharges(), 2) }}</td>
                                <td>
                                    <div class="date-main">{{ $rental->issued_at->format('M d, Y') }}</div>
                                    <div class="date-time">{{ $rental->issued_at->format('g:i A') }}</div>
                                </td>
                                <td>
                                    @if($rental->returned_at)
                                        <div class="date-main">{{ $rental->returned_at->format('M d, Y') }}</div>
                                        <div class="date-time">{{ $rental->returned_at->format('g:i A') }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn btn-view" onclick="viewRentalDetail({{ $rental->id }})"
                                            title="View Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center empty-state">
                                    <i class="fas fa-inbox fa-3x"></i>
                                    <p>No rentals found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination removed - using scrollable table -->
        </div>
    </div>

    <!-- Issue Rental Modal -->
    @include('admin.rentals.modals.issue')

    <!-- Return/Damage Modal -->
    @include('admin.rentals.modals.return')

    <!-- Add Fee Modal -->
    @include('admin.rentals.modals.add-fee')
@endsection

@section('scripts')
    <script src="{{ asset('js/admin/rentals.js') }}"></script>
@endsection