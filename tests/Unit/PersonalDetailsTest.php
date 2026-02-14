<?php

namespace Tests\Feature;

use Tests\TestCase;

class PersonalDetailsTest extends TestCase
{
    /**
     * Helper function to simulate validation of personal details.
     */
    private function validatePersonalDetails($data)
    {
        $required = ['first_name', 'last_name', 'email', 'phone', 'address'];

        // Check for missing required fields
        foreach ($required as $field) {
            if (empty($data[$field] ?? '')) {
                return "The {$field} field is required.";
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format.";
        }

        // Validate phone number (must be 11 digits, start with 09)
        if (!preg_match('/^09[0-9]{9}$/', $data['phone'])) {
            return "Invalid Philippine phone number.";
        }

        // If all validations pass
        return "Valid";
    }

    /** @test */
    public function accepts_valid_personal_details()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'Amar',
            'email' => 'john@example.com',
            'phone' => '09123456789',
            'address' => 'Cebu City',
            'socialmedia' => 'instagram/johndoe',
        ];

        $result = $this->validatePersonalDetails($data);
        $this->assertEquals("Valid", $result);
    }

    /** @test */
    public function rejects_invalid_email_format()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'phone' => '09123456789',
            'address' => 'Cebu City',
        ];

        $result = $this->validatePersonalDetails($data);
        $this->assertEquals("Invalid email format.", $result);
    }

    /** @test */
    public function rejects_invalid_phone_number_length()
    {
        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '091234567', // too short
            'address' => 'Cebu City',
        ];

        $result = $this->validatePersonalDetails($data);
        $this->assertEquals("Invalid Philippine phone number.", $result);
    }

    /** @test */
    public function rejects_invalid_phone_number_start()
    {
        $data = [
            'first_name' => 'Jake',
            'last_name' => 'Reyes',
            'email' => 'jake@example.com',
            'phone' => '08123456789', // doesn’t start with 09
            'address' => 'Manila City',
        ];

        $result = $this->validatePersonalDetails($data);
        $this->assertEquals("Invalid Philippine phone number.", $result);
    }

    /** @test */
    public function rejects_missing_required_fields()
    {
        $data = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
        ];

        $result = $this->validatePersonalDetails($data);
        $this->assertEquals("The first_name field is required.", $result);
    }

    /** @test */
    public function accepts_empty_social_media_field()
    {
        $data = [
            'first_name' => 'Anna',
            'last_name' => 'Smith',
            'email' => 'anna@example.com',
            'phone' => '09998887777',
            'address' => 'Davao City',
            'socialmedia' => '', // optional
        ];

        $result = $this->validatePersonalDetails($data);
        $this->assertEquals("Valid", $result);
    }
}
