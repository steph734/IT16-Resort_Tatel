-- Script to clear all guest and booking data
-- This will delete all bookings first, then all guests
-- Run this in your SQL client (phpMyAdmin, MySQL Workbench, etc.)

-- Step 1: Disable foreign key checks temporarily (optional, for safety)
SET FOREIGN_KEY_CHECKS = 0;

-- Step 2: Delete all payments first (if there are any payment records)
DELETE FROM payments;

-- Step 3: Delete all bookings
DELETE FROM bookings;

-- Step 4: Delete all guests
DELETE FROM guests;

-- Step 5: Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify deletion
SELECT COUNT(*) as total_guests FROM guests;
SELECT COUNT(*) as total_bookings FROM bookings;
SELECT COUNT(*) as total_payments FROM payments;
