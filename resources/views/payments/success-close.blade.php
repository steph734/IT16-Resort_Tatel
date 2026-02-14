<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md mx-auto p-8 bg-white rounded-lg shadow-lg text-center">
        <!-- Success Icon -->
        <div class="mb-6">
            <svg class="w-20 h-20 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <!-- Success Message -->
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Payment Successful!</h1>
        <p class="text-gray-600 mb-6">
            Your payment for booking <strong class="text-teal-600">#{{ $booking->BookingID }}</strong> has been received.
        </p>

        <!-- Payment Details -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6 text-left">
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600 text-sm">Booking ID:</span>
                <span class="font-semibold text-gray-800">{{ $booking->BookingID }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Status:</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Payment Received
                </span>
            </div>
        </div>

        <!-- Auto Close Message -->
        <p class="text-sm text-gray-500 mb-4">
            This window will close automatically in <span id="countdown" class="font-semibold text-teal-600">3</span> seconds...
        </p>

        <!-- Manual Close Button -->
        <button onclick="window.close()" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
            Close Window
        </button>
    </div>

    <script>
        // Countdown and auto-close
        let countdown = 3;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.close();
                
                // Fallback: if window.close() doesn't work, redirect to home
                setTimeout(() => {
                    window.location.href = '/';
                }, 500);
            }
        }, 1000);
    </script>
</body>
</html>
