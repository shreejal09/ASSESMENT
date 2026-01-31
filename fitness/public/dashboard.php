<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

require_login();

$page_title = 'Dashboard';
include '../includes/header.php';

// Get statistics for dashboard
$stats = [];

if (is_admin()) {
    // Admin dashboard stats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM members WHERE status = 'Active'");
    $stats['total_members'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM trainers");
    $stats['total_trainers'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("
        SELECT COUNT(*) as total FROM attendance 
        WHERE DATE(check_in) = CURDATE()
    ");
    $stats['today_attendance'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("
        SELECT COUNT(*) as total FROM memberships 
        WHERE expiry_date >= CURDATE() AND payment_status = 'Paid'
    ");
    $stats['active_memberships'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("
        SELECT SUM(amount) as total FROM payments 
        WHERE MONTH(payment_date) = MONTH(CURDATE()) 
        AND YEAR(payment_date) = YEAR(CURDATE())
        AND status = 'Completed'
    ");
    $stats['monthly_revenue'] = $stmt->fetch()['total'] ?? 0;

    // Recent members
    $stmt = $pdo->prepare("
        SELECT m.first_name, m.last_name, m.join_date, m.status 
        FROM members m 
        ORDER BY m.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_members = $stmt->fetchAll();

    // Pending payments
    $stmt = $pdo->prepare("
        SELECT ms.plan_name, m.first_name, m.last_name, ms.price, ms.payment_status 
        FROM memberships ms 
        JOIN members m ON ms.member_id = m.id 
        WHERE ms.payment_status IN ('Pending', 'Overdue') 
        ORDER BY ms.expiry_date ASC 
        LIMIT 5
    ");
    $stmt->execute();
    $pending_payments = $stmt->fetchAll();

    // Checked In Members (Currently)
    $stmt = $pdo->prepare("
        SELECT m.first_name, m.last_name, a.check_in, a.workout_type 
        FROM attendance a
        JOIN members m ON a.member_id = m.id
        WHERE a.check_out IS NULL 
        AND DATE(a.check_in) = CURDATE()
        ORDER BY a.check_in DESC
    ");
    $stmt->execute();
    $current_checkins = $stmt->fetchAll();

} elseif (is_trainer()) {
    // Trainer dashboard stats
    $trainer_id = $_SESSION['trainer_id'];

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT member_id) as total 
        FROM attendance 
        WHERE trainer_id = ? 
        AND DATE(check_in) = CURDATE()
    ");
    $stmt->execute([$trainer_id]);
    $stats['clients_today'] = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM member_workouts mw
        JOIN members m ON mw.member_id = m.id
        WHERE mw.assigned_by = ?
        AND mw.status = 'Active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['active_assignments'] = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM workout_plans 
        WHERE created_by = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['workout_plans'] = $stmt->fetch()['total'];

    // Upcoming sessions (today)
    $stmt = $pdo->prepare("
        SELECT a.check_in, m.first_name, m.last_name, a.workout_type 
        FROM attendance a 
        JOIN members m ON a.member_id = m.id 
        WHERE a.trainer_id = ? 
        AND DATE(a.check_in) = CURDATE()
        AND a.check_out IS NULL
        ORDER BY a.check_in ASC 
        LIMIT 5
    ");
    $stmt->execute([$trainer_id]);
    $upcoming_sessions = $stmt->fetchAll();

} else {
    // Member dashboard stats
    $member_id = get_member_id();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM attendance 
        WHERE member_id = ? AND DATE(check_in) = CURDATE()
    ");
    $stmt->execute([$member_id]);
    $stats['today_checkins'] = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM attendance 
        WHERE member_id = ? AND MONTH(check_in) = MONTH(CURDATE())
    ");
    $stmt->execute([$member_id]);
    $stats['monthly_visits'] = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM member_workouts 
        WHERE member_id = ? AND status = 'Active'
    ");
    $stmt->execute([$member_id]);
    $stats['active_workouts'] = $stmt->fetch()['total'];

    // Current membership
    $stmt = $pdo->prepare("
        SELECT plan_name, plan_type, expiry_date, payment_status 
        FROM memberships 
        WHERE member_id = ? 
        AND expiry_date >= CURDATE() 
        ORDER BY expiry_date DESC 
        LIMIT 1
    ");
    $stmt->execute([$member_id]);
    $current_membership = $stmt->fetch();

    // Recent workouts
    $stmt = $pdo->prepare("
        SELECT wp.plan_name, we.exercise_name, we.sets, we.reps 
        FROM member_workouts mw
        JOIN workout_plans wp ON mw.workout_plan_id = wp.id
        JOIN workout_exercises we ON wp.id = we.workout_plan_id
        WHERE mw.member_id = ? 
        AND mw.status = 'Active'
        LIMIT 3
    ");
    $stmt->execute([$member_id]);
    $recent_workouts = $stmt->fetchAll();
}
?>

<div class="dashboard-header">
    <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
    <p>Welcome back, <?php echo e($_SESSION['user_name']); ?>! Here's your overview.</p>
</div>

<div class="stats-grid">
    <?php if (is_admin()): ?>
        <div class="stat-card">
            <div class="stat-icon admin">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stats['total_members']); ?></h3>
                <p>Active Members</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon trainer">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stats['total_trainers']); ?></h3>
                <p>Trainers</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stats['today_attendance']); ?></h3>
                <p>Today's Check-ins</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-id-card"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stats['active_memberships']); ?></h3>
                <p>Active Memberships</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count($pending_payments); ?></h3>
                <p>Pending Payments</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <h3>$<?php echo number_format($stats['monthly_revenue'], 2); ?></h3>
                <p>Monthly Revenue</p>
            </div>
        </div>

    <?php elseif (is_trainer()): ?>
        <div class="stat-card">
            <div class="stat-icon trainer">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stats['clients_today']); ?></h3>
                <p>Clients Today</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stats['active_assignments']); ?></h3>
                <p>Active Assignments</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-running"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stats['workout_plans']); ?></h3>
                <p>Workout Plans</p>
            </div>
        </div>

    <?php else: ?>
        <div class="stat-card">
            <div class="stat-icon member">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stats['today_checkins']); ?></h3>
                <p>Today's Check-ins</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stats['monthly_visits']); ?></h3>
                <p>Monthly Visits</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-running"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stats['active_workouts']); ?></h3>
                <p>Active Workouts</p>
            </div>
        </div>

        <?php if ($current_membership): ?>
            <div class="stat-card">
                <div class="stat-icon 
                <?php echo $current_membership['payment_status'] === 'Paid' ? 'success' :
                    ($current_membership['payment_status'] === 'Pending' ? 'warning' : 'error'); ?>">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo e($current_membership['plan_name']); ?></h3>
                    <p>Expires: <?php echo e(format_date($current_membership['expiry_date'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="dashboard-content">
    <?php if (is_admin()): ?>
        <!-- Admin Dashboard Sections -->
        <div class="dashboard-row">
            <div class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-user-plus"></i> Recently Joined Members</h3>
                    <a href="members/index.php" class="btn btn-sm">View All</a>
                </div>

                <?php if ($recent_members): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Join Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_members as $member): ?>
                                    <tr>
                                        <td><?php echo e($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                        <td><?php echo e(format_date($member['join_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo get_status_badge($member['status']); ?>">
                                                <?php echo e($member['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No members yet.</p>
                <?php endif; ?>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-stopwatch"></i> Currently Checked In</h3>
                    <a href="attendance/index.php" class="btn btn-sm">View All</a>
                </div>

                <?php if (!empty($current_checkins)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Check-in Time</th>
                                    <th>Workout</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($current_checkins as $checkin): ?>
                                    <tr>
                                        <td><?php echo e($checkin['first_name'] . ' ' . $checkin['last_name']); ?></td>
                                        <td><?php echo e(date('H:i', strtotime($checkin['check_in']))); ?></td>
                                        <td><?php echo e($checkin['workout_type'] ?: 'General'); ?></td>
                                        <td><span class="status-badge success">Active</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No members currently checked in.</p>
                <?php endif; ?>
            </div>

            <div class="dashboard-section">

                <div class="dashboard-section">
                    <div class="section-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Pending Payments</h3>
                        <a href="payments/index.php" class="btn btn-sm">View All</a>
                    </div>

                    <?php if ($pending_payments): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Plan</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo e($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                            <td><?php echo e($payment['plan_name']); ?></td>
                                            <td>$<?php echo number_format($payment['price'], 2); ?></td>
                                            <td>
                                                <span
                                                    class="status-badge <?php echo get_status_badge($payment['payment_status']); ?>">
                                                    <?php echo e($payment['payment_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No pending payments.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="actions-grid">
                    <a href="members/add.php" class="action-card">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Member</span>
                    </a>
                    <a href="trainers/add.php" class="action-card">
                        <i class="fas fa-user-tie"></i>
                        <span>Add Trainer</span>
                    </a>
                    <a href="memberships/add.php" class="action-card">
                        <i class="fas fa-id-card"></i>
                        <span>Create Membership</span>
                    </a>
                    <a href="payments/add.php" class="action-card">
                        <i class="fas fa-credit-card"></i>
                        <span>Record Payment</span>
                    </a>
                    <a href="attendance/checkin.php" class="action-card">
                        <i class="fas fa-check-circle"></i>
                        <span>Manual Check-in</span>
                    </a>
                    <a href="workouts/manage.php" class="action-card">
                        <i class="fas fa-running"></i>
                        <span>Manage Workouts</span>
                    </a>
                </div>
            </div>

        <?php elseif (is_trainer()): ?>
            <!-- Trainer Dashboard Sections -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-clock"></i> Today's Sessions</h3>
                    <a href="attendance/index.php" class="btn btn-sm">View All</a>
                </div>

                <?php if ($upcoming_sessions): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Client</th>
                                    <th>Workout Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_sessions as $session): ?>
                                    <tr>
                                        <td><?php echo e(date('H:i', strtotime($session['check_in']))); ?></td>
                                        <td><?php echo e($session['first_name'] . ' ' . $session['last_name']); ?></td>
                                        <td><?php echo e($session['workout_type']); ?></td>
                                        <td>
                                            <span class="status-badge info">In Progress</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No sessions scheduled for today.</p>
                <?php endif; ?>
            </div>

            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="actions-grid">
                    <a href="attendance/checkin.php" class="action-card">
                        <i class="fas fa-check-circle"></i>
                        <span>Check-in Client</span>
                    </a>
                    <a href="workouts/index.php" class="action-card">
                        <i class="fas fa-running"></i>
                        <span>Create Workout Plan</span>
                    </a>
                    <a href="members/index.php" class="action-card">
                        <i class="fas fa-users"></i>
                        <span>View Clients</span>
                    </a>
                    <a href="members/index.php" class="action-card">
                        <i class="fas fa-chart-line"></i>
                        <span>Track Progress</span>
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Member Dashboard Sections -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-running"></i> My Current Workout</h3>
                    <a href="workouts/plans.php" class="btn btn-sm">View All</a>
                </div>

                <?php if ($recent_workouts): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Plan</th>
                                    <th>Exercise</th>
                                    <th>Sets</th>
                                    <th>Reps</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_workouts as $workout): ?>
                                    <tr>
                                        <td><?php echo e($workout['plan_name']); ?></td>
                                        <td><?php echo e($workout['exercise_name']); ?></td>
                                        <td><?php echo e($workout['sets']); ?></td>
                                        <td><?php echo e($workout['reps']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No active workout plans. <a href="workouts/plans.php">Browse plans</a></p>
                <?php endif; ?>
            </div>

            <div class="quick-actions">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="actions-grid">
                    <a href="attendance/checkin.php" class="action-card">
                        <i class="fas fa-check-circle"></i>
                        <span>Check-in Today</span>
                    </a>
                    <a href="nutrition/add.php" class="action-card">
                        <i class="fas fa-apple-alt"></i>
                        <span>Log Meal</span>
                    </a>
                    <a href="progress/add.php" class="action-card">
                        <i class="fas fa-weight"></i>
                        <span>Log Weight</span>
                    </a>
                    <a href="memberships/index.php" class="action-card">
                        <i class="fas fa-id-card"></i>
                        <span>View Membership</span>
                    </a>
                    <a href="workouts/plans.php" class="action-card">
                        <i class="fas fa-running"></i>
                        <span>Browse Workouts</span>
                    </a>
                    <a href="profile/profile.php" class="action-card">
                        <i class="fas fa-user-edit"></i>
                        <span>Update Profile</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>