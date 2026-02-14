@extends('layouts.admin')

@section('title', 'Purchase Entries')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/inventory/purchases.css') }}">
@endsection

@section('content')
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Purchase Entries</h1>
        </div>

        <!-- Toolbar -->
        <div class="card-header">
            <div class="filters-section">
                <div class="filter-group">
                    <input type="text" id="searchInput" placeholder="Search entry # or vendor..." class="filter-input">
                </div>
                <div class="filter-group">
                    <input type="date" id="dateFromFilter" class="filter-input filter-date">
                </div>
                <div class="filter-group">
                    <input type="date" id="dateToFilter" class="filter-input filter-date">
                </div>
            </div>
            <div class="card-actions">
                @if(auth()->user()->role !== 'staff')
                    <button class="btn btn-primary"
                        onclick="window.location.href='{{ route('admin.inventory.purchase-entry') }}'">
                        <i class="fas fa-plus"></i> New Purchase
                    </button>
                @endif
            </div>
        </div>

        <!-- Purchases Table -->
        <div class="table-container">
            <table class="purchases-table" id="purchasesTable">
                <thead>
                    <tr>
                        <th>Entry #</th>
                        <th>Date</th>
                        <th>Vendor</th>
                        <th class="text-center">Total Amount</th>
                        <th class="text-center">Items</th>
                        <th>Recorded By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="purchasesTableBody">
                    @forelse($purchases as $purchase)
                        <tr data-purchase-id="{{ $purchase->entry_number }}"
                            data-date="{{ $purchase->purchase_date->format('Y-m-d') }}"
                            data-vendor="{{ strtolower($purchase->vendor_name) }}"
                            data-entry="{{ strtolower($purchase->entry_number) }}">
                            <td>
                                <strong>{{ $purchase->entry_number }}</strong>
                            </td>
                            <td>
                                <div>{{ $purchase->purchase_date->format('M d, Y') }}</div>
                                <div class="text-muted">{{ $purchase->purchase_date->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div class="vendor-name">{{ $purchase->vendor_name }}</div>
                            </td>
                            <td class="text-center">
                                <strong>₱{{ number_format($purchase->total_amount, 2) }}</strong>
                            </td>
                            <td class="text-center">
                                {{ $purchase->items->count() }} items
                            </td>
                            <td>
                                <div>{{ $purchase->creator->name }}</div>
                                <div class="text-muted">{{ $purchase->created_at->format('M d, g:i A') }}</div>
                            </td>
                            <td>
                                <button class="action-btn primary"
                                    onclick="viewPurchase({{ json_encode($purchase->entry_number) }})"
                                    style="padding: 8px 16px;">
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-shopping-cart"></i>
                                    <p>No purchase entries found</p>
                                    <p class="text-muted">Click "New Purchase" to record your first purchase</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Purchase Details Modal (View Only) -->
    <div id="purchaseDetailsModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2 id="modalTitle">Purchase Details</h2>
                <button class="modal-close" onclick="closePurchaseDetailsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="purchaseDetailsContent">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Purchase entry modals removed - now using dedicated page at /purchase-entry -->
@endsection

@section('scripts')
    <script>
        // Available inventory items for dropdown
        const inventoryItems = @json($inventoryItems);

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            // Check for highlight parameter
            const urlParams = new URLSearchParams(window.location.search);
            const highlightId = urlParams.get('highlight');
            if (highlightId) {
                setTimeout(() => {
                    const row = document.querySelector(`tr[data-purchase-id="${highlightId}"]`);
                    if (row) {
                        row.style.backgroundColor = '#d1fae5';
                        row.style.transition = 'background-color 0.3s ease';
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });

                        // Remove highlight after 3 seconds
                        setTimeout(() => {
                            row.style.backgroundColor = '';
                        }, 3000);
                    }
                }, 100);
            }
        });

        // Filter functionality
        document.getElementById('searchInput').addEventListener('input', filterTable);
        document.getElementById('dateFromFilter').addEventListener('change', filterTable);
        document.getElementById('dateToFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const dateFrom = document.getElementById('dateFromFilter').value;
            const dateTo = document.getElementById('dateToFilter').value;

            const rows = document.querySelectorAll('#purchasesTableBody tr[data-purchase-id]');

            rows.forEach(row => {
                const entry = row.getAttribute('data-entry');
                const vendor = row.getAttribute('data-vendor');
                const date = row.getAttribute('data-date');

                const matchesSearch = entry.includes(searchTerm) || vendor.includes(searchTerm);
                const matchesDateFrom = !dateFrom || date >= dateFrom;
                const matchesDateTo = !dateTo || date <= dateTo;

                const isVisible = matchesSearch && matchesDateFrom && matchesDateTo;
                row.style.display = isVisible ? '' : 'none';
            });
        }

        // View Purchase Details
        function viewPurchase(purchaseId) {
            fetch(`/admin/inventory/purchases/${purchaseId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    displayPurchaseDetails(data);
                    document.getElementById('purchaseDetailsModal').classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load purchase details');
                });
        }

        function displayPurchaseDetails(purchase) {
            const content = `
                        <!-- Left and Right Column Layout -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 3rem;">
                            <!-- Left Column: Entry Number, Vendor, Notes -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                <div class="detail-item">
                                    <label>Entry Number:</label>
                                    <strong>${purchase.entry_number}</strong>
                                </div>
                                <div class="detail-item">
                                    <label>Vendor:</label>
                                    <span>${purchase.vendor_name}</span>
                                </div>
                                ${purchase.receipt_no ? `
                                <div class="detail-item">
                                    <label>Receipt No.:</label>
                                    <span>${purchase.receipt_no}</span>
                                </div>
                                ` : ''}
                                ${purchase.notes ? `
                                <div class="detail-item">
                                    <label>Notes:</label>
                                    <p>${purchase.notes}</p>
                                </div>
                                ` : ''}
                            </div>
                            <!-- Right Column: Purchase Date, Recorded By, Recorded At -->
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                <div class="detail-item">
                                    <label>Purchase Date:</label>
                                    <span>${new Date(purchase.purchase_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Recorded By:</label>
                                    <span>${purchase.creator.name}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Recorded At:</label>
                                    <span>${new Date(purchase.created_at).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })}</span>
                                </div>
                            </div>
                        </div>

                        <div class="section-divider">
                            <h3>Purchase Items</h3>
                        </div>

                        <table class="details-table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Item Name</th>
                                    <th class="text-right">Quantity</th>
                                    <th class="text-right">Unit Cost</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${purchase.items.map(item => `
                                    <tr>
                                        <td><strong>${item.inventory_item?.sku || 'N/A'}</strong></td>
                                        <td>${item.item_name || item.inventory_item?.name || 'Unknown'}</td>
                                        <td class="text-right">${item.quantity}</td>
                                        <td class="text-right">₱${parseFloat(item.unit_cost).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                        <td class="text-right">₱${parseFloat(item.subtotal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>

                        <!-- Total Amount Below Table -->
                        <div class="purchase-total">
                            <span class="purchase-total-label">Total:</span>
                            <span class="purchase-total-amount">₱${parseFloat(purchase.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                        </div>
                    `;

            document.getElementById('purchaseDetailsContent').innerHTML = content;
        }

        function closePurchaseDetailsModal() {
            document.getElementById('purchaseDetailsModal').classList.remove('show');
        }

        // Delete Purchase function removed from UI to prevent manipulation

        // Purchase entry modal functions removed - now using dedicated page at /purchase-entry

        // Close modals on outside click
        window.onclick = function (event) {
            const detailsModal = document.getElementById('purchaseDetailsModal');

            if (event.target == detailsModal) {
                closePurchaseDetailsModal();
            }
        }
    </script>
@endsection