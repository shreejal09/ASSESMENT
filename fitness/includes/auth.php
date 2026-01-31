<?php
// This file contains authentication checks
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in (for protected pages)
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('../public/login.php');
    }
}

// Check if user has specific role
function require_role($role) {
    require_login();
    if (!has_role($role)) {
        $_SESSION['error'] = 'Access denied. Required role: ' . $role;
        redirect('../public/dashboard.php');
    }
}

// Check if user is admin (for admin-only pages)
function require_admin() {
    require_role('admin');
}

// Check if user is trainer (for trainer-only pages)
function require_trainer() {
    require_role('trainer');
}

// Check if user is member (for member-only pages)
function require_member() {
    require_role('member');
}

// Check if user is admin or trainer (for staff pages)
function require_staff() {
    require_login();
    if (!is_admin() && !is_trainer()) {
        $_SESSION['error'] = 'Access denied. Staff privileges required.';
        redirect('../public/dashboard.php');
    }
}
?>