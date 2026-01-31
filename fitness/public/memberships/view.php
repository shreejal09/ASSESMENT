<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid membership ID";
    redirect('index.php');
}

// Get membership data
$stmt = $pdo->prepare("
    SELECT ms.*, m.first_name, m.last_name, m.email as member_email, m.phone
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

$page_title = 'View Membership';
include '../../includes/header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-id-card"></i> Membership Details</h1>
    <div class="header-actions">
        <?php if (is_admin()): ?>
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Membership
            </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-info-circle"></i> Membership ID: #
            <?php echo $id; ?>
        </h2>
        <span class="status-badge <?php echo get_status_badge($membership['payment_status']); ?>">
            <?php echo e($membership['payment_status']); ?>
        </span>
    </div>
    <div class="card-body">
        <div class="detail-grid">
            <div class="detail-section">
                <h3><i class="fas fa-user"></i> Member Info</h3>
                <div class="detail-item">
                    <strong>Name:</strong>
                    <span>
                        <?php echo e($membership['first_name'] . ' ' . $membership['last_name']); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Email:</strong>
                    <span>
                        <?php echo e($membership['member_email']); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Phone:</strong>
                    <span>
                        <?php echo e($membership['phone'] ?: 'N/A'); ?>
                    </span>
                </div>
            </div>

            <div class="detail-section">
                <h3><i class="fas fa-tag"></i> Plan Info</h3>
                <div class="detail-item">
                    <strong>Plan Name:</strong>
                    <span>
                        <?php echo e($membership['plan_name']); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Plan Type:</strong>
                    <span>
                        <?php echo e($membership['plan_type']); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Price:</strong>
                    <span>$
                        <?php echo number_format($membership['price'], 2); ?>
                    </span>
                </div>
            </div>

            <div class="detail-section">
                <h3><i class="fas fa-calendar-alt"></i> Dates</h3>
                <div class="detail-item">
                    <strong>Start Date:</strong>
                    <span>
                        <?php echo format_date($membership['start_date']); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Expiry Date:</strong>
                    <span>
                        <?php echo format_date($membership['expiry_date']); ?>
                    </span>
                </div>
                <?php
                $days_left = ceil((strtotime($membership['expiry_date']) - time()) / (60 * 60 * 24));
                ?>
                <div class="detail-item">
                    <strong>Status:</strong>
                    <span
                        class="<?php echo $days_left < 0 ? 'text-danger' : ($days_left <= 7 ? 'text-warning' : 'text-success'); ?>">
                        <?php echo $days_left < 0 ? 'Expired' : ($days_left == 0 ? 'Expires today' : "$days_left days remaining"); ?>
                    </span>
                </div>
            </div>

            <div class="detail-section">
                <h3><i class="fas fa-credit-card"></i> Payment Details</h3>
                <div class="detail-item">
                    <strong>Payment Method:</strong>
                    <span>
                        <?php echo e($membership['payment_method'] ?: 'Not specified'); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Auto-renew:</strong>
                    <span>
                        <?php echo $membership['auto_renew'] ? '<i class="fas fa-check-circle text-success"></i> Yes' : '<i class="fas fa-times-circle text-danger"></i> No'; ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Created At:</strong>
                    <span>
                        <?php echo format_datetime($membership['created_at']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    .detail-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .detail-section h3 {
        margin-top: 0;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
        font-size: 1.1rem;
        color: #2c3e50;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px dashed #eee;
    }

    .detail-item:last-child {
        border-bottom: none;
    }

    .detail-item strong {
        color: #495057;
    }
</style>

<?php include '../../includes/footer.php'; ?>