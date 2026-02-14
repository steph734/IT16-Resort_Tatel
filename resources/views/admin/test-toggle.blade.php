<!DOCTYPE html>
<html>
<head>
    <title>Close Date Toggle Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
        }
        button:hover {
            background: #2563eb;
        }
        .result {
            margin-top: 10px;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <h1>Close Date Toggle Test</h1>
    
    <div class="section">
        <h2>1. Get All Closed Dates</h2>
        <button onclick="getClosedDates()">Get Closed Dates</button>
        <div id="closedDatesResult" class="result"></div>
    </div>
    
    <div class="section">
        <h2>2. Toggle Close Date (2025-12-15)</h2>
        <button onclick="toggleCloseDate('2025-12-15', true)">Close Date</button>
        <button onclick="toggleCloseDate('2025-12-15', false)">Open Date</button>
        <div id="toggleResult" class="result"></div>
    </div>
    
    <div class="section">
        <h2>3. Get Calendar Data (December 2025)</h2>
        <button onclick="getCalendarData(2025, 12)">Get Calendar Data</button>
        <div id="calendarResult" class="result"></div>
    </div>

    <script>
        async function getClosedDates() {
            const resultDiv = document.getElementById('closedDatesResult');
            resultDiv.textContent = 'Loading...';
            resultDiv.className = 'result';
            
            try {
                const response = await fetch('/admin/bookings/closed-dates', {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                resultDiv.textContent = JSON.stringify(data, null, 2);
                resultDiv.className = 'result success';
            } catch (error) {
                resultDiv.textContent = 'Error: ' + error.message;
                resultDiv.className = 'result error';
            }
        }
        
        async function toggleCloseDate(date, isClosed) {
            const resultDiv = document.getElementById('toggleResult');
            resultDiv.textContent = 'Loading...';
            resultDiv.className = 'result';
            
            try {
                const response = await fetch('/admin/dashboard/toggle-closed-date', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        date: date,
                        is_closed: isClosed
                    })
                });
                
                const data = await response.json();
                resultDiv.textContent = `Action: ${isClosed ? 'Close' : 'Open'} date ${date}\n` + JSON.stringify(data, null, 2);
                resultDiv.className = 'result success';
                
                // Auto-refresh closed dates
                setTimeout(() => getClosedDates(), 500);
            } catch (error) {
                resultDiv.textContent = 'Error: ' + error.message;
                resultDiv.className = 'result error';
            }
        }
        
        async function getCalendarData(year, month) {
            const resultDiv = document.getElementById('calendarResult');
            resultDiv.textContent = 'Loading...';
            resultDiv.className = 'result';
            
            try {
                const response = await fetch(`/admin/dashboard/calendar-data?year=${year}&month=${month}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                resultDiv.textContent = JSON.stringify(data, null, 2);
                resultDiv.className = 'result success';
            } catch (error) {
                resultDiv.textContent = 'Error: ' + error.message;
                resultDiv.className = 'result error';
            }
        }
        
        // Auto-load closed dates on page load
        window.addEventListener('DOMContentLoaded', () => {
            getClosedDates();
        });
    </script>
</body>
</html>