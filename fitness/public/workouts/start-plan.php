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
        // Check if already assigned
        $stmt = $pdo->prepare("
            SELECT id FROM member_workouts 
            WHERE member_id = ? AND workout_plan_id = ? AND status = 'Active'
        ");
        $stmt->execute([$member_id, $workout_plan_id]);

        if ($stmt->fetch()) {
            $_SESSION['error'] = "You are already following this workout plan.";
            redirect('plans.php');
        }

        // Assign workout to member
        $stmt = $pdo->prepare("
            INSERT INTO member_workouts (member_id, workout_plan_id, assigned_by, start_date, status)
            VALUES (?, ?, ?, NOW(), 'Active')
        ");
        $stmt->execute([$member_id, $workout_plan_id, $_SESSION['user_id']]);

        $_SESSION['success'] = "Workout plan started successfully! Keep up the great work!";
        redirect('plans.php');

    } catch (PDOException $e) {
        error_log("Error assigning workout: " . $e->getMessage());
        $_SESSION['error'] = "Failed to start workout plan. Please try again.";
        redirect('plans.php');
    }
}

// If GET request, redirect back
redirect('plans.php');
