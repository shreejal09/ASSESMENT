<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// Start session for CSRF if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only accept AJAX requests
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Direct access not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get input data
$member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : 'check';

if ($member_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid member ID'
    ]);
    exit;
}

try {
    // Get member details
    $stmt = $pdo->prepare("
        SELECT m.first_name, m.last_name, m.status
        FROM members m
        WHERE m.id = ?
    ");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        echo json_encode([
            'success' => false,
            'message' => 'Member not found'
        ]);
        exit;
    }
    
    // Check if member is active
    if ($member['status'] !== 'Active') {
        echo json_encode([
            'success' => false,
            'message' => "Member status is {$member['status']}. Cannot proceed.",
            'member_name' => $member['first_name'] . ' ' . $member['last_name'],
            'member_status' => $member['status']
        ]);
        exit;
    }
    
    // Check for active membership
    $stmt = $pdo->prepare("
        SELECT 
            ms.id,
            ms.plan_name,
            ms.plan_type,
            ms.expiry_date,
            ms.payment_status,
            DATEDIFF(ms.expiry_date, CURDATE()) as days_left
        FROM memberships ms
        WHERE ms.member_id = ? 
        AND ms.expiry_date >= CURDATE()
        AND ms.payment_status = 'Paid'
        ORDER BY ms.expiry_date DESC
        LIMIT 1
    ");
    $stmt->execute([$member_id]);
    $membership = $stmt->fetch();
    
    if (!$membership) {
        echo json_encode([
            'success' => false,
            'message' => 'No active paid membership found',
            'member_name' => $member['first_name'] . ' ' . $member['last_name'],
            'member_status' => $member['status']
        ]);
        exit;
    }
    
    // Check if membership is expiring soon (within 7 days)
    $expiring_soon = $membership['days_left'] <= 7;
    $membership_status = $expiring_soon ? 'warning' : 'valid';
    
    // If action is check-in, record attendance
    $attendance_id = null;
    if ($action === 'checkin') {
        // Check if already checked in today
        $stmt = $pdo->prepare("
            SELECT id 
            FROM attendance 
            WHERE member_id = ? 
            AND DATE(check_in) = CURDATE()
            AND check_out IS NULL
        ");
        $stmt->execute([$member_id]);
        
        if ($stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Member is already checked in today',
                'member_name' => $member['first_name'] . ' ' . $member['last_name']
            ]);
            exit;
        }
        
        // Record check-in
        $stmt = $pdo->prepare("
            INSERT INTO attendance (member_id, check_in, notes) 
            VALUES (?, NOW(), 'Auto check-in via system')
        ");
        $stmt->execute([$member_id]);
        $attendance_id = $pdo->lastInsertId();
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => $action === 'checkin' ? 'Check-in successful' : 'Membership valid',
        'member' => [
            'id' => $member_id,
            'name' => $member['first_name'] . ' ' . $member['last_name'],
            'status' => $member['status']
        ],
        'membership' => [
            'id' => $membership['id'],
            'plan_name' => $membership['plan_name'],
            'plan_type' => $membership['plan_type'],
            'expiry_date' => format_date($membership['expiry_date']),
            'payment_status' => $membership['payment_status'],
            'days_left' => $membership['days_left'],
            'status' => $membership_status
        ]
    ];
    
    if ($attendance_id) {
        $response['attendance_id'] = $attendance_id;
        $response['checkin_time'] = date('Y-m-d H:i:s');
    }
    
    // Log the validation (optional)
    if (isset($_SESSION['user_id'])) {
        $log_sql = "
            INSERT INTO audit_logs (user_id, action, table_name, record_id, created_at)
            VALUES (?, ?, 'members', ?, NOW())
        ";
        $pdo->prepare($log_sql)->execute([
            $_SESSION['user_id'],
            $action === 'checkin' ? 'member_checkin' : 'membership_validation',
            $member_id
        ]);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Validation failed',
        'message' => $e->getMessage()
    ]);
}
?>