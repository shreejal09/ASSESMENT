<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login(); // All logged in users can delete their own nutrition logs

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid nutrition log ID';
    redirect('index.php');
}

$log_id = (int) $_GET['id'];

// Get nutrition log details
$stmt = $pdo->prepare("
    SELECT nl.*, m.first_name, m.last_name 
    FROM nutrition_logs nl
    LEFT JOIN members m ON nl.member_id = m.id
    WHERE nl.id = ?
");
$stmt->execute([$log_id]);
$log = $stmt->fetch();

if (!$log) {
    $_SESSION['error'] = 'Nutrition log not found';
    redirect('index.php');
}

// Check permissions
if (is_member()) {
    $member_id = get_member_id();
    if ($log['member_id'] != $member_id) {
        $_SESSION['error'] = 'You can only delete your own nutrition logs';
        redirect('index.php');
    }
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM nutrition_logs WHERE id = ?");
        $stmt->execute([$log_id]);

        $_SESSION['success'] = "Nutrition log for '{$log['food_name']}' has been deleted successfully.";
        redirect('index.php');

    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to delete nutrition log: ' . $e->getMessage();
        redirect('index.php');
    }
}

$page_title = 'Delete Nutrition Log';
include '../../includes/header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-trash"></i> Delete Nutrition Log</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Nutrition Logs
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header warning">
        <h2><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h2>
    </div>
    <div class="card-body">
        <div class="warning-message">
            <h3>Are you sure you want to delete this nutrition log?</h3>

            <div class="log-details">
                <h4><?php echo e($log['food_name']); ?></h4>
                <p><strong>Member:</strong> <?php echo e($log['first_name'] . ' ' . $log['last_name']); ?></p>
                <p><strong>Date:</strong> <?php echo e(format_date($log['log_date'])); ?> at
                    <?php echo e(date('H:i', strtotime($log['log_time']))); ?></p>
                <p><strong>Meal Type:</strong> <?php echo e($log['meal_type']); ?></p>
                <p><strong>Calories:</strong> <?php echo e($log['calories']); ?> cal</p>
                <?php if ($log['notes']): ?>
                    <p><strong>Notes:</strong> <?php echo e($log['notes']); ?></p>
                <?php endif; ?>
            </div>

            <form method="POST" action="">
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fas fa-trash"></i> Delete Nutrition Log
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const confirmCheckbox = document.getElementById('confirm_delete');
        const deleteBtn = document.getElementById('deleteBtn');

        confirmCheckbox.addEventListener('change', function () {
            deleteBtn.disabled = !this.checked;
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>