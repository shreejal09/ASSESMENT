<?php
require_once '../includes/auth.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$success = '';
$form_data = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => '',
    'date_of_birth' => '',
    'gender' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $form_data['username'] = trim($_POST['username'] ?? '');
    $form_data['email'] = trim($_POST['email'] ?? '');
    $form_data['full_name'] = trim($_POST['full_name'] ?? '');
    $form_data['phone'] = trim($_POST['phone'] ?? '');
    $form_data['date_of_birth'] = $_POST['date_of_birth'] ?? '';
    $form_data['gender'] = $_POST['gender'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($form_data['username'])) {
        $errors[] = 'Username is required';
    } elseif (strlen($form_data['username']) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }
    
    if (empty($form_data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($form_data['full_name'])) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$form_data['username'], $form_data['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists';
        }
    }
    
    // Create user and member if no errors
    if (empty($errors)) {
        $pdo->beginTransaction();
        
        try {
            // Create user account
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, status) 
                VALUES (?, ?, ?, 'member', 'active')
            ");
            
            $stmt->execute([
                $form_data['username'],
                $form_data['email'],
                $password_hash
            ]);
            $user_id = $pdo->lastInsertId();
            
            // Parse full name into first and last name
            $name_parts = explode(' ', $form_data['full_name'], 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
            
            // Create member record
            $stmt = $pdo->prepare("
                INSERT INTO members (
                    user_id, first_name, last_name, email, phone, 
                    date_of_birth, gender, join_date, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'Active')
            ");
            
            $stmt->execute([
                $user_id,
                $first_name,
                $last_name,
                $form_data['email'],
                $form_data['phone'],
                $form_data['date_of_birth'] ?: null,
                $form_data['gender'] ?: null
            ]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Registration successful! You can now login.';
            redirect('login.php');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed. Please try again. Error: ' . $e->getMessage();
        }
    }
}
?>
<?php
$page_title = 'Register';
include '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-form">
        <h2><i class="fas fa-user-plus"></i> Member Registration</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <h4><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username *</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo e($form_data['username']); ?>" required 
                       placeholder="Choose a username (letters, numbers, underscores only)">
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo e($form_data['email']); ?>" required 
                       placeholder="your.email@example.com">
            </div>
            
            <div class="form-group">
                <label for="full_name"><i class="fas fa-id-card"></i> Full Name *</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo e($form_data['full_name']); ?>" required 
                       placeholder="Enter your full name">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo e($form_data['phone']); ?>"
                           placeholder="(123) 456-7890">
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth"><i class="fas fa-birthday-cake"></i> Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" 
                           value="<?php echo e($form_data['date_of_birth']); ?>"
                           max="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="gender"><i class="fas fa-venus-mars"></i> Gender</label>
                <select id="gender" name="gender">
                    <option value="">Select Gender</option>
                    <option value="Male" <?php echo $form_data['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $form_data['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo $form_data['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password *</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" required 
                               placeholder="At least 6 characters">
                        <button type="button" class="password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password *</label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirm your password">
                        <button type="button" class="password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="#" onclick="alert('Terms and conditions would appear here in a real application.'); return false;">Terms and Conditions</a> *
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </div>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    });
    
    // Form validation
    const form = document.getElementById('registerForm');
    form.addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long!');
            return false;
        }
        
        const username = document.getElementById('username').value;
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            e.preventDefault();
            alert('Username can only contain letters, numbers, and underscores!');
            return false;
        }
        
        return true;
    });
});
</script>

<?php include '../includes/footer.php'; ?>