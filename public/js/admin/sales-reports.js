/**
 * Sales Reports JavaScript
 * Handles report generation and management
 */

// SweetAlert2 Configuration with Project Colors
const SwalConfig = {
    confirmButtonColor: '#284B53',
    cancelButtonColor: '#6b7280',
    customClass: {
        popup: 'swal-custom-popup',
        confirmButton: 'swal-custom-confirm',
        cancelButton: 'swal-custom-cancel'
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // Setup event listeners
    setupEventListeners();
    
    // Load recent reports
    loadRecentReports();
    
    // Populate year dropdown
    populateYearDropdown();
    
    // Set current month
    setCurrentMonth();
});

// Event listeners setup
function setupEventListeners() {
    // Report generation buttons
    document.querySelectorAll('[data-report]').forEach(btn => {
        btn.addEventListener('click', function() {
            const reportType = this.dataset.report;
            openReportGenerator(reportType);
        });
    });
    
    // Preview report button
    document.getElementById('previewReportBtn')?.addEventListener('click', handlePreviewReport);
    
    // Download report button
    document.getElementById('downloadReportBtn')?.addEventListener('click', handleDownloadReport);
    
    // Download from preview button
    document.getElementById('downloadFromPreviewBtn')?.addEventListener('click', handleDownloadReport);
    
    // Clear history
    document.getElementById('clearHistoryBtn')?.addEventListener('click', handleClearHistory);
    
    // Modal close
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            closeModal(this.closest('.modal').id);
        });
    });
}

// Populate year dropdown
function populateYearDropdown() {
    const yearSelect = document.getElementById('reportYear');
    if (!yearSelect) return;
    
    const currentYear = new Date().getFullYear();
    yearSelect.innerHTML = '';
    
    // Add years from 5 years ago to current year
    for (let year = currentYear; year >= currentYear - 5; year--) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        if (year === currentYear) {
            option.selected = true;
        }
        yearSelect.appendChild(option);
    }
}

// Set current month
function setCurrentMonth() {
    const monthSelect = document.getElementById('reportMonth');
    if (monthSelect) {
        const currentMonth = new Date().getMonth() + 1;
        monthSelect.value = currentMonth;
    }
}

// Open report generator modal
function openReportGenerator(reportType) {
    const modal = document.getElementById('reportGeneratorModal');
    const titleElement = document.getElementById('reportModalTitle');
    const reportTypeInput = document.getElementById('reportType');
    const fileNameInput = document.getElementById('reportFileName');
    
    // Get today's date
    const today = new Date();
    const formattedDate = formatDateForInput(today);
    
    // Report configurations
    const currentMonth = String(today.getMonth() + 1).padStart(2, '0');
    const currentYear = today.getFullYear();
    
    const reportConfig = {
        'per-booking': {
            title: 'Generate Per Booking Sales Report',
            fileName: 'per-booking-sales-report',
            showBookingSelect: true,
            showPeriodGroup: false,
            showSpecificDate: false,
            showCustomRange: false,
            showMonthYear: false
        },
        'daily': {
            title: 'Generate Daily Sales Report',
            fileName: 'daily-sales-report-' + today.toISOString().split('T')[0],
            periodDisplay: 'Today',
            showPeriodGroup: true,
            showSpecificDate: false,
            showCustomRange: false,
            showMonthYear: false
        },
        'monthly': {
            title: 'Generate Monthly Sales Report',
            fileName: `monthly-sales-report-${currentYear}-${currentMonth}`,
            periodDisplay: 'Select Month and Year',
            showPeriodGroup: false,
            showSpecificDate: false,
            showCustomRange: false,
            showMonthYear: true
        },
        'annual': {
            title: 'Generate Annual Sales Report',
            fileName: `annual-sales-report-${currentYear}`,
            periodDisplay: 'Select Year',
            showPeriodGroup: false,
            showSpecificDate: false,
            showCustomRange: false,
            showMonthYear: true,
            hideMonth: true
        },
        'custom': {
            title: 'Generate Custom Sales Report',
            fileName: 'custom-sales-report-select-dates',
            periodDisplay: 'Custom Date Range',
            showPeriodGroup: false,
            showSpecificDate: false,
            showCustomRange: true,
            showMonthYear: false
        }
    };
    
    const config = reportConfig[reportType] || reportConfig['daily'];
    
    // Set modal title and defaults
    titleElement.textContent = config.title;
    reportTypeInput.value = reportType;
    fileNameInput.value = config.fileName;
    
    // Configure form fields based on report type
    const bookingSelectSection = document.getElementById('bookingSelectSection');
    const dateRangeSection = document.getElementById('dateRangeSection');
    const periodGroup = document.getElementById('periodGroup');
    const specificDateGroup = document.getElementById('specificDateGroup');
    const customDateRange = document.getElementById('customDateRange');
    const monthYearSelectors = document.getElementById('monthYearSelectors');
    const monthGroup = document.getElementById('monthGroup');
    const periodDisplay = document.getElementById('reportPeriodDisplay');
    
    // Reset all groups
    bookingSelectSection.style.display = 'none';
    dateRangeSection.style.display = 'none';
    periodGroup.style.display = 'none';
    specificDateGroup.style.display = 'none';
    customDateRange.style.display = 'none';
    monthYearSelectors.style.display = 'none';
    
    // Show booking selector for per-booking report
    if (config.showBookingSelect) {
        bookingSelectSection.style.display = 'block';
        // Hide the separate Date Range Section for per-booking report
        dateRangeSection.style.display = 'none';
    } else {
        // Show Date Range Section for other report types
        dateRangeSection.style.display = 'block';
    }
    
    // Show relevant groups
    if (config.showPeriodGroup) {
        periodGroup.style.display = 'block';
        periodDisplay.value = config.periodDisplay;
    }
    
    if (config.showSpecificDate) {
        specificDateGroup.style.display = 'block';
        document.getElementById('reportSpecificDate').value = formattedDate;
    }
    
    if (config.showCustomRange) {
        customDateRange.style.display = 'grid';
        // Set default to current month
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        document.getElementById('reportStartDate').value = formatDateForInput(firstDay);
        document.getElementById('reportEndDate').value = formattedDate;
    }
    
    if (config.showMonthYear) {
        monthYearSelectors.style.display = 'grid';
        if (config.hideMonth) {
            monthGroup.style.display = 'none';
        } else {
            monthGroup.style.display = 'block';
        }
    }
    
    openModal('reportGeneratorModal');
    
    // Set month and year after modal is opened (for proper DOM interaction)
    if (config.showMonthYear) {
        // Populate year dropdown first
        populateYearDropdown();
        // Then set current selections
        document.getElementById('reportMonth').value = String(today.getMonth() + 1);
        document.getElementById('reportYear').value = currentYear;
    }
    
    // Add event listeners for dynamic file name updates
    setupFileNameUpdaters(reportType);
}

// Setup event listeners to update file name based on selections
function setupFileNameUpdaters(reportType) {
    const monthSelect = document.getElementById('reportMonth');
    const yearSelect = document.getElementById('reportYear');
    const startDateInput = document.getElementById('reportStartDate');
    const endDateInput = document.getElementById('reportEndDate');
    const bookingSelect = document.getElementById('bookingSelect');
    
    // Remove existing listeners
    const newMonthSelect = monthSelect.cloneNode(true);
    const newYearSelect = yearSelect.cloneNode(true);
    const newStartDate = startDateInput.cloneNode(true);
    const newEndDate = endDateInput.cloneNode(true);
    const newBookingSelect = bookingSelect.cloneNode(true);
    
    monthSelect.parentNode.replaceChild(newMonthSelect, monthSelect);
    yearSelect.parentNode.replaceChild(newYearSelect, yearSelect);
    startDateInput.parentNode.replaceChild(newStartDate, startDateInput);
    endDateInput.parentNode.replaceChild(newEndDate, endDateInput);
    bookingSelect.parentNode.replaceChild(newBookingSelect, bookingSelect);
    
    if (reportType === 'monthly') {
        newMonthSelect.addEventListener('change', updateMonthlyFileName);
        newYearSelect.addEventListener('change', updateMonthlyFileName);
    } else if (reportType === 'annual') {
        newYearSelect.addEventListener('change', updateAnnualFileName);
    } else if (reportType === 'custom') {
        newStartDate.addEventListener('change', updateCustomFileName);
        newEndDate.addEventListener('change', updateCustomFileName);
    } else if (reportType === 'per-booking') {
        newBookingSelect.addEventListener('change', updatePerBookingFileName);
        newBookingSelect.addEventListener('change', updateBookingDateRange);
    }
}

// Initialize searchable select dropdown
function initializeSearchableSelect(selectElement) {
    // Create wrapper for custom searchable select
    const wrapper = document.createElement('div');
    wrapper.className = 'searchable-select-wrapper';
    wrapper.style.position = 'relative';
    
    // Create custom input that will show selected value and accept search
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'form-input searchable-select-input';
    searchInput.placeholder = 'Type to search booking ID or guest name...';
    searchInput.autocomplete = 'off';
    
    // Create dropdown list container
    const dropdownList = document.createElement('div');
    dropdownList.className = 'searchable-select-dropdown';
    
    // Store original options
    const options = Array.from(selectElement.options).slice(1); // Skip first "Select" option
    
    // Function to render options in dropdown
    function renderOptions(filterText = '') {
        dropdownList.innerHTML = '';
        const filter = filterText.toLowerCase();
        let hasResults = false;
        
        options.forEach(option => {
            const text = option.textContent.toLowerCase();
            if (!filterText || text.includes(filter)) {
                hasResults = true;
                const optionDiv = document.createElement('div');
                optionDiv.className = 'searchable-select-option';
                optionDiv.textContent = option.textContent;
                optionDiv.dataset.value = option.value;
                optionDiv.dataset.checkin = option.dataset.checkin;
                optionDiv.dataset.checkout = option.dataset.checkout;
                optionDiv.dataset.package = option.dataset.package;
                
                optionDiv.addEventListener('mouseenter', function() {
                    this.classList.add('hover');
                });
                
                optionDiv.addEventListener('mouseleave', function() {
                    this.classList.remove('hover');
                });
                
                optionDiv.addEventListener('click', function() {
                    selectElement.value = this.dataset.value;
                    searchInput.value = this.textContent;
                    dropdownList.style.display = 'none';
                    
                    // Update the hidden select with data attributes
                    const selectedOption = Array.from(selectElement.options).find(opt => opt.value === this.dataset.value);
                    if (selectedOption) {
                        selectedOption.dataset.checkin = this.dataset.checkin;
                        selectedOption.dataset.checkout = this.dataset.checkout;
                    }
                    
                    // Trigger change event on select
                    const event = new Event('change', { bubbles: true });
                    selectElement.dispatchEvent(event);
                });
                
                dropdownList.appendChild(optionDiv);
            }
        });
        
        if (!hasResults) {
            const noResults = document.createElement('div');
            noResults.textContent = 'No bookings found';
            noResults.className = 'searchable-select-no-results';
            dropdownList.appendChild(noResults);
        }
    }
    
    // Show dropdown on focus
    searchInput.addEventListener('focus', function() {
        renderOptions(this.value);
        dropdownList.style.display = 'block';
    });
    
    // Filter as user types
    searchInput.addEventListener('input', function() {
        renderOptions(this.value);
        dropdownList.style.display = 'block';
    });
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            dropdownList.style.display = 'none';
        }
    });
    
    // Replace select with custom searchable select
    selectElement.style.display = 'none';
    selectElement.parentNode.insertBefore(wrapper, selectElement);
    wrapper.appendChild(searchInput);
    wrapper.appendChild(dropdownList);
    wrapper.appendChild(selectElement);
}

// Update date range when booking is selected
function updateBookingDateRange() {
    const bookingSelect = document.getElementById('bookingSelect');
    const dateRangeInput = document.getElementById('bookingDateRange');
    const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        const checkIn = selectedOption.dataset.checkin;
        const checkOut = selectedOption.dataset.checkout;
        
        if (checkIn && checkOut) {
            dateRangeInput.value = `${checkIn} to ${checkOut}`;
        }
    } else {
        dateRangeInput.value = '';
    }
}

// Update file name for monthly reports
function updateMonthlyFileName() {
    const month = document.getElementById('reportMonth').value;
    const year = document.getElementById('reportYear').value;
    if (month && year) {
        const monthPadded = String(month).padStart(2, '0');
        document.getElementById('reportFileName').value = `monthly-sales-report-${year}-${monthPadded}`;
    }
}

// Update file name for annual reports
function updateAnnualFileName() {
    const year = document.getElementById('reportYear').value;
    if (year) {
        document.getElementById('reportFileName').value = `annual-sales-report-${year}`;
    }
}

// Update file name for custom reports
function updateCustomFileName() {
    const startDate = document.getElementById('reportStartDate').value;
    const endDate = document.getElementById('reportEndDate').value;
    if (startDate && endDate) {
        document.getElementById('reportFileName').value = `custom-sales-report-${startDate}-to-${endDate}`;
    }
}

// Update file name for per-booking reports
function updatePerBookingFileName() {
    const bookingSelect = document.getElementById('bookingSelect');
    const bookingId = bookingSelect.value;
    if (bookingId) {
        document.getElementById('reportFileName').value = `per-booking-sales-report-BK${bookingId}`;
    }
}

// Format date for input field
function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Handle preview report
function handlePreviewReport() {
    const reportData = collectReportData();
    
    if (!validateReportData(reportData)) {
        return;
    }
    
    // Show loading state
    const previewBtn = document.getElementById('previewReportBtn');
    const originalText = previewBtn.innerHTML;
    previewBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    previewBtn.disabled = true;
    
    // Fetch report data from API
    fetchReportData(reportData)
        .then(data => {
            generatePreview(reportData, data);
            
            // Reset button
            previewBtn.innerHTML = originalText;
            previewBtn.disabled = false;
            
            // Open preview modal
            openModal('reportPreviewModal');
        })
        .catch(error => {
            console.error('Error generating preview:', error);
            Swal.fire({
                icon: 'error',
                title: 'Preview Failed',
                text: error.message || 'Failed to generate report preview. Please try again.',
                confirmButtonColor: SwalConfig.confirmButtonColor,
                customClass: SwalConfig.customClass
            });
            
            // Reset button
            previewBtn.innerHTML = originalText;
            previewBtn.disabled = false;
        });
}

// Handle download report
function handleDownloadReport() {
    const reportData = collectReportData();
    
    if (!validateReportData(reportData)) {
        return;
    }
    
    // Show loading state - find which button was clicked
    let downloadBtn = document.getElementById('downloadFromPreviewBtn');
    if (!downloadBtn || downloadBtn.offsetParent === null) {
        // If preview button is not visible, use the modal button
        downloadBtn = document.getElementById('downloadReportBtn');
    }
    
    const originalText = downloadBtn.innerHTML;
    downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    downloadBtn.disabled = true;
    
    // Download report from API
    downloadReportFromAPI(reportData)
        .then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Download Complete!',
                text: `${reportData.fileName}.${reportData.format} has been downloaded successfully.`,
                confirmButtonColor: SwalConfig.confirmButtonColor,
                customClass: SwalConfig.customClass,
                timer: 3000,
                timerProgressBar: true
            });
            
            // Add to recent reports
            addToRecentReports(reportData);
            
            // Reset button
            downloadBtn.innerHTML = originalText;
            downloadBtn.disabled = false;
            
            // Close modals
            closeModal('reportGeneratorModal');
            closeModal('reportPreviewModal');
        })
        .catch(error => {
            console.error('Error downloading report:', error);
            Swal.fire({
                icon: 'error',
                title: 'Download Failed',
                text: error.message || 'Failed to download report. Please try again.',
                confirmButtonColor: SwalConfig.confirmButtonColor,
                customClass: SwalConfig.customClass
            });
            
            // Reset button
            downloadBtn.innerHTML = originalText;
            downloadBtn.disabled = false;
        });
}

// Collect report data from form
function collectReportData() {
    const reportType = document.getElementById('reportType').value;
    const format = document.getElementById('reportFormat').value;
    const fileName = document.getElementById('reportFileName').value;
    
    let periodInfo = '';
    let startDate = '';
    let endDate = '';
    let bookingId = '';
    
    if (reportType === 'per-booking') {
        bookingId = document.getElementById('bookingSelect').value;
        const selectElement = document.getElementById('bookingSelect');
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        periodInfo = selectedOption.text;
    } else if (reportType === 'daily') {
        const today = new Date();
        startDate = endDate = formatDateForInput(today);
        periodInfo = 'Today';
    } else if (reportType === 'monthly') {
        const month = document.getElementById('reportMonth').value;
        const year = document.getElementById('reportYear').value;
        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        startDate = formatDateForInput(firstDay);
        endDate = formatDateForInput(lastDay);
        periodInfo = `${getMonthName(month)} ${year}`;
    } else if (reportType === 'annual') {
        const year = document.getElementById('reportYear').value;
        startDate = `${year}-01-01`;
        endDate = `${year}-12-31`;
        periodInfo = `Year ${year}`;
    } else {
        startDate = document.getElementById('reportStartDate').value;
        endDate = document.getElementById('reportEndDate').value;
        periodInfo = `${startDate} to ${endDate}`;
    }
    
    return {
        type: reportType,
        format: format,
        fileName: fileName,
        bookingId: bookingId,
        startDate: startDate,
        endDate: endDate,
        periodInfo: periodInfo
    };
}

// Validate report data
function validateReportData(data) {
    if (!data.fileName) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing File Name',
            text: 'Please enter a file name for the report.',
            confirmButtonColor: SwalConfig.confirmButtonColor,
            customClass: SwalConfig.customClass
        });
        return false;
    }
    
    if (data.type === 'per-booking') {
        if (!data.bookingId) {
            Swal.fire({
                icon: 'warning',
                title: 'Booking Required',
                text: 'Please select a completed booking to generate the report.',
                confirmButtonColor: SwalConfig.confirmButtonColor,
                customClass: SwalConfig.customClass
            });
            return false;
        }
    } else if (!data.startDate || !data.endDate) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Date Range',
            text: 'Please select a valid start and end date for the report.',
            confirmButtonColor: SwalConfig.confirmButtonColor,
            customClass: SwalConfig.customClass
        });
        return false;
    }
    
    return true;
}

// Fetch report data from API
async function fetchReportData(reportData) {
    let url = '';
    let requestBody = {};
    
    switch(reportData.type) {
        case 'per-booking':
            url = '/admin/sales/reports/per-booking';
            requestBody = {
                booking_id: reportData.bookingId,
                format: 'json' // Always use JSON for preview
            };
            break;
        case 'monthly':
            url = '/admin/sales/reports/monthly';
            const monthYear = reportData.startDate.slice(0, 7); // YYYY-MM
            requestBody = {
                month: monthYear,
                format: 'json' // Always use JSON for preview
            };
            break;
        case 'annual':
            url = '/admin/sales/reports/annual';
            const year = reportData.startDate.slice(0, 4); // YYYY
            requestBody = {
                year: year,
                format: 'json' // Always use JSON for preview
            };
            break;
        case 'custom':
            url = '/admin/sales/reports/custom';
            requestBody = {
                start_date: reportData.startDate,
                end_date: reportData.endDate,
                format: 'json' // Always use JSON for preview
            };
            break;
        default:
            throw new Error('Invalid report type');
    }
    
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(requestBody)
    });
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return await response.json();
}

// Download report from API
async function downloadReportFromAPI(reportData) {
    let url = '';
    let requestBody = {};
    
    switch(reportData.type) {
        case 'per-booking':
            url = '/admin/sales/reports/per-booking';
            requestBody = {
                booking_id: reportData.bookingId,
                format: reportData.format
            };
            break;
        case 'monthly':
            url = '/admin/sales/reports/monthly';
            const monthYear = reportData.startDate.slice(0, 7); // YYYY-MM
            requestBody = {
                month: monthYear,
                format: reportData.format
            };
            break;
        case 'annual':
            url = '/admin/sales/reports/annual';
            const year = reportData.startDate.slice(0, 4); // YYYY
            requestBody = {
                year: year,
                format: reportData.format
            };
            break;
        case 'custom':
            url = '/admin/sales/reports/custom';
            requestBody = {
                start_date: reportData.startDate,
                end_date: reportData.endDate,
                format: reportData.format
            };
            break;
        default:
            throw new Error('Invalid report type');
    }
    
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(requestBody)
    });
    
    if (!response.ok) {
        const errorText = await response.text();
        console.error('Server response:', errorText);
        throw new Error(`Server returned ${response.status}: ${response.statusText}`);
    }
    
    // Check if response is actually a PDF
    const contentType = response.headers.get('Content-Type');
    if (!contentType || !contentType.includes('application/pdf')) {
        const responseText = await response.text();
        console.error('Unexpected response type:', contentType);
        console.error('Response body:', responseText);
        throw new Error('Server did not return a PDF file');
    }
    
    // Get the filename from Content-Disposition header
    const contentDisposition = response.headers.get('Content-Disposition');
    let filename = reportData.fileName + '.pdf';
    if (contentDisposition) {
        const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(contentDisposition);
        if (matches && matches[1]) {
            filename = matches[1].replace(/['"]/g, '');
        }
    }
    
    // Convert response to blob and trigger download
    const blob = await response.blob();
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(downloadUrl);
    
    return true;
}

// Generate preview
function generatePreview(reportData, apiData = null) {
    const previewBody = document.getElementById('reportPreviewBody');
    
    // Use real data if available, otherwise fall back to sample data
    let reportContent = '';
    if (apiData) {
        reportContent = generateRealDataPreview(reportData, apiData);
    } else {
        const sampleData = generateSampleData(reportData.type);
        reportContent = generateSamplePreview(reportData, sampleData);
    }
    
    previewBody.innerHTML = reportContent;
}

// Generate preview from real API data
function generateRealDataPreview(reportData, apiData) {
    const reportTitle = getReportTitle(reportData.type);
    
    let contentHTML = '';
    
    if (reportData.type === 'per-booking') {
        contentHTML = generatePerBookingPreview(apiData);
    } else if (reportData.type === 'monthly') {
        contentHTML = generateMonthlyPreview(apiData);
    } else if (reportData.type === 'annual') {
        contentHTML = generateAnnualPreview(apiData);
    } else if (reportData.type === 'custom') {
        contentHTML = generateCustomPreview(apiData);
    }
    
    return `
        <div class="report-preview-content">
            <div class="report-preview-header">
                <h2>${reportTitle}</h2>
                <div class="report-meta-info">
                    <p>Period: ${reportData.periodInfo}</p>
                    <p>Generated: ${new Date().toLocaleString('en-PH')}</p>
                </div>
            </div>
            ${contentHTML}
        </div>
    `;
}

// Generate sample preview (legacy fallback)
function generateSamplePreview(reportData, sampleData) {
    const reportTitle = getReportTitle(reportData.type);
    
    return `
        <div class="report-preview-content">
            <div class="report-preview-header">
                <h2>${reportTitle}</h2>
                <div class="report-meta-info">
                    <p>Period: ${reportData.periodInfo}</p>
                    <p>Generated: ${new Date().toLocaleString('en-PH')}</p>
                </div>
            </div>
            
            <div class="report-preview-table">
                ${sampleData.table}
            </div>
            
            <div class="report-summary">
                <h4>Summary</h4>
                <div class="report-summary-grid">
                    ${sampleData.summary}
                </div>
            </div>
        </div>
    `;
}

// Generate Per Booking preview content
function generatePerBookingPreview(data) {
    const booking = data.booking;
    const formatCurrency = (amount) => '₱' + parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    let paymentsHTML = '';
    data.payments.forEach(payment => {
        paymentsHTML += `
            <tr>
                <td>${payment.PaymentDate}</td>
                <td>${payment.PaymentPurpose}</td>
                <td>${payment.PaymentMethod}</td>
                <td class="text-right">${formatCurrency(payment.Amount)}</td>
            </tr>
        `;
    });
    
    let rentalsHTML = '';
    data.rentals.forEach(rental => {
        rentalsHTML += `
            <tr>
                <td>${rental.item_name}</td>
                <td class="text-center">${rental.quantity}</td>
                <td>${rental.usage_display}</td>
                <td class="text-right">${formatCurrency(rental.rental_fee)}</td>
                <td class="text-right">${formatCurrency(rental.damage_fee)}</td>
                <td class="text-right">${formatCurrency(rental.lost_fee)}</td>
                <td class="text-right"><strong>${formatCurrency(rental.total)}</strong></td>
            </tr>
        `;
    });
    
    let unpaidItemsHTML = '';
    data.unpaidItems.forEach(item => {
        unpaidItemsHTML += `
            <tr>
                <td>${item.ItemName}</td>
                <td>${item.Quantity}</td>
                <td class="text-right">${formatCurrency(item.UnitPrice)}</td>
                <td class="text-right"><strong>${formatCurrency(item.TotalAmount)}</strong></td>
            </tr>
        `;
    });
    
    return `
        <div class="report-section">
            <h3>Booking Information</h3>
            <div class="booking-info-grid">
                <div class="info-item">
                    <div class="info-label">Booking ID</div>
                    <div class="info-value">${booking.BookingID}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Guest</div>
                    <div class="info-value">${booking.GuestName}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Package</div>
                    <div class="info-value">${booking.PackageName}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Check-in</div>
                    <div class="info-value">${booking.CheckInDate}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Check-out</div>
                    <div class="info-value">${booking.CheckOutDate}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Days Staying</div>
                    <div class="info-value">${booking.Days}</div>
                </div>
            </div>
        </div>
        
        <div class="report-section">
            <h3>Booking Charges</h3>
            <table class="report-table">
                <tbody>
                    <tr>
                        <td>Package Cost (${booking.Days} day${booking.Days > 1 ? 's' : ''})</td>
                        <td class="text-right">${formatCurrency(booking.PackageCost)}</td>
                    </tr>
                    ${booking.ExcessFee > 0 ? `
                    <tr>
                        <td>Excess Fee</td>
                        <td class="text-right">${formatCurrency(booking.ExcessFee)}</td>
                    </tr>` : ''}
                    ${booking.SeniorDiscount > 0 ? `
                    <tr>
                        <td>Senior Citizen Discount</td>
                        <td class="text-right text-success">-${formatCurrency(booking.SeniorDiscount)}</td>
                    </tr>` : ''}
                    <tr class="font-weight-bold">
                        <td>Booking Total</td>
                        <td class="text-right">${formatCurrency(booking.BookingTotal)}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Payments Received</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Purpose</th>
                        <th>Method</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${paymentsHTML}
                </tbody>
            </table>
        </div>
        
        ${data.rentals.length > 0 ? `
        <div class="report-section">
            <h3>Rental Items</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th>Usage</th>
                        <th class="text-right">Rental Fee</th>
                        <th class="text-right">Damage Fee</th>
                        <th class="text-right">Lost Fee</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${rentalsHTML}
                </tbody>
            </table>
        </div>` : ''}
        
        ${data.unpaidItems.length > 0 ? `
        <div class="report-section">
            <h3>Store Purchases</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${unpaidItemsHTML}
                </tbody>
            </table>
        </div>` : ''}
        
        <div class="report-summary">
            <h4>Grand Total</h4>
            <div class="report-summary-grid">
                <div class="summary-item">
                    <span class="summary-label">Booking Amount</span>
                    <span class="summary-value">${formatCurrency(data.totals.BookingAmount)}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Rental Amount</span>
                    <span class="summary-value">${formatCurrency(data.totals.RentalAmount)}</span>
                </div>
                <div class="summary-item highlight">
                    <span class="summary-label"><strong>Total Collected</strong></span>
                    <span class="summary-value"><strong>${formatCurrency(data.totals.GrandTotal)}</strong></span>
                </div>
            </div>
        </div>
    `;
}

// Generate Monthly preview content
function generateMonthlyPreview(data) {
    const formatCurrency = (amount) => 'PHP ' + parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    // Daily sales table
    let dailySalesHTML = '';
    if (data.daily_sales && data.daily_sales.length > 0) {
        data.daily_sales.forEach(day => {
            dailySalesHTML += `
                <tr>
                    <td>${day.date}</td>
                    <td class="text-right">${formatCurrency(day.total)}</td>
                </tr>
            `;
        });
    } else {
        dailySalesHTML = '<tr><td colspan="2" class="text-center">No sales data for this period</td></tr>';
    }
    
    // Package sales table
    let packageSalesHTML = '';
    if (data.package_sales && data.package_sales.length > 0) {
        data.package_sales.forEach(pkg => {
            packageSalesHTML += `
                <tr>
                    <td>${pkg.name}</td>
                    <td class="text-center">${pkg.bookings}</td>
                    <td class="text-right">${formatCurrency(pkg.sales)}</td>
                </tr>
            `;
        });
        packageSalesHTML += `
            <tr style="background: #f3f4f6; font-weight: bold;">
                <td colspan="2"><strong>Total</strong></td>
                <td class="text-right"><strong>${formatCurrency(data.package_sales_total || 0)}</strong></td>
            </tr>
        `;
    } else {
        packageSalesHTML = '<tr><td colspan="3" class="text-center">No package data available</td></tr>';
    }
    
    // Rental item sales table
    let rentalItemSalesHTML = '';
    if (data.rental_item_sales && data.rental_item_sales.length > 0) {
        data.rental_item_sales.forEach(rental => {
            rentalItemSalesHTML += `
                <tr>
                    <td>${rental.name}</td>
                    <td class="text-center">${rental.times_rented}</td>
                    <td class="text-right">${formatCurrency(rental.sales)}</td>
                </tr>
            `;
        });
        rentalItemSalesHTML += `
            <tr style="background: #f3f4f6; font-weight: bold;">
                <td colspan="2"><strong>Total</strong></td>
                <td class="text-right"><strong>${formatCurrency(data.rental_item_sales_total || 0)}</strong></td>
            </tr>
        `;
    } else {
        rentalItemSalesHTML = '<tr><td colspan="3" class="text-center">No rental data available</td></tr>';
    }
    
    // Rental charges
    let rentalCharges = data.rental_charges || {damage_fees: 0, lost_fees: 0, total: 0};
    
    // Weekly summary table
    let weeklySummaryHTML = '';
    if (data.weekly_summary && data.weekly_summary.length > 0) {
        data.weekly_summary.forEach(week => {
            weeklySummaryHTML += `
                <tr>
                    <td>${week.week}</td>
                    <td>${week.date_range}</td>
                    <td class="text-right">${formatCurrency(week.sales)}</td>
                </tr>
            `;
        });
    } else {
        weeklySummaryHTML = '<tr><td colspan="3" class="text-center">No weekly data available</td></tr>';
    }
    
    return `
        <div class="report-section">
            <h3>Period Overview</h3>
            <div class="info-grid">
                <div><strong>Period:</strong> ${data.period.month}</div>
                <div><strong>Total Sales:</strong> ${formatCurrency(data.totals.sales)}</div>
                <div><strong>Total Bookings:</strong> ${data.totals.bookings}</div>
            </div>
        </div>
        
        <div class="report-section">
            <h3>Package Sales</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Package Name</th>
                        <th class="text-center">Bookings</th>
                        <th class="text-right">Sales</th>
                    </tr>
                </thead>
                <tbody>
                    ${packageSalesHTML}
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Rental Item Sales</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th class="text-center">Times Rented</th>
                        <th class="text-right">Sales</th>
                    </tr>
                </thead>
                <tbody>
                    ${rentalItemSalesHTML}
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Rental Charges Summary</h3>
            <table class="report-table">
                <tbody>
                    <tr>
                        <td>Damage Fees</td>
                        <td class="text-right">${formatCurrency(rentalCharges.damage_fees)}</td>
                    </tr>
                    <tr>
                        <td>Lost/Missing Item Fees</td>
                        <td class="text-right">${formatCurrency(rentalCharges.lost_fees)}</td>
                    </tr>
                    <tr style="background: #f3f4f6; font-weight: bold;">
                        <td><strong>Total Charges</strong></td>
                        <td class="text-right"><strong>${formatCurrency(rentalCharges.total)}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Daily Sales Breakdown</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Sales</th>
                    </tr>
                </thead>
                <tbody>
                    ${dailySalesHTML}
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Weekly Sales Summary</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Week</th>
                        <th>Date Range</th>
                        <th class="text-right">Sales</th>
                    </tr>
                </thead>
                <tbody>
                    ${weeklySummaryHTML}
                </tbody>
            </table>
        </div>
    `;
}

// Generate Annual preview content
function generateAnnualPreview(data) {
    const formatCurrency = (amount) => 'PHP ' + parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    // Monthly sales table with growth
    let monthlySalesHTML = '';
    if (data.monthly_sales && data.monthly_sales.length > 0) {
        data.monthly_sales.forEach(month => {
            const growthClass = month.growth >= 0 ? 'text-success' : 'text-danger';
            const growthIcon = month.growth >= 0 ? '↑' : '↓';
            monthlySalesHTML += `
                <tr>
                    <td>${month.month}</td>
                    <td class="text-right">${formatCurrency(month.sales)}</td>
                    <td class="text-right ${growthClass}">${growthIcon} ${Math.abs(month.growth)}%</td>
                </tr>
            `;
        });
    } else {
        monthlySalesHTML = '<tr><td colspan="3" class="text-center">No sales data for this period</td></tr>';
    }
    
    // Package performance table
    let packagePerformanceHTML = '';
    if (data.package_performance && data.package_performance.length > 0) {
        data.package_performance.forEach(pkg => {
            packagePerformanceHTML += `
                <tr>
                    <td>${pkg.name}</td>
                    <td class="text-center">${pkg.bookings}</td>
                    <td class="text-right">${formatCurrency(pkg.sales)}</td>
                </tr>
            `;
        });
    } else {
        packagePerformanceHTML = '<tr><td colspan="3" class="text-center">No package data available</td></tr>';
    }
    
    // Rental performance table
    let rentalPerformanceHTML = '';
    if (data.rental_performance && data.rental_performance.length > 0) {
        data.rental_performance.forEach(rental => {
            rentalPerformanceHTML += `
                <tr>
                    <td>${rental.name}</td>
                    <td class="text-center">${rental.times_rented}</td>
                    <td class="text-right">${formatCurrency(rental.sales)}</td>
                </tr>
            `;
        });
    } else {
        rentalPerformanceHTML = '<tr><td colspan="3" class="text-center">No rental data available</td></tr>';
    }
    
    // Rental charges
    let rentalCharges = data.rental_charges || {damage_fees: 0, lost_fees: 0, total: 0};
    
    return `
        <div class="report-section">
            <h3>Year Overview</h3>
            <div class="info-grid">
                <div><strong>Year:</strong> ${data.period.year}</div>
                <div><strong>Total Sales:</strong> ${formatCurrency(data.totals.sales)}</div>
                <div><strong>Total Bookings:</strong> ${data.totals.bookings}</div>
            </div>
        </div>
        
        <div class="report-section">
            <h3>Monthly Sales Trends</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="text-right">Sales</th>
                        <th class="text-right">Growth</th>
                    </tr>
                </thead>
                <tbody>
                    ${monthlySalesHTML}
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Package Performance</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Package Name</th>
                        <th class="text-center">Total Bookings</th>
                        <th class="text-right">Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    ${packagePerformanceHTML}
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Rental Performance</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th class="text-center">Times Rented</th>
                        <th class="text-right">Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    ${rentalPerformanceHTML}
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Rental Charges Summary</h3>
            <table class="report-table">
                <tbody>
                    <tr>
                        <td>Damage Fees</td>
                        <td class="text-right">${formatCurrency(rentalCharges.damage_fees)}</td>
                    </tr>
                    <tr>
                        <td>Lost/Missing Item Fees</td>
                        <td class="text-right">${formatCurrency(rentalCharges.lost_fees)}</td>
                    </tr>
                    <tr style="background: #f3f4f6; font-weight: bold;">
                        <td><strong>Total Charges</strong></td>
                        <td class="text-right"><strong>${formatCurrency(rentalCharges.total)}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
}

// Generate Custom preview content
function generateCustomPreview(data) {
    const formatCurrency = (amount) => 'PHP ' + parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    // Daily sales table
    let dailySalesHTML = '';
    if (data.daily_sales && data.daily_sales.length > 0) {
        data.daily_sales.forEach(day => {
            dailySalesHTML += `
                <tr>
                    <td>${day.date}</td>
                    <td class="text-right">${formatCurrency(day.total)}</td>
                </tr>
            `;
        });
    } else {
        dailySalesHTML = '<tr><td colspan="2" class="text-center">No sales data for this period</td></tr>';
    }
    
    // Package sales table
    let packageSalesHTML = '';
    if (data.package_sales && data.package_sales.length > 0) {
        data.package_sales.forEach(pkg => {
            packageSalesHTML += `
                <tr>
                    <td>${pkg.name}</td>
                    <td class="text-center">${pkg.bookings}</td>
                    <td class="text-right">${formatCurrency(pkg.sales)}</td>
                </tr>
            `;
        });
    } else {
        packageSalesHTML = '<tr><td colspan="3" class="text-center">No package data available</td></tr>';
    }
    
    // Rental item sales table
    let rentalItemSalesHTML = '';
    if (data.rental_item_sales && data.rental_item_sales.length > 0) {
        data.rental_item_sales.forEach(rental => {
            rentalItemSalesHTML += `
                <tr>
                    <td>${rental.name}</td>
                    <td class="text-center">${rental.times_rented}</td>
                    <td class="text-right">${formatCurrency(rental.sales)}</td>
                </tr>
            `;
        });
    } else {
        rentalItemSalesHTML = '<tr><td colspan="3" class="text-center">No rental data available</td></tr>';
    }
    
    // Rental charges
    let rentalCharges = data.rental_charges || {damage_fees: 0, lost_fees: 0, total: 0};
    
    // Transactions table
    let transactionsHTML = '';
    if (data.transactions && data.transactions.length > 0) {
        data.transactions.forEach(txn => {
            const voidedStyle = txn.is_voided ? ' style="opacity: 0.5; text-decoration: line-through;"' : '';
            // Format date with time (e.g., Dec 01, 2022 12:00 AM)
            const txnDate = new Date(txn.date);
            const formattedDate = txnDate.toLocaleDateString('en-US', { 
                month: 'short', 
                day: '2-digit', 
                year: 'numeric' 
            }) + ' ' + txnDate.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            
            transactionsHTML += `
                <tr${voidedStyle}>
                    <td>${formattedDate}</td>
                    <td>#${txn.transaction_id}</td>
                    <td>${txn.booking_payment_id}</td>
                    <td>${txn.source}</td>
                    <td>${txn.guest_name}</td>
                    <td>${txn.purpose}</td>
                    <td>${txn.payment_method}</td>
                    <td class="text-right">${formatCurrency(txn.amount)}</td>
                </tr>
            `;
        });
    } else {
        transactionsHTML = '<tr><td colspan="8" class="text-center">No transactions found for this period</td></tr>';
    }
    
    return `
        <div class="report-section">
            <h3>Period Overview</h3>
            <div class="info-grid">
                <div><strong>Date Range:</strong> ${data.period.formatted_range}</div>
                <div><strong>Duration:</strong> ${Math.floor(data.period.days)} ${data.period.days === 1 ? 'day' : 'days'}</div>
                <div><strong>Total Sales:</strong> ${formatCurrency(data.totals.sales)}</div>
                <div><strong>Total Bookings:</strong> ${data.totals.bookings}</div>
            </div>
        </div>
        
        <div class="report-section">
            <h3>Package Sales</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Package Name</th>
                        <th class="text-center">Bookings</th>
                        <th class="text-right">Sales</th>
                    </tr>
                </thead>
                <tbody>
                    ${packageSalesHTML}
                    <tr style="background: #f3f4f6; font-weight: bold;">
                        <td colspan="2"><strong>Total</strong></td>
                        <td class="text-right"><strong>${formatCurrency(data.package_sales_total || 0)}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Rental Item Sales</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th class="text-center">Times Rented</th>
                        <th class="text-right">Sales</th>
                    </tr>
                </thead>
                <tbody>
                    ${rentalItemSalesHTML}
                    <tr style="background: #f3f4f6; font-weight: bold;">
                        <td colspan="2"><strong>Total</strong></td>
                        <td class="text-right"><strong>${formatCurrency(data.rental_item_sales_total || 0)}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Rental Charges Summary</h3>
            <table class="report-table">
                <tbody>
                    <tr>
                        <td>Damage Fees</td>
                        <td class="text-right">${formatCurrency(rentalCharges.damage_fees)}</td>
                    </tr>
                    <tr>
                        <td>Lost/Missing Item Fees</td>
                        <td class="text-right">${formatCurrency(rentalCharges.lost_fees)}</td>
                    </tr>
                    <tr style="background: #f3f4f6; font-weight: bold;">
                        <td><strong>Total Charges</strong></td>
                        <td class="text-right"><strong>${formatCurrency(rentalCharges.total)}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Daily Sales Breakdown</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Sales</th>
                    </tr>
                </thead>
                <tbody>
                    ${dailySalesHTML}
                </tbody>
            </table>
        </div>
        
        <div class="report-section">
            <h3>Transaction History</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction ID</th>
                        <th>Booking/Payment ID</th>
                        <th>Source</th>
                        <th>Guest Name</th>
                        <th>Purpose</th>
                        <th>Method</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${transactionsHTML}
                </tbody>
            </table>
        </div>
    `;
}

// Get report title
function getReportTitle(type) {
    const titles = {
        'per-booking': 'Per Booking Sales Report',
        'daily': 'Daily Sales Report',
        'monthly': 'Monthly Sales Report',
        'annual': 'Annual Sales Report',
        'custom': 'Custom Sales Report'
    };
    return titles[type] || 'Sales Report';
}

// Generate sample data for preview
function generateSampleData(type) {
    if (type === 'daily') {
        return {
            table: `
                <div class="report-section">
                    <h3 class="report-section-title">Booking Transactions</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Guest Name</th>
                                <th>Package</th>
                                <th>Check-in</th>
                                <th>Payment Method</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>BK-2025-001</td>
                                <td>Juan Dela Cruz</td>
                                <td>Day Tour Package</td>
                                <td>Nov 2, 2025</td>
                                <td>GCash</td>
                                <td>₱12,000.00</td>
                            </tr>
                            <tr>
                                <td>BK-2025-002</td>
                                <td>Maria Santos</td>
                                <td>Overnight Stay</td>
                                <td>Nov 2, 2025</td>
                                <td>Cash</td>
                                <td>₱15,000.00</td>
                            </tr>
                            <tr>
                                <td>BK-2025-003</td>
                                <td>Pedro Reyes</td>
                                <td>Day Tour Package</td>
                                <td>Nov 2, 2025</td>
                                <td>BDO Transfer</td>
                                <td>₱12,000.00</td>
                            </tr>
                            <tr class="subtotal-row">
                                <td colspan="5"><strong>Bookings Subtotal</strong></td>
                                <td><strong>₱39,000.00</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="report-section">
                    <h3 class="report-section-title">Rental Add-ons</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Guest Name</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Payment Method</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>BK-2025-001</td>
                                <td>Juan Dela Cruz</td>
                                <td>Karaoke Set</td>
                                <td>1</td>
                                <td>GCash</td>
                                <td>₱1,500.00</td>
                            </tr>
                            <tr>
                                <td>BK-2025-001</td>
                                <td>Juan Dela Cruz</td>
                                <td>Extra Towels</td>
                                <td>3</td>
                                <td>GCash</td>
                                <td>₱300.00</td>
                            </tr>
                            <tr>
                                <td>BK-2025-002</td>
                                <td>Maria Santos</td>
                                <td>BBQ Grill</td>
                                <td>1</td>
                                <td>Cash</td>
                                <td>₱800.00</td>
                            </tr>
                            <tr>
                                <td>BK-2025-003</td>
                                <td>Pedro Reyes</td>
                                <td>Projector</td>
                                <td>1</td>
                                <td>BDO Transfer</td>
                                <td>₱2,000.00</td>
                            </tr>
                            <tr class="subtotal-row">
                                <td colspan="5"><strong>Rentals Subtotal</strong></td>
                                <td><strong>₱4,600.00</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `,
            summary: `
                <div class="summary-item">
                    <div class="summary-item-label">Total Bookings</div>
                    <div class="summary-item-value">3</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Booking Revenue</div>
                    <div class="summary-item-value">₱39,000.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Rental Revenue</div>
                    <div class="summary-item-value">₱4,600.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Total Revenue</div>
                    <div class="summary-item-value">₱43,600.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Cash Payments</div>
                    <div class="summary-item-value">₱15,800.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">GCash Payments</div>
                    <div class="summary-item-value">₱13,800.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Bank Transfer</div>
                    <div class="summary-item-value">₱14,000.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Average Transaction</div>
                    <div class="summary-item-value">₱14,533.33</div>
                </div>
            `
        };
    } else if (type === 'monthly') {
        return {
            table: `
                <div class="report-section">
                    <h3 class="report-section-title">Daily Booking Revenue</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Bookings Count</th>
                                <th>Booking Revenue</th>
                                <th>Rental Revenue</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Nov 1, 2025</td>
                                <td>5</td>
                                <td>₱55,000.00</td>
                                <td>₱8,500.00</td>
                                <td>₱63,500.00</td>
                            </tr>
                            <tr>
                                <td>Nov 2, 2025</td>
                                <td>3</td>
                                <td>₱39,000.00</td>
                                <td>₱4,600.00</td>
                                <td>₱43,600.00</td>
                            </tr>
                            <tr class="subtotal-row">
                                <td><strong>Month to Date</strong></td>
                                <td><strong>8</strong></td>
                                <td><strong>₱94,000.00</strong></td>
                                <td><strong>₱13,100.00</strong></td>
                                <td><strong>₱107,100.00</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="report-section">
                    <h3 class="report-section-title">Top Packages</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Day Tour Package</td>
                                <td>5</td>
                                <td>₱60,000.00</td>
                                <td>63.8%</td>
                            </tr>
                            <tr>
                                <td>Overnight Stay</td>
                                <td>3</td>
                                <td>₱34,000.00</td>
                                <td>36.2%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="report-section">
                    <h3 class="report-section-title">Top Rental Items</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Times Rented</th>
                                <th>Revenue</th>
                                <th>% of Rental</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Karaoke Set</td>
                                <td>4</td>
                                <td>₱6,000.00</td>
                                <td>45.8%</td>
                            </tr>
                            <tr>
                                <td>Projector</td>
                                <td>3</td>
                                <td>₱6,000.00</td>
                                <td>45.8%</td>
                            </tr>
                            <tr>
                                <td>BBQ Grill</td>
                                <td>2</td>
                                <td>₱1,100.00</td>
                                <td>8.4%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `,
            summary: `
                <div class="summary-item">
                    <div class="summary-item-label">Total Bookings</div>
                    <div class="summary-item-value">8</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Total Revenue</div>
                    <div class="summary-item-value">₱107,100.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Booking Revenue</div>
                    <div class="summary-item-value">₱94,000.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Rental Revenue</div>
                    <div class="summary-item-value">₱13,100.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Avg Daily Revenue</div>
                    <div class="summary-item-value">₱53,550.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Rental Attach Rate</div>
                    <div class="summary-item-value">87.5%</div>
                </div>
            `
        };
    } else if (type === 'annual') {
        return {
            table: `
                <div class="report-section">
                    <h3 class="report-section-title">Monthly Revenue Summary</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Bookings</th>
                                <th>Booking Revenue</th>
                                <th>Rental Revenue</th>
                                <th>Total Revenue</th>
                                <th>Growth</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>January</td>
                                <td>45</td>
                                <td>₱520,000.00</td>
                                <td>₱78,000.00</td>
                                <td>₱598,000.00</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>February</td>
                                <td>52</td>
                                <td>₱595,000.00</td>
                                <td>₱89,250.00</td>
                                <td>₱684,250.00</td>
                                <td>+14.4%</td>
                            </tr>
                            <tr class="subtotal-row">
                                <td><strong>Year to Date</strong></td>
                                <td><strong>97</strong></td>
                                <td><strong>₱1,115,000.00</strong></td>
                                <td><strong>₱167,250.00</strong></td>
                                <td><strong>₱1,282,250.00</strong></td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="report-section">
                    <h3 class="report-section-title">Payment Method Distribution</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Payment Method</th>
                                <th>Transactions</th>
                                <th>Amount</th>
                                <th>% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>GCash</td>
                                <td>45</td>
                                <td>₱576,000.00</td>
                                <td>44.9%</td>
                            </tr>
                            <tr>
                                <td>Cash</td>
                                <td>38</td>
                                <td>₱487,250.00</td>
                                <td>38.0%</td>
                            </tr>
                            <tr>
                                <td>Bank Transfer</td>
                                <td>14</td>
                                <td>₱219,000.00</td>
                                <td>17.1%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `,
            summary: `
                <div class="summary-item">
                    <div class="summary-item-label">Total Bookings</div>
                    <div class="summary-item-value">97</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Total Revenue</div>
                    <div class="summary-item-value">₱1,282,250.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Booking Revenue</div>
                    <div class="summary-item-value">₱1,115,000.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Rental Revenue</div>
                    <div class="summary-item-value">₱167,250.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Avg Monthly Revenue</div>
                    <div class="summary-item-value">₱641,125.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Rental Attach Rate</div>
                    <div class="summary-item-value">85.6%</div>
                </div>
            `
        };
    } else {
        // Custom report
        return {
            table: `
                <div class="report-section">
                    <h3 class="report-section-title">Booking Transactions</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Booking ID</th>
                                <th>Guest Name</th>
                                <th>Package</th>
                                <th>Payment Method</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Nov 1, 2025</td>
                                <td>BK-2025-001</td>
                                <td>Juan Dela Cruz</td>
                                <td>Day Tour Package</td>
                                <td>GCash</td>
                                <td>₱12,000.00</td>
                            </tr>
                            <tr>
                                <td>Nov 1, 2025</td>
                                <td>BK-2025-002</td>
                                <td>Maria Santos</td>
                                <td>Overnight Stay</td>
                                <td>Cash</td>
                                <td>₱15,000.00</td>
                            </tr>
                            <tr>
                                <td>Nov 2, 2025</td>
                                <td>BK-2025-003</td>
                                <td>Pedro Reyes</td>
                                <td>Day Tour Package</td>
                                <td>BDO Transfer</td>
                                <td>₱12,000.00</td>
                            </tr>
                            <tr class="subtotal-row">
                                <td colspan="5"><strong>Bookings Subtotal</strong></td>
                                <td><strong>₱39,000.00</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="report-section">
                    <h3 class="report-section-title">Rental Add-ons by Booking</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Booking ID</th>
                                <th>Guest Name</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Nov 1, 2025</td>
                                <td>BK-2025-001</td>
                                <td>Juan Dela Cruz</td>
                                <td>Karaoke Set</td>
                                <td>1</td>
                                <td>₱1,500.00</td>
                            </tr>
                            <tr>
                                <td>Nov 1, 2025</td>
                                <td>BK-2025-002</td>
                                <td>Maria Santos</td>
                                <td>BBQ Grill</td>
                                <td>1</td>
                                <td>₱800.00</td>
                            </tr>
                            <tr>
                                <td>Nov 2, 2025</td>
                                <td>BK-2025-003</td>
                                <td>Pedro Reyes</td>
                                <td>Projector</td>
                                <td>1</td>
                                <td>₱2,000.00</td>
                            </tr>
                            <tr class="subtotal-row">
                                <td colspan="5"><strong>Rentals Subtotal</strong></td>
                                <td><strong>₱4,300.00</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `,
            summary: `
                <div class="summary-item">
                    <div class="summary-item-label">Total Bookings</div>
                    <div class="summary-item-value">3</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Total Revenue</div>
                    <div class="summary-item-value">₱43,300.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Booking Revenue</div>
                    <div class="summary-item-value">₱39,000.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Rental Revenue</div>
                    <div class="summary-item-value">₱4,300.00</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Rental Attach Rate</div>
                    <div class="summary-item-value">100%</div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Avg Booking Value</div>
                    <div class="summary-item-value">₱14,433.33</div>
                </div>
            `
        };
    }
}

// Get month name
function getMonthName(monthNumber) {
    const months = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'];
    return months[monthNumber - 1];
}

// Add to recent reports
function addToRecentReports(reportData) {
    const recentReports = JSON.parse(localStorage.getItem('recentReports') || '[]');
    
    recentReports.unshift({
        ...reportData,
        generatedAt: new Date().toISOString()
    });
    
    // Keep only last 10 reports
    recentReports.splice(10);
    
    localStorage.setItem('recentReports', JSON.stringify(recentReports));
    loadRecentReports();
}

// Load recent reports
function loadRecentReports() {
    const container = document.getElementById('recentReportsList');
    if (!container) return;
    
    const recentReports = JSON.parse(localStorage.getItem('recentReports') || '[]');
    
    if (recentReports.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <p>No reports generated yet</p>
                <span>Your generated reports will appear here</span>
            </div>
        `;
        return;
    }
    
    container.innerHTML = recentReports.map((report, index) => `
        <div class="report-item">
            <div class="report-item-icon">
                <i class="fas fa-file-${getFormatIcon(report.format)}"></i>
            </div>
            <div class="report-item-info">
                <div class="report-item-name">${report.fileName}.${report.format}</div>
                <div class="report-item-date">${formatReportDate(report.generatedAt)}</div>
            </div>
            <div class="report-item-actions">
                <button type="button" class="btn btn-outline-sm" onclick="downloadReport(${index})">
                    <i class="fas fa-download"></i>
                    Download
                </button>
            </div>
        </div>
    `).join('');
}

// Get format icon
function getFormatIcon(format) {
    const icons = {
        'pdf': 'pdf',
        'csv': 'csv'
    };
    return icons[format] || 'alt';
}

// Format report date
function formatReportDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Download report
function downloadReport(index) {
    const recentReports = JSON.parse(localStorage.getItem('recentReports') || '[]');
    const report = recentReports[index];
    
    if (report) {
        Swal.fire({
            icon: 'info',
            title: 'Preparing Download',
            text: `Preparing ${report.fileName}.${report.format} for download...`,
            confirmButtonColor: SwalConfig.confirmButtonColor,
            customClass: SwalConfig.customClass,
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false
        });
        // Implement actual download logic here
    }
}

// Handle clear history
function handleClearHistory() {
    Swal.fire({
        title: 'Clear Report History?',
        text: "All report history will be permanently removed. This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: SwalConfig.cancelButtonColor,
        confirmButtonText: 'Yes, clear it!',
        cancelButtonText: 'Cancel',
        customClass: SwalConfig.customClass
    }).then((result) => {
        if (result.isConfirmed) {
            localStorage.removeItem('recentReports');
            loadRecentReports();
            Swal.fire({
                icon: 'success',
                title: 'Cleared!',
                text: 'Report history has been cleared.',
                confirmButtonColor: SwalConfig.confirmButtonColor,
                customClass: SwalConfig.customClass,
                timer: 2000,
                timerProgressBar: true
            });
        }
    });
}

// Modal functions
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
