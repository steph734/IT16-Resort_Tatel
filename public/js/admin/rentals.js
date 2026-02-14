// Rentals List Page JavaScript

// Global variables
let availableItems = [];
let currentRentalId = null;
let allRentals = [];
let filteredRentals = [];
let currentFilters = {
    search: '',
    status: '',
    itemId: '',
    dateFrom: '',
    dateTo: ''
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    loadAvailableItems();
    loadRentalsData();
});

// ========== FILTERS AND SEARCH ==========
function initializeFilters() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const itemFilter = document.getElementById('itemFilter');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');

    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearchInput, 300));
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', handleFilterChange);
    }

    if (itemFilter) {
        itemFilter.addEventListener('change', handleFilterChange);
    }

    if (dateFrom) {
        dateFrom.addEventListener('change', handleFilterChange);
    }

    if (dateTo) {
        dateTo.addEventListener('change', handleFilterChange);
    }
}

function handleSearchInput(e) {
    currentFilters.search = e.target.value.toLowerCase();
    applyClientSideFilters();
}

function handleFilterChange() {
    currentFilters.status = document.getElementById('statusFilter')?.value || '';
    currentFilters.itemId = document.getElementById('itemFilter')?.value || '';
    currentFilters.dateFrom = document.getElementById('dateFrom')?.value || '';
    currentFilters.dateTo = document.getElementById('dateTo')?.value || '';
    
    applyClientSideFilters();
}

function applyClientSideFilters() {
    filteredRentals = allRentals.filter(rental => {
        // Search filter
        if (currentFilters.search) {
            const searchTerm = currentFilters.search;
            const bookingId = (rental.booking_id || '').toLowerCase();
            const guestName = (rental.guest_name || '').toLowerCase();
            const itemName = (rental.item_name || '').toLowerCase();
            const itemCode = (rental.item_code || '').toLowerCase();
            
            if (!bookingId.includes(searchTerm) && 
                !guestName.includes(searchTerm) && 
                !itemName.includes(searchTerm) && 
                !itemCode.includes(searchTerm)) {
                return false;
            }
        }
        
        // Status filter
        if (currentFilters.status && rental.status !== currentFilters.status) {
            return false;
        }
        
        // Item filter
        if (currentFilters.itemId && rental.item_id != currentFilters.itemId) {
            return false;
        }
        
        // Date range filter
        if (currentFilters.dateFrom || currentFilters.dateTo) {
            const issuedDate = new Date(rental.issued_at);
            
            if (currentFilters.dateFrom) {
                const fromDate = new Date(currentFilters.dateFrom);
                fromDate.setHours(0, 0, 0, 0);
                if (issuedDate < fromDate) {
                    return false;
                }
            }
            
            if (currentFilters.dateTo) {
                const toDate = new Date(currentFilters.dateTo);
                toDate.setHours(23, 59, 59, 999);
                if (issuedDate > toDate) {
                    return false;
                }
            }
        }
        
        return true;
    });
    
    renderRentalsTable();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ========== LOAD RENTALS DATA ==========
function loadRentalsData() {
    fetch('/admin/rentals/api/list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allRentals = data.rentals;
                filteredRentals = [...allRentals];
                renderRentalsTable();
            }
        })
        .catch(error => {
            console.error('Error loading rentals:', error);
            showNotification('Error loading rentals data', 'error');
        });
}

function renderRentalsTable() {
    const tbody = document.querySelector('.rentals-table tbody');
    
    if (!tbody) return;
    
    if (filteredRentals.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center empty-state">
                    <i class="fas fa-inbox fa-3x"></i>
                    <p>No rentals found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = filteredRentals.map(rental => {
        const statusBadge = getStatusBadge(rental.status);
        const returnedDate = rental.returned_at 
            ? `<div class="date-main">${formatDate(rental.returned_at)}</div>
               <div class="date-time">${formatTime(rental.returned_at)}</div>`
            : '<span class="text-muted">—</span>';
        
        return `
            <tr>
                <td class="booking-id">${rental.booking_id}</td>
                <td>
                    <div class="guest-name">${rental.guest_name}</div>
                </td>
                <td>
                    <div class="item-name">${rental.item_name}</div>
                    <div class="item-code">${rental.item_code}</div>
                </td>
                <td class="text-center">${rental.quantity}</td>
                <td>${statusBadge}</td>
                <td class="amount">₱${parseFloat(rental.total_charges).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                <td>
                    <div class="date-main">${formatDate(rental.issued_at)}</div>
                    <div class="date-time">${formatTime(rental.issued_at)}</div>
                </td>
                <td>${returnedDate}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn btn-view" onclick="viewRentalDetail(${rental.id})"
                            title="View Detail">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function getStatusBadge(status) {
    const badges = {
        'Issued': '<span class="status-badge status-issued"><i class="fas fa-clock"></i> Issued</span>',
        'Returned': '<span class="status-badge status-returned"><i class="fas fa-check-circle"></i> Returned</span>',
        'Damaged': '<span class="status-badge status-damaged"><i class="fas fa-exclamation-triangle"></i> Damaged</span>',
        'Lost': '<span class="status-badge status-damaged"><i class="fas fa-times-circle"></i> Lost</span>'
    };
    return badges[status] || '<span class="status-badge status-damaged"><i class="fas fa-question-circle"></i> Unknown</span>';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

// ========== LOAD AVAILABLE ITEMS ==========
function loadAvailableItems() {
    return fetch('/admin/rentals/api/available-items')
        .then(response => response.json())
        .then(data => {
            availableItems = data;
            return data;
        })
        .catch(error => {
            console.error('Error loading available items:', error);
            return [];
        });
}

// ========== ISSUE RENTAL MODAL ==========
function openIssueRentalModal(bookingId = null) {
    const modal = document.getElementById('issueRentalModal');
    if (modal) {
        modal.classList.add('show');
        
        // Load items immediately
        if (availableItems.length === 0) {
            loadAvailableItems().then(() => {
                populateItemsDropdown();
            });
        } else {
            populateItemsDropdown();
        }
        
        // Auto-select first staying booking if none provided
        const bookingSelect = document.getElementById('issueBookingId');
        if (bookingSelect && !bookingId && bookingSelect.options.length > 1) {
            bookingSelect.selectedIndex = 1;
        } else if (bookingId) {
            bookingSelect.value = bookingId;
        }
    }
}

function loadCurrentlyStayingGuests() {
    // Fetch currently staying guests for the booking ID datalist
    fetch('/admin/currently-staying/search')
        .then(response => response.json())
        .then(data => {
            const datalist = document.getElementById('currentlyStayingList');
            if (datalist && data.guests) {
                datalist.innerHTML = '';
                data.guests.forEach(guest => {
                    const option = document.createElement('option');
                    option.value = guest.BookingID;
                    option.textContent = `${guest.BookingID} - ${guest.FName} ${guest.LName}`;
                    datalist.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading currently staying guests:', error);
        });
}

function closeIssueRentalModal() {
    const modal = document.getElementById('issueRentalModal');
    if (modal) {
        modal.classList.remove('show');
        document.getElementById('issueRentalForm').reset();
        document.getElementById('itemAvailability').innerHTML = '';
        document.getElementById('rateDisplay').innerHTML = `
            <span class="rate-amount">—</span>
            <span class="rate-type-label"></span>
        `;
    }
}

function populateItemsDropdown() {
    const select = document.getElementById('issueItemId');
    if (!select) return;

    select.innerHTML = '<option value="">Select an item...</option>';
    
    availableItems.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = `${item.name} (${item.code}) - ${item.available_quantity} available`;
        option.dataset.rate = item.rate;
        option.dataset.rateType = item.rate_type;
        option.dataset.available = item.available_quantity;
        option.dataset.sku = item.sku || item.code;
        option.dataset.code = item.code;
        select.appendChild(option);
    });

    // Remove old listener before adding new one
    select.removeEventListener('change', handleItemSelection);
    select.addEventListener('change', handleItemSelection);
    
    // Store options for search functionality
    window.allItemOptions = Array.from(select.options).slice(1);
}

function handleItemSelection(e) {
    const select = e.target;
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        const rate = selectedOption.dataset.rate;
        const rateType = selectedOption.dataset.rateType;
        const available = selectedOption.dataset.available;

        // Update rate display
        document.getElementById('rateDisplay').innerHTML = `
            <span class="rate-amount">₱${parseFloat(rate).toFixed(2)}</span>
            <span class="rate-type-label">${rateType}</span>
        `;

        // Update availability info
        const availabilityDiv = document.getElementById('itemAvailability');
        if (parseInt(available) > 0) {
            availabilityDiv.innerHTML = `
                <span style="color: #059669; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> ${available} available
                </span>
            `;
        } else {
            availabilityDiv.innerHTML = `
                <span style="color: #dc2626; font-weight: 600;">
                    <i class="fas fa-times-circle"></i> Out of stock
                </span>
            `;
        }
    } else {
        document.getElementById('rateDisplay').innerHTML = `
            <span class="rate-amount">—</span>
            <span class="rate-type-label"></span>
        `;
        document.getElementById('itemAvailability').innerHTML = '';
    }
}

// Handle Issue Rental Form Submission
document.getElementById('issueRentalForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    fetch('/admin/rentals/issue', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Rental issued successfully!', 'success');
            closeIssueRentalModal();
            loadRentalsData(); // Reload data without page refresh
        } else {
            showNotification(result.message || 'Error issuing rental', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while issuing rental', 'error');
    });
});

// ========== RETURN/DAMAGE MODAL ==========
function openReturnModal(rentalId) {
    currentRentalId = rentalId;
    const modal = document.getElementById('returnRentalModal');
    if (modal) {
        modal.classList.add('show');
        document.getElementById('returnRentalId').value = rentalId;
        
        // Get rental details to populate form
        // You can fetch the rental data if needed
    }
}

function closeReturnModal() {
    const modal = document.getElementById('returnRentalModal');
    if (modal) {
        modal.classList.remove('show');
        document.getElementById('returnRentalForm').reset();
        document.getElementById('damageFields').style.display = 'none';
        currentRentalId = null;
    }
}

function handleConditionChange() {
    const condition = document.getElementById('condition').value;
    const damageFields = document.getElementById('damageFields');
    const damageDescription = document.getElementById('damageDescription');
    
    if (condition === 'Damaged' || condition === 'Lost') {
        damageFields.style.display = 'block';
        damageDescription.required = true;
    } else {
        damageFields.style.display = 'none';
        damageDescription.required = false;
    }
}

// Handle Return Form Submission
document.getElementById('returnRentalForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const rentalId = document.getElementById('returnRentalId').value;

    fetch(`/admin/rentals/${rentalId}/return`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Return processed successfully!', 'success');
            closeReturnModal();
            loadRentalsData(); // Reload data without page refresh
        } else {
            showNotification(result.message || 'Error processing return', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while processing return', 'error');
    });
});

// ========== ADD FEE MODAL ==========
function openAddFeeModal(rentalId) {
    currentRentalId = rentalId;
    const modal = document.getElementById('addFeeModal');
    if (modal) {
        modal.classList.add('show');
        document.getElementById('feeRentalId').value = rentalId;
    }
}

function closeAddFeeModal() {
    const modal = document.getElementById('addFeeModal');
    if (modal) {
        modal.classList.remove('show');
        document.getElementById('addFeeForm').reset();
        currentRentalId = null;
    }
}

// Handle Add Fee Form Submission
document.getElementById('addFeeForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    const rentalId = document.getElementById('feeRentalId').value;

    fetch(`/admin/rentals/${rentalId}/add-fee`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Fee added successfully!', 'success');
            closeAddFeeModal();
            loadRentalsData(); // Reload data without page refresh
        } else {
            showNotification(result.message || 'Error adding fee', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while adding fee', 'error');
    });
});

// ========== VIEW RENTAL DETAIL ==========
function viewRentalDetail(rentalId) {
    window.location.href = `/admin/rentals/${rentalId}`;
}

// ========== NOTIFICATIONS ==========
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#d1fae5' : type === 'error' ? '#fee2e2' : '#dbeafe'};
        color: ${type === 'success' ? '#065f46' : type === 'error' ? '#991b1b' : '#1e40af'};
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        animation: slideInRight 0.3s ease;
    `;

    const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    notification.innerHTML = `<i class="fas ${icon}"></i> ${message}`;

    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Close modals when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
