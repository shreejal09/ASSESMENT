<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can edit

$page_title = 'Edit Member';
include '../../includes/header.php';

// Get member ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid member ID';
    redirect('index.php');
}

$member_id = (int)$_GET['id'];

// Get member data
$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.email as user_email 
    FROM members m 
    LEFT JOIN users u ON m.user_id = u.id 
    WHERE m.id = ?
");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    $_SESSION['error'] = 'Member not found';
    redirect('index.php');
}

$errors = [];
$form_data = [
    'first_name' => $member['first_name'],
    'last_name' => $member['last_name'],
    'email' => $member['email'],
    'phone' => $member['phone'] ?? '',
    'date_of_birth' => $member['date_of_birth'] ?? '',
    'gender' => $member['gender'] ?? '',
    'address' => $member['address'] ?? '',
    'emergency_contact' => $member['emergency_contact'] ?? '',
    'fitness_goals' => $member['fitness_goals'] ?? '',
    'medical_notes' => $member['medical_notes'] ?? '',
    'height_cm' => $member['height_cm'] ?? '',
    'weight_kg' => $member['weight_kg'] ?? '',
    'status' => $member['status']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    // Check if email already exists (excluding current member)
    if (empty($errors) && $form_data['email'] !== $member['email']) {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
        $stmt->execute([$form_data['email'], $member_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already exists for another member';
        }
    }
    
    // If no errors, update member
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE members SET
                    first_name = ?,
                    last_name = ?,
                    email = ?,
                    phone = ?,
                    date_of_birth = ?,
                    gender = ?,
                    address = ?,
                    emergency_contact = ?,
                    fitness_goals = ?,
                    medical_notes = ?,
                    height_cm = ?,
                    weight_kg = ?,
                    status = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([
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
                $form_data['status'],
                $member_id
            ]);
            
            // Update user email if different
            if ($form_data['email'] !== $member['user_email'] && $member['user_id']) {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$form_data['email'], $member['user_id']]);
            }
            
            $_SESSION['success'] = "Member updated successfully!";
            redirect('index.php');
            
        } catch (Exception $e) {
            $errors[] = 'Failed to update member: ' . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-user-edit"></i> Edit Member: <?php echo e($member['first_name'] . ' ' . $member['last_name']); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Members
        </a>
        <a href="view.php?id=<?php echo e($member_id); ?>" class="btn btn-info">
            <i class="fas fa-eye"></i> View Profile
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-user-circle"></i> Edit Member Information</h2>
        <div class="member-info">
            <span class="badge <?php echo get_status_badge($member['status']); ?>">
                <?php echo e($member['status']); ?>
            </span>
            <span class="text-muted">Member since <?php echo e(format_date($member['join_date'])); ?></span>
        </div>
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
                               value="<?php echo e($form_data['first_name']); ?>" required
                               placeholder="Enter first name">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo e($form_data['last_name']); ?>" required
                               placeholder="Enter last name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" 
                               value="<?php echo e($form_data['date_of_birth']); ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo $form_data['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $form_data['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $form_data['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-address-book"></i> Contact Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo e($form_data['email']); ?>" required
                               placeholder="member@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo e($form_data['phone']); ?>"
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
                           value="<?php echo e($form_data['emergency_contact']); ?>"
                           placeholder="Name and phone number">
                </div>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-running"></i> Fitness Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="height_cm">Height (cm)</label>
                        <input type="number" id="height_cm" name="height_cm" 
                               value="<?php echo e($form_data['height_cm']); ?>" step="0.1"
                               placeholder="e.g., 175.5">
                    </div>
                    
                    <div class="form-group">
                        <label for="weight_kg">Weight (kg)</label>
                        <input type="number" id="weight_kg" name="weight_kg" 
                               value="<?php echo e($form_data['weight_kg']); ?>" step="0.1"
                               placeholder="e.g., 70.5">
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
                <h3><i class="fas fa-cog"></i> Account Settings</h3>
                
                <div class="form-group">
                    <label for="status">Member Status *</label>
                    <select id="status" name="status" required>
                        <option value="Active" <?php echo $form_data['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $form_data['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Suspended" <?php echo $form_data['status'] == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo e($member['username']); ?>" disabled
                           class="disabled-input" 
                           title="Username cannot be changed">
                    <small class="form-text">Username cannot be changed for security reasons</small>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Update Member
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <?php if (is_admin()): ?>
                <a href="delete.php?id=<?php echo e($member_id); ?>" class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this member? This action cannot be undone.')">
                    <i class="fas fa-trash"></i> Delete Member
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>