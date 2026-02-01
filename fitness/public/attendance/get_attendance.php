<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login(); // Members can view their own attendance

// Only accept AJAX requests
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Direct access not allowed']);
    exit;
}

// Get attendance ID
$attendance_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($attendance_id <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid attendance ID'
    ]);
    exit;
}

try {
    // Get attendance details with member and trainer info
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            m.first_name as member_first,
            m.last_name as member_last,
            m.email as member_email,
            t.full_name as trainer_name,
            t.specialization as trainer_specialization,
            TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) as calculated_duration
        FROM attendance a
        LEFT JOIN members m ON a.member_id = m.id
        LEFT JOIN trainers t ON a.trainer_id = t.id
        WHERE a.id = ?
    ");
    $stmt->execute([$attendance_id]);
    $record = $stmt->fetch();

    if (!$record) {
        echo json_encode([
            'success' => false,
            'error' => 'Attendance record not found'
        ]);
        exit;
    }

    // Calculate duration
    $duration = $record['duration_minutes'] ?: $record['calculated_duration'];
    if (!$duration && !$record['check_out']) {
        // Calculate current duration if still checked in
        $duration = floor((time() - strtotime($record['check_in'])) / 60);
    }

    // Format dates
    $check_in_formatted = date('M j, Y @ H:i', strtotime($record['check_in']));
    $check_out_formatted = $record['check_out']
        ? date('M j, Y @ H:i', strtotime($record['check_out']))
        : 'Still checked in';

    // Prepare response
    $response = [
        'success' => true,
        'id' => $record['id'],
        'member' => [
            'name' => $record['member_first'] . ' ' . $record['member_last'],
            'email' => $record['member_email']
        ],
        'check_in_formatted' => $check_in_formatted,
        'check_out_formatted' => $check_out_formatted,
        'duration' => $duration ?: 0,
        'workout_type' => $record['workout_type'] ?: 'General',
        'notes' => $record['notes'] ?: 'No notes'
    ];

    // Add trainer info if available
    if ($record['trainer_name']) {
        $response['trainer'] = [
            'name' => $record['trainer_name'],
            'specialization' => $record['trainer_specialization'] ?: 'N/A'
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch attendance details',
        'message' => $e->getMessage()
    ]);
}
?>