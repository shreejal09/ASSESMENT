<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can check out members

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid attendance ID";
    redirect('index.php');
}

// Get attendance record
$stmt = $pdo->prepare("
    SELECT a.*, m.first_name, m.last_name 
    FROM attendance a
    JOIN members m ON a.member_id = m.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    $_SESSION['error'] = "Attendance record not found";
    redirect('index.php');
}

if ($record['check_out']) {
    $_SESSION['error'] = "Member is already checked out";
    redirect('index.php');
}

// Perform checkout
try {
    $now = date('Y-m-d H:i:s');
    $check_in = $record['check_in'];
    $duration = (strtotime($now) - strtotime($check_in)) / 60; // in minutes

    $stmt = $pdo->prepare("
        UPDATE attendance SET 
            check_out = ?, 
            duration_minutes = ?
        WHERE id = ?
    ");

    if ($stmt->execute([$now, round($duration), $id])) {
        $_SESSION['success'] = "Checked out " . $record['first_name'] . " " . $record['last_name'] . " successfully.";
    } else {
        $_SESSION['error'] = "Failed to record check-out.";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

redirect('index.php');