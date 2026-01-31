<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_admin(); // Only admin can renew memberships

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid membership ID";
    redirect('index.php');
}

// Get membership data
$stmt = $pdo->prepare("
    SELECT ms.*, m.first_name, m.last_name, m.email
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

$page_title = 'Renew Membership';
include '../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duration = $_POST['duration'] ?? '1';
    $payment_method = trim($_POST['payment_method'] ?? 'Cash');

    // Calculate new dates
    $current_expiry = strtotime($membership['expiry_date']);
    $base_date = ($current_expiry > time()) ? $current_expiry : time();
    $new_expiry = date('Y-m-d', strtotime("+$duration month", $base_date));
    $new_start = date('Y-m-d', $base_date);

    try {
        // We could either update the existing one or create a new one. 
        // Usually, for audit history, creating a new one or updating the expiry is common.
        // For simplicity, let's update the existing one's expiry and set to Paid.
        $stmt = $pdo->prepare("
            UPDATE memberships SET 
                start_date = ?, 
                expiry_date = ?, 
                payment_status = 'Paid',
                payment_method = ?,
                created_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $new_start,
            $new_expiry,
            $payment_method,
            $id
        ]);

        $_SESSION['success'] = "Membership renewed until " . format_date($new_expiry);
        redirect('index.php');

    } catch (Exception $e) {
        $error = "Failed to renew: " . $e->getMessage();
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-sync-alt"></i> Renew Membership</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-history"></i> Renewal for
            <?php echo e($membership['first_name'] . ' ' . $membership['last_name']); ?>
        </h2>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            Current membership (
            <?php echo e($membership['plan_name']); ?>) expires on <strong>
                <?php echo format_date($membership['expiry_date']); ?>
            </strong>.
        </div>

        <form method="POST" action="" class="form-grid">
            <div class="form-group">
                <label for="duration">Renewal Duration</label>
                <select id="duration" name="duration" required>
                    <option value="1">1 Month</option>
                    <option value="3">3 Months</option>
                    <option value="6">6 Months</option>
                    <option value="12">1 Year</option>
                </select>
            </div>

            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <input type="text" id="payment_method" name="payment_method" value="Cash" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Process Renewal
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>