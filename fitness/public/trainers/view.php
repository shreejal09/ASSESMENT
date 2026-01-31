<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid trainer ID";
    redirect('index.php');
}

// Get trainer data
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.created_at as account_created
    FROM trainers t
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    $_SESSION['error'] = "Trainer not found";
    redirect('index.php');
}

$page_title = 'Trainer Profile: ' . $trainer['full_name'];
include '../../includes/header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-user-tie"></i> Trainer Profile</h1>
    <div class="header-actions">
        <?php if (is_admin()): ?>
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Trainer
            </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-info-circle"></i>
            <?php echo e($trainer['full_name']); ?>
        </h2>
        <span class="badge info">
            <?php echo e($trainer['availability']); ?>
        </span>
    </div>
    <div class="card-body">
        <div class="profile-grid">
            <div class="profile-section">
                <h3><i class="fas fa-address-card"></i> Professional Info</h3>
                <div class="detail-item">
                    <strong>Specialization:</strong>
                    <span>
                        <?php echo e($trainer['specialization'] ?: 'General'); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Experience:</strong>
                    <span>
                        <?php echo e($trainer['experience_years']); ?> years
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Certifications:</strong>
                    <span>
                        <?php echo e($trainer['certification'] ?: 'N/A'); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Hourly Rate:</strong>
                    <span>$
                        <?php echo number_format($trainer['hourly_rate'], 2); ?>
                    </span>
                </div>
            </div>

            <div class="profile-section">
                <h3><i class="fas fa-envelope"></i> Contact Info</h3>
                <div class="detail-item">
                    <strong>Email:</strong>
                    <span>
                        <?php echo e($trainer['email']); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Phone:</strong>
                    <span>
                        <?php echo e($trainer['phone'] ?: 'N/A'); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Username:</strong>
                    <span>@
                        <?php echo e($trainer['username'] ?: 'N/A'); ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($trainer['bio']): ?>
            <div class="bio-section mt-20">
                <h3><i class="fas fa-quote-left"></i> Biography</h3>
                <p>
                    <?php echo nl2br(e($trainer['bio'])); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    .profile-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px dashed #eee;
    }

    .mt-20 {
        margin-top: 20px;
    }

    .bio-section {
        padding: 20px;
        background: #fff8f0;
        border-left: 4px solid #f39c12;
        border-radius: 4px;
    }
</style>

<?php include '../../includes/footer.php'; ?>