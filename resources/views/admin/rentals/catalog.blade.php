@extends('layouts.admin')

@section('title', 'Rental Items Catalog')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/rentals/items-catalog.css') }}">
@endsection

@section('content')
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Items Catalog</h1>
        </div>

        <!-- Info Banner -->
        @if($pendingCount > 0)
        <div class="info-banner main">
            <i class="fas fa-info-circle icon-warning"></i>
            <span>
                <a href="{{ route('admin.rentals.catalog', ['view' => 'pending']) }}">
                    {{ $pendingCount }} rental item(s)</a> from inventory are ready to be added to the catalog.
            </span>
        </div>
        @endif

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
                    <div class="item-card {{ $item->status === 'Archived' ? 'archived' : '' }}">
                        <div class="item-card-header">
                            <h3 class="item-name">{{ $item->name }}</h3>
                            <span class="item-status {{ strtolower($item->status) }}">
                                {{ $item->status }}
                            </span>
                        </div>
                        
                        <div class="item-card-body">
                            <div class="item-info-row">
                                <span class="item-label">Code:</span>
                                <span class="item-value">{{ $item->code }}</span>
                            </div>
                            
                            <div class="item-info-row">
                                <span class="item-label">Rate Type:</span>
                                <span class="item-value">
                                    <span class="badge rate-type">{{ $item->rate_type }}</span>
                                </span>
                            </div>
                            
                            <div class="item-info-row">
                                <span class="item-label">Rate:</span>
                                <span class="item-value rate">₱{{ number_format($item->rate, 2) }}</span>
                            </div>
                            
                            <div class="item-info-row">
                                <span class="item-label">Stock on Hand:</span>
                                <span class="item-value stock">{{ $item->stock_on_hand }}</span>
                            </div>
                            
                            <div class="item-info-row">
                                <span class="item-label">Available:</span>
                                <span class="item-value available">{{ $item->getAvailableQuantity() }}</span>
                            </div>
                            
                            @if($item->description)
                                <div class="item-description">
                                    {{ $item->description }}
                                </div>
                            @endif

                            @if($item->rentals->count() > 0)
                                <div class="item-stats">
                                    <i class="fas fa-history"></i>
                                    Rented {{ $item->rentals->count() }} time(s)
                                </div>
                            @endif
                        </div>
                        
                        <div class="item-card-footer">
                            <button class="item-action-btn btn-edit" onclick="openEditItemModal({{ $item->id }}, {{ json_encode($item) }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="item-action-btn {{ $item->status === 'Active' ? 'btn-archive' : 'btn-restore' }}" 
                                    onclick="toggleItemStatus({{ $item->id }}, '{{ $item->status }}')" 
                                    title="{{ $item->status === 'Active' ? 'Archive' : 'Restore' }}">
                                <i class="fas {{ $item->status === 'Active' ? 'fa-archive' : 'fa-undo' }}"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="empty-state-card">
                        <i class="fas fa-box-open fa-4x"></i>
                        <h3>No rental items configured</h3>
                        @if($pendingCount > 0)
                            <p>{{ $pendingCount }} rental item(s) from inventory need to be set up</p>

                        @else
                            <p>Add items with category "Rental Item" in the inventory first</p>
                        @endif
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

    <!-- Edit Item Modal -->
    <div id="editItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Rental Item</h2>
                <button class="modal-close" onclick="closeEditItemModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="info-banner warning">
                    <i class="fas fa-info-circle icon-note"></i>
                    <span>
                        <strong>Note:</strong> Stock quantity is managed in the inventory. Only rental rates can be edited here.
                    </span>
                </div>
                <form id="editItemForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editItemId" name="item_id">
                    
                    <div id="editItemInfo" class="form-info-box">
                        <div class="form-info-grid">
                            <div><strong>Item Name:</strong> <span id="editInfoName">-</span></div>
                            <div><strong>SKU:</strong> <span id="editInfoSku">-</span></div>
                            <div><strong>Current Stock:</strong> <span id="editInfoStock">-</span></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="editRateType">Rate Type <span class="required">*</span></label>
                            <select id="editRateType" name="rate_type" class="form-input" required>
                                <option value="Per-Day">Per Day</option>
                                <option value="Flat">Flat Rate</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="editRate">Rate (₱) <span class="required">*</span></label>
                            <input type="number" id="editRate" name="rate" class="form-input" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="editDescription">Description</label>
                        <textarea id="editDescription" name="description" class="form-input" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="editStatus">Status <span class="required">*</span></label>
                        <select id="editStatus" name="status" class="form-input" required>
                            <option value="Active">Active</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditItemModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Item
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
