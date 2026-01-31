<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$page_title = 'Memberships';
include '../../includes/header.php';

$member_id = is_member() ? get_member_id() : 0;

// Get memberships
if (is_member() && $member_id) {
    $stmt = $pdo->prepare("SELECT * FROM memberships WHERE member_id = ? ORDER BY expiry_date DESC");
    $stmt->execute([$member_id]);
    $memberships = $stmt->fetchAll();
} elseif (is_admin() || is_trainer()) {
    $stmt = $pdo->query("
        SELECT m.*, mem.first_name, mem.last_name, mem.email 
        FROM memberships m
        JOIN members mem ON m.member_id = mem.id
        ORDER BY m.expiry_date DESC
        LIMIT 20
    ");
    $memberships = $stmt->fetchAll();
} else {
    $memberships = [];
}
?>

<div class="content-header">
    <h1><i class="fas fa-id-card"></i> Memberships</h1>
    <?php if (is_staff()): ?>
        <div class="header-actions">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Membership
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-list"></i>
            <?php echo is_member() ? 'My Memberships' : 'All Memberships'; ?>
        </h2>
    </div>
    <div class="card-body">
        <?php if ($memberships): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php if (is_staff()): ?>
                                <th>Member</th>
                            <?php endif; ?>
                            <th>Plan Name</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Start Date</th>
                            <th>Expiry Date</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memberships as $membership):
                            $days_left = ceil((strtotime($membership['expiry_date']) - time()) / (60 * 60 * 24));
                            ?>
                            <tr>
                                <?php if (is_staff()): ?>
                                    <td>
                                        <?php echo e($membership['first_name'] . ' ' . $membership['last_name']); ?>
                                        <br><small><?php echo e($membership['email']); ?></small>
                                    </td>
                                <?php endif; ?>
                                <td><?php echo e($membership['plan_name']); ?></td>
                                <td><?php echo e($membership['plan_type']); ?></td>
                                <td>$<?php echo number_format($membership['price'], 2); ?></td>
                                <td><?php echo e(format_date($membership['start_date'])); ?></td>
                                <td>
                                    <?php echo e(format_date($membership['expiry_date'])); ?>
                                    <?php if ($days_left <= 7): ?>
                                        <br><small class="text-warning">(<?php echo e($days_left); ?> days left)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo get_status_badge($membership['payment_status']); ?>">
                                        <?php echo e($membership['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view.php?id=<?php echo e($membership['id']); ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (is_staff()): ?>
                                            <a href="edit.php?id=<?php echo e($membership['id']); ?>"
                                                class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo e($membership['id']); ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Delete this membership record?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-id-card fa-3x"></i>
                <h3>No Memberships Found</h3>
                <p><?php echo is_member() ? 'You don\'t have any memberships yet.' : 'No memberships in the system.'; ?></p>
                <?php if (is_staff()): ?>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add First Membership
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>