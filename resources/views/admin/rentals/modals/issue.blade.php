<!-- Issue Rental Modal -->
<div id="issueRentalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Issue Rental</h2>
            <button class="modal-close" onclick="closeIssueRentalModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="issueRentalForm">
                @csrf
                <div class="form-group">
                    <label for="issueBookingId">Booking ID <span class="required">*</span></label>

                    @php
                        $stayingBooking = \DB::table('bookings')
                            ->where('BookingStatus', 'Staying')
                            ->orderBy('BookingID', 'desc')
                            ->first();
                    @endphp

                    <input type="text" 
                           id="issueBookingId" 
                           name="booking_id" 
                           class="form-input" 
                           value="{{ $stayingBooking ? $stayingBooking->BookingID : '' }}" 
                           readonly 
                           required
                           style="background-color: #f3f4f6; font-weight: 600; color: #1f2937; cursor: not-allowed;">

                    <small class="form-hint" style="color: #059669; font-weight: 500;">
                        <i class="fas fa-info-circle"></i> Currently staying guest's booking ID
                    </small>

                    @if(!$stayingBooking)
                        <small class="form-hint" style="color: #ef4444; font-weight: 500; display: block; margin-top: 4px;">
                            <i class="fas fa-exclamation-triangle"></i> No guests currently staying
                        </small>
                    @endif
                </div>
                <div class="form-group">
                    <label for="issueItemId">Rental Item <span class="required">*</span></label>
                    <input type="hidden" id="issueItemId" name="rental_item_id" required>
                    <input type="text" 
                           id="itemSearchInput" 
                           class="form-input" 
                           placeholder="Search by item name or SKU..." 
                           autocomplete="off">
                    <div id="itemSearchResults" 
                         style="display: none; 
                                position: relative; 
                                max-height: 150px; 
                                overflow-y: auto; 
                                border: 1px solid #d1d5db; 
                                border-radius: 6px; 
                                background: white; 
                                margin-top: 4px; 
                                box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
                                z-index: 10;">
                        <!-- Search results will appear here -->
                    </div>
                    <div id="itemAvailability" class="item-availability" style="margin-top: 5px; font-size: 0.9em;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="issueQuantity">Quantity <span class="required">*</span></label>
                        <input type="number" id="issueQuantity" name="quantity" class="form-input" min="1" value=""
                            placeholder="Enter quantity" required>
                        <small class="form-hint" id="quantityHint">Enter quantity to issue</small>
                    </div>
                    <div class="form-group">
                        <label>Rate Information</label>
                        <div class="rate-display" id="rateDisplay">
                            <span class="rate-amount">—</span>
                            <span class="rate-type-label"></span>
                        </div>
                        <div id="totalCostDisplay" class="total-cost-display"
                            style="margin-top: 8px; font-weight: 600;">
                            Total: <span id="totalCostAmount">₱0.00</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="issueNotes">Notes</label>
                    <textarea id="issueNotes" name="notes" class="form-input" rows="3"
                        placeholder="Optional notes about this rental..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeIssueRentalModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitRentalBtn">Issue Rental</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Global variables
    let currentItemRate = 0;
    let currentRateType = '';

    // Store all rental items globally
    let allRentalItems = [];

    document.addEventListener('DOMContentLoaded', function () {
        // Item search functionality
        const itemSearchInput = document.getElementById('itemSearchInput');
        const resultsDiv = document.getElementById('itemSearchResults');

        if (itemSearchInput) {
            itemSearchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase().trim();
                
                if (searchTerm.length === 0) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                // Filter items by name or SKU
                const filtered = allRentalItems.filter(item => {
                    const name = item.name.toLowerCase();
                    const code = (item.code || '').toLowerCase();
                    const sku = (item.sku || '').toLowerCase();
                    return name.includes(searchTerm) || code.includes(searchTerm) || sku.includes(searchTerm);
                });

                if (filtered.length > 0) {
                    let html = '';
                    filtered.forEach(item => {
                        const availText = item.available_quantity > 0 
                            ? `${item.available_quantity} available` 
                            : 'Out of stock';
                        const availColor = item.available_quantity > 0 ? '#10b981' : '#ef4444';
                        
                        html += `
                            <div onclick="selectRentalItem(${item.id}, '${item.name.replace(/'/g, "\\'")}', '${(item.sku || item.code).replace(/'/g, "\\'")}', ${item.rate || 0}, '${item.rate_type || 'per use'}', ${item.available_quantity || 0})" 
                                 style="padding: 12px; 
                                        cursor: pointer; 
                                        border-bottom: 1px solid #e5e7eb;
                                        transition: background-color 0.15s;"
                                 onmouseover="this.style.backgroundColor='#f3f4f6'" 
                                 onmouseout="this.style.backgroundColor='white'">
                                <div style="font-weight: 500; color: #111827; margin-bottom: 4px;">${item.name}</div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 11px; color: #6b7280;">SKU: ${item.sku || item.code}</span>
                                    <span style="font-size: 11px; font-weight: 400; color: ${availColor};">${availText}</span>
                                </div>
                            </div>
                        `;
                    });
                    resultsDiv.innerHTML = html;
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div style="padding: 12px; color: #6b7280; text-align: center;">No items found</div>';
                    resultsDiv.style.display = 'block';
                }
            });

            // Close results when clicking outside
            document.addEventListener('click', function(e) {
                if (!itemSearchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                    resultsDiv.style.display = 'none';
                }
            });
        }
    });
    // Load rental items (only active items)
    async function loadRentalItems() {
        const searchInput = document.getElementById('itemSearchInput');
        
        if (searchInput) searchInput.disabled = true;

        try {
            const response = await fetch('/admin/rentals/api/available-items');
            const items = await response.json();

            // Store items globally for search
            allRentalItems = items;

            if (searchInput) searchInput.disabled = false;

        } catch (error) {
            console.error('Error loading rental items:', error);
            alert('Failed to load rental items');
            if (searchInput) searchInput.disabled = true;
        }
    }

    // Select an item from search results
    function selectRentalItem(id, name, sku, rate, rateType, available) {
        // Set hidden input value
        document.getElementById('issueItemId').value = id;
        
        // Set search input display
        document.getElementById('itemSearchInput').value = `${name} (SKU: ${sku})`;
        
        // Hide results
        document.getElementById('itemSearchResults').style.display = 'none';
        
        // Update global rate variables
        currentItemRate = parseFloat(rate) || 0;
        currentRateType = rateType || 'per use';
        
        // Update rate display
        if (currentItemRate > 0) {
            const rateFormatted = currentItemRate.toLocaleString('en-PH', {
                style: 'currency',
                currency: 'PHP'
            });
            document.querySelector('#rateDisplay .rate-amount').textContent = rateFormatted;
            document.querySelector('#rateDisplay .rate-type-label').textContent = ` ${currentRateType}`;
        } else {
            document.querySelector('#rateDisplay .rate-amount').textContent = '—';
            document.querySelector('#rateDisplay .rate-type-label').textContent = '';
        }

        // Update availability
        const availDiv = document.getElementById('itemAvailability');
        if (available > 0) {
            availDiv.textContent = `Available: ${available} unit(s)`;
            availDiv.style.color = '#10b981';
        } else {
            availDiv.textContent = 'Not available';
            availDiv.style.color = '#ef4444';
        }

        // Set quantity max and reset
        const qtyInput = document.getElementById('issueQuantity');
        qtyInput.max = available;
        qtyInput.value = '1';
        qtyInput.setAttribute('data-just-initialized', 'true');

        updateTotalCost();
    }

    // Update total cost
    function updateTotalCost() {
        const quantity = parseInt(document.getElementById('issueQuantity').value) || 0;
        const total = currentItemRate * quantity;

        const formatted = total.toLocaleString('en-PH', {
            style: 'currency',
            currency: 'PHP'
        });

        document.getElementById('totalCostAmount').textContent = formatted;
    }

    // Handle quantity change
    document.getElementById('issueQuantity').addEventListener('input', function () {
        let qty = this.value.trim();
        
        // If empty, allow user to continue typing
        if (qty === '') {
            document.getElementById('quantityHint').textContent = 'Enter quantity to issue';
            updateTotalCost();
            return;
        }

        qty = parseInt(qty) || 0;
        const max = parseInt(this.max) || 999;

        // Validate quantity
        if (qty < 1) {
            // Let user continue typing, don't force set it
            return;
        }
        
        if (qty > max && max > 0) {
            this.value = max;
            alert(`Only ${max} unit(s) available!`);
            qty = max;
        }

        document.getElementById('quantityHint').textContent = qty === 1 ? '1 item' : `${qty} items`;
        updateTotalCost();
    });

    // Handle quantity blur to set default if empty
    document.getElementById('issueQuantity').addEventListener('blur', function () {
        if (this.value.trim() === '') {
            this.value = '1';
            document.getElementById('quantityHint').textContent = '1 item';
            updateTotalCost();
        }
    });

    // Handle quantity focus to clear placeholder-like behavior
    document.getElementById('issueQuantity').addEventListener('focus', function () {
        if (this.value === '1' && this.getAttribute('data-just-initialized') === 'true') {
            this.value = '';
            this.removeAttribute('data-just-initialized');
        }
    });

    // Initialize total on page load
    document.addEventListener('DOMContentLoaded', function () {
        updateTotalCost();
        // Load rental items when page loads
        loadRentalItems();
    });
</script>