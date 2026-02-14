// Rentals Catalog Page JavaScript

// Global variables
let currentEditItemId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeCatalog();
});

// ========== SETUP RATE MODAL ==========
function openSetupRateModal(itemSku, itemData) {
    const modal = document.getElementById('setupRateModal');
    
    if (modal && itemData) {
        // Populate form with item data (inventory_item_id field expects SKU)
        document.getElementById('setupInventoryItemId').value = itemSku;
        
        // Display item info
        document.getElementById('setupInfoName').textContent = itemData.name || 'N/A';
        document.getElementById('setupInfoSku').textContent = itemData.sku || 'N/A';
        document.getElementById('setupInfoStock').textContent = itemData.quantity_on_hand || '0';
        
        // Reset form fields
        document.getElementById('setupRateType').value = 'Per-Day';
        document.getElementById('setupRate').value = '';
        document.getElementById('setupDescription').value = '';
        
        modal.classList.add('show');
    }
}

function closeSetupRateModal() {
    const modal = document.getElementById('setupRateModal');
    if (modal) {
        modal.classList.remove('show');
        document.getElementById('setupRateForm').reset();
    }
}

// Handle Setup Rate Form Submission
document.getElementById('setupRateForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);

    fetch('/admin/rentals/items', {
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
            showNotification('Item added to catalog successfully!', 'success');
            closeSetupRateModal();
            setTimeout(() => window.location.href = '/admin/rentals/catalog', 1000);
        } else {
            showNotification(result.message || 'Error adding item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while adding item', 'error');
    });
});

// ========== EDIT ITEM MODAL ==========
function openEditItemModal(itemId, itemData) {
    currentEditItemId = itemId;
    const modal = document.getElementById('editItemModal');
    
    if (modal && itemData) {
        // Populate form with item data
        document.getElementById('editItemId').value = itemId;
        
        // Display item info (read-only from inventory)
        document.getElementById('editInfoName').textContent = itemData.name || 'N/A';
        document.getElementById('editInfoSku').textContent = itemData.code || 'N/A';
        document.getElementById('editInfoStock').textContent = itemData.stock_on_hand || '0';
        
        // Populate editable fields
        document.getElementById('editRateType').value = itemData.rate_type;
        document.getElementById('editRate').value = itemData.rate;
        document.getElementById('editDescription').value = itemData.description || '';
        document.getElementById('editStatus').value = itemData.status;
        
        modal.classList.add('show');
    }
}

function closeEditItemModal() {
    const modal = document.getElementById('editItemModal');
    if (modal) {
        modal.classList.remove('show');
        document.getElementById('editItemForm').reset();
        currentEditItemId = null;
    }
}

// Handle Edit Item Form Submission
document.getElementById('editItemForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    const itemId = document.getElementById('editItemId').value;

    fetch(`/admin/rentals/items/${itemId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Item updated successfully!', 'success');
            closeEditItemModal();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(result.message || 'Error updating item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating item', 'error');
    });
});

// ========== TOGGLE ITEM STATUS ==========
function toggleItemStatus(itemId, currentStatus) {
    const action = currentStatus === 'Active' ? 'archive' : 'restore';
    const confirmMessage = `Are you sure you want to ${action} this item?`;
    
    if (!confirm(confirmMessage)) {
        return;
    }

    fetch(`/admin/rentals/items/${itemId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification(`Item ${action}d successfully!`, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(result.message || `Error ${action}ing item`, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification(`An error occurred while ${action}ing item`, 'error');
    });
}

// ========== INITIALIZE CATALOG ==========
function initializeCatalog() {
    const searchInput = document.getElementById('catalogSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterCatalogItems, 300));
    }
}

// ========== SEARCH AND FILTER ==========
function filterCatalogItems(e) {
    const searchTerm = (e.target.value || '').toLowerCase();
    const itemCards = document.querySelectorAll('.item-card, .item-card.pending-setup');
    
    itemCards.forEach(card => {
        const itemName = (card.querySelector('.item-name')?.textContent || '').toLowerCase();
        const itemCode = (card.querySelector('.item-code')?.textContent || '');
        const codeValue = (card.querySelector('[class*="item-value"]')?.textContent || '').toLowerCase();
        const code = (itemCode + codeValue).toLowerCase();
        
        if (itemName.includes(searchTerm) || code.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Check if any items are visible
    const visibleCards = document.querySelectorAll('.item-card:not([style*="display: none"]), .item-card.pending-setup:not([style*="display: none"])');
    const emptyStateCard = document.querySelector('.empty-state-card:not(#noSearchResults)');
    
    if (visibleCards.length === 0 && !emptyStateCard) {
        const itemsGrid = document.querySelector('.items-grid');
        if (itemsGrid) {
            const noResults = document.createElement('div');
            noResults.className = 'empty-state-card';
            noResults.id = 'noSearchResults';
            noResults.innerHTML = `
                <i class="fas fa-search fa-4x"></i>
                <h3>No items found</h3>
                <p>Try searching with different keywords</p>
            `;
            itemsGrid.appendChild(noResults);
        }
    } else if (visibleCards.length > 0) {
        const noResults = document.getElementById('noSearchResults');
        if (noResults) {
            noResults.remove();
        }
    }
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
