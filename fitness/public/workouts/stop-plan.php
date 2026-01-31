<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();
require_member();

// Get member ID
$member_id = get_member_id();

if (!$member_id) {
    $_SESSION['error'] = "Member profile not found.";
    redirect('../dashboard.php');
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workout_plan_id = isset($_POST['workout_plan_id']) ? (int) $_POST['workout_plan_id'] : 0;

    if (!$workout_plan_id) {
        $_SESSION['error'] = "Invalid workout plan.";
        redirect('plans.php');
    }

    try {
        // Update the assignment status to 'Stopped'
        $stmt = $pdo->prepare("
            UPDATE member_workouts 
            SET status = 'Stopped', end_date = NOW()
            WHERE member_id = ? AND workout_plan_id = ? AND status = 'Active'
        ");
        $stmt->execute([$member_id, $workout_plan_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Workout plan stopped successfully. You can start it again anytime!";
        } else {
            $_SESSION['error'] = "This workout plan is not currently active.";
        }

        redirect('plans.php');

    } catch (PDOException $e) {
        error_log("Error stopping workout: " . $e->getMessage());
        $_SESSION['error'] = "Failed to stop workout plan. Please try again.";
        redirect('plans.php');
    }
}

// If GET request, redirect back
redirect('plans.php');
