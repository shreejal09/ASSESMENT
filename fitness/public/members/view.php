<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can view member details

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid member ID";
    redirect('index.php');
}

// Get member data
$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.email as account_email, u.created_at as account_created
    FROM members m
    LEFT JOIN users u ON m.user_id = u.id
    WHERE m.id = ?
");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    $_SESSION['error'] = "Member not found";
    redirect('index.php');
}

// Get active membership
$stmt = $pdo->prepare("
    SELECT * FROM memberships 
    WHERE member_id = ? AND expiry_date >= CURDATE() AND payment_status = 'Paid'
    ORDER BY expiry_date DESC LIMIT 1
");
$stmt->execute([$id]);
$active_membership = $stmt->fetch();

// Get recent attendance
$stmt = $pdo->prepare("
    SELECT a.*, t.full_name as trainer_name 
    FROM attendance a 
    LEFT JOIN trainers t ON a.trainer_id = t.id 
    WHERE a.member_id = ? 
    ORDER BY a.check_in DESC LIMIT 5
");
$stmt->execute([$id]);
$recent_attendance = $stmt->fetchAll();

$page_title = 'Member Profile: ' . $member['first_name'] . ' ' . $member['last_name'];
include '../../includes/header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-user"></i> Member Profile</h1>
    <div class="header-actions">
        <a href="../progress/index.php?member_id=<?php echo $id; ?>" class="btn btn-success">
            <i class="fas fa-chart-line"></i> View Progress
        </a>
        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit Member
        </a>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="profile-layout">
    <div class="profile-main">
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-id-card"></i> Personal Information</h2>
                <span class="status-badge <?php echo get_status_badge($member['status']); ?>">
                    <?php echo e($member['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Full Name:</strong>
                        <span>
                            <?php echo e($member['first_name'] . ' ' . $member['last_name']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>Email:</strong>
                        <span>
                            <?php echo e($member['email']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>Phone:</strong>
                        <span>
                            <?php echo e($member['phone'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>Gender:</strong>
                        <span>
                            <?php echo e($member['gender'] ?: 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>Birth Date:</strong>
                        <span>
                            <?php echo e($member['date_of_birth'] ? format_date($member['date_of_birth']) : 'N/A'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>Join Date:</strong>
                        <span>
                            <?php echo e(format_date($member['join_date'])); ?>
                        </span>
                    </div>
                </div>

                <?php if ($member['address']): ?>
                    <div class="mt-20">
                        <strong>Address:</strong>
                        <p>
                            <?php echo nl2br(e($member['address'])); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-card mt-20">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Recent Attendance</h2>
                <a href="../attendance/index.php?member_id=<?php echo $id; ?>" class="btn btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if ($recent_attendance): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Type</th>
                                    <th>Trainer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attendance as $record): ?>
                                    <tr>
                                        <td>
                                            <?php echo e(format_date($record['check_in'])); ?>
                                        </td>
                                        <td>
                                            <?php echo e(date('H:i', strtotime($record['check_in']))); ?>
                                        </td>
                                        <td>
                                            <?php echo $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '<span class="text-muted">Active</span>'; ?>
                                        </td>
                                        <td>
                                            <?php echo e($record['workout_type']); ?>
                                        </td>
                                        <td>
                                            <?php echo e($record['trainer_name'] ?: 'None'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data">No attendance records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="profile-side">
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-crown"></i> Current Membership</h2>
            </div>
            <div class="card-body">
                <?php if ($active_membership): ?>
                    <div class="membership-info">
                        <h3>
                            <?php echo e($active_membership['plan_name']); ?>
                        </h3>
                        <p class="expiry">Expires:
                            <?php echo e(format_date($active_membership['expiry_date'])); ?>
                        </p>
                        <span class="status-badge success">PAID</span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">No active paid membership found.</div>
                    <a href="../memberships/add.php?member_id=<?php echo $id; ?>" class="btn btn-primary btn-block">Assign
                        Membership</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-card mt-20">
            <div class="card-header">
                <h2><i class="fas fa-user-lock"></i> Account Details</h2>
            </div>
            <div class="card-body">
                <div class="detail-item">
                    <strong>Username:</strong>
                    <span>@
                        <?php echo e($member['username'] ?: 'N/A'); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Account Created:</strong>
                    <span>
                        <?php echo e(format_date($member['account_created'])); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .info-item,
    .detail-item {
        display: flex;
        justify-content: space-between;
        padding-bottom: 8px;
        border-bottom: 1px dashed #eee;
    }

    .membership-info h3 {
        margin: 0;
        color: #2c3e50;
    }

    .membership-info .expiry {
        color: #e74c3c;
        font-weight: bold;
        margin: 10px 0;
    }

    .mt-20 {
        margin-top: 20px;
    }

    .btn-block {
        display: block;
        width: 100%;
        text-align: center;
    }

    @media (max-width: 992px) {
        .profile-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include '../../includes/footer.php'; ?>