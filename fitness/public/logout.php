<?php
require_once '../config/config.php';

require_once '../includes/functions.php';

// Check if user is a member and check them out
if (isset($_SESSION['member_id'])) {
    check_out_member($pdo, $_SESSION['member_id']);
}

// Destroy all session data
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>