<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can delete attendance records

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid attendance ID";
    redirect('index.php');
}

// Get record info for message
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

// If trainer, only allow deleting their own assigned sessions (optional policy check)
// For now, allow any staff to delete records as requested.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "Attendance record for " . $record['first_name'] . " " . $record['last_name'] . " has been deleted.";
        } else {
            $_SESSION['error'] = "Failed to delete attendance record.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    redirect('index.php');
}

$page_title = 'Delete Attendance Record';
include '../../includes/header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-trash"></i> Delete Attendance Record</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Attendance
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header warning">
        <h2><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h2>
    </div>
    <div class="card-body">
        <div class="warning-message">
            <h3>Are you sure you want to delete this attendance record?</h3>
            <p>This action cannot be undone.</p>

            <div class="log-details">
                <p><strong>Member:</strong>
                    <?php echo e($record['first_name'] . ' ' . $record['last_name']); ?>
                </p>
                <p><strong>Check-in:</strong>
                    <?php echo e(format_date($record['check_in'])); ?> at
                    <?php echo e(date('H:i', strtotime($record['check_in']))); ?>
                </p>
                <p><strong>Type:</strong>
                    <?php echo e($record['workout_type'] ?: 'General'); ?>
                </p>
            </div>

            <form method="POST" action="">
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="fas fa-trash"></i> Delete Record
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>