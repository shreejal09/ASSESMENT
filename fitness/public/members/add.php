<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can add members

$page_title = 'Add New Member';
include '../../includes/header.php';

$errors = [];
$success = '';
$form_data = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'date_of_birth' => '',
    'gender' => '',
    'address' => '',
    'emergency_contact' => '',
    'fitness_goals' => '',
    'medical_notes' => '',
    'height_cm' => '',
    'weight_kg' => '',
    'username' => '',
    'password' => '',
    'status' => 'Active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    // Collect and sanitize form data
    $form_data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'date_of_birth' => $_POST['date_of_birth'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'address' => trim($_POST['address'] ?? ''),
        'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
        'fitness_goals' => trim($_POST['fitness_goals'] ?? ''),
        'medical_notes' => trim($_POST['medical_notes'] ?? ''),
        'height_cm' => $_POST['height_cm'] ?? '',
        'weight_kg' => $_POST['weight_kg'] ?? '',
        'username' => trim($_POST['username'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'status' => $_POST['status'] ?? 'Active'
    ];

    // Validation
    if (empty($form_data['first_name'])) {
        $errors[] = 'First name is required';
    }

    if (empty($form_data['last_name'])) {
        $errors[] = 'Last name is required';
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

    // Check if member email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ?");
        $stmt->execute([$form_data['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'Member with this email already exists';
        }
    }

    // If no errors, create user and member
    if (empty($errors)) {
        $pdo->beginTransaction();

        try {
            // Create user account
            $password_hash = password_hash($form_data['password'], PASSWORD_DEFAULT);

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

            // Create member record
            $stmt = $pdo->prepare("
                INSERT INTO members (
                    user_id, first_name, last_name, email, phone, date_of_birth, 
                    gender, address, emergency_contact, fitness_goals, 
                    medical_notes, height_cm, weight_kg, status, join_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");

            $stmt->execute([
                $user_id,
                $form_data['first_name'],
                $form_data['last_name'],
                $form_data['email'],
                $form_data['phone'],
                $form_data['date_of_birth'] ?: null,
                $form_data['gender'] ?: null,
                $form_data['address'],
                $form_data['emergency_contact'],
                $form_data['fitness_goals'],
                $form_data['medical_notes'],
                $form_data['height_cm'] ?: null,
                $form_data['weight_kg'] ?: null,
                $form_data['status']
            ]);

            $pdo->commit();

            $member_id = $pdo->lastInsertId();
            $_SESSION['success'] = "Member added successfully! Member ID: #$member_id";
            redirect('index.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to add member: ' . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-user-plus"></i> Add New Member</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Members
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-user-circle"></i> Member Information</h2>
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

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name"
                            value="<?php echo e($form_data['first_name']); ?>" required placeholder="Enter first name">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name"
                            value="<?php echo e($form_data['last_name']); ?>" required placeholder="Enter last name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth"
                            value="<?php echo e($form_data['date_of_birth']); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo $form_data['gender'] == 'Male' ? 'selected' : ''; ?>>Male
                            </option>
                            <option value="Female" <?php echo $form_data['gender'] == 'Female' ? 'selected' : ''; ?>>
                                Female</option>
                            <option value="Other" <?php echo $form_data['gender'] == 'Other' ? 'selected' : ''; ?>>Other
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-address-book"></i> Contact Information</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo e($form_data['email']); ?>"
                            required placeholder="member@example.com">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo e($form_data['phone']); ?>"
                            placeholder="(123) 456-7890">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="2"
                        placeholder="Enter full address"><?php echo e($form_data['address']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="emergency_contact">Emergency Contact</label>
                    <input type="text" id="emergency_contact" name="emergency_contact"
                        value="<?php echo e($form_data['emergency_contact']); ?>" placeholder="Name and phone number">
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-running"></i> Fitness Information</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="height_cm">Height (cm)</label>
                        <input type="number" id="height_cm" name="height_cm"
                            value="<?php echo e($form_data['height_cm']); ?>" step="0.1" placeholder="e.g., 175.5">
                    </div>

                    <div class="form-group">
                        <label for="weight_kg">Weight (kg)</label>
                        <input type="number" id="weight_kg" name="weight_kg"
                            value="<?php echo e($form_data['weight_kg']); ?>" step="0.1" placeholder="e.g., 70.5">
                    </div>
                </div>

                <div class="form-group">
                    <label for="fitness_goals">Fitness Goals</label>
                    <textarea id="fitness_goals" name="fitness_goals" rows="3"
                        placeholder="Describe fitness goals..."><?php echo e($form_data['fitness_goals']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="medical_notes">Medical Notes</label>
                    <textarea id="medical_notes" name="medical_notes" rows="3"
                        placeholder="Any medical conditions or restrictions..."><?php echo e($form_data['medical_notes']); ?></textarea>
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

                <div class="form-group">
                    <label for="status">Member Status</label>
                    <select id="status" name="status" required>
                        <option value="Active" <?php echo $form_data['status'] == 'Active' ? 'selected' : ''; ?>>Active
                        </option>
                        <option value="Inactive" <?php echo $form_data['status'] == 'Inactive' ? 'selected' : ''; ?>>
                            Inactive</option>
                        <option value="Suspended" <?php echo $form_data['status'] == 'Suspended' ? 'selected' : ''; ?>>
                            Suspended</option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Add Member
            </button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
    </form>
</div>
</div>

<?php include '../../includes/footer.php'; ?>