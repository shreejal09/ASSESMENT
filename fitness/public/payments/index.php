<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_admin(); // Only admin can view payments

$page_title = 'Payments';
include '../../includes/header.php';

// Get payments
$stmt = $pdo->query("
    SELECT p.*, m.first_name, m.last_name, m.email, ms.plan_name
    FROM payments p
    JOIN memberships ms ON p.membership_id = ms.id
    JOIN members m ON ms.member_id = m.id
    ORDER BY p.payment_date DESC
    LIMIT 20
");
$payments = $stmt->fetchAll();

// Calculate totals
$total_revenue = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'Completed'")->fetch()['total'] ?? 0;
$pending_payments = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE status = 'Pending'")->fetch()['total'] ?? 0;
?>

<div class="content-header">
    <h1><i class="fas fa-credit-card"></i> Payment Management</h1>
    <div class="header-actions">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Record Payment
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-info">
            <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo e($pending_payments); ?></h3>
            <p>Pending Payments</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo date('F'); ?></h3>
            <p>Current Month</p>
        </div>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-list"></i> Recent Payments</h2>
    </div>
    <div class="card-body">
        <?php if ($payments): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Member</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo e(format_date($payment['payment_date'])); ?></td>
                            <td>
                                <?php echo e($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                <br><small><?php echo e($payment['email']); ?></small>
                            </td>
                            <td><?php echo e($payment['plan_name']); ?></td>
                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                            <td><?php echo e($payment['payment_method']); ?></td>
                            <td>
                                <span class="status-badge <?php echo get_status_badge($payment['status']); ?>">
                                    <?php echo e($payment['status']); ?>
                                </span>
                            </td>
                            <td><small><?php echo e($payment['transaction_id'] ?: 'N/A'); ?></small></td>
                            <td>
                                <?php if ($payment['processed_by']): ?>
                                    <span class="badge info">Admin</span>
                                <?php else: ?>
                                    <span class="badge secondary">Auto</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-credit-card fa-3x"></i>
                <h3>No Payments Found</h3>
                <p>No payment records in the system yet.</p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Record First Payment
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>