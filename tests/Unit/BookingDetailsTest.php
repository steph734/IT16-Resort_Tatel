<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Unit\PackageData;

class BookingDetailsTest extends TestCase
{
    use WithFaker;

    private $packages = [];
    private $pkg1Id;
    private $pkg2Id;

    protected function setUp(): void
    {
        parent::setUp();

        // Build packages using PackageData helper and adapt keys used by the tests
        $p1 = PackageData::make(['Name' => 'Package A', 'Price' => 15000, 'max_guests' => 30, 'excess_rate' => 100])->toArray();
        $p2 = PackageData::make(['Name' => 'Package B', 'Price' => 12000, 'max_guests' => 20, 'excess_rate' => 100])->toArray();

        // Tests expect lowercase 'price' key; map accordingly
        $this->packages = [
            array_merge($p1, ['price' => $p1['Price']]),
            array_merge($p2, ['price' => $p2['Price']]),
        ];

        // Expose usable PackageIDs so tests don't rely on hard-coded IDs
        $this->pkg1Id = $this->packages[0]['PackageID'] ?? ($this->packages[0]['PackageId'] ?? null);
        $this->pkg2Id = $this->packages[1]['PackageID'] ?? ($this->packages[1]['PackageId'] ?? null);
    }

    /**
     * Helper: Validate booking details.
     */
    private function validateBookingDetails($data, $packages)
    {
        $requiredFields = ['package_id', 'num_of_adults', 'total_guests', 'total_amount'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return "The field '{$field}' is required.";
            }
        }

        $package = collect($packages)->firstWhere('PackageID', $data['package_id']);
        if (!$package) {
            return "Invalid package selected.";
        }

        $expectedGuests = ($data['num_of_adults'] ?? 0)
            + ($data['num_of_child'] ?? 0)
            + ($data['num_of_seniors'] ?? 0);

        if ($expectedGuests !== (int)$data['total_guests']) {
            return "Total guests do not match breakdown.";
        }

        if ($data['total_guests'] <= 0) {
            return "Total guests must be greater than zero.";
        }

        if ($data['total_amount'] <= 0) {
            return "Invalid total amount.";
        }

        // Handle excess guests logic
        if ($data['total_guests'] > $package['max_guests']) {
            $excess = $data['total_guests'] - $package['max_guests'];
            $excessFee = $excess * $package['excess_rate'];
            $expectedAmount = $package['price'] + $excessFee;

            if ($data['total_amount'] != $expectedAmount) {
                return "Excess guests detected. Expected total ₱{$expectedAmount}.";
            }

            return "Excess guests detected. Additional ₱{$excessFee} fee applied. Booking details valid.";
        }

        return "Booking details valid.";
    }

    /** @test */
    public function successful_booking_with_valid_details()
    {
        $data = [
            'package_id' => $this->pkg1Id,
            'num_of_adults' => 5,
            'num_of_child' => 2,
            'num_of_seniors' => 1,
            'total_guests' => 8,
            'total_amount' => 15000,
        ];
        $result = $this->validateBookingDetails($data, $this->packages);
        $this->assertEquals("Booking details valid.", $result);
    }

    /** @test */
    public function booking_with_invalid_package_id()
    {
        $data = [
            'package_id' => 'INVALID',
            'num_of_adults' => 2,
            'num_of_child' => 0,
            'num_of_seniors' => 0,
            'total_guests' => 2,
            'total_amount' => 20000,
        ];
        $result = $this->validateBookingDetails($data, $this->packages);
        $this->assertEquals("Invalid package selected.", $result);
    }

    /** @test */
    public function guest_count_mismatch_between_breakdown_and_total()
    {
        $data = [
            'package_id' => $this->pkg1Id,
            'num_of_adults' => 2,
            'num_of_child' => 1,
            'num_of_seniors' => 0,
            // Intentionally mismatched total_guests to trigger validation
            'total_guests' => 4,
            'total_amount' => 15000,
        ];
        $result = $this->validateBookingDetails($data, $this->packages);
        $this->assertEquals("Total guests do not match breakdown.", $result);
    }

    /** @test */
    public function total_guests_equals_zero()
    {
        $data = [
            'package_id' => $this->pkg1Id,
            'num_of_adults' => 0,
            'num_of_child' => 0,
            'num_of_seniors' => 0,
            'total_guests' => 0,
            'total_amount' => 20000,
        ];
        $result = $this->validateBookingDetails($data, $this->packages);
        $this->assertEquals("Total guests must be greater than zero.", $result);
    }

    /** @test */
    public function total_amount_equals_zero()
    {
        $data = [
            'package_id' => $this->pkg1Id,
            'num_of_adults' => 2,
            'num_of_child' => 1,
            'num_of_seniors' => 1,
            'total_guests' => 4,
            'total_amount' => 0,
        ];
        $result = $this->validateBookingDetails($data, $this->packages);
        $this->assertEquals("Invalid total amount.", $result);
    }

    /** @test */
    public function missing_required_field_adults()
    {
        $data = [
            'package_id' => $this->pkg1Id,
            // Missing num_of_adults
            'num_of_child' => 1,
            'num_of_seniors' => 1,
            'total_guests' => 2,
            'total_amount' => 15000,
        ];
        $result = $this->validateBookingDetails($data, $this->packages);
        $this->assertEquals("The field 'num_of_adults' is required.", $result);
    }

    /** @test */
    public function missing_required_field_total_guests()
    {
        $data = [
            'package_id' => $this->pkg1Id,
            'num_of_adults' => 2,
            'num_of_child' => 1,
            'num_of_seniors' => 1,
            // Missing total_guests
            'total_amount' => 15000,
        ];
        $result = $this->validateBookingDetails($data, $this->packages);
        $this->assertEquals("The field 'total_guests' is required.", $result);
    }

    /** @test */
    public function missing_required_field_total_amount()
    {
        $data = [
            'package_id' => $this->pkg1Id,
            'num_of_adults' => 2,
            'num_of_child' => 1,
            'num_of_seniors' => 1,
            'total_guests' => 4,
            // Missing total_amount
        ];
        $result = $this->validateBookingDetails($data, $this->packages);
        $this->assertEquals("The field 'total_amount' is required.", $result);
    }

    /** @test */
    public function guest_exceeds_package_limit_and_excess_fee_applied()
    {
        $data = [
            'package_id' => $this->pkg2Id,
            'num_of_adults' => 18,
            'num_of_child' => 5, // total 23 guests
            'num_of_seniors' => 0,
            'total_guests' => 23,
            'total_amount' => 12300, // 12000 + (3 excess × 100)
        ];
        $result = $this->validateBookingDetails($data, $this->packages);
        $this->assertEquals("Excess guests detected. Additional ₱300 fee applied. Booking details valid.", $result);
    }
}
