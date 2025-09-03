<?php
/**
 * Fix for PHP 8.1 timezone database corruption issue
 * This script sets a default timezone to prevent Composer errors
 * CodeIgniter App version
 */

// Set a default timezone to prevent timezone database corruption errors
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// Verify timezone is set correctly
echo "CodeIgniter App - Timezone Fix\n";
echo "==============================\n";
echo "Current timezone: " . date_default_timezone_get() . "\n";
echo "Current date/time: " . date('Y-m-d H:i:s T') . "\n";

// Test timezone functionality
try {
    $date = new DateTime();
    echo "DateTime test successful: " . $date->format('Y-m-d H:i:s T') . "\n";
} catch (Exception $e) {
    echo "DateTime test failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test date functions
echo "Time function test: " . time() . "\n";
echo "Date function test: " . date('c') . "\n";

// Test timezone with different formats
echo "ISO 8601 format: " . date('c') . "\n";
echo "RFC 2822 format: " . date('r') . "\n";

echo "Timezone fix applied successfully for CodeIgniter App.\n";
