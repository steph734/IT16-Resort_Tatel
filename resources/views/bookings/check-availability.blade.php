@extends('layouts.app')

@push('styles')
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Check Availability Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/check-availability.css') }}">
@endpush

@section('content')
    <div class="flex flex-col min-h-screen bg-resort-background text-gray-900">

        <!-- Topbar -->
        <header class="w-full">
            @include('partials.topbar-internal')
        </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-6 lg:px-24 py-8 pt-8">
            <!-- Page Header -->
            <nav class="text-gray-600 font-poppins text-sm tracking-wide mb-6">
                Home &gt; <span class="text-resort-primary font-semibold">Check Availability</span>
            </nav>

            <div class="flex justify-center">

                <!-- Main Calendar & Booking (centered, wider) -->
                <div class="w-full lg:w-11/12 space-y-6" x-data="calendar()">

                    <!-- Calendar Widget -->
                    <div class="calendar-widget">
                        <!-- Month Header -->
                        <div class="calendar-header">
                            <h2 class="calendar-title" x-text="monthNames[month] + ' ' + year"></h2>
                            <div class="calendar-nav">
                                <button type="button" @click="prevMonth()" class="calendar-nav-btn">
                                    <i class="fa-solid fa-chevron-left text-gray-700"></i>
                                </button>
                                <button type="button" @click="nextMonth()" class="calendar-nav-btn">
                                    <i class="fa-solid fa-chevron-right text-gray-700"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="calendar-legend">
                            <div class="legend-item">
                                <span class="legend-dot legend-dot-available"></span>
                                <span>Available</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot legend-dot-booked"></span>
                                <span>Booked</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot legend-dot-closed"></span>
                                <span>Closed</span>
                            </div>
                        </div>

                        <!-- Days of the Week -->
                        <div class="calendar-weekdays">
                            <div>SU</div>
                            <div>MO</div>
                            <div>TU</div>
                            <div>WE</div>
                            <div>TH</div>
                            <div>FR</div>
                            <div>SA</div>
                        </div>

                        <!-- Calendar Dates -->
                        <div class="calendar-dates">
                            <template x-for="(day, index) in daysInMonth" :key="index">
                                <div>
                                    <!-- Empty slots -->
                                    <template x-if="day === ''">
                                        <div class="w-12 h-12"></div>
                                    </template>

                                    <!-- Actual day -->
                                    <template x-if="day !== ''">
                                        <div class="relative">
                                            <div class="calendar-day"
                                                :class="{
                                                    'day-available': getDateStatus(day) === 'available',
                                                    'day-booked': getDateStatus(day) === 'booked',
                                                    'day-closed': getDateStatus(day) === 'closed',
                                                    'day-past': isPastDate(day),
                                                    'day-today': isToday(day),
                                                    'day-selected': selectedDate === formatDate(day)
                                                }"
                                                x-text="day"
                                                @click="selectDay(day)">
                                            </div>

                                            <!-- Indicator Dot for booked/closed -->
                                            <div class="day-indicator">
                                                <template x-if="getDateStatus(day) === 'booked' && !isPastDate(day)">
                                                    <span class="indicator-dot dot-booked"></span>
                                                </template>
                                                <template x-if="getDateStatus(day) === 'closed' && !isPastDate(day)">
                                                    <span class="indicator-dot dot-closed"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Booking Widget -->
                    <form action="{{ route('bookings.check-availability.store') }}" method="POST" id="bookingForm"
                        class="booking-form" x-data="bookingForm()">
                        @csrf
                        
                        @if ($errors->any())
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                                <ul class="list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div class="form-row">
                            <div class="form-field">
                                <label class="form-label">Check In</label>
                                <input type="text" name="check_in" id="checkInDate" placeholder="Select Date"
                                    value="{{ $bookingData['check_in'] ?? old('check_in') }}"
                                    class="form-input" required readonly>
                            </div>

                            <div class="form-field">
                                <label class="form-label">Check Out</label>
                                <input type="text" name="check_out" id="checkOutDate" placeholder="Select Date"
                                    value="{{ $bookingData['check_out'] ?? old('check_out') }}"
                                    class="form-input" required readonly>
                            </div>

                            <div class="form-field">
                                <label class="form-label">Regular Guests</label>
                                <input type="number" name="regular_guests" id="regularGuests" min="0" step="1" inputmode="numeric" placeholder="0"
                                    value="{{ $bookingData['regular_guests'] ?? old('regular_guests', '') }}"
                                    @input="updateTotalGuests()"
                                    oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                    class="form-input" required>
                            </div>

                            <div class="form-field">
                                <label class="form-label" style="white-space: nowrap;">Seniors</label>
                                <input type="number" name="seniors" id="seniors" min="0" step="1" inputmode="numeric" placeholder="0"
                                    value="{{ $bookingData['seniors'] ?? old('seniors', '') }}"
                                    @input="updateTotalGuests()"
                                    oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                    class="form-input">
                            </div>

                            <div class="form-field">
                                <label class="form-label" style="white-space: nowrap;">Children (6 y.o and below)</label>
                                <input type="number" name="children" id="children" min="0" step="1" inputmode="numeric" placeholder="0"
                                    value="{{ $bookingData['children'] ?? old('children', '') }}"
                                    @input="updateTotalGuests()"
                                    oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                    class="form-input">
                            </div>

                            <div class="form-field">
                                <button type="submit" class="submit-btn" style="margin-top: 1.75rem;">
                                    Book Date!
                                </button>
                            </div>
                        </div>

                        <!-- Hidden field for total pax -->
                        <input type="hidden" name="pax" id="totalGuests" value="0">
                    </form>

                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full">
            @include('partials.footer')
        </footer>
    </div>

    <!-- Alpine.js Calendar Logic + Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        window.calendarData = {
            booked: @json($bookedDates ?? []),
            closed: @json($closedDates ?? [])
        };

        // Debug: Log the data to console
        console.log('Booked Dates:', window.calendarData.booked);
        console.log('Closed Dates:', window.calendarData.closed);

        function calendar() {
            return {
                selectedDate: null,
                month: new Date().getMonth(),
                year: new Date().getFullYear(),
                monthNames: [
                    "January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"
                ],
                bookedRanges: window.calendarData.booked || [],
                closed: window.calendarData.closed || [],

                get daysInMonth() {
                    const firstDay = new Date(this.year, this.month, 1).getDay();
                    const totalDays = new Date(this.year, this.month + 1, 0).getDate();
                    const days = [];

                    for (let i = 0; i < firstDay; i++) days.push('');
                    for (let i = 1; i <= totalDays; i++) days.push(i);
                    return days;
                },

                getDateStatus(day) {
                    if (!day) return '';
                    const dateStr = `${this.year}-${String(this.month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    
                    if (this.closed.includes(dateStr)) return 'closed';
                    // Check if date falls within any booked range
                    const isBooked = this.bookedRanges.some(range => {
                        return dateStr >= range.start && dateStr <= range.end;
                    });
                    if (isBooked) return 'booked';
                    return 'available';
                },

                formatDate(day) {
                    return `${this.year}-${String(this.month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                },

                selectDay(day) {
                    const date = this.formatDate(day);
                    // don't allow selecting past dates
                    if (this.isPastDate(day)) return;
                    this.selectedDate = date;
                },

                isPastDate(day) {
                    if (!day) return false;
                    const dateToCheck = new Date(this.year, this.month, day);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    // Treat today as non-selectable (considered past for selection purposes)
                    return dateToCheck <= today;
                },

                isToday(day) {
                    if (!day) return false;
                    const today = new Date();
                    return day === today.getDate() && 
                           this.month === today.getMonth() && 
                           this.year === today.getFullYear();
                },

                prevMonth() {
                    if (this.month === 0) {
                        this.month = 11;
                        this.year--;
                    } else {
                        this.month--;
                    }
                },
                nextMonth() {
                    if (this.month === 11) {
                        this.month = 0;
                        this.year++;
                    } else {
                        this.month++;
                    }
                }
            }
        }

        function bookingForm() {
            return {
                checkInPicker: null,
                checkOutPicker: null,

                init() {
                    this.initializeDatePickers();
                    this.updateTotalGuests();
                },

                initializeDatePickers() {
                    const bookedRanges = window.calendarData.booked || [];
                    const closed = window.calendarData.closed || [];

                    // Compute 'tomorrow' as the minimum selectable date
                    const tomorrow = new Date();
                    tomorrow.setHours(0, 0, 0, 0);
                    tomorrow.setDate(tomorrow.getDate() + 1);

                    // Get default values from the input fields (if any from session)
                    const defaultCheckIn = document.getElementById('checkInDate').value;
                    const defaultCheckOut = document.getElementById('checkOutDate').value;

                    // Function to check if a date is booked (within any booked range)
                    const isDateBooked = (date) => {
                        const dateStr = date.toISOString().split('T')[0];
                        // Check if date is in closed dates
                        if (closed.includes(dateStr)) return true;
                        // Check if date falls within any booked range
                        return bookedRanges.some(range => {
                            return dateStr >= range.start && dateStr <= range.end;
                        });
                    };

                    // Check In Date Picker
                    this.checkInPicker = flatpickr("#checkInDate", {
                        dateFormat: "Y-m-d",
                        // Do not allow selecting the current date
                        minDate: tomorrow,
                        disable: [isDateBooked],
                        defaultDate: defaultCheckIn || null,
                        onChange: (selectedDates, dateStr) => {
                            if (dateStr) {
                                // Update check-out picker min date
                                const nextDay = new Date(selectedDates[0]);
                                nextDay.setDate(nextDay.getDate() + 1);
                                
                                if (this.checkOutPicker) {
                                    this.checkOutPicker.set('minDate', nextDay);
                                    this.checkOutPicker.clear();
                                }
                            }
                        }
                    });

                    // Check Out Date Picker
                    this.checkOutPicker = flatpickr("#checkOutDate", {
                        dateFormat: "Y-m-d",
                        // Also block selecting current date for checkout
                        minDate: tomorrow,
                        disable: [isDateBooked],
                        defaultDate: defaultCheckOut || null
                    });
                },

                updateTotalGuests() {
                    const regular = parseInt(document.getElementById('regularGuests').value) || 0;
                    const children = parseInt(document.getElementById('children').value) || 0;
                    const seniors = parseInt(document.getElementById('seniors')?.value) || 0;
                    document.getElementById('totalGuests').value = regular + children + seniors;
                }
            }
        }
    </script>
@endsection
