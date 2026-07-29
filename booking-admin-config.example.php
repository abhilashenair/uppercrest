<?php
// Copy this file to booking-admin-config.php and change all values.
// booking-admin-config.php is ignored by Git.

define('BOOKING_DB_HOST', 'localhost');
define('BOOKING_DB_NAME', 'uppercrest_booking');
define('BOOKING_DB_USER', 'uppercrest_user');
define('BOOKING_DB_PASS', 'change-this-db-password');
define('BOOKING_DB_CHARSET', 'utf8mb4');

define('BOOKING_ADMIN_USERNAME', 'admin');
define('BOOKING_ADMIN_PASSWORD', 'change-this-admin-password');

define('BOOKING_PROPERTY_ID', 'uppercrest');
define('BOOKING_PROPERTY_NAME', 'The Upper Crest');
define('BOOKING_CURRENCY', 'INR');
define('BOOKING_RACK_RATE', 4500);
define('BOOKING_DEFAULT_RATE', 3500);
define('BOOKING_RATE_PLAN', array(
    30 => 1500,
    15 => 2000,
    10 => 2500,
    7 => 3000,
    2 => 3250,
    1 => 3500,
));
define('BOOKING_TAX_RATE', 0);
define('BOOKING_MAX_ADVANCE_DAYS', 365);
define('BOOKING_TIMEZONE', 'Asia/Kolkata');
