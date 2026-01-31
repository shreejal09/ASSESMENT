<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration
define('BASE_URL', 'http://localhost/FINALSHI%20copy/fitness');
define('SITE_NAME', 'Fitness Club Management');

// Set default timezone
date_default_timezone_set('Asia/Kathmandu');

// Error reporting
error_reporting(E_ALL);

// User roles from database
define('ROLE_ADMIN', 'admin');
define('ROLE_TRAINER', 'trainer');
define('ROLE_MEMBER', 'member');

// Include database connection
require_once __DIR__ . '/db.php';
?>