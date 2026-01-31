<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'Change Password';
include '../../includes/header.php';

$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // General validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    }

    if (empty($errors)) {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $success_msg = "Password updated successfully!";
            } else {
                $errors[] = "Failed to update password. Please try again.";
            }
        } else {
            $errors[] = "Incorrect current password.";
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-key"></i> Change Password</h1>
    <div class="header-actions">
        <a href="profile.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>

<div class="content-card narrow-card">
    <div class="card-header">
        <h2><i class="fas fa-lock"></i> Secure Your Account</h2>
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

        <form method="POST" action="">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-unlock"></i>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
            </div>

            <div class="form-actions space-between">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-shield-alt"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .narrow-card {
        max-width: 500px;
        margin: 0 auto;
    }

    .input-with-icon {
        position: relative;
    }

    .input-with-icon i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #95a5a6;
    }

    .input-with-icon input {
        padding-left: 45px;
    }

    .space-between {
        justify-content: flex-end;
    }
</style>

<?php include '../../includes/footer.php'; ?>