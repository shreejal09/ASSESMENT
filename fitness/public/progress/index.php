<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$page_title = 'My Progress';
include '../../includes/header.php';

$member_id = is_member() ? get_member_id() : (isset($_GET['member_id']) ? (int) $_GET['member_id'] : 0);

if (!$member_id && is_member()) {
    $_SESSION['error'] = "Member profile not found.";
    redirect('../dashboard.php');
}

if (!$member_id && is_staff()) {
    $_SESSION['error'] = "Please select a member to view their progress history.";
    redirect('../members/index.php');
}

// Get progress history
$stmt = $pdo->prepare("
    SELECT * FROM progress_logs 
    WHERE member_id = ? 
    ORDER BY logged_at DESC
");
$stmt->execute([$member_id]);
$progress_history = $stmt->fetchAll();

// Get member name for title if admin/trainer
$member_name = "";
if (is_staff()) {
    $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member_name = " for " . $stmt->fetchColumn();
}
?>

<div class="content-header">
    <h1><i class="fas fa-chart-line"></i> Progress Tracking
        <?php echo $member_name; ?>
    </h1>
    <div class="header-actions">
        <?php if (is_member() || is_staff()): ?>
            <a href="add.php<?php echo is_staff() ? '?member_id=' . $member_id : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Measurement
            </a>
        <?php endif; ?>
        <?php if (is_staff()): ?>
            <a href="../members/view.php?id=<?php echo $member_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Member
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-history"></i> Measurement History</h2>
    </div>
    <div class="card-body">
        <?php if ($progress_history): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Weight (kg)</th>
                            <th>Body Fat %</th>
                            <th>Chest</th>
                            <th>Waist</th>
                            <th>Biceps</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($progress_history as $log): ?>
                            <tr>
                                <td>
                                    <?php echo e(format_date($log['logged_at'])); ?>
                                </td>
                                <td><strong>
                                        <?php echo e($log['weight_kg']); ?>
                                    </strong></td>
                                <td>
                                    <?php echo e($log['body_fat_percentage'] ?: 'N/A'); ?>%
                                </td>
                                <td>
                                    <?php echo e($log['chest_cm'] ?: 'N/A'); ?> cm
                                </td>
                                <td>
                                    <?php echo e($log['waist_cm'] ?: 'N/A'); ?> cm
                                </td>
                                <td>
                                    <?php echo e($log['biceps_cm'] ?: 'N/A'); ?> cm
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit.php?id=<?php echo $log['id']; ?>" class="btn btn-sm btn-warning"><i
                                                class="fas fa-edit"></i></a>
                                        <a href="delete.php?id=<?php echo $log['id']; ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this log?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-weight fa-3x"></i>
                <h3>No progress logs yet</h3>
                <p>Start tracking your transformation today!</p>
                <a href="add.php<?php echo is_staff() ? '?member_id=' . $member_id : ''; ?>" class="btn btn-primary">Add
                    First
                    Measurement</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>