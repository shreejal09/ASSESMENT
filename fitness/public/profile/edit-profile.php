<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$page_title = 'Edit Profile';
include '../../includes/header.php';

$errors = [];
$success_msg = '';

// Get user data
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();

// Get supplementary data based on role
$profile_data = [];
if ($role === ROLE_MEMBER) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile_data = $stmt->fetch();
} elseif ($role === ROLE_TRAINER || $role === ROLE_ADMIN) {
    // Admins might not have a trainer record, check first
    $stmt = $pdo->prepare("SELECT * FROM trainers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile_data = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update users table
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $user_id]);

            // Update role-specific table
            if ($role === ROLE_MEMBER && $profile_data) {
                $first_name = trim($_POST['first_name'] ?? '');
                $last_name = trim($_POST['last_name'] ?? '');
                $stmt = $pdo->prepare("UPDATE members SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
                $stmt->execute([$first_name, $last_name, $email, $phone, $user_id]);
            } elseif (($role === ROLE_TRAINER || $role === ROLE_ADMIN) && $profile_data) {
                $stmt = $pdo->prepare("UPDATE trainers SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
                $stmt->execute([$full_name, $email, $phone, $user_id]);
            }

            $pdo->commit();
            $success_msg = "Profile updated successfully!";

            // Refresh local data
            $user_data['email'] = $email;
            if ($role === ROLE_MEMBER) {
                $profile_data['first_name'] = $first_name;
                $profile_data['last_name'] = $last_name;
                $profile_data['phone'] = $phone;
            } else {
                $profile_data['full_name'] = $full_name;
                $profile_data['phone'] = $phone;
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-user-edit"></i> Edit Profile</h1>
    <div class="header-actions">
        <a href="profile.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-id-card"></i> Update Your Information</h2>
    </div>
    <div class="card-body">
        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <?php echo $error; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form-grid">
            <div class="form-section">
                <h3><i class="fas fa-user"></i> Account Details</h3>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo e($user_data['username']); ?>" disabled class="disabled-input">
                    <small class="text-muted">Username cannot be changed.</small>
                </div>
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo e($user_data['email']); ?>" required>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-address-card"></i> Personal Details</h3>
                <?php if ($role === ROLE_MEMBER): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name"
                                value="<?php echo e($profile_data['first_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name"
                                value="<?php echo e($profile_data['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                            value="<?php echo e($profile_data['full_name'] ?? ($user_data['username'])); ?>">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="<?php echo e($profile_data['phone'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
    .disabled-input {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }

    .form-section {
        margin-bottom: 30px;
    }

    .form-section h3 {
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 20px;
        color: #2c3e50;
    }
</style>

<?php include '../../includes/footer.php'; ?>