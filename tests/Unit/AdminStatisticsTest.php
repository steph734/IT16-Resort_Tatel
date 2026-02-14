<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Carbon\Carbon;

class AdminStatisticsTest extends TestCase
{
    private $mockBookings;
    private $mockPayments;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock payments
        $this->mockPayments = [
            new TestPaymentStats('PY001', 10000, 'Paid', 'Cash', '2025-10-09'),
            new TestPaymentStats('PY002', 5000, 'Downpayment', 'GCash', '2025-10-10'),
            new TestPaymentStats('PY003', 15000, 'Paid', 'GCash', '2025-10-01'),
        ];

        // Mock bookings
        $this->mockBookings = [
            new TestBookingStats(
                'B001',
                'Confirmed',
                '2025-10-09',
                '2025-10-09',
                '2025-10-12',
                3,
                0,
                3,
                0,
                new TestGuestStats('G001', 'John', 'Doe', 'john@example.com'),
                new TestPackageStats('PK001', 'Package A', 15000, 30),
                [$this->mockPayments[0]]
            ),
            new TestBookingStats(
                'B002',
                'Pending',
                '2025-10-10',
                '2025-10-10',
                '2025-10-15',
                4,
                1,
                5,
                0,
                new TestGuestStats('G002', 'Jane', 'Smith', 'jane@example.com'),
                new TestPackageStats('PK002', 'Package B', 12000, 20),
                [$this->mockPayments[1]]
            ),
            new TestBookingStats(
                'B003',
                'Cancelled',
                '2025-10-01',
                '2025-10-01',
                '2025-10-03',
                2,
                0,
                2,
                0,
                new TestGuestStats('G003', 'Alice', 'Jones', 'alice@example.com'),
                new TestPackageStats('PK003', 'Package C', 10000, 25),
                [$this->mockPayments[2]]
            ),
        ];
    }

    /** @test */
    public function it_returns_calendar_data_only_for_confirmed_and_pending_bookings()
    {
        $calendarData = $this->simulateGetCalendarData($this->mockBookings);

        $this->assertCount(2, $calendarData); // Only B001 and B002
        $this->assertEquals('Confirmed', $calendarData[0]['extendedProps']['status']);
        $this->assertEquals('Pending', $calendarData[1]['extendedProps']['status']);
    }

    /** @test */
    public function it_calculates_statistics_correctly()
    {
        $stats = $this->simulateGetStatistics($this->mockBookings, $this->mockPayments);

        $this->assertEquals(3, $stats['total_bookings']);
        $this->assertEquals(1, $stats['confirmed_bookings']);
        $this->assertEquals(1, $stats['pending_bookings']);
        $this->assertGreaterThan(0, $stats['total_revenue']);
    }

    /** @test */
    public function it_shows_booking_details_correctly()
    {
        $bookingDetails = $this->simulateShow($this->mockBookings[0]);

        $this->assertTrue($bookingDetails['success']);
        $this->assertEquals('B001', $bookingDetails['booking']['BookingID']);
        $this->assertEquals('John Doe', $bookingDetails['guest']['GuestName']);
        $this->assertEquals('Package A', $bookingDetails['package']['PackageName']);
        $this->assertEquals(3, $bookingDetails['booking']['Pax']);
    }

    /** @test */
    public function it_returns_payment_history_for_booking()
    {
        $payments = $this->simulateGetPaymentHistory($this->mockBookings[0]);

        $this->assertCount(1, $payments['payments']);
        $this->assertEquals('PY001', $payments['payments'][0]['PaymentID']);
    }

    // -------------------- Mocked simulation methods --------------------

    private function simulateGetCalendarData($bookings)
    {
        // Filter first, then map
        $filtered = array_filter($bookings, fn($b) => in_array($b->BookingStatus, ['Confirmed', 'Pending']));
        return array_values(array_map(function ($booking) {
            return [
                'id' => $booking->BookingID,
                'title' => $booking->guest->FName . ' - ' . $booking->package->Name,
                'start' => $booking->CheckInDate,
                'end' => $booking->CheckOutDate,
                'backgroundColor' => $booking->BookingStatus === 'Confirmed' ? '#10b981' : '#f59e0b',
                'borderColor' => $booking->BookingStatus === 'Confirmed' ? '#059669' : '#d97706',
                'extendedProps' => [
                    'guestName' => $booking->guest->FName . ' ' . $booking->guest->LName,
                    'packageName' => $booking->package->Name,
                    'pax' => $booking->Pax,
                    'status' => $booking->BookingStatus,
                ]
            ];
        }, $filtered));
    }

    private function simulateGetStatistics($bookings, $payments)
    {
        $today = Carbon::today()->format('Y-m-d');
        $thisMonth = Carbon::now()->startOfMonth()->format('Y-m-d');

        $totalRevenue = array_reduce($payments, fn($carry, $p) => $carry + ($p->PaymentStatus === 'Paid' ? $p->Amount : 0), 0);

        return [
            'total_bookings' => count($bookings),
            'confirmed_bookings' => count(array_filter($bookings, fn($b) => $b->BookingStatus === 'Confirmed')),
            'pending_bookings' => count(array_filter($bookings, fn($b) => $b->BookingStatus === 'Pending')),
            'today_checkins' => count(array_filter($bookings, fn($b) => $b->CheckInDate === $today)),
            'month_bookings' => count(array_filter($bookings, fn($b) => $b->BookingDate >= $thisMonth)),
            'total_revenue' => $totalRevenue,
            'month_revenue' => $totalRevenue,
        ];
    }

    private function simulateShow($booking)
    {
        $checkIn = Carbon::parse($booking->CheckInDate);
        $checkOut = Carbon::parse($booking->CheckOutDate);
        $daysOfStay = $checkIn->diffInDays($checkOut);
        $totalPaid = array_sum(array_map(fn($p) => $p->Amount, $booking->payments));

        $paymentStatus = $totalPaid >= $booking->package->Price * $daysOfStay ? 'Fully Paid' :
            ($totalPaid > 0 ? 'Downpayment' : 'Unpaid');

        return [
            'success' => true,
            'booking' => [
                'BookingID' => $booking->BookingID,
                'CheckInDate' => $checkIn->format('Y-m-d'),
                'CheckOutDate' => $checkOut->format('Y-m-d'),
                'DaysOfStay' => $daysOfStay,
                'Pax' => $booking->Pax,
                'NumOfAdults' => $booking->NumOfAdults,
                'NumOfChild' => $booking->NumOfChild,
            ],
            'guest' => [
                'GuestName' => $booking->guest->FName . ' ' . $booking->guest->LName
            ],
            'package' => [
                'PackageName' => $booking->package->Name,
            ],
            'payment' => [
                'PaymentStatus' => $paymentStatus,
                'AmountPaid' => $totalPaid
            ],
            'payments' => $booking->payments
        ];
    }

    private function simulateGetPaymentHistory($booking)
    {
        return [
            'success' => true,
            'payments' => array_map(fn($p) => [
                'PaymentID' => $p->PaymentID,
                'Amount' => $p->Amount,
                'PaymentMethod' => $p->PaymentMethod,
                'PaymentStatus' => $p->PaymentStatus,
                'PaymentDate' => $p->PaymentDate,
            ], $booking->payments)
        ];
    }
}

// -------------------- Mock Classes --------------------
class TestBookingStats
{
    public $BookingID, $BookingStatus, $BookingDate, $CheckInDate, $CheckOutDate;
    public $NumOfAdults, $NumOfChild, $Pax, $ExcessFee;
    public $guest, $package, $payments;

    public function __construct($id, $status, $bookingDate, $checkIn, $checkOut, $adults, $children, $pax, $excessFee, $guest, $package, $payments)
    {
        $this->BookingID = $id;
        $this->BookingStatus = $status;
        $this->BookingDate = $bookingDate;
        $this->CheckInDate = $checkIn;
        $this->CheckOutDate = $checkOut;
        $this->NumOfAdults = $adults;
        $this->NumOfChild = $children;
        $this->Pax = $pax;
        $this->ExcessFee = $excessFee;
        $this->guest = $guest;
        $this->package = $package;
        $this->payments = $payments;
    }
}

class TestGuestStats
{
    public $GuestID, $FName, $LName, $Email;

    public function __construct($id, $fname, $lname, $email)
    {
        $this->GuestID = $id;
        $this->FName = $fname;
        $this->LName = $lname;
        $this->Email = $email;
    }
}

class TestPackageStats
{
    public $PackageID, $Name, $Price, $max_guests;

    public function __construct($id, $name, $price, $max_guests)
    {
        $this->PackageID = $id;
        $this->Name = $name;
        $this->Price = $price;
        $this->max_guests = $max_guests;
    }
}

class TestPaymentStats
{
    public $PaymentID, $Amount, $PaymentStatus, $PaymentMethod, $PaymentDate;

    public function __construct($id, $amount, $status, $method, $date)
    {
        $this->PaymentID = $id;
        $this->Amount = $amount;
        $this->PaymentStatus = $status;
        $this->PaymentMethod = $method;
        $this->PaymentDate = $date;
    }
}
