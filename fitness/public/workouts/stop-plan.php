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
        // First, verify the workout plan exists and is assigned to this member
        $check_stmt = $pdo->prepare("
            SELECT mw.id, mw.status, wp.plan_name
            FROM member_workouts mw
            JOIN workout_plans wp ON mw.workout_plan_id = wp.id
            WHERE mw.member_id = ? AND mw.workout_plan_id = ?
        ");
        $check_stmt->execute([$member_id, $workout_plan_id]);
        $assignment = $check_stmt->fetch();

        if (!$assignment) {
            error_log("Stop plan failed: No assignment found for member $member_id and plan $workout_plan_id");
            $_SESSION['error'] = "You are not assigned to this workout plan.";
            redirect('plans.php');
        }

        if ($assignment['status'] !== 'Active') {
            error_log("Stop plan failed: Assignment status is {$assignment['status']}, not Active");
            $_SESSION['error'] = "This workout plan is already {$assignment['status']}.";
            redirect('plans.php');
        }

        // Update the assignment status to 'Completed'
        $stmt = $pdo->prepare("
            UPDATE member_workouts 
            SET status = 'Completed', end_date = NOW()
            WHERE member_id = ? AND workout_plan_id = ? AND status = 'Active'
        ");
        $result = $stmt->execute([$member_id, $workout_plan_id]);

        if (!$result) {
            error_log("Stop plan failed: Database update failed for member $member_id and plan $workout_plan_id");
            $_SESSION['error'] = "Database error: Failed to stop workout plan. Please try again.";
            redirect('plans.php');
        }

        if ($stmt->rowCount() > 0) {
            error_log("Stop plan success: Stopped plan {$assignment['plan_name']} for member $member_id");
            $_SESSION['success'] = "Workout plan '{$assignment['plan_name']}' stopped successfully. You can start it again anytime!";
        } else {
            error_log("Stop plan warning: No rows updated for member $member_id and plan $workout_plan_id");
            $_SESSION['error'] = "This workout plan is not currently active.";
        }

        redirect('plans.php');

    } catch (PDOException $e) {
        error_log("Error stopping workout: " . $e->getMessage() . " | Member: $member_id | Plan: $workout_plan_id");
        $_SESSION['error'] = "Failed to stop workout plan. Error: " . $e->getMessage();
        redirect('plans.php');
    }
}

// If GET request, redirect back
redirect('plans.php');
exit;
