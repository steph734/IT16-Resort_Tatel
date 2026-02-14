/**
 * Booking Dashboard JavaScript
 * Handles date filtering and dynamic data updates for booking dashboard
 */

// Store current filter state
let currentDateFilter = {
    preset: 'month', // Default to "This Month"
    startDate: null,
    endDate: null
};

document.addEventListener('DOMContentLoaded', function() {
    // Setup event listeners
    setupEventListeners();
    
    // Initialize date filter from URL on page load
    initializeDateFilter();
});

// Event Listeners
function setupEventListeners() {
    // Date range button
    const dateRangeBtn = document.getElementById('dateRangeBtn');
    if (dateRangeBtn) {
        dateRangeBtn.addEventListener('click', () => openModal('dateRangeModal'));
    }
    
    // Apply date range
    const applyDateRange = document.getElementById('applyDateRange');
    if (applyDateRange) {
        applyDateRange.addEventListener('click', handleDateRangeApply);
    }
    
    // Modal close buttons
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            closeModal(this.closest('.modal').id);
        });
    });
    
    // Preset buttons
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            // Clear custom date inputs when preset is selected
            document.getElementById('customStartDate').value = '';
            document.getElementById('customEndDate').value = '';
        });
    });

    // Custom date inputs - deselect presets when user enters custom dates
    const customStartDate = document.getElementById('customStartDate');
    const customEndDate = document.getElementById('customEndDate');
    
    if (customStartDate) {
        customStartDate.addEventListener('change', function() {
            if (this.value) {
                document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            }
        });
    }
    
    if (customEndDate) {
        customEndDate.addEventListener('change', function() {
            if (this.value) {
                document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            }
        });
    }
    
    // Close modal when clicking outside
    const dateRangeModal = document.getElementById('dateRangeModal');
    if (dateRangeModal) {
        dateRangeModal.addEventListener('click', (e) => {
            if (e.target === dateRangeModal) {
                closeModal('dateRangeModal');
            }
        });
    }
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Handle Date Range Apply
function handleDateRangeApply() {
    const activePreset = document.querySelector('.preset-btn.active');
    const startDateInput = document.getElementById('customStartDate');
    const endDateInput = document.getElementById('customEndDate');
    
    // Check if custom dates are filled
    const hasCustomDates = startDateInput.value && endDateInput.value;
    
    if (activePreset && !hasCustomDates) {
        // Using preset
        const preset = activePreset.dataset.preset;
        const presetText = activePreset.textContent;
        
        // Update current filter
        currentDateFilter.preset = preset;
        currentDateFilter.startDate = null;
        currentDateFilter.endDate = null;
        
        document.getElementById('dateRangeText').textContent = presetText;
    } else if (hasCustomDates) {
        // Using custom range
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        // Update current filter
        currentDateFilter.preset = 'custom';
        currentDateFilter.startDate = startDate;
        currentDateFilter.endDate = endDate;
        
        // Format date for display
        const startFormatted = new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        const endFormatted = new Date(endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        document.getElementById('dateRangeText').textContent = `${startFormatted} - ${endFormatted}`;
        
        // Deselect all preset buttons when using custom range
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
    } else {
        alert('Please select a date range (either preset or custom dates)');
        return;
    }
    
    // Reload page with new date range parameters
    applyDateFilter();
    
    closeModal('dateRangeModal');
}

// Apply date filter by reloading the page with query parameters
function applyDateFilter() {
    const url = new URL(window.location.href);
    
    if (currentDateFilter.preset !== 'custom') {
        // For presets, just pass the preset parameter
        url.searchParams.set('preset', currentDateFilter.preset);
        url.searchParams.delete('start_date');
        url.searchParams.delete('end_date');
    } else {
        // For custom range, pass start and end dates
        url.searchParams.set('preset', 'custom');
        url.searchParams.set('start_date', currentDateFilter.startDate);
        url.searchParams.set('end_date', currentDateFilter.endDate);
    }
    
    // Reload page with new parameters
    window.location.href = url.toString();
}

// Initialize date filter from URL parameters on page load
function initializeDateFilter() {
    const urlParams = new URLSearchParams(window.location.search);
    const preset = urlParams.get('preset');
    const startDate = urlParams.get('start_date');
    const endDate = urlParams.get('end_date');
    
    if (preset) {
        currentDateFilter.preset = preset;
        
        if (preset === 'custom' && startDate && endDate) {
            // Custom range was selected
            currentDateFilter.startDate = startDate;
            currentDateFilter.endDate = endDate;
            
            const startFormatted = new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            const endFormatted = new Date(endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            document.getElementById('dateRangeText').textContent = `${startFormatted} - ${endFormatted}`;
            
            // Make sure no preset buttons are active
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            
            // Set the custom date inputs for the modal
            document.getElementById('customStartDate').value = startDate;
            document.getElementById('customEndDate').value = endDate;
        } else if (preset !== 'custom') {
            // Preset was selected
            const presetTexts = {
                'year': 'This Year',
                'month': 'This Month',
                'week': 'This Week'
            };
            
            if (presetTexts[preset]) {
                document.getElementById('dateRangeText').textContent = presetTexts[preset];
                
                // Set the active preset button in the modal
                document.querySelectorAll('.preset-btn').forEach(btn => {
                    if (btn.dataset.preset === preset) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }
            
            // Clear custom date inputs
            document.getElementById('customStartDate').value = '';
            document.getElementById('customEndDate').value = '';
        }
    }
}
