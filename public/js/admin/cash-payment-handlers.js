/**
 * Cash Payment Handlers
 * Functions to calculate change and toggle cash-specific fields
 */

// ====== PAYMENT HISTORY (Add Payment Modal) ======
function calculateChangePaymentHistory() {
    const amountReceived = parseFloat(document.getElementById('amountReceivedPaymentHistory')?.value) || 0;
    const totalAmount = parseFloat(document.getElementById('paymentHistoryAmount')?.value) || 0;
    const change = Math.max(0, amountReceived - totalAmount);
    
    const changeInput = document.getElementById('changeAmountPaymentHistory');
    if (changeInput) {
        changeInput.value = change.toFixed(2);
    }
}

// ====== NEW BOOKING MODAL ======
function calculateChangeNewBooking() {
    const amountReceived = parseFloat(document.getElementById('amountReceivedNewBooking')?.value) || 0;
    const totalAmount = parseFloat(document.getElementById('paymentAmount')?.value) || 0;
    const change = Math.max(0, amountReceived - totalAmount);
    
    const changeInput = document.getElementById('changeAmountNewBooking');
    if (changeInput) {
        changeInput.value = change.toFixed(2);
    }
}

// ====== CREATE BOOKING PAGE ======
function calculateChangeCreateBooking() {
    const amountReceived = parseFloat(document.getElementById('amountReceivedCreateBooking')?.value) || 0;
    const totalAmount = parseFloat(document.getElementById('paymentAmount')?.value) || 0;
    const change = Math.max(0, amountReceived - totalAmount);
    
    const changeInput = document.getElementById('changeAmountCreateBooking');
    if (changeInput) {
        changeInput.value = change.toFixed(2);
    }
}

// ====== BILL OUT MODAL ======
function calculateChangeBillOut() {
    const amountReceived = parseFloat(document.getElementById('billOutAmountReceived')?.value) || 0;
    const totalAmount = parseFloat(document.getElementById('billOutAmount')?.value) || 0;
    const change = Math.max(0, amountReceived - totalAmount);
    
    const changeInput = document.getElementById('billOutChange');
    if (changeInput) {
        changeInput.value = change.toFixed(2);
    }
}

// ====== RENTAL PAYMENT MODAL ======
function calculateChangeRental() {
    const amountReceived = parseFloat(document.getElementById('rentalAmountReceived')?.value) || 0;
    const totalAmount = parseFloat(document.getElementById('rentalAmount')?.value) || 0;
    const change = Math.max(0, amountReceived - totalAmount);
    
    const changeInput = document.getElementById('rentalChange');
    if (changeInput) {
        changeInput.value = change.toFixed(2);
    }
}

// ====== TOGGLE FUNCTIONS FOR CASH FIELDS ======

// Toggle cash fields in Add Payment modal (bookings.blade.php)
function toggleCashFieldsPaymentHistory() {
    const paymentMethod = document.getElementById('paymentMethod')?.value;
    const cashFields = document.getElementById('cashFieldsPaymentHistory');
    
    if (cashFields) {
        if (paymentMethod === 'cash') {
            cashFields.style.display = 'flex';
        } else {
            cashFields.style.display = 'none';
            // Clear fields when hidden
            if (document.getElementById('amountReceivedPaymentHistory')) {
                document.getElementById('amountReceivedPaymentHistory').value = '';
            }
            if (document.getElementById('changeAmountPaymentHistory')) {
                document.getElementById('changeAmountPaymentHistory').value = '';
            }
        }
    }
}

// Toggle cash fields in New Booking modal (bookings.blade.php)
function toggleCashFieldsNewBooking() {
    const cashFields = document.getElementById('cashFieldsNewBooking');
    
    if (cashFields) {
        if (selectedPaymentMethod === 'cash') {
            cashFields.style.display = 'flex';
        } else {
            cashFields.style.display = 'none';
            // Clear fields when hidden
            if (document.getElementById('amountReceivedNewBooking')) {
                document.getElementById('amountReceivedNewBooking').value = '';
            }
            if (document.getElementById('changeAmountNewBooking')) {
                document.getElementById('changeAmountNewBooking').value = '';
            }
        }
    }
}

// Toggle cash fields in Create Booking page (create.blade.php)
function toggleCashFieldsCreateBooking() {
    const cashFields = document.getElementById('cashFieldsCreateBooking');
    
    if (cashFields) {
        if (selectedPaymentMethod === 'cash') {
            cashFields.style.display = 'grid';
        } else {
            cashFields.style.display = 'none';
            // Clear fields when hidden
            if (document.getElementById('amountReceivedCreateBooking')) {
                document.getElementById('amountReceivedCreateBooking').value = '';
            }
            if (document.getElementById('changeAmountCreateBooking')) {
                document.getElementById('changeAmountCreateBooking').value = '';
            }
        }
    }
}

// Toggle cash fields in Bill Out modal (currently-staying.blade.php)
function toggleCashFieldsBillOut() {
    const paymentMethod = document.getElementById('billOutPaymentMethod')?.value;
    const cashFields = document.getElementById('billOutCashFields');
    
    if (cashFields) {
        if (paymentMethod === 'Cash') {
            cashFields.style.display = 'block';
        } else {
            cashFields.style.display = 'none';
            // Clear fields when hidden
            if (document.getElementById('billOutAmountReceived')) {
                document.getElementById('billOutAmountReceived').value = '';
            }
            if (document.getElementById('billOutChange')) {
                document.getElementById('billOutChange').value = '';
            }
        }
    }
}

// Toggle cash fields in Rental Payment modal (currently-staying.blade.php)
function toggleCashFieldsRental() {
    const paymentMethod = document.getElementById('rentalPaymentMethod')?.value;
    const cashFields = document.getElementById('rentalCashFields');
    
    if (cashFields) {
        if (paymentMethod === 'Cash') {
            cashFields.style.display = 'block';
        } else {
            cashFields.style.display = 'none';
            // Clear fields when hidden
            if (document.getElementById('rentalAmountReceived')) {
                document.getElementById('rentalAmountReceived').value = '';
            }
            if (document.getElementById('rentalChange')) {
                document.getElementById('rentalChange').value = '';
            }
        }
    }
}
