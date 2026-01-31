<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    redirect('index.php');
}

// Get log data for redirect
$stmt = $pdo->prepare("SELECT member_id FROM progress_logs WHERE id = ?");
$stmt->execute([$id]);
$member_id = $stmt->fetchColumn();

// Authorization check happens in delete logic usually, but let's be safe
if (!is_staff()) {
    $stmt = $pdo->prepare("SELECT member_id FROM progress_logs WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() != get_member_id()) {
        $_SESSION['error'] = "Access denied.";
        redirect('index.php');
    }
}

$stmt = $pdo->prepare("DELETE FROM progress_logs WHERE id = ?");
if ($stmt->execute([$id])) {
    $_SESSION['success'] = "Measurement deleted.";
} else {
    $_SESSION['error'] = "Failed to delete.";
}

redirect('index.php' . (is_staff() && isset($_GET['member_id']) ? '?member_id=' . $member_id : ''));
