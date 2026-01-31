<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_admin(); // Only admin can edit trainers

$page_title = 'Edit Trainer';
include '../../includes/header.php';

// Get trainer ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid trainer ID';
    redirect('index.php');
}

$trainer_id = (int) $_GET['id'];

// Get trainer data
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.email as user_email 
    FROM trainers t 
    LEFT JOIN users u ON t.user_id = u.id 
    WHERE t.id = ?
");
$stmt->execute([$trainer_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    $_SESSION['error'] = 'Trainer not found';
    redirect('index.php');
}

$errors = [];
$form_data = [
    'full_name' => $trainer['full_name'],
    'email' => $trainer['email'],
    'phone' => $trainer['phone'] ?? '',
    'specialization' => $trainer['specialization'] ?? '',
    'certification' => $trainer['certification'] ?? '',
    'experience_years' => $trainer['experience_years'] ?? '',
    'hourly_rate' => $trainer['hourly_rate'] ?? '',
    'availability' => $trainer['availability'] ?? 'Full-time',
    'bio' => $trainer['bio'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        'bio' => trim($_POST['bio'] ?? '')
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

    // Check if email already exists (excluding current trainer)
    if (empty($errors) && $form_data['email'] !== $trainer['email']) {
        $stmt = $pdo->prepare("SELECT id FROM trainers WHERE email = ? AND id != ?");
        $stmt->execute([$form_data['email'], $trainer_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already exists for another trainer';
        }
    }

    // If no errors, update trainer
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE trainers SET
                    full_name = ?,
                    email = ?,
                    phone = ?,
                    specialization = ?,
                    certification = ?,
                    experience_years = ?,
                    hourly_rate = ?,
                    availability = ?,
                    bio = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $form_data['full_name'],
                $form_data['email'],
                $form_data['phone'],
                $form_data['specialization'],
                $form_data['certification'],
                $form_data['experience_years'] ?: 0,
                $form_data['hourly_rate'] ?: 0,
                $form_data['availability'],
                $form_data['bio'],
                $trainer_id
            ]);

            // Update user email if different
            if ($form_data['email'] !== $trainer['user_email'] && $trainer['user_id']) {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$form_data['email'], $trainer['user_id']]);
            }

            $_SESSION['success'] = "Trainer updated successfully!";
            redirect('index.php');

        } catch (Exception $e) {
            $errors[] = 'Failed to update trainer: ' . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-user-edit"></i> Edit Trainer: <?php echo e($trainer['full_name']); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Trainers
        </a>
        <a href="view.php?id=<?php echo e($trainer_id); ?>" class="btn btn-info">
            <i class="fas fa-eye"></i> View Profile
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-user-tie"></i> Edit Trainer Information</h2>
        <div class="trainer-info">
            <span class="badge info">
                <?php echo e($trainer['availability']); ?> Trainer
            </span>
            <span class="text-muted">| Join Date: <?php echo e(format_date($trainer['created_at'])); ?></span>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
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
                        required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo e($form_data['email']); ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo e($form_data['phone']); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-briefcase"></i> Professional Details</h3>
                <div class="form-group">
                    <label for="specialization">Specialization</label>
                    <input type="text" id="specialization" name="specialization"
                        value="<?php echo e($form_data['specialization']); ?>"
                        placeholder="e.g. Bodybuilding, Yoga, CrossFit">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="experience_years">Experience (Years)</label>
                        <input type="number" id="experience_years" name="experience_years"
                            value="<?php echo e($form_data['experience_years']); ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="hourly_rate">Hourly Rate ($)</label>
                        <input type="number" id="hourly_rate" name="hourly_rate" step="0.01"
                            value="<?php echo e($form_data['hourly_rate']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="availability">Availability</label>
                    <select id="availability" name="availability">
                        <option value="Full-time" <?php echo $form_data['availability'] == 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                        <option value="Part-time" <?php echo $form_data['availability'] == 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                        <option value="Contract" <?php echo $form_data['availability'] == 'Contract' ? 'selected' : ''; ?>>Contract</option>
                    </select>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="certification">Certifications</label>
                <input type="text" id="certification" name="certification"
                    value="<?php echo e($form_data['certification']); ?>" placeholder="e.g. NASM, ACE, ISSA">
            </div>

            <div class="form-group full-width">
                <label for="bio">Biography</label>
                <textarea id="bio" name="bio" rows="4"><?php echo e($form_data['bio']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Update Trainer
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>