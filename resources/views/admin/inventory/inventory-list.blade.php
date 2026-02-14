@extends('layouts.admin')

@section('title', 'Inventory List')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/inventory/inventory-list.css?v=3.0') }}">
@endsection

@section('content')
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Inventory List</h1>
        </div>

        <!-- Toolbar -->
        <div class="card-header">
            <div class="filters-section">
                <div class="filter-group">
                    <input type="text" id="searchInput" placeholder="Search by name or SKU..." class="filter-input">
                </div>
                <div class="filter-group">
                    <select id="categoryFilter" class="filter-select">
                        <option value="">All Categories</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="kitchen">Kitchen</option>
                        <option value="amenity">Amenity</option>
                        <option value="rental_item">Rental Items</option>
                    </select>
                </div>
                <div class="filter-group">
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="low">Low Stock</option>
                        <option value="normal">Normal</option>
                    </select>
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
                    <button class="btn btn-primary" onclick="openExportModal()">
                        <i class="fas fa-download"></i> Export
                    </button>
                @endif
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="table-container">
            <table class="inventory-table" id="inventoryTable">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>On Hand</th>
                        <th>Reorder Level</th>
                        <th>Avg Cost</th>
                        <th>Last Purchase</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody">
                    @forelse($items as $item)
                        @php
    $categoryValue = is_object($item->category) ? $item->category->value : $item->category;
    $categoryLabel = is_object($item->category) ? $item->category->label() : ucfirst(str_replace('_', ' ', $item->category));
    $lastPurchaseDate = $item->last_purchase_date ? $item->last_purchase_date->format('Y-m-d') : '';
                        @endphp
                        <tr data-item-id="{{ $item->sku }}" data-category="{{ $categoryValue }}"
                            data-last-purchase="{{ $lastPurchaseDate }}">
                            <td>
                                <strong>{{ $item->sku ?? 'N/A' }}</strong>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $item->name }}</strong>
                                </div>
                            </td>
                            <td>
                                @php
    $textColorClass = match ($categoryValue) {
        'cleaning' => 'text-category-cleaning',
        'kitchen' => 'text-category-kitchen',
        'amenity' => 'text-category-amenity',
        'rental_item' => 'text-category-rental',
        default => 'text-category-default'
    };
                                @endphp
                                <span class="{{ $textColorClass }}">{{ $categoryLabel }}</span>
                            </td>
                            <td>
                                <span class="{{ $item->isLowStock() ? 'text-warning' : '' }}">
                                    {{ $item->quantity_on_hand }}
                                </span>
                            </td>
                            <td>{{ $item->reorder_level }}</td>
                            <td>₱{{ number_format($item->average_cost, 2) }}</td>
                            <td>
                                @if($item->last_purchase_date)
                                    <div>{{ $item->last_purchase_date->format('M d, Y') }}</div>
                                    <div class="date-difference-text">{{ $item->last_purchase_date->diffForHumans() }}</div>
                                @else
                                    <span class="purchase-never-text">Never</span>
                                @endif
                            </td>
                            <td>
                                @if($item->isLowStock())
                                    <span class="text-status-low-stock">Low Stock</span>
                                @else
                                    <span class="text-status-normal">Normal</span>
                                @endif
                            </td>
                            <td>
                                @if(Auth::user()->role === 'admin')
                                    <button class="action-btn primary"
                                        onclick="openAdjustStock('{{ $item->sku }}', '{{ addslashes($item->name) }}', {{ $item->quantity_on_hand }}, {{ $item->reorder_level }})">
                                        <i class="fas fa-edit"></i> Adjust
                                    </button>
                                @endif
                                <button class="action-btn"
                                    onclick="viewMovements('{{ $item->sku }}', '{{ addslashes($item->name) }}')">
                                    <i class="fas fa-history"></i> History
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="fas fa-boxes"></i>
                                    <p>No inventory items found</p>
                                    <p class="empty-state-helper">Click "New Item" to add your first inventory item</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Adjust Stock Modal -->
        <div class="modal" id="adjustStockModal">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">Adjust Stock</h3>
                        <button type="button" class="modal-close" onclick="closeAdjustStockModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="adjustStockForm">
                            <input type="hidden" id="adjustItemId">

                            <div class="form-group">
                                <label class="form-label">Item Name</label>
                                <input type="text" class="form-input" id="adjustItemName" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Current Quantity</label>
                                <input type="number" class="form-input" id="adjustCurrentQty" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">New Quantity </label>
                                <input type="number" class="form-input" id="adjustNewQty" min="0" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Reorder Level</label>
                                <input type="number" class="form-input" id="adjustReorderLevel" min="0" required>
                            </div>

                    <div class="form-group">
                        <label class="form-label">Reason for Adjustment</label>
                        <textarea class="form-input" id="adjustReason" rows="2" placeholder="Enter the reason for this adjustment (optional)..."></textarea>
                    </div>

                            <div class="form-divider">
                                <span>Admin Verification Required</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Enter Your Admin ID</label>
                                <input type="text" class="form-input" id="adminIdVerification"
                                    placeholder="Enter your admin ID" required>
                                <small class="form-helper-text">
                                    Your admin ID is required to authorize this adjustment
                                </small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeAdjustStockModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveStockAdjustment()">
                            <i class="fas fa-save"></i> Save Adjustment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Modal -->
        <div class="modal" id="exportModal">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">Export Inventory Report</h3>
                        <button type="button" class="modal-close" onclick="closeExportModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="exportForm">
                            <div class="form-section">
                                <h4 class="form-section-title">Export Range</h4>
                                <div class="export-options">
                                    <label class="export-option">
                                        <input type="radio" name="exportRange" value="current" checked>
                                        <span>Current Page</span>
                                    </label>
                                    <label class="export-option">
                                        <input type="radio" name="exportRange" value="filtered">
                                        <span>Filtered Results (All matching items)</span>
                                    </label>
                                    <label class="export-option">
                                        <input type="radio" name="exportRange" value="all">
                                        <span>All Inventory Items</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-section">
                                <h4 class="form-section-title">Export Format</h4>
                                <div class="form-group">
                                    <input type="text" class="form-input form-input-readonly" id="exportFormat" value="PDF Document" readonly>
                                    <input type="hidden" id="exportFormatValue" value="pdf">
                                </div>
                            </div>

                            <div class="form-section">
                                <h4 class="form-section-title">File Name</h4>
                                <div class="form-group">
                                    <input type="text" class="form-input" id="exportFileName"
                                        placeholder="inventory-report">
                                    <small class="form-helper-text">
                                        File extension will be added automatically
                                    </small>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeExportModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="executeExport()">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Modal -->
        <div class="modal" id="historyModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">Item History - <span id="historyItemName"></span></h3>
                        <button type="button" class="modal-close" onclick="closeHistoryModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Tabs for different history views -->
                        <div class="history-tabs">
                            <button class="history-tab-btn active" onclick="switchHistoryTab('stock-movements')">Stock
                                Movements</button>
                            <button class="history-tab-btn" onclick="switchHistoryTab('purchases')">Purchase
                                History</button>
                        </div>

                        <!-- Stock Movements Tab -->
                        <div id="stock-movements-tab" class="history-tab-content active">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Reason</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="stockMovementsBody">
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #999;">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Purchase History Tab -->
                        <div id="purchases-tab" class="history-tab-content">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Total Cost</th>
                                        <th>Supplier</th>
                                    </tr>
                                </thead>
                                <tbody id="purchaseHistoryBody">
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #999;">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeHistoryModal()">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            // DEBUG: Check if modals exist in DOM
            console.log('🔍 DEBUG: Page loaded, checking for modals...');
            const adjustModal = document.getElementById('adjustStockModal');
            const historyModal = document.getElementById('historyModal');
            const exportModal = document.getElementById('exportModal');

            console.log('🔍 DEBUG: adjustStockModal exists?', adjustModal !== null);
            console.log('🔍 DEBUG: historyModal exists?', historyModal !== null);
            console.log('🔍 DEBUG: exportModal exists?', exportModal !== null);

            if (!adjustModal) console.error('❌ adjustStockModal NOT found in DOM!');
            if (!historyModal) console.error('❌ historyModal NOT found in DOM!');
            if (!exportModal) console.error('❌ exportModal NOT found in DOM!');
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeAdjustStockModal();
                closeExportModal();
                closeHistoryModal();
            }
        });

        // Click outside any modal → close it
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', e => {
                if (e.target === modal) {
                    if (modal.id === 'adjustStockModal') closeAdjustStockModal();
                    if (modal.id === 'exportModal') closeExportModal();
                    if (modal.id === 'historyModal') closeHistoryModal();
                }
            });
        });

        /* ================================================================
           1. ADJUST STOCK MODAL
           ================================================================ */
       window.openAdjustStock = function (itemId, itemName, currentQty, reorderLevel) {
            document.getElementById('adjustItemId').value = itemId;  // now it's a number like 7
            document.getElementById('adjustItemName').value = itemName;
            document.getElementById('adjustCurrentQty').value = currentQty;
            document.getElementById('adjustNewQty').value = currentQty;
            document.getElementById('adjustReorderLevel').value = reorderLevel;
            document.getElementById('adminIdVerification').value = '';
            document.getElementById('adjustStockModal').classList.add('show');
       };
        window.closeAdjustStockModal = function () {
            const modal = document.getElementById('adjustStockModal');
            if (modal) {
                modal.classList.remove('show');
                document.getElementById('adjustStockForm').reset();
            }
        };

        window.saveStockAdjustment = async function () {
                const itemId = document.getElementById('adjustItemId').value;
                const newQty = parseInt(document.getElementById('adjustNewQty').value);
                const reorderLevel = parseInt(document.getElementById('adjustReorderLevel').value);
                const reason = document.getElementById('adjustReason').value.trim();
                const adminId = document.getElementById('adminIdVerification').value.trim();

                if (!itemId || isNaN(newQty) || newQty < 0 || isNaN(reorderLevel) || !adminId) {
                    alert('Please complete all required fields.');
                    return;
                }

                if (!confirm('Save this stock adjustment?')) return;

                try {
                    const response = await fetch('{{ route("admin.inventory.adjust") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ 
                            item_id: itemId,
                            new_quantity: newQty,
                            new_reorder_level: reorderLevel,
                            reason: reason,
                            admin_id: adminId
                        })
                    });

                    // If not OK, try to parse JSON error message, otherwise show status
                    if (!response.ok) {
                        const text = await response.text();
                        let message = `Request failed with status ${response.status}`;
                        try {
                            const json = JSON.parse(text);
                            message = json.message || message;
                        } catch (e) {
                            // leave message as-is
                        }
                        alert(message);
                        return;
                    }

                    const data = await response.json();
                    if (data && data.success) {
                        alert('Stock adjusted successfully!');
                        closeAdjustStockModal();
                        location.reload();
                    } else {
                        alert((data && data.message) ? data.message : 'Adjustment failed');
                    }
                } catch (err) {
                    console.error('Adjustment error:', err);
                    alert('Network or server error. Check console for details.');
                }
        };

        /* ================================================================
           2. EXPORT MODAL
           ================================================================ */
        window.openExportModal = function () {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('exportFileName').value = `inventory-report-${today}`;
            document.getElementById('exportModal').classList.add('show');
        };

        window.closeExportModal = function () {
            document.getElementById('exportModal')?.classList.remove('show');
        };

        window.executeExport = function () {
            const range = document.querySelector('input[name="exportRange"]:checked').value;
            const format = document.getElementById('exportFormat').value;
            const fileName = document.getElementById('exportFileName').value.trim() || 'inventory-report';

            if (format === 'csv') exportToCSV(range, fileName);
            else exportToPDF(range, fileName);

            closeExportModal();
        };

        /* ================================================================
           3. HISTORY MODAL
           ================================================================ */
        window.openHistoryModal = function (itemId, itemName) {
            document.getElementById('historyItemName').textContent = itemName;
            document.getElementById('historyModal').classList.add('show');
            switchHistoryTab('stock-movements');
            loadStockMovements(itemId);
            loadPurchaseHistory(itemId);
        };

        window.closeHistoryModal = function () {
            document.getElementById('historyModal')?.classList.remove('show');
        };

        window.switchHistoryTab = function (tab) {
            document.querySelectorAll('.history-tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.history-tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tab + '-tab').classList.add('active');
            document.querySelector(`[onclick="switchHistoryTab('${tab}')"]`).classList.add('active');
        };

        /* ================================================================
           BUTTON CONNECTIONS – Delegated & Safe
           ================================================================ */
        document.addEventListener('DOMContentLoaded', function () {
            // Export button
            document.getElementById('openExportBtn')?.addEventListener('click', openExportModal);

            // Adjust buttons
            document.getElementById('inventoryTableBody').addEventListener('click', e => {
                const adjustBtn = e.target.closest('.adjust-btn');
                if (!adjustBtn) return;

                const row = adjustBtn.closest('tr');
                const itemId = row.dataset.itemId;
                const itemName = row.querySelector('td:nth-child(2) strong').textContent.trim();
                const qtyText = row.querySelector('td:nth-child(4)').textContent.trim();
                const currentQty = parseInt(qtyText.replace(/[^\d]/g, '')) || 0;
                const reorderLevel = parseInt(row.querySelector('td:nth-child(5)').textContent) || 0;

                openAdjustStockModal(itemId, itemName, currentQty, reorderLevel);
            });

            // History buttons
            document.getElementById('inventoryTableBody').addEventListener('click', e => {
                const historyBtn = e.target.closest('.history-btn');
                if (!historyBtn) return;

                const row = historyBtn.closest('tr');
                const itemId = row.dataset.itemId;
                const itemName = row.querySelector('td:nth-child(2) strong').textContent.trim();

                openHistoryModal(itemId, itemName);
            });
        });

        // Initialize pagination on page load
        document.addEventListener('DOMContentLoaded', function () {
            setupPagination();

            // DEBUG: Check if modals exist in DOM
            console.log('🔍 DEBUG: Page loaded, checking for modals...');
            const adjustModal = document.getElementById('adjustStockModal');
            const historyModal = document.getElementById('historyModal');
            const exportModal = document.getElementById('exportModal');

            console.log('🔍 DEBUG: adjustStockModal exists?', adjustModal !== null);
            console.log('🔍 DEBUG: historyModal exists?', historyModal !== null);
            console.log('🔍 DEBUG: exportModal exists?', exportModal !== null);

            if (!adjustModal) console.error('❌ adjustStockModal NOT found in DOM!');
            if (!historyModal) console.error('❌ historyModal NOT found in DOM!');
            if (!exportModal) console.error('❌ exportModal NOT found in DOM!');
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', filterTable);

        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', filterTable);

        // Status filter
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        // Date filters
        document.getElementById('dateFromFilter').addEventListener('change', filterTable);
        document.getElementById('dateToFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFromFilter').value;
            const dateTo = document.getElementById('dateToFilter').value;

            const rows = document.querySelectorAll('#inventoryTableBody tr[data-item-id]');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const itemCategory = row.getAttribute('data-category');
                const statusCell = row.querySelector('td:nth-child(8)');
                const hasLowStockClass = statusCell && statusCell.querySelector('.text-status-low-stock');
                const itemStatus = hasLowStockClass ? 'low' : 'normal';
                const lastPurchaseText = row.getAttribute('data-last-purchase');

                const matchesSearch = text.includes(searchTerm);
                const matchesCategory = !category || itemCategory === category;
                const matchesStatus = !status || itemStatus === status;

                let matchesDate = true;
                if (dateFrom || dateTo) {
                    if (lastPurchaseText) {
                        const lastPurchaseDate = new Date(lastPurchaseText);
                        if (dateFrom) {
                            matchesDate = matchesDate && lastPurchaseDate >= new Date(dateFrom);
                        }
                        if (dateTo) {
                            matchesDate = matchesDate && lastPurchaseDate <= new Date(dateTo);
                        }
                    } else {
                        matchesDate = false;
                    }
                }

                const isVisible = matchesSearch && matchesCategory && matchesStatus && matchesDate;
                row.style.display = isVisible ? '' : 'none';
            });
        }

        // Export to CSV
        function exportToCSV() {
            const table = document.getElementById('inventoryTable');
            const rows = Array.from(table.querySelectorAll('tr:not([style*="display: none"])'));

            let csv = [];
            rows.forEach(row => {
                const cols = Array.from(row.querySelectorAll('th, td'));
                const rowData = cols.slice(0, -1).map(col => {
                    return '"' + col.textContent.trim().replace(/"/g, '""') + '"';
                });
                csv.push(rowData.join(','));
            });

            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'inventory_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Adjust Stock Modal Functions
        function openAdjustStock(itemId, itemName, currentQty, reorderLevel) {
            console.log('🔍 DEBUG: openAdjustStock called with:', { itemId, itemName, currentQty, reorderLevel });

            const modal = document.getElementById('adjustStockModal');
            console.log('🔍 DEBUG: Modal element found?', modal !== null);
            console.log('🔍 DEBUG: Modal element:', modal);

            if (!modal) {
                alert('ERROR: Modal element #adjustStockModal not found in DOM!');
                return;
            }

            document.getElementById('adjustItemId').value = itemId;
            document.getElementById('adjustItemName').value = itemName;
            document.getElementById('adjustCurrentQty').value = currentQty;
            document.getElementById('adjustNewQty').value = currentQty;
            document.getElementById('adjustReorderLevel').value = reorderLevel;
            document.getElementById('adjustReason').value = '';
            document.getElementById('adminIdVerification').value = '';

            console.log('🔍 DEBUG: About to add "show" class to modal');
            modal.classList.add('show');
            console.log('🔍 DEBUG: Modal classes after adding show:', modal.className);
            console.log('🔍 DEBUG: Modal display style:', window.getComputedStyle(modal).display);
        }

        function closeAdjustStockModal() {
            document.getElementById('adjustStockModal').classList.remove('show');
            document.getElementById('adjustStockForm').reset();
        }

        function saveStockAdjustment() {
            const itemId = document.getElementById('adjustItemId').value;
            const newQty = document.getElementById('adjustNewQty').value;
            const reorderLevel = document.getElementById('adjustReorderLevel').value;
            const reason = document.getElementById('adjustReason').value;
            const adminId = document.getElementById('adminIdVerification').value;

            // Validate inputs
            if (!newQty || !reorderLevel) {
                alert('Please fill in all required fields');
                return;
            }

            if (!adminId) {
                alert('Admin ID verification is required');
                return;
            }

            // TODO: Implement actual API call to save adjustment
            // For now, show confirmation
            if (confirm('Are you sure you want to save this adjustment?')) {
                // Simulate API call
                console.log('Saving adjustment:', {
                    itemId: itemId,
                    newQty: newQty,
                    reorderLevel: reorderLevel,
                    reason: reason,
                    adminId: adminId
                });

                alert('Stock adjustment saved successfully!');
                closeAdjustStockModal();

                // Reload page to see changes
                // window.location.reload();
            }
        }

        function viewMovements(itemId, itemName) {
            console.log('🔍 DEBUG: viewMovements called with:', { itemId, itemName });

            const modal = document.getElementById('historyModal');
            console.log('🔍 DEBUG: History modal element found?', modal !== null);
            console.log('🔍 DEBUG: History modal element:', modal);

            if (!modal) {
                alert('ERROR: Modal element #historyModal not found in DOM!');
                return;
            }

            document.getElementById('historyItemName').textContent = itemName;

            console.log('🔍 DEBUG: About to add "show" class to history modal');
            modal.classList.add('show');
            console.log('🔍 DEBUG: History modal classes after adding show:', modal.className);
            console.log('🔍 DEBUG: History modal display style:', window.getComputedStyle(modal).display);

            // Load stock movements
            loadStockMovements(itemId);
            // Load purchase history
            loadPurchaseHistory(itemId);
        }

        function closeHistoryModal() {
            document.getElementById('historyModal').classList.remove('show');
        }

        function switchHistoryTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.history-tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.history-tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        function loadStockMovements(itemId) {
            fetch('{{ url('admin/inventory/items') }}/' + itemId + '/movements')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('stockMovementsBody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #999;">No stock movements found</td></tr>';
                        return;
                    }

                    data.forEach(movement => {
                        const row = document.createElement('tr');
                        const date = new Date(movement.created_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        const movementType = movement.movement_type === 'in' ?
                            '<span style="color: #16a34a; font-weight: 600;">IN</span>' :
                            '<span style="color: #dc2626; font-weight: 600;">OUT</span>';

                        row.innerHTML = `
                                                                    <td>${date}</td>
                                                                    <td>${movementType}</td>
                                                                    <td>${movement.quantity}</td>
                                                                    <td>${movement.reason || '-'}</td>
                                                                    <td>${movement.notes || '-'}</td>
                                                                `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading stock movements:', error);
                    document.getElementById('stockMovementsBody').innerHTML =
                        '<tr><td colspan="5" style="text-align: center; color: #dc2626;">Error loading data</td></tr>';
                });
        }

        function loadPurchaseHistory(itemId) {
            fetch('{{ url('admin/inventory/items') }}/' + itemId + '/purchases')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('purchaseHistoryBody');
                    tbody.innerHTML = '';

                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #999;">No purchase history found</td></tr>';
                        return;
                    }

                    data.forEach(purchase => {
                        const row = document.createElement('tr');
                        const date = new Date(purchase.purchase_date).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });

                        const quantity = parseFloat(purchase.quantity) || 0;
                        const unitCost = parseFloat(purchase.unit_cost) || 0;
                        const totalCost = quantity * unitCost;

                        row.innerHTML = `
                                                                    <td>${date}</td>
                                                                    <td>${quantity.toFixed(2)}</td>
                                                                    <td>₱${unitCost.toFixed(2)}</td>
                                                                    <td>₱${totalCost.toFixed(2)}</td>
                                                                    <td>${purchase.supplier_name || '-'}</td>
                                                                `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error loading purchase history:', error);
                    document.getElementById('purchaseHistoryBody').innerHTML =
                        '<tr><td colspan="5" style="text-align: center; color: #dc2626;">Error loading data</td></tr>';
                });
        }

        // Export Modal Functions
        function openExportModal() {
            // Auto-generate filename
            const today = new Date();
            const dateStr = today.toISOString().split('T')[0];
            document.getElementById('exportFileName').value = `inventory-report-${dateStr}`;
            document.getElementById('exportModal').classList.add('show');
        }

        function closeExportModal() {
            document.getElementById('exportModal').classList.remove('show');
        }

        function executeExport() {
            const range = document.querySelector('input[name="exportRange"]:checked').value;
            const format = 'pdf'; // Always PDF
            const fileName = document.getElementById('exportFileName').value || 'inventory-report';

            exportToPDF(range, fileName);
        }

        function exportToCSV(range, fileName) {
            const table = document.getElementById('inventoryTable');
            let rows;

            if (range === 'current') {
                // Export only visible rows on current page
                rows = Array.from(table.querySelectorAll('tr')).filter(row => {
                    return row.style.display !== 'none' && row.querySelector('td');
                });
            } else if (range === 'filtered') {
                // Export all rows matching current filters
                rows = Array.from(table.querySelectorAll('tbody tr[data-item-id]')).filter(row => {
                    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                    const category = document.getElementById('categoryFilter').value;
                    const status = document.getElementById('statusFilter').value;

                    const text = row.textContent.toLowerCase();
                    const itemCategory = row.getAttribute('data-category');
                    const statusCell = row.querySelector('td:nth-child(8)');
                    const hasLowStockClass = statusCell && statusCell.querySelector('.text-status-low-stock');
                    const itemStatus = hasLowStockClass ? 'low' : 'normal';

                    const matchesSearch = text.includes(searchTerm);
                    const matchesCategory = !category || itemCategory === category;
                    const matchesStatus = !status || itemStatus === status;

                    return matchesSearch && matchesCategory && matchesStatus;
                });
            } else {
                // Export all rows
                rows = Array.from(table.querySelectorAll('tbody tr[data-item-id]'));
            }

            // Add header row
            const headerRow = table.querySelector('thead tr');
            const allRows = [headerRow, ...rows];

            let csv = [];
            allRows.forEach(row => {
                const cols = Array.from(row.querySelectorAll('th, td'));
                const rowData = cols.slice(0, -1).map(col => {
                    return '"' + col.textContent.trim().replace(/"/g, '""').replace(/\s+/g, ' ') + '"';
                });
                csv.push(rowData.join(','));
            });

            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = fileName + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function exportToPDF(range, fileName) {
            // Get the export button
            const exportBtn = document.querySelector('#exportModal .btn-primary');
            const originalText = exportBtn.innerHTML;
            
            // Show loading state with spinner
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
            exportBtn.disabled = true;

            // Prepare filter data
            const searchTerm = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFromFilter').value;
            const dateTo = document.getElementById('dateToFilter').value;

            // Build form data
            const formData = new FormData();
            formData.append('range', range);
            formData.append('fileName', fileName);
            formData.append('search', searchTerm);
            formData.append('category', category);
            formData.append('status', status);
            formData.append('dateFrom', dateFrom);
            formData.append('dateTo', dateTo);

            // Make API request
            fetch('{{ route("admin.inventory.export-pdf") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Export failed');
                    }
                    return response.blob();
                })
                .then(blob => {
                    // Create download link
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = fileName + '.pdf';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);

                    // Close modal after successful export
                    closeExportModal();

                    // Show success message
                    alert('PDF exported successfully!');
                })
                .catch(error => {
                    console.error('PDF export error:', error);
                    alert('Failed to export PDF. Please try again.');
                    
                    // Reset button on error
                    exportBtn.innerHTML = originalText;
                    exportBtn.disabled = false;
                })
                .finally(() => {
                    // Reset button if still loading (only if not already closed)
                    if (document.getElementById('exportModal').classList.contains('show')) {
                        exportBtn.innerHTML = originalText;
                        exportBtn.disabled = false;
                    }
                });
        }

        // Check URL params for filters
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get('filter') === 'low_stock') {
                document.getElementById('statusFilter').value = 'low';
                filterTable();
            }

            const category = urlParams.get('category');
            if (category) {
                document.getElementById('categoryFilter').value = category;
                filterTable();
            }
        });
    </script>
@endsection