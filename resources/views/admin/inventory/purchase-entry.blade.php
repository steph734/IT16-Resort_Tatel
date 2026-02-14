@extends('layouts.admin')

@section('title', 'New Purchase Entry')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/inventory/purchase-entry.css') }}">
@endsection

@section('content')
                <div class="main-content">
                    <div class="page-header">
                        <button class="btn-back" onclick="window.location.href='{{ route('admin.inventory.purchases') }}'"
                            title="Back to Purchases">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                        <h1 class="page-title">New Purchase Entry</h1>
                        <p class="page-subtitle">Record inventory purchases and update stock levels</p>
                    </div>

                    <!-- Toggle Navigation -->
                    <div class="entry-toggle">
                        <button class="toggle-option active" onclick="switchEntryMode('standard')">
                            <i class="fas fa-file-invoice"></i> Standard Entry
                        </button>
                        <button class="toggle-option" onclick="switchEntryMode('quick')">
                            <i class="fas fa-bolt"></i> Quick Entry
                        </button>
                    </div>

                    <!-- Entry Content -->
                    <div class="entry-content">
                        <!-- Standard Entry Mode -->
                        <div id="standard-entry" class="entry-mode active">
                            <div class="entry-card">
                                <div class="info-banner">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <strong>Standard Entry Mode:</strong> Complete purchase entry with detailed tracking.
                                        Perfect for maintaining comprehensive purchase records with vendor information and item-by-item
                                        breakdown.
                                    </div>
                                </div>

                                <form id="standardEntryForm">
                                    <div class="form-row form-row-3">
                                        <div class="form-group">
                                            <label for="purchaseDate">Purchase Date *</label>
                                            <input type="date" id="purchaseDate" name="purchase_date" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="receiptNo">Receipt No.</label>
                                            <input type="text" id="receiptNo" name="receipt_no" class="form-control"
                                                placeholder="Optional" oninput="capitalizeAllCaps(this)">
                                        </div>
                                    <div class="form-group">
                                        <label for="vendorName">Vendor Name *</label>
                                        <input type="text" id="vendorName" name="vendor_name" class="form-control" placeholder="e.g., Manila Suppliers Inc."
                                            required oninput="capitalizeProper(this)">
                                    </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="notes">Notes (Optional)</label>
                                        <textarea id="notes" name="notes" class="form-control" rows="2"
                                            placeholder="Additional notes about this purchase..."></textarea>
                                    </div>

                                    <div class="form-section">
                                        <div class="section-header">
                                            <h3>Purchase Items</h3>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="addItemRow()">
                                                <i class="fas fa-plus"></i> Add Item
                                            </button>
                                        </div>

                                        <div class="items-table-container">
                                            <table class="items-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 20%;">Category *</th>
                                                        <th style="width: 35%;">Item *</th>
                                                        <th style="width: 15%;">Quantity *</th>
                                                        <th style="width: 18%;">Unit Cost *</th>
                                                        <th style="width: 18%;">Subtotal</th>
                                                        <th style="width: 4%;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="itemsTableBody">
                                                    <!-- Rows added via JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="total-section">
                                            <div class="total-row">
                                                <span class="total-label">Total Amount:</span>
                                                <span class="total-value" id="totalAmount">₱0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <button type="button" class="btn btn-secondary"
                                            onclick="window.location.href='{{ route('admin.inventory.purchases') }}'">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                        <button type="button" class="btn btn-primary" onclick="submitStandardEntry()">
                                            <i class="fas fa-save"></i> Save Purchase
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>


                        <!-- Quick Entry Mode -->
                        <div id="quick-entry" class="entry-mode">
                            <div class="entry-card">
                                <div class="info-banner info-banner-warning">
                                    <i class="fas fa-lightbulb"></i>
                                    <div>
                                        <strong>Quick Entry Mode:</strong> Quickly add multiple purchases without detailed tracking.
                                        Perfect for daily stock replenishment from regular vendors.
                                    </div>
                                </div>

                                <form id="quickEntryForm">
                                    <div class="form-row form-row-3">
                                        <div class="form-group">
                                            <label for="quickDate">Date *</label>
                                            <input type="date" id="quickDate" name="date" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="quickReceiptNo">Receipt No.</label>
                                            <input type="text" id="quickReceiptNo" name="receipt_no" class="form-control"
                                                placeholder="Optional" oninput="capitalizeAllCaps(this)">
                                        </div>
                                    <div class="form-group">
                                        <label for="quickVendor">Vendor *</label>
                                        <input type="text" id="quickVendor" name="vendor" class="form-control" placeholder="e.g., Manila Suppliers Inc."
                                            required oninput="capitalizeProper(this)">
                                    </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="quickNotes">Notes (Optional)</label>
                                        <textarea id="quickNotes" name="notes" class="form-control" rows="2"
                                            placeholder="Additional notes about this purchase..."></textarea>
                                    </div>

                                    <div class="form-section">
                                        <div class="section-header">
                                            <h3>Select Items to Purchase</h3>
                                            <p class="section-subtitle">Check items and enter quantity and cost</p>
                                        </div>

                                    <div class="form-group" style="margin-bottom: 1.5rem;">
                                        <input type="text" id="quickSearchInput" class="form-control" placeholder="🔍 Search Items By Name Or SKU..."
                                            onkeyup="searchQuickItems()">
                                    </div>

                                        @php
    $groupedItems = $inventoryItems->groupBy('category');
    $categoryLabels = [
        'cleaning' => 'Cleaning Supplies',
        'kitchen' => 'Kitchen Supplies',
        'amenity' => 'Amenities',
        'rental_item' => 'Rental Items'
    ];
                                        @endphp

                                        @foreach(['cleaning', 'kitchen', 'amenity', 'rental_item'] as $category)
                                            @if($groupedItems->has($category) && $groupedItems[$category]->count() > 0)
                                                <div class="quick-category-section" data-category="{{ $category }}">
                                                    <h4 class="quick-category-title">
                                                        <i
                                                            class="fas fa-{{ $category === 'cleaning' ? 'spray-can' : ($category === 'kitchen' ? 'utensils' : ($category === 'amenity' ? 'gift' : 'box')) }}"></i>
                                                        {{ $categoryLabels[$category] }}
                                                        <span class="quick-category-count">({{ $groupedItems[$category]->count() }}
                                                            items)</span>
                                                    </h4>
                                                    <div class="quick-entry-grid-wrapper">
                                                        <div class="quick-entry-grid">
                                                            @foreach($groupedItems[$category] as $item)
                                                                <div class="quick-item-card" data-item-name="{{ strtolower($item->name) }}"
                                                                    data-item-sku="{{ strtolower($item->sku) }}">
                                                                    <div class="quick-item-header">
                                                                        <input type="checkbox" id="quick-{{ $item->sku }}" class="quick-checkbox"
                                                                            onchange="toggleQuickItem({{ json_encode($item->sku) }})">
                                                                        <label for="quick-{{ $item->sku }}" class="quick-item-name">
                                                                            <strong>{{ $item->name }}</strong>
                                                                            <span class="quick-item-sku">{{ $item->sku }}</span>
                                                                        </label>
                                                                    </div>
                                                                    <div class="quick-item-inputs" id="inputs-{{ $item->sku }}">
                                                                        <div class="quick-input-group">
                                                                            <label>Qty</label>
                                                                            <input type="number" name="items[{{ $item->sku }}][quantity]"
                                                                                class="form-control-sm" min="1" step="1" placeholder="0">
                                                                        </div>
                                                                        <div class="quick-input-group">
                                                                            <label>Cost (₱)</label>
                                                                            <input type="number" name="items[{{ $item->sku }}][cost]"
                                                                                class="form-control-sm" min="0" step="0.01" placeholder="0.00">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach

                                        @if($inventoryItems->count() === 0)
                                            <div class="empty-state">
                                                <i class="fas fa-box-open"></i>
                                                <p>No inventory items available yet.</p>
                                                <p class="empty-state-subtitle">Add items using Standard Entry first.</p>
                                            </div>
                                        @endif

                                        <div id="noSearchResults" class="empty-state search-no-results">
                                            <i class="fas fa-search"></i>
                                            <p>No items found matching your search.</p>
                                            <p class="empty-state-subtitle">Try a different search term.</p>
                                        </div>
                                    </div>

                                    <!-- Quick Entry Summary Table -->
                                    <div class="form-section">
                                        <div class="section-header">
                                            <h3>Selected Items Summary</h3>
                                        </div>
                                        <div id="quickSummaryTableContainer" class="quick-summary-table-container">
                                            <div class="items-table-container">
                                                <table class="items-table quick-summary-table">
                                                    <thead>
                                                        <tr>
                                                            <th class="summary-col-item">Item Name</th>
                                                            <th class="summary-col-qty">Quantity</th>
                                                            <th class="summary-col-cost">Unit Cost (₱)</th>
                                                            <th class="summary-col-subtotal">Subtotal (₱)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="quickSummaryTableBody">
                                                        <!-- Summary rows added via JavaScript -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="total-section">
                                                <div class="total-row">
                                                    <span class="total-label">Total Amount:</span>
                                                    <span class="total-value" id="quickSummaryTotal">₱0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="quickSummaryEmpty" class="empty-state quick-summary-empty">
                                            <p class="summary-empty-text">No items selected yet</p>
                                            <p class="empty-state-subtitle">Check items above to add them to your purchase</p>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <button type="button" class="btn btn-secondary" onclick="clearQuickEntry()">
                                            <i class="fas fa-trash"></i> Clear
                                        </button>
                                        <button type="button" class="btn btn-primary" onclick="submitQuickEntry()">
                                            <i class="fas fa-bolt"></i> Quick Save All
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation Modal -->
                <div class="modal" id="confirmationModal">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title">Confirm Purchase Entry</h3>
                                <button type="button" class="modal-close" onclick="closeConfirmationModal()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="confirmation-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <p><strong>Please review your purchase entry carefully.</strong></p>
                                    <p>Are you sure all the information is accurate?</p>
                                </div>

                                <div id="confirmationSummary" class="confirmation-summary">
                                    <!-- Summary will be injected here -->
                                </div>

                                <div class="form-group verification-group">
                                    <label for="userIdVerification">Verify your User ID *</label>
                                    <input type="text" id="userIdVerification" class="form-control"
                                        placeholder="Enter your user ID to confirm" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="closeConfirmationModal()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="button" class="btn btn-primary" onclick="confirmAndSubmit()">
                                    <i class="fas fa-check"></i> Confirm & Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
@endsection

@section('scripts')
        <script>
            // Available inventory items for dropdown
            const inventoryItems = @json($inventoryItems);
            const currentUserId = "{{ Auth::user()->user_id }}";
            let pendingSubmission = null;

            // Initialize
            document.addEventListener('DOMContentLoaded', function () {
                // Set today's date as default
                document.getElementById('purchaseDate').valueAsDate = new Date();
                document.getElementById('quickDate').valueAsDate = new Date();

                // Add first item row for standard entry
                addItemRow();

                // Initialize empty summary state
                updateQuickSummary();
            });

            // Toggle between entry modes
            function switchEntryMode(mode) {
                // Update toggle buttons
                document.querySelectorAll('.toggle-option').forEach(btn => {
                    btn.classList.remove('active');
                });
                event.target.closest('.toggle-option').classList.add('active');

                // Update entry modes
                document.querySelectorAll('.entry-mode').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(mode + '-entry').classList.add('active');
            }

            // Standard Entry Functions
            let itemRowCounter = 0;
            function addItemRow(category = '', sku = '', itemName = '', quantity = '', unitCost = '') {
                itemRowCounter++;
                const row = document.createElement('tr');
                row.id = `item-row-${itemRowCounter}`;

                row.innerHTML = `
                                    <td>
                                        <select name="items[${itemRowCounter}][category]" 
                                                class="form-control category-select" 
                                                required>
                                            <option value="">Select...</option>
                                            <option value="cleaning" ${category === 'cleaning' ? 'selected' : ''}>Cleaning</option>
                                            <option value="kitchen" ${category === 'kitchen' ? 'selected' : ''}>Kitchen</option>
                                            <option value="amenity" ${category === 'amenity' ? 'selected' : ''}>Amenity</option>
                                            <option value="rental_item" ${category === 'rental_item' ? 'selected' : ''}>Rental Item</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div style="position: relative;">
                                            <input type="text" 
                                                   id="itemSearch-${itemRowCounter}"
                                                   class="form-control item-search-input" 
                                                   placeholder="Search item name or SKU..." 
                                                   value="${itemName}" 
                                                   required 
                                                   oninput="capitalizeProper(this); searchItem(${itemRowCounter})">
                                            <div id="itemSearchResults-${itemRowCounter}" class="item-search-results"></div>
                                            <input type="hidden" id="itemSelect-${itemRowCounter}" name="items[${itemRowCounter}][inventory_item_id]" value="">
                                            <input type="hidden" id="itemName-${itemRowCounter}" name="items[${itemRowCounter}][item_name]" value="${itemName}">
                                            <input type="hidden" id="itemCategory-${itemRowCounter}" value="${category}">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" name="items[${itemRowCounter}][quantity]" class="form-control" 
                                               min="1" step="1" value="${quantity}" required onchange="updateSubtotal(${itemRowCounter})" 
                                               placeholder="0">
                                    </td>
                                    <td>
                                        <input type="number" name="items[${itemRowCounter}][unit_cost]" class="form-control" 
                                               min="0" step="0.01" value="${unitCost}" required onchange="updateSubtotal(${itemRowCounter})" 
                                               placeholder="0.00">
                                    </td>
                                    <td>
                                        <span class="subtotal-display" id="subtotal-${itemRowCounter}">₱0.00</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-icon-danger" onclick="removeItemRow(${itemRowCounter})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                `;

                document.getElementById('itemsTableBody').appendChild(row);

                if (quantity && unitCost) {
                    updateSubtotal(itemRowCounter);
                }
            }
        function capitalizeProper(element) {
            if (!element) return;

            const start = element.selectionStart;
            const end = element.selectionEnd;
            let value = element.value;

            // Only capitalize lowercase letters at word boundaries
            // This allows manual capitalization (e.g., Region XI stays as XI)
            value = value.replace(/\b[a-z]/g, char => char.toUpperCase());

            element.value = value;
            element.setSelectionRange(start, end);
        }

        // Capitalize all letters (for receipt numbers)
        function capitalizeAllCaps(element) {
            if (!element) return;

            const start = element.selectionStart;
            const end = element.selectionEnd;
            element.value = element.value.toUpperCase();
            element.setSelectionRange(start, end);
        }
            // Search items by name or SKU
            function searchItem(rowId) {
                const searchInput = document.getElementById(`itemSearch-${rowId}`);
                const resultsDiv = document.getElementById(`itemSearchResults-${rowId}`);
                const term = searchInput.value.toLowerCase();

                if (!term) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                let html = '';
                let count = 0;

                inventoryItems.forEach(item => {
                    const itemName = item.name.toLowerCase();
                    const itemSku = item.sku.toLowerCase();

                    if (itemName.includes(term) || itemSku.includes(term)) {
                        html += `<div class="search-result-item" onclick="selectStandardItem(${rowId}, '${item.sku}', '${item.name.replace(/'/g, "\\'")}', '${item.category}')">
                                                        <div class="search-result-name">${item.name}</div>
                                                        <div class="search-result-sku">SKU: ${item.sku}</div>
                                                     </div>`;
                        count++;
                    }
                });

                resultsDiv.innerHTML = count ? html : '<div style="padding:1rem;color:#9ca3af;text-align:center;">No items found</div>';
                resultsDiv.style.display = 'block';
            }

            // Select item from search results
    function selectStandardItem(rowId, sku, name, category) {
            const trimmedName = name.trim();
            const formattedName = trimmedName
                ? trimmedName.charAt(0).toUpperCase() + trimmedName.slice(1).toLowerCase()
                : '';

            document.getElementById(`itemSearch-${rowId}`).value = formattedName;
            document.getElementById(`itemSelect-${rowId}`).value = sku;
            document.getElementById(`itemName-${rowId}`).value = formattedName;
            document.getElementById(`itemCategory-${rowId}`).value = category;

            document.getElementById(`itemSearchResults-${rowId}`).style.display = 'none';

            const row = document.getElementById(`item-row-${rowId}`);
            const categorySelect = row.querySelector('select[name*="[category]"]');
            if (categorySelect) {
                categorySelect.value = category;
            }
        }
            // Close search results when clicking outside
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.item-search-input') && !e.target.closest('.item-search-results')) {
                    document.querySelectorAll('.item-search-results').forEach(div => {
                        div.style.display = 'none';
                    });
                }
            });

            function removeItemRow(rowId) {
                const row = document.getElementById(`item-row-${rowId}`);
                if (row) {
                    row.remove();
                    calculateTotal();
                }
            }

            function updateSubtotal(rowId) {
                const row = document.getElementById(`item-row-${rowId}`);
                const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
                const unitCost = parseFloat(row.querySelector('input[name*="[unit_cost]"]').value) || 0;
                const subtotal = quantity * unitCost;

                row.querySelector(`#subtotal-${rowId}`).textContent = '₱' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                calculateTotal();
            }

            function calculateTotal() {
                let total = 0;
                document.querySelectorAll('.subtotal-display').forEach(el => {
                    const value = parseFloat(el.textContent.replace('₱', '').replace(/,/g, '')) || 0;
                    total += value;
                });

                document.getElementById('totalAmount').textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // Quick Entry Functions
            function toggleQuickItem(itemId) {
                const checkbox = document.getElementById(`quick-${itemId}`);
                const inputs = document.getElementById(`inputs-${itemId}`);

                if (checkbox.checked) {
                    inputs.classList.add('show');
                    const qtyInput = inputs.querySelector('input[name*="[quantity]"]');
                    const costInput = inputs.querySelector('input[name*="[cost]"]');

                    // Add event listeners to these inputs if not already added
                    if (!qtyInput.dataset.listenerAttached) {
                        qtyInput.addEventListener('change', updateQuickSummary);
                        qtyInput.addEventListener('input', updateQuickSummary);
                        qtyInput.dataset.listenerAttached = 'true';
                    }

                    if (!costInput.dataset.listenerAttached) {
                        costInput.addEventListener('change', updateQuickSummary);
                        costInput.addEventListener('input', updateQuickSummary);
                        costInput.dataset.listenerAttached = 'true';
                    }

                    setTimeout(() => qtyInput.focus(), 100);
                } else {
                    inputs.classList.remove('show');
                    // Don't clear the inputs when unchecking - user might want to re-check
                    // inputs.querySelectorAll('input').forEach(input => input.value = '');
                }

                // Update summary table
                updateQuickSummary();
            }

            // Update Quick Entry Summary Table
            function updateQuickSummary() {
                const checkedItems = document.querySelectorAll('.quick-checkbox:checked');
                const summaryTableContainer = document.getElementById('quickSummaryTableContainer');
                const summaryEmpty = document.getElementById('quickSummaryEmpty');
                const summaryBody = document.getElementById('quickSummaryTableBody');

                summaryBody.innerHTML = '';
                let hasItems = false;
                let totalAmount = 0;

                checkedItems.forEach(checkbox => {
                    const itemId = checkbox.id.replace('quick-', '');

                    // Get the inputs from within the inputs container for this specific item
                    const inputsContainer = document.getElementById(`inputs-${itemId}`);
                    if (!inputsContainer) {
                        console.warn(`Inputs container not found for item ${itemId}`);
                        return;
                    }

                    const qtyInput = inputsContainer.querySelector('input[name*="[quantity]"]');
                    const costInput = inputsContainer.querySelector('input[name*="[cost]"]');

                    // Check if inputs exist before trying to access their values
                    if (!qtyInput || !costInput) {
                        console.warn(`Inputs not found for item ${itemId}`);
                        return;
                    }

                    // Read values and ensure they're treated as strings first, then parsed
                    const qtyValue = qtyInput.value.trim();
                    const costValue = costInput.value.trim();

                    const quantity = qtyValue ? parseInt(qtyValue) : 0;
                    const cost = costValue ? parseFloat(costValue) : 0;

                    if (quantity > 0 && cost >= 0) {
                        const item = inventoryItems.find(i => i.sku == itemId);

                        const subtotal = quantity * cost;
                        totalAmount += subtotal;

                        const row = document.createElement('tr');
                        row.innerHTML = `
                                            <td class="summary-cell-item">${item ? item.name : 'Unknown'}</td>
                                            <td class="summary-cell-qty">${quantity}</td>
                                            <td class="summary-cell-cost">${cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                            <td class="summary-cell-subtotal">${subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                        `;
                        summaryBody.appendChild(row);
                        hasItems = true;
                    }
                });

                if (hasItems) {
                    summaryTableContainer.classList.add('show');
                    summaryEmpty.classList.remove('show');
                    document.getElementById('quickSummaryTotal').textContent = '₱' + totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else {
                    summaryTableContainer.classList.remove('show');
                    summaryEmpty.classList.add('show');
                }
            }

            // Clear Quick Entry with confirmation
            function clearQuickEntry() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Clear All Items?',
                    text: 'This will uncheck all selected items. Are you sure?',
                    showCancelButton: true,
                    confirmButtonColor: '#284B53',
                    cancelButtonColor: '#d1d5db',
                    confirmButtonText: 'Yes, Clear',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Uncheck all checkboxes and hide inputs
                        document.querySelectorAll('.quick-checkbox').forEach(checkbox => {
                            checkbox.checked = false;
                            const itemId = checkbox.id.replace('quick-', '');
                            const inputs = document.getElementById(`inputs-${itemId}`);
                            inputs.classList.remove('show');
                            inputs.querySelectorAll('input').forEach(input => input.value = '');
                        });

                        // Update summary
                        updateQuickSummary();
                    }
                });
            }

            // Search Quick Items
            function searchQuickItems() {
                const searchTerm = document.getElementById('quickSearchInput').value.toLowerCase();
                const categories = document.querySelectorAll('.quick-category-section');
                let totalVisibleItems = 0;

                categories.forEach(category => {
                    const items = category.querySelectorAll('.quick-item-card');
                    let visibleItemsInCategory = 0;

                    items.forEach(item => {
                        const itemName = item.getAttribute('data-item-name');
                        const itemSku = item.getAttribute('data-item-sku');
                        const matches = itemName.includes(searchTerm) || itemSku.includes(searchTerm);

                        item.style.display = matches ? '' : 'none';
                        if (matches) visibleItemsInCategory++;
                    });

                    // Hide category if no items match
                    category.style.display = visibleItemsInCategory > 0 ? '' : 'none';
                    totalVisibleItems += visibleItemsInCategory;
                });

                // Show "no results" message if nothing found
                const noResults = document.getElementById('noSearchResults');
                if (totalVisibleItems === 0 && searchTerm.length > 0) {
                    noResults.style.display = 'block';
                } else {
                    noResults.style.display = 'none';
                }
            }

            // Submit Standard Entry
            function submitStandardEntry() {
                const form = document.getElementById('standardEntryForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const formData = new FormData(form);
                const data = {
                    purchase_date: formData.get('purchase_date'),
                    vendor_name: formData.get('vendor_name'),
                    receipt_no: formData.get('receipt_no') || null,
                    notes: formData.get('notes'),
                    items: []
                };

                // Collect items
                document.querySelectorAll('#itemsTableBody tr').forEach(row => {
                    const rowId = row.id.replace('item-row-', '');
                    const category = row.querySelector('select[name*="[category]"]').value;
                    const itemName = document.getElementById(`itemName-${rowId}`)?.value || document.getElementById(`itemSearch-${rowId}`)?.value.trim();
                    const itemId = document.getElementById(`itemSelect-${rowId}`)?.value;
                    const quantity = row.querySelector('input[name*="[quantity]"]').value;
                    const unitCost = row.querySelector('input[name*="[unit_cost]"]').value;

                    if (category && itemName && quantity && unitCost) {
                        const itemData = {
                            category: category,
                            item_name: itemName,
                            quantity: parseInt(quantity),
                            unit_cost: parseFloat(unitCost)
                        };

                        // Include inventory_item_id if item already exists
                        if (itemId) {
                            itemData.inventory_item_id = itemId;
                        }

                        data.items.push(itemData);
                    }
                });

                if (data.items.length === 0) {
                    alert('Please add at least one item to the purchase');
                    return;
                }

                // Show confirmation modal
                showConfirmationModal(data, 'standard');
            }

            // Submit Quick Entry
            function submitQuickEntry() {
                const form = document.getElementById('quickEntryForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const formData = new FormData(form);
                const data = {
                    purchase_date: formData.get('date'),
                    vendor_name: formData.get('vendor'),
                    receipt_no: formData.get('receipt_no') || null,
                    notes: formData.get('notes') || 'Quick entry',
                    items: []
                };

                // Collect checked items
                document.querySelectorAll('.quick-checkbox:checked').forEach(checkbox => {
                    const itemId = checkbox.id.replace('quick-', '');

                    // Get inputs from the specific container for this item
                    const inputsContainer = document.getElementById(`inputs-${itemId}`);
                    if (!inputsContainer) {
                        console.warn(`Inputs container not found for item ${itemId}`);
                        return;
                    }

                    const qtyInput = inputsContainer.querySelector('input[name*="[quantity]"]');
                    const costInput = inputsContainer.querySelector('input[name*="[cost]"]');

                    if (!qtyInput || !costInput) {
                        console.warn(`Inputs not found for item ${itemId}`);
                        return;
                    }

                    const qtyValue = qtyInput.value.trim();
                    const costValue = costInput.value.trim();

                    const quantity = qtyValue ? parseInt(qtyValue) : 0;
                    const cost = costValue ? parseFloat(costValue) : 0;

                    if (quantity > 0 && cost >= 0) {
                        const item = inventoryItems.find(i => i.sku == itemId);
                        data.items.push({
                            category: item ? item.category : 'cleaning',
                            inventory_item_id: itemId,
                            item_name: item ? item.name : 'Unknown',
                            quantity: quantity,
                            unit_cost: cost
                        });
                    }
                });

                if (data.items.length === 0) {
                    alert('Please select at least one item with quantity and cost');
                    return;
                }

                // Show confirmation modal
                showConfirmationModal(data, 'quick');
            }

            // Show Confirmation Modal
            function showConfirmationModal(data, entryMode) {
                pendingSubmission = { data, entryMode };

                // Build summary
                const totalAmount = data.items.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
                const summaryHTML = `
                                    <div class="summary-item">
                                        <span class="summary-label">Vendor:</span>
                                        <span class="summary-value">${data.vendor_name}</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Date:</span>
                                        <span class="summary-value">${new Date(data.purchase_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">Total Items:</span>
                                        <span class="summary-value">${data.items.length}</span>
                                    </div>
                                    <div class="summary-item summary-total">
                                        <span class="summary-label">Total Amount:</span>
                                        <span class="summary-value">₱${totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                    </div>
                                `;

                document.getElementById('confirmationSummary').innerHTML = summaryHTML;
                document.getElementById('userIdVerification').value = '';
                document.getElementById('confirmationModal').classList.add('show');
            }

            function closeConfirmationModal() {
                document.getElementById('confirmationModal').classList.remove('show');
                pendingSubmission = null;
            }

            function confirmAndSubmit() {
                const userId = document.getElementById('userIdVerification').value.trim();

                if (!userId) {
                    alert('Please enter your User ID');
                    return;
                }

                if (userId !== currentUserId) {
                    alert('User ID does not match. Please enter your correct User ID.');
                    return;
                }

                if (!pendingSubmission) {
                    alert('No pending submission found');
                    return;
                }

                // Show loading state
                const confirmBtn = event.target;
                const originalText = confirmBtn.innerHTML;
                confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                confirmBtn.disabled = true;

                // Submit via AJAX
                fetch('/admin/inventory/purchases', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(pendingSubmission.data)
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Purchase Entry Saved!',
                                text: `${pendingSubmission.data.items.length} items added successfully.`,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            }).then(() => {
                                // Redirect with entry ID to highlight
                                window.location.href = '{{ route("admin.inventory.purchases") }}?highlight=' + result.purchase.id;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to Save',
                                text: result.message || 'Failed to save purchase',
                                confirmButtonColor: '#284B53'
                            });
                            confirmBtn.innerHTML = originalText;
                            confirmBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to save purchase. Please try again.',
                            confirmButtonColor: '#284B53'
                        });
                        confirmBtn.innerHTML = originalText;
                        confirmBtn.disabled = false;
                    });
            }
        </script>
@endsection