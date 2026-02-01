<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_admin(); // Only admin can add trainers

$page_title = 'Add New Trainer';
include '../../includes/header.php';

$errors = [];
$form_data = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'specialization' => '',
    'certification' => '',
    'experience_years' => '',
    'hourly_rate' => '',
    'availability' => 'Full-time',
    'bio' => '',
    'username' => '',
    'password' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    // Collect and sanitize form data
    $form_data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'specialization' => trim($_POST['specialization'] ?? ''),
        'certification' => trim($_POST['certification'] ?? ''),
        'experience_years' => $_POST['experience_years'] ?? '',
        'hourly_rate' => $_POST['hourly_rate'] ?? '',
        'availability' => $_POST['availability'] ?? 'Full-time',
        'bio' => trim($_POST['bio'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? ''
    ];

    // Validation
    if (empty($form_data['full_name'])) {
        $errors[] = 'Full name is required';
    }

    if (empty($form_data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($form_data['username'])) {
        $errors[] = 'Username is required';
    } elseif (strlen($form_data['username']) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }

    if (empty($form_data['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($form_data['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }

    // Check if email or username already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$form_data['email'], $form_data['username']]);
        if ($stmt->fetch()) {
            $errors[] = 'Email or username already exists';
        }
    }

    // Check if trainer email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM trainers WHERE email = ?");
        $stmt->execute([$form_data['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'Trainer with this email already exists';
        }
    }

    // If no errors, create user and trainer
    if (empty($errors)) {
        $pdo->beginTransaction();

        try {
            // Create user account
            $password_hash = password_hash($form_data['password'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, status) 
                VALUES (?, ?, ?, 'trainer', 'active')
            ");
            $stmt->execute([
                $form_data['username'],
                $form_data['email'],
                $password_hash
            ]);
            $user_id = $pdo->lastInsertId();

            // Create trainer record
            $stmt = $pdo->prepare("
                INSERT INTO trainers (
                    user_id, full_name, email, phone, specialization, 
                    certification, experience_years, hourly_rate, 
                    availability, bio
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user_id,
                $form_data['full_name'],
                $form_data['email'],
                $form_data['phone'],
                $form_data['specialization'],
                $form_data['certification'],
                $form_data['experience_years'] ?: 0,
                $form_data['hourly_rate'] ?: 0,
                $form_data['availability'],
                $form_data['bio']
            ]);

            $pdo->commit();

            $trainer_id = $pdo->lastInsertId();
            $_SESSION['success'] = "Trainer added successfully! Trainer ID: #$trainer_id";
            redirect('index.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to add trainer: ' . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-user-plus"></i> Add New Trainer</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Trainers
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-user-tie"></i> Trainer Information</h2>
    </div>
    <div class="card-body">
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

        <form method="POST" action="" class="form-grid">
            <div class="form-section">
                <h3><i class="fas fa-id-card"></i> Personal Information</h3>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo e($form_data['full_name']); ?>"
                        required placeholder="Enter full name">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo e($form_data['email']); ?>"
                            required placeholder="trainer@example.com">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo e($form_data['phone']); ?>"
                            placeholder="(123) 456-7890">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-graduation-cap"></i> Professional Information</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="specialization">Specialization</label>
                        <input type="text" id="specialization" name="specialization"
                            value="<?php echo e($form_data['specialization']); ?>"
                            placeholder="e.g., Strength Training, Yoga, etc.">
                    </div>

                    <div class="form-group">
                        <label for="experience_years">Experience (Years)</label>
                        <input type="number" id="experience_years" name="experience_years"
                            value="<?php echo e($form_data['experience_years']); ?>" min="0" max="50"
                            placeholder="Number of years">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="hourly_rate">Hourly Rate ($)</label>
                        <input type="number" id="hourly_rate" name="hourly_rate"
                            value="<?php echo e($form_data['hourly_rate']); ?>" step="0.01" min="0"
                            placeholder="e.g., 50.00">
                    </div>

                    <div class="form-group">
                        <label for="availability">Availability</label>
                        <select id="availability" name="availability">
                            <option value="Full-time" <?php echo $form_data['availability'] == 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                            <option value="Part-time" <?php echo $form_data['availability'] == 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                            <option value="Weekends" <?php echo $form_data['availability'] == 'Weekends' ? 'selected' : ''; ?>>Weekends Only</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="certification">Certifications</label>
                    <textarea id="certification" name="certification" rows="3"
                        placeholder="List certifications (separate with commas)"><?php echo e($form_data['certification']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="bio">Bio/Description</label>
                    <textarea id="bio" name="bio" rows="4"
                        placeholder="Tell us about your training philosophy, achievements, etc."><?php echo e($form_data['bio']); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-user-lock"></i> Account Information</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username"
                            value="<?php echo e($form_data['username']); ?>" required placeholder="Choose a username">
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password"
                            value="<?php echo e($form_data['password']); ?>" required
                            placeholder="At least 6 characters">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Add Trainer
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>