<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminUpdateStatusTest extends TestCase
{
    /** @test */
    public function it_updates_booking_status_successfully()
    {
        $booking = new MockBookingStatus('B001', 'Pending');

        $requestData = ['status' => 'Confirmed', 'user_id' => null];

        $result = $this->simulateUpdateStatus($booking, $requestData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Booking status updated successfully', $result['message']);
        $this->assertEquals('Confirmed', $booking->BookingStatus);
    }

    /** @test */
    public function it_requires_user_id_when_cancelling_booking()
    {
        $booking = new MockBookingStatus('B002', 'Pending');

        $requestData = ['status' => 'Cancelled', 'user_id' => null];

        $result = $this->simulateUpdateStatus($booking, $requestData);

        $this->assertFalse($result['success']);
        $this->assertEquals('User ID confirmation required for this action', $result['message']);
        $this->assertTrue($result['require_confirmation']);
    }

    /** @test */
    public function it_requires_user_id_when_reopening_cancelled_booking()
    {
        $booking = new MockBookingStatus('B003', 'Cancelled');

        $requestData = ['status' => 'Confirmed', 'user_id' => null];

        $result = $this->simulateUpdateStatus($booking, $requestData);

        $this->assertFalse($result['success']);
        $this->assertEquals('User ID confirmation required for this action', $result['message']);
        $this->assertTrue($result['require_confirmation']);
    }

    /**
     * Mocked function to simulate updateStatus logic.
     */
    private function simulateUpdateStatus($booking, $data)
    {
        $allowedStatuses = ['Confirmed', 'Cancelled', 'Completed', 'Staying'];

        if (!isset($data['status']) || !in_array($data['status'], $allowedStatuses)) {
            return ['success' => false, 'message' => 'Validation failed'];
        }

        if (
            ($booking->BookingStatus === 'Cancelled' && $data['status'] !== 'Cancelled') ||
            ($booking->BookingStatus !== 'Cancelled' && $data['status'] === 'Cancelled')
        ) {
            if (empty($data['user_id'])) {
                return [
                    'success' => false,
                    'message' => 'User ID confirmation required for this action',
                    'require_confirmation' => true
                ];
            }
        }

        // "Update" booking status
        $booking->BookingStatus = $data['status'];

        return [
            'success' => true,
            'message' => 'Booking status updated successfully',
            'booking' => $booking
        ];
    }
}

/**
 * Simplified mock booking class for status updates
 */
class MockBookingStatus
{
    public $BookingID;
    public $BookingStatus;

    public function __construct(string $id, string $status)
    {
        $this->BookingID = $id;
        $this->BookingStatus = $status;
    }
}
