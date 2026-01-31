<?php
/**
 * Check if user is logged in
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function has_role($role)
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Create a notification for a user
 */
function create_notification($pdo, $user_id, $message)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$user_id, $message]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get unread notifications for a user
 */
function get_unread_notifications($pdo, $user_id)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Mark notifications as read
 */
function mark_notifications_read($pdo, $user_id)
{
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if user is admin
 */
function is_admin()
{
    return has_role('admin');
}

/**
 * Check if user is trainer
 */
function is_trainer()
{
    return has_role('trainer');
}

/**
 * Check if user is member
 */
function is_member()
{
    return has_role('member');
}

/**
 * Check if user is admin or trainer (staff)
 */
function is_staff()
{
    return is_admin() || is_trainer();
}

/**
 * Get current user's member ID if they are a member
 */
function get_member_id()
{
    return $_SESSION['member_id'] ?? null;
}

/**
 * Redirect to specified page
 */
function redirect($url)
{
    header('Location: ' . $url);
    exit();
}

/**
 * Format date for display
 */
function format_date($date_string)
{
    if (empty($date_string))
        return 'N/A';
    return date('M j, Y', strtotime($date_string));
}

/**
 * Format datetime for display
 */
function format_datetime($datetime_string)
{
    if (empty($datetime_string))
        return 'N/A';
    return date('M j, Y H:i', strtotime($datetime_string));
}

/**
 * Escape HTML output
 */
function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Calculate age from date of birth
 */
function calculate_age($dob)
{
    if (empty($dob))
        return 'N/A';
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y;
}

/**
 * Get status badge class
 */
function get_status_badge($status)
{
    $status_lower = strtolower($status);
    switch ($status_lower) {
        case 'active':
        case 'paid':
        case 'completed':
            return 'success';
        case 'inactive':
        case 'pending':
            return 'warning';
        case 'suspended':
        case 'overdue':
        case 'failed':
            return 'error';
        default:
            return 'info';
    }
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token)
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check out member (update attendance record)
 */
function check_out_member($pdo, $member_id)
{
    try {
        // Find open attendance record for today
        $stmt = $pdo->prepare("
            UPDATE attendance 
            SET check_out = NOW(), 
                duration_minutes = TIMESTAMPDIFF(MINUTE, check_in, NOW())
            WHERE member_id = ? 
            AND check_out IS NULL 
            AND DATE(check_in) = CURDATE()
        ");
        $stmt->execute([$member_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
?>