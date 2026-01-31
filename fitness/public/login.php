<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        // Check credentials
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.password_hash, u.role, u.status,
                   m.id as member_id, m.first_name, m.last_name,
                   t.id as trainer_id, t.full_name as trainer_name
            FROM users u
            LEFT JOIN members m ON u.id = m.user_id
            LEFT JOIN trainers t ON u.id = t.user_id
            WHERE u.username = ? OR u.email = ?
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if account is active
            if ($user['status'] !== 'active') {
                $error = 'Your account is inactive. Please contact administrator.';
            } else {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                // Set role-specific data
                if ($user['role'] === 'member' && $user['member_id']) {
                    $_SESSION['member_id'] = $user['member_id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                } elseif ($user['role'] === 'trainer' && $user['trainer_id']) {
                    $_SESSION['trainer_id'] = $user['trainer_id'];
                    $_SESSION['user_name'] = $user['trainer_name'];
                } else {
                    $_SESSION['user_name'] = $user['username'];
                }
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Redirect to dashboard or intended URL
                $redirect_url = $_SESSION['redirect_url'] ?? 'dashboard.php';
                unset($_SESSION['redirect_url']);
                redirect($redirect_url);
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<?php
$page_title = 'Login';
include '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-form">
        <h2><i class="fas fa-sign-in-alt"></i> Login to Fitness Club</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username or Email</label>
                <input type="text" id="username" name="username" value="<?php echo e($username); ?>" required 
                       placeholder="Enter your username or email">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </div>
            
            <div class="auth-links">
                <p>Demo Accounts:</p>
                <ul class="demo-accounts">
                    <li><strong>Admin:</strong> admin / admin123</li>
                    <li><strong>Trainer:</strong> trainer / trainer123</li>
                    <li><strong>Member:</strong> john_doe / member123</li>
                </ul>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>