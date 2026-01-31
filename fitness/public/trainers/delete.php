<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

require_admin(); // Only admin can delete trainers

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid trainer ID";
    redirect('index.php');
}

$id = (int) $_GET['id'];

// Check if trainer exists
$stmt = $pdo->prepare("SELECT full_name FROM trainers WHERE id = ?");
$stmt->execute([$id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    $_SESSION['error'] = "Trainer not found";
    redirect('index.php');
}

try {
    $pdo->beginTransaction();

    // Check for assignments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM member_workouts WHERE assigned_by = (SELECT user_id FROM trainers WHERE id = ?)");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Cannot delete trainer because they have assigned workout plans to members.");
    }

    // Clear trainer from attendance records (set to NULL to preserve history)
    $stmt = $pdo->prepare("UPDATE attendance SET trainer_id = NULL WHERE trainer_id = ?");
    $stmt->execute([$id]);

    // Delete trainer record
    $stmt = $pdo->prepare("DELETE FROM trainers WHERE id = ?");
    $stmt->execute([$id]);

    // Note: We usually keep the user record for audit or let the admin delete it manually
    // But for consistency with members, we could delete it too if it's strictly a trainer

    $pdo->commit();
    $_SESSION['success'] = "Trainer '{$trainer['full_name']}' deleted successfully.";

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    $_SESSION['error'] = "Failed to delete trainer: " . $e->getMessage();
}

redirect('index.php');
?>