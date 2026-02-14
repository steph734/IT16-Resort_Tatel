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

// ========================================
// SWEETALERT2 HELPER FUNCTIONS
// ========================================

/**
 * Show success message
 */
function showSuccess(title, text, timer = 1500) {
    return Swal.fire({
        ...SwalConfig,
        icon: 'success',
        title: title,
        text: text,
        showConfirmButton: false,
        timer: timer
    });
}

/**
 * Show error message
 */
function showError(title, text) {
    return Swal.fire({
        ...SwalConfig,
        icon: 'error',
        title: title,
        text: text
    });
}

/**
 * Show warning message
 */
function showWarning(title, text) {
    return Swal.fire({
        ...SwalConfig,
        icon: 'warning',
        title: title,
        text: text
    });
}

/**
 * Show info message
 */
function showInfo(title, text) {
    return Swal.fire({
        ...SwalConfig,
        icon: 'info',
        title: title,
        text: text
    });
}

/**
 * Show confirmation dialog
 */
function showConfirm(title, text, confirmText = 'Confirm', cancelText = 'Cancel') {
    return Swal.fire({
        ...SwalConfig,
        icon: 'question',
        title: title,
        text: text,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    });
}

/**
 * Show confirmation with HTML content
 */
function showConfirmHTML(title, html, confirmText = 'Confirm', cancelText = 'Cancel') {
    return Swal.fire({
        ...SwalConfig,
        icon: 'question',
        title: title,
        html: html,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    });
}

/**
 * Show input dialog
 */
function showInput(title, text, inputPlaceholder, validatorMessage) {
    return Swal.fire({
        ...SwalConfig,
        icon: 'question',
        title: title,
        text: text,
        input: 'text',
        inputPlaceholder: inputPlaceholder,
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return validatorMessage;
            }
        }
    });
}

/**
 * Show loading indicator
 */
function showLoading(title = 'Processing...', text = 'Please wait') {
    return Swal.fire({
        ...SwalConfig,
        title: title,
        text: text,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

// Export functions to window object for use in inline scripts
window.showSuccess = showSuccess;
window.showError = showError;
window.showWarning = showWarning;
window.showInfo = showInfo;
window.showConfirm = showConfirm;
window.showConfirmHTML = showConfirmHTML;
window.showInput = showInput;
window.showLoading = showLoading;
window.SwalConfig = SwalConfig;
