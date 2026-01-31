<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$page_title = 'My Profile';
include '../../includes/header.php';

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.*, 
           m.first_name, m.last_name, m.email as member_email, m.phone, 
           m.date_of_birth, m.gender, m.address, m.join_date, m.status as member_status
    FROM users u
    LEFT JOIN members m ON u.id = m.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div class="content-header">
    <h1><i class="fas fa-user-circle"></i> My Profile</h1>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-user"></i> Profile Information</h2>
        <div class="profile-status">
            <span class="role-badge <?php echo e($_SESSION['user_role']); ?>">
                <?php echo e(ucfirst($_SESSION['user_role'])); ?>
            </span>
            <span class="status-badge <?php echo get_status_badge($user['member_status'] ?? 'Active'); ?>">
                <?php echo e($user['member_status'] ?? 'Active'); ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="profile-grid">
            <div class="profile-section">
                <h3><i class="fas fa-id-card"></i> Account Details</h3>
                <div class="profile-details">
                    <p><strong>Username:</strong> <?php echo e($user['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo e($user['member_email'] ?: $user['email']); ?></p>
                    <p><strong>Role:</strong> <?php echo e(ucfirst($user['role'])); ?></p>
                    <p><strong>Account Created:</strong> <?php echo e(format_date($user['created_at'])); ?></p>
                    <p><strong>Last Login:</strong>
                        <?php echo e($user['last_login'] ? format_datetime($user['last_login']) : 'Never'); ?></p>
                </div>
            </div>

            <?php if ($user['first_name']): ?>
                <div class="profile-section">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="profile-details">
                        <p><strong>Name:</strong> <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo e($user['phone'] ?: 'Not provided'); ?></p>
                        <p><strong>Gender:</strong> <?php echo e($user['gender'] ?: 'Not specified'); ?></p>
                        <p><strong>Date of Birth:</strong>
                            <?php echo e($user['date_of_birth'] ? format_date($user['date_of_birth']) : 'Not provided'); ?>
                        </p>
                        <p><strong>Age:</strong>
                            <?php echo e($user['date_of_birth'] ? calculate_age($user['date_of_birth']) : 'N/A'); ?> years
                        </p>
                        <p><strong>Member Since:</strong> <?php echo e(format_date($user['join_date'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($user['address']): ?>
                <div class="profile-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Address</h3>
                    <div class="profile-details">
                        <p><?php echo nl2br(e($user['address'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-actions">
            <a href="edit-profile.php" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <a href="change-password.php" class="btn btn-secondary">
                <i class="fas fa-key"></i> Change Password
            </a>
        </div>
    </div>
</div>

<style>
    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }

    .profile-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .profile-section h3 {
        margin-top: 0;
        color: #2c3e50;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .profile-details p {
        margin: 10px 0;
        padding: 5px 0;
        border-bottom: 1px dashed #dee2e6;
    }

    .profile-details p:last-child {
        border-bottom: none;
    }

    .profile-details strong {
        color: #2c3e50;
        min-width: 150px;
        display: inline-block;
    }

    .profile-actions {
        display: flex;
        gap: 15px;
        padding-top: 20px;
        border-top: 1px solid #dee2e6;
    }
</style>

<?php include '../../includes/footer.php'; ?>