@extends('layouts.admin')

@section('title', 'Stock Movements')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/inventory/stock-movements.css') }}">
@endsection

@section('topnav')
    @include('admin.partials.topnav-inventory')
@endsection

@section('content')
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Stock Movements</h1>
        </div>

        <!-- Filters -->
        <div class="card-header">
            <div class="filters-section">
                    <div class="filter-group">
                        <input type="text" id="searchInput" placeholder="Search movements..." class="filter-input">
                    </div>
                    <div class="filter-group">
                        <input type="date" id="startDateFilter" class="filter-input filter-date">
                    </div>
                    <div class="filter-group">
                        <input type="date" id="endDateFilter" class="filter-input filter-date">
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="itemFilter">
                            <option value="">All Categories</option>
                            <option value="cleaning_supplies">Cleaning</option>
                            <option value="kitchen_supplies">Kitchen</option>
                            <option value="amenity_supplies">Amenity</option>
                            <option value="rental_items">Rental Items</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="in">Stock In</option>
                            <option value="out">Stock Out</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="reasonFilter">
                            <option value="">All Reasons</option>
                            <option value="adjustment_in">Adjustment (In)</option>
                            <option value="adjustment_out">Adjustment (Out)</option>
                            <option value="usage">Usage</option>
                            <option value="rental_damage">Rental Damage</option>
                            <option value="lost">Lost/Stolen</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                    @if(auth()->user()->role !== 'staff')
                        <button class="btn btn-primary" onclick="openStockOutModal()" style="margin-left: auto;">
                            <i class="fas fa-minus"></i> Stock Out
                        </button>
                    @endif
                </div>
            </div>

            <div class="table-container">
                <table class="movements-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                            <th>Performed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                            <tr>
                                <td>
                                    <div class="date-main">{{ $movement->created_at->format('M d, Y') }}</div>
                                    <div class="date-time">{{ $movement->created_at->format('g:i A') }}</div>
                                </td>
                                <td>
                                    <div class="item-name-cell">{{ $movement->inventoryItem->name }}</div>
                                    @if($movement->inventoryItem->sku)
                                        <div class="item-sku">SKU: {{ $movement->inventoryItem->sku }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($movement->movement_type === 'in')
                                        <span class="movement-badge in">
                                            Stock In
                                        </span>
                                    @else
                                        <span class="movement-badge out">
                                            Stock Out
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="quantity {{ $movement->movement_type === 'in' ? 'positive' : 'negative' }}">
                                        {{ $movement->quantity }}
                                    </span>
                                </td>
                                <td>
                                    <span class="reason-badge">
                                        {{ ucwords(str_replace('_', ' ', $movement->reason)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="performer">
                                        @if($movement->performer)
                                            <div class="performer-icon">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <span class="performer-name">
                                                @php
                                                    $firstName = $movement->performer->first_name ?? explode(' ', $movement->performer->name)[0];
                                                    $nameParts = explode(' ', $movement->performer->name);
                                                    $lastName = $nameParts[1] ?? null;
                                                @endphp
                                                {{ $firstName }}
                                                @if($lastName)
                                                    {{ substr($lastName, 0, 1) }}.
                                                @endif
                                            </span>
                                        @else
                                            <div class="performer-icon">
                                                <i class="fas fa-robot"></i>
                                            </div>
                                            <span class="performer-name">System</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-view" onclick="viewMovementDetails({{ $movement->id }})">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <div class="empty-icon">
                                        Exchange
                                    </div>
                                    <div class="empty-title">No Stock Movements Found</div>
                                    <div class="empty-text">There are no stock movements recorded yet.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    </div>

    <!-- Movement Details Modal -->
    <div id="movementDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Movement Details</h2>
                <button class="modal-close" onclick="closeMovementDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="movementDetailsContent">
                    <div class="detail-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeMovementDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Stock Out Modal -->
    <div id="stockOutModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2 id="modalTitle">Stock Out</h2>
                <button class="modal-close" onclick="closeStockOutModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="stockOutForm">
                    <div class="form-section">
                        <label class="form-label">Item <span style="color: #dc2626;">*</span></label>
                        <div style="position: relative;">
                            <input type="text" id="itemSearch" placeholder="Search items..." class="form-input"
                                style="margin-bottom: 0.5rem;">
                            <select class="form-input" id="itemSelect" required style="display: none;">
                                <option value="">Select an item...</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->sku }}" data-name="{{ $item->name }}" data-sku="{{ $item->sku }}">
                                        {{ $item->name }} (SKU: {{ $item->sku }})
                                    </option>
                                @endforeach
                            </select>
                            <div id="itemSearchResults" class="search-results"
                                style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 10;">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-section">
                            <label class="form-label">Reason <span style="color: #dc2626;">*</span></label>
                            <select class="form-input" id="reasonSelect" required>
                                <option value="">Select a reason...</option>
                                <option value="adjustment_out">Adjustment (Out)</option>
                                <option value="usage">Usage</option>
                                <option value="rental_damage">Rental Damage</option>
                                <option value="lost">Lost/Stolen</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>

                        <div class="form-section">
                            <label class="form-label">Quantity <span style="color: #dc2626;">*</span></label>
                            <input type="number" id="quantityInput" placeholder="Enter quantity" class="form-input" min="1"
                                required>
                        </div>
                    </div>

                    <div class="form-section">
                        <label class="form-label">Notes</label>
                        <textarea id="notesInput" placeholder="Add notes (optional)" class="form-input" rows="3"></textarea>
                    </div>

                    <div class="form-section"
                        style="border-top: 1px solid #e5e7eb; padding-top: 1.5rem; margin-top: 1.5rem;">
                        <label class="form-label">User ID Verification <span style="color: #dc2626;">*</span></label>
                        <input type="text" id="userIdInput" placeholder="Enter your user ID to confirm"
                            class="form-input" required>
                        <small style="color: #6b7280; display: block; margin-top: 0.25rem;">This is required for security
                            purposes</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeStockOutModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitStockOut()">
                    Confirm Stock Out
                </button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Map SKU prefixes to category filter values
        function getCategoryFromSku(sku) {
            if (!sku) return '';
            const prefix = sku.trim().substring(0, 3).toUpperCase();

            const map = {
                'RNT': 'rental_items',
                'AMN': 'amenity_supplies',
                'CLN': 'cleaning_supplies',
                'KTC': 'kitchen_supplies'
                // Add more prefixes here if needed
            };

            return map[prefix] || '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const filters = ['searchInput', 'startDateFilter', 'endDateFilter', 'itemFilter', 'typeFilter', 'reasonFilter'];
            filters.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', applyFilters);
                    if (el.tagName === 'SELECT') el.addEventListener('change', applyFilters);
                }
            });

            applyFilters();
        });

        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const startDate = document.getElementById('startDateFilter').value;
            const endDate = document.getElementById('endDateFilter').value;
            const categoryFilter = document.getElementById('itemFilter').value; // e.g. "cleaning_supplies"
            const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
            const reasonFilter = document.getElementById('reasonFilter').value.toLowerCase();

            const rows = document.querySelectorAll('.movements-table tbody tr:not(.empty-state)');

            rows.forEach(row => {
                let isVisible = true;

                // Search in any text
                if (searchTerm) {
                    const rowText = row.textContent.toLowerCase();
                    if (!rowText.includes(searchTerm)) {
                        isVisible = false;
                    }
                }

                // Category filter – based on SKU prefix
                if (categoryFilter && isVisible) {
                    const skuEl = row.querySelector('.item-sku');
                    const sku = skuEl ? skuEl.textContent.replace('SKU:', '').trim() : '';
                    const rowCategory = getCategoryFromSku(sku);
                    if (rowCategory !== categoryFilter) {
                        isVisible = false;
                    }
                }

                // Type filter
                if (typeFilter && isVisible) {
                    const typeBadge = row.querySelector('.movement-badge');
                    const typeText = typeBadge ? typeBadge.textContent.toLowerCase().trim() : '';
                    // Match 'stock in' with 'in' and 'stock out' with 'out'
                    const isMatch = (typeFilter === 'in' && typeText === 'stock in') || 
                                    (typeFilter === 'out' && typeText === 'stock out');
                    if (!isMatch) {
                        isVisible = false;
                    }
                }

                // Reason filter
                if (reasonFilter && isVisible) {
                    const reasonBadge = row.querySelector('.reason-badge');
                    const reasonText = reasonBadge ? reasonBadge.textContent.toLowerCase().trim() : '';
                    // The blade template uses ucwords(str_replace('_', ' ', $movement->reason))
                    // So "adjustment_in" becomes "Adjustment In", etc.
                    const reasonMap = {
                        'adjustment_in': 'adjustment in',
                        'adjustment_out': 'adjustment out',
                        'usage': 'usage',
                        'rental_damage': 'rental damage',
                        'lost': 'lost',
                        'expired': 'expired'
                    };
                    const expectedText = reasonMap[reasonFilter];
                    if (reasonText !== expectedText) {
                        isVisible = false;
                    }
                }

                // Date range
                if ((startDate || endDate) && isVisible) {
                    const dateCell = row.querySelector('.date-main');
                    if (!dateCell) {
                        isVisible = false;
                    } else {
                        const dateStr = dateCell.textContent.trim(); // e.g. "Dec 03, 2025"
                        const parts = dateStr.split(' ');
                        if (parts.length !== 3) {
                            isVisible = false;
                        } else {
                            const monthName = parts[0];
                            const day = parseInt(parts[1].replace(',', ''));
                            const year = parseInt(parts[2]);

                            const monthMap = { jan: 0, feb: 1, mar: 2, apr: 3, may: 4, jun: 5, jul: 6, aug: 7, sep: 8, oct: 9, nov: 10, dec: 11 };
                            const month = monthMap[monthName.slice(0, 3).toLowerCase()];
                            if (typeof month === 'undefined') {
                                isVisible = false;
                            } else {
                                // Create dates at midnight for proper comparison
                                const cellDate = new Date(year, month, day);
                                cellDate.setHours(0, 0, 0, 0);

                                if (startDate) {
                                    const startDateObj = new Date(startDate);
                                    startDateObj.setHours(0, 0, 0, 0);
                                    if (cellDate < startDateObj) {
                                        isVisible = false;
                                    }
                                }

                                if (endDate && isVisible) {
                                    const endDateObj = new Date(endDate);
                                    endDateObj.setHours(23, 59, 59, 999);
                                    if (cellDate > endDateObj) {
                                        isVisible = false;
                                    }
                                }
                            }
                        }
                    }
                }

                row.style.display = isVisible ? '' : 'none';
            });
        }

        // Movement Details Modal functions
        function viewMovementDetails(movementId) {
            const modal = document.getElementById('movementDetailsModal');
            const content = document.getElementById('movementDetailsContent');
            
            modal.classList.add('show');
            content.innerHTML = '<div class="detail-loading"><i class="fas fa-spinner fa-spin"></i><p>Loading details...</p></div>';
            
            fetch(`/admin/inventory/stock-movements/${movementId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const m = data.movement;
                        content.innerHTML = `
                            <div class="details-grid">
                                <div class="detail-row">
                                    <div class="detail-label">Date & Time:</div>
                                    <div class="detail-value">${new Date(m.created_at).toLocaleString('en-US', { 
                                        month: 'short', day: '2-digit', year: 'numeric', 
                                        hour: '2-digit', minute: '2-digit', hour12: true 
                                    })}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Item:</div>
                                    <div class="detail-value"><strong>${m.item_name}</strong> (SKU: ${m.item_sku})</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Type:</div>
                                    <div class="detail-value">
                                        <span class="movement-badge ${m.movement_type}">
                                            <i class="fas fa-arrow-${m.movement_type === 'in' ? 'up' : 'down'}"></i>
                                            Stock ${m.movement_type === 'in' ? 'In' : 'Out'}
                                        </span>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Quantity:</div>
                                    <div class="detail-value">
                                        <span class="quantity ${m.movement_type === 'in' ? 'positive' : 'negative'}">
                                            ${m.movement_type === 'in' ? '+' : '-'}${m.quantity}
                                        </span>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Reason:</div>
                                    <div class="detail-value">${m.reason_display}</div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Performed By:</div>
                                    <div class="detail-value">${m.performed_by || 'System'}</div>
                                </div>
                                <div class="detail-row full-width">
                                    <div class="detail-label">Notes:</div>
                                    <div class="detail-value notes-detail">${m.notes || 'No notes provided'}</div>
                                </div>
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<div class="detail-error"><i class="fas fa-exclamation-circle"></i><p>Failed to load details</p></div>';
                    }
                })
                .catch(err => {
                    console.error('Error loading movement details:', err);
                    content.innerHTML = '<div class="detail-error"><i class="fas fa-exclamation-circle"></i><p>Error loading details</p></div>';
                });
        }
        
        function closeMovementDetailsModal() {
            document.getElementById('movementDetailsModal').classList.remove('show');
        }

        // Modal functions
        function openStockOutModal() {
            document.getElementById('stockOutModal').classList.add('show');
            document.getElementById('stockOutForm').reset();
            document.getElementById('itemSearch').value = '';
            document.getElementById('itemSearchResults').style.display = 'none';
        }

        function closeStockOutModal() {
            document.getElementById('stockOutModal').classList.remove('show');
        }

        // Item search in modal
        document.addEventListener('DOMContentLoaded', function () {
            const itemSearch = document.getElementById('itemSearch');
            const itemSelect = document.getElementById('itemSelect');
            const resultsDiv = document.getElementById('itemSearchResults');

            if (itemSearch) {
                itemSearch.addEventListener('input', function () {
                    const term = this.value.toLowerCase();
                    if (!term) {
                        resultsDiv.style.display = 'none';
                        return;
                    }

                    let html = '';
                    let count = 0;
                    itemSelect.querySelectorAll('option').forEach(opt => {
                        if (opt.value && opt.textContent.toLowerCase().includes(term)) {
                            html += `<div class="search-results-item" onclick="selectItem('${opt.value}', '${opt.dataset.name}', '${opt.dataset.sku}')">
                                            <div class="search-results-name">${opt.dataset.name}</div>
                                            <div class="search-results-sku">SKU: ${opt.dataset.sku}</div>
                                         </div>`;
                            count++;
                        }
                    });

                    resultsDiv.innerHTML = count ? html : '<div style="padding:1rem;color:#9ca3af;">No items found</div>';
                    resultsDiv.style.display = 'block';
                });

                // Close results when clicking outside
                document.addEventListener('click', e => {
                    if (!e.target.closest('#itemSearch') && !e.target.closest('#itemSearchResults')) {
                        resultsDiv.style.display = 'none';
                    }
                });
            }
        });

        function selectItem(id, name, sku) {
            document.getElementById('itemSelect').value = id;
            document.getElementById('itemSearch').value = `${name} (SKU: ${sku})`;
            document.getElementById('itemSearchResults').style.display = 'none';
        }

        function submitStockOut() {
            const itemId = document.getElementById('itemSelect').value;
            const reason = document.getElementById('reasonSelect').value;
            const qty = document.getElementById('quantityInput').value;
            const notes = document.getElementById('notesInput').value;
            const userId = document.getElementById('userIdInput').value;

            if (!itemId || !reason || !qty || !userId) {
                alert('Please fill all required fields');
                return;
            }
            if (qty <= 0) {
                alert('Quantity must be greater than 0');
                return;
            }

            const data = new FormData();
            data.append('item_id', itemId);
            data.append('reason', reason);
            data.append('quantity', qty);
            data.append('notes', notes);
            data.append('user_id', userId);

            fetch('{{ route("admin.inventory.stock-out") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: data
            })
                .then(r => {
                    if (!r.ok) return r.json().then(err => { throw new Error(err.message || 'Failed'); });
                    return r.json();
                })
                .then(() => {
                    alert('Stock out recorded successfully!');
                    closeStockOutModal();
                    location.reload();
                })
                .catch(e => alert('Error: ' + e.message));
        }

        // Close modal when clicking outside
        window.addEventListener('click', e => {
            const stockOutModal = document.getElementById('stockOutModal');
            const detailsModal = document.getElementById('movementDetailsModal');
            if (e.target === stockOutModal) closeStockOutModal();
            if (e.target === detailsModal) closeMovementDetailsModal();
        });
    </script>
@endsection