@extends('layouts.admin')

@section('title', 'Rental Detail')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/rentals/rentals.css') }}">
@endsection

@section('content')
    <div class="main-content">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="{{ route('admin.rentals.index') }}">Rentals</a>
            <i class="fas fa-chevron-right"></i>
            <span>Rental #{{ $rental->id }}</span>
        </div>

        <div class="page-header">
            <h1 class="page-title">Rental Detail #{{ $rental->id }}</h1>
            <!-- REMOVED THE HEADER-ACTIONS DIV CONTAINING THE TWO BUTTONS -->
        </div>

        <div class="rental-detail-container">
            <!-- Summary Section -->
            <div class="detail-card">
                <h2 class="card-title">
                    <i class="fas fa-info-circle"></i>
                    Summary
                </h2>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Booking ID:</label>
                        <span class="booking-id">{{ $rental->BookingID }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Guest:</label>
                        <span>{{ $rental->booking->guest->FName ?? 'N/A' }}
                            {{ $rental->booking->guest->LName ?? '' }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Item:</label>
                        <span>
                            {{ $rental->rentalItem->name }}
                            <small>({{ $rental->rentalItem->code }})</small>
                        </span>
                    </div>
                    <div class="detail-item">
                        <label>Quantity:</label>
                        <span>{{ $rental->quantity }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Rate Type:</label>
                        <span>{{ $rental->rate_type_snapshot }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Rate:</label>
                        <span>₱{{ number_format($rental->rate_snapshot, 2) }}</span>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span>
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
                        </span>
                    </div>
                </div>
            </div>

            <!-- Timeline Section -->
            <div class="detail-card">
                <h2 class="card-title">
                    <i class="fas fa-timeline"></i>
                    Timeline
                </h2>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker issued"></div>
                        <div class="timeline-content">
                            <div class="timeline-title">Issued</div>
                            <div class="timeline-date">{{ $rental->issued_at->format('M d, Y g:i A') }}</div>
                            <div class="timeline-user">
                                By: {{ $rental->issuedByUser->name ?? 'System' }}
                            </div>
                        </div>
                    </div>

                    @if($rental->returned_at)
                        <div class="timeline-item">
                            <div class="timeline-marker {{ $rental->status === 'Returned' ? 'returned' : 'damaged' }}"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">{{ $rental->status }}</div>
                                <div class="timeline-date">{{ $rental->returned_at->format('M d, Y g:i A') }}</div>
                                <div class="timeline-user">
                                    By: {{ $rental->returnedByUser->name ?? 'System' }}
                                </div>
                                @if($rental->condition)
                                    <div class="timeline-detail">
                                        Condition: <strong>{{ $rental->condition }}</strong>
                                    </div>
                                @endif
                                @if($rental->returned_quantity)
                                    <div class="timeline-detail">
                                        Returned Quantity: <strong>{{ $rental->returned_quantity }}/{{ $rental->quantity }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Fees Breakdown Section -->
            <div class="detail-card">
                <h2 class="card-title">
                    <i class="fas fa-receipt"></i>
                    Fees Breakdown
                </h2>

                <div class="fees-table-container">
                    <table class="fees-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="fee-type rental">Rental Fee</span></td>
                                <td>
                                    @if($rental->rate_type_snapshot === 'Per-Day')
                                        {{ $feeBreakdown['days'] }} day(s) × ₱{{ number_format($rental->rate_snapshot, 2) }} ×
                                        {{ $rental->quantity }} qty
                                    @else
                                        Flat Rate × {{ $rental->quantity }} qty
                                    @endif
                                </td>
                                <td class="amount">₱{{ number_format($feeBreakdown['rental_fee'], 2) }}</td>
                            </tr>

                            @foreach($feeBreakdown['additional_fees'] as $fee)
                                <tr>
                                    <td>
                                        <span class="fee-type {{ strtolower($fee->type) }}">{{ $fee->type }}</span>
                                    </td>
                                    <td>
                                        {{ $fee->reason }}
                                        @if($fee->addedByUser)
                                            <small class="text-muted">
                                                (Added by {{ $fee->addedByUser->name }} on {{ $fee->created_at->format('M d, Y') }})
                                            </small>
                                        @endif
                                    </td>
                                    <td class="amount">₱{{ number_format($fee->amount, 2) }}</td>
                                </tr>
                            @endforeach

                            <tr class="total-row">
                                <td colspan="2"><strong>Total Charges</strong></td>
                                <td class="amount total"><strong>₱{{ number_format($feeBreakdown['total'], 2) }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Notes Section -->
            @if($rental->notes || $rental->damage_description)
                <div class="detail-card">
                    <h2 class="card-title">
                        <i class="fas fa-sticky-note"></i>
                        Notes & Description
                    </h2>

                    @if($rental->notes)
                        <div class="notes-section">
                            <h3>General Notes:</h3>
                            <p class="notes-text">{{ $rental->notes }}</p>
                        </div>
                    @endif

                    @if($rental->damage_description)
                        <div class="notes-section">
                            <h3>Damage/Loss Description:</h3>
                            <p class="notes-text damage">{{ $rental->damage_description }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Photos Section -->
            @if($rental->fees->where('photo_path', '!=', null)->count() > 0)
                <div class="detail-card">
                    <h2 class="card-title">
                        <i class="fas fa-images"></i>
                        Damage/Loss Photos
                    </h2>
                    <div class="photos-grid">
                        @foreach($rental->fees->where('photo_path', '!=', null) as $fee)
                            <div class="photo-item">
                                <img src="{{ asset('storage/' . $fee->photo_path) }}" alt="Damage Photo">
                                <p class="photo-caption">{{ $fee->reason }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- REMOVED THE MODAL INCLUDES SINCE THE BUTTONS ARE GONE -->
@endsection

@section('scripts')
    <script src="{{ asset('js/admin/rentals.js') }}"></script>
@endsection