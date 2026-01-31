<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can delete memberships

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid membership ID";
    redirect('index.php');
}

// Get membership data
$stmt = $pdo->prepare("
    SELECT ms.*, m.first_name, m.last_name 
    FROM memberships ms
    JOIN members m ON ms.member_id = m.id
    WHERE ms.id = ?
");
$stmt->execute([$id]);
$membership = $stmt->fetch();

if (!$membership) {
    $_SESSION['error'] = "Membership not found";
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token if implemented, or just confirm action
    try {
        // Delete associated payments first to avoid foreign key issues
        $pdo->prepare("DELETE FROM payments WHERE membership_id = ?")->execute([$id]);

        // Delete membership
        $pdo->prepare("DELETE FROM memberships WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = "Membership deleted successfully";
        redirect('index.php');

    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to delete membership: " . $e->getMessage();
        redirect('index.php');
    }
}

$page_title = 'Delete Membership';
include '../../includes/header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-trash-alt"></i> Delete Membership</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header warning">
        <h2><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h2>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            Are you sure you want to delete the <strong>
                <?php echo e($membership['plan_name']); ?>
            </strong> membership for <strong>
                <?php echo e($membership['first_name'] . ' ' . $membership['last_name']); ?>
            </strong>?
            <br><br>
            <strong>Warning:</strong> This will also delete all payment records associated with this membership. This
            action cannot be undone.
        </div>

        <form method="POST" action="">
            <div class="form-actions">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-trash"></i> Permanently Delete
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>