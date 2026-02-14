@extends('layouts.admin')

@section('title', 'Rental Items - Pending Setup')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/rentals/items-catalog.css') }}">
@endsection

@section('content')
    <div class="main-content">
        <div class="page-header">
            <div class="page-header-back-wrapper">
                <a href="{{ route('admin.rentals.catalog') }}" class="btn-back" title="Back to Catalog">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="page-title">Items Pending Setup</h1>
                </div>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="info-banner setup">
            <i class="fas fa-info-circle icon-info"></i>
            <span>
                These items are from your inventory and need rental rate configuration. Click <strong>"Set Up Rate"</strong> to add them to the catalog.
            </span>
        </div>

        <!-- Catalog Section -->
        <div class="catalog-section">
            <!-- Search Bar -->
            <div class="search-filter-bar" style="margin-bottom: 2rem;">
                <input type="text" class="search-input" placeholder="Search by Item Name or Code..." id="catalogSearch"
                    style="flex: 1; min-width: 250px;">
            </div>

            <!-- Items Grid -->
            <div class="items-grid">
                @forelse($items as $item)
                    <div class="item-card pending-setup">
                        <div class="item-card-header">
                            <h3 class="item-name">{{ $item->name }}</h3>
                            <span class="item-status pending">
                                Pending Setup
                            </span>
                        </div>
                        
                        <div class="item-card-body">
                            <div class="item-info-row">
                                <span class="item-label">Code:</span>
                                <span class="item-value">{{ $item->sku }}</span>
                            </div>
                            
                            <div class="item-info-row">
                                <span class="item-label">Stock on Hand:</span>
                                <span class="item-value stock">{{ $item->quantity_on_hand }}</span>
                            </div>
                            
                            @if($item->description)
                                <div class="item-description">
                                    {{ $item->description }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="item-card-footer">
                            @if(auth()->user()->role === 'staff')
                                <button class="btn btn-secondary setup-button" 
                                        disabled
                                        title="Only Admin and Owner can set up rental rates">
                                    <i class="fas fa-lock"></i>
                                    Set Up Rate (Admin Only)
                                </button>
                            @else
                                <button class="btn btn-primary setup-button" 
                                        onclick="openSetupRateModal({{ json_encode($item->sku) }}, {{ json_encode($item) }})">
                                    <i class="fas fa-cog"></i>
                                    Set Up Rate
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state-card">
                        <i class="fas fa-check-circle fa-4x empty-state-icon success"></i>
                        <h3>All items are configured!</h3>
                        <p>There are no pending items to set up</p>
                        <a href="{{ route('admin.rentals.catalog') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Catalog
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Setup Rate Modal -->
    <div id="setupRateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Set Up Rental Rates</h2>
                <button class="modal-close" onclick="closeSetupRateModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="info-banner setup">
                    <i class="fas fa-info-circle icon-info"></i>
                    <span>
                        Configure rental rates for this inventory item
                    </span>
                </div>
                <form id="setupRateForm">
                    @csrf
                    <input type="hidden" id="setupInventoryItemId" name="inventory_item_id">
                    
                    <div id="setupItemInfo" class="form-info-box">
                        <div class="form-info-grid">
                            <div><strong>Item Name:</strong> <span id="setupInfoName">-</span></div>
                            <div><strong>SKU:</strong> <span id="setupInfoSku">-</span></div>
                            <div><strong>Current Stock:</strong> <span id="setupInfoStock">-</span></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="setupRateType">Rate Type <span class="required">*</span></label>
                            <select id="setupRateType" name="rate_type" class="form-input" required>
                                <option value="Per-Day">Per Day</option>
                                <option value="Flat">Flat Rate</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="setupRate">Rate (₱) <span class="required">*</span></label>
                            <input type="number" id="setupRate" name="rate" class="form-input" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="setupDescription">Description (Optional)</label>
                        <textarea id="setupDescription" name="description" class="form-input" rows="3" placeholder="Add rental-specific notes..."></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeSetupRateModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i>
                            Add to Catalog
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/admin/rentals-catalog.js') }}"></script>
@endsection
