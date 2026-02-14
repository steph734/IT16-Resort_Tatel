// Inventory Dashboard Date Filter Script
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    initializeDateFilter();
});

function setupEventListeners() {
    const dateRangeBtn = document.getElementById('dateRangeBtn');
    const dateRangeModal = document.getElementById('dateRangeModal');
    const modalClose = document.querySelector('[data-dismiss="modal"]');
    const cancelBtn = document.querySelector('.modal-footer .btn-secondary');
    const applyBtn = document.getElementById('applyDateRange');
    const presetBtns = document.querySelectorAll('.preset-btn');
    const customStartDate = document.getElementById('customStartDate');
    const customEndDate = document.getElementById('customEndDate');

    // Open modal
    if (dateRangeBtn) {
        dateRangeBtn.addEventListener('click', () => {
            dateRangeModal.classList.add('show');
        });
    }

    // Close modal
    function closeModal() {
        dateRangeModal.classList.remove('show');
    }

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // Handle preset buttons
    presetBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            presetBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            // Clear custom dates when preset is selected
            customStartDate.value = '';
            customEndDate.value = '';
        });
    });

    // Handle custom date changes
    [customStartDate, customEndDate].forEach(input => {
        if (input) {
            input.addEventListener('change', () => {
                // When custom dates are entered, deactivate all presets
                if (customStartDate.value || customEndDate.value) {
                    presetBtns.forEach(btn => btn.classList.remove('active'));
                }
            });
        }
    });

    // Apply date range
    if (applyBtn) {
        applyBtn.addEventListener('click', handleDateRangeApply);
    }

    // Close modal when clicking outside
    dateRangeModal.addEventListener('click', (e) => {
        if (e.target === dateRangeModal) {
            closeModal();
        }
    });
}

function handleDateRangeApply() {
    const activePreset = document.querySelector('.preset-btn.active');
    const customStartDate = document.getElementById('customStartDate').value;
    const customEndDate = document.getElementById('customEndDate').value;

    const urlParams = new URLSearchParams();

    if (customStartDate && customEndDate) {
        // Custom date range
        urlParams.set('preset', 'custom');
        urlParams.set('start_date', customStartDate);
        urlParams.set('end_date', customEndDate);
    } else if (activePreset) {
        // Preset selection
        urlParams.set('preset', activePreset.dataset.preset);
    } else {
        // Default to month
        urlParams.set('preset', 'month');
    }

    // Reload page with new parameters
    window.location.href = `${window.location.pathname}?${urlParams.toString()}`;
}

function initializeDateFilter() {
    const urlParams = new URLSearchParams(window.location.search);
    const preset = urlParams.get('preset') || 'month';
    const startDate = urlParams.get('start_date');
    const endDate = urlParams.get('end_date');

    const dateRangeText = document.getElementById('dateRangeText');
    const presetBtns = document.querySelectorAll('.preset-btn');
    const customStartDate = document.getElementById('customStartDate');
    const customEndDate = document.getElementById('customEndDate');

    if (preset === 'custom' && startDate && endDate) {
        dateRangeText.textContent = 'Custom';
        presetBtns.forEach(btn => btn.classList.remove('active'));
        if (customStartDate) customStartDate.value = startDate;
        if (customEndDate) customEndDate.value = endDate;
    } else {

        presetBtns.forEach(btn => {
            if (btn.dataset.preset === preset) {
                btn.classList.add('active');
                dateRangeText.textContent = btn.textContent;
            } else {
                btn.classList.remove('active');
            }
        });
        if (customStartDate) customStartDate.value = '';
        if (customEndDate) customEndDate.value = '';
    }
}
