<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login(); // All logged in users can add nutrition logs

$page_title = 'Log Meal';
include '../../includes/header.php';

// Determine member ID
if (is_member()) {
    $member_id = get_member_id();
} elseif (is_admin() || is_trainer()) {
    $member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
} else {
    $member_id = 0;
}

$errors = [];
$form_data = [
    'member_id' => $member_id,
    'meal_type' => 'Snack',
    'food_name' => '',
    'calories' => '',
    'protein_g' => '',
    'carbs_g' => '',
    'fat_g' => '',
    'serving_size' => '',
    'log_date' => date('Y-m-d'),
    'log_time' => date('H:i'),
    'notes' => ''
];

// Get members for admin/trainer selection
$members = [];
if (is_admin() || is_trainer()) {
    $stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM members WHERE status = 'Active' ORDER BY first_name");
    $members = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    // Collect and sanitize form data
    $form_data['meal_type'] = $_POST['meal_type'] ?? 'Snack';
    $form_data['food_name'] = trim($_POST['food_name'] ?? '');
    $form_data['calories'] = (int)($_POST['calories'] ?? 0);
    $form_data['protein_g'] = !empty($_POST['protein_g']) ? (float)$_POST['protein_g'] : null;
    $form_data['carbs_g'] = !empty($_POST['carbs_g']) ? (float)$_POST['carbs_g'] : null;
    $form_data['fat_g'] = !empty($_POST['fat_g']) ? (float)$_POST['fat_g'] : null;
    $form_data['serving_size'] = trim($_POST['serving_size'] ?? '');
    $form_data['log_date'] = $_POST['log_date'] ?? date('Y-m-d');
    $form_data['log_time'] = $_POST['log_time'] ?? date('H:i');
    $form_data['notes'] = trim($_POST['notes'] ?? '');
    
    // For admin/trainer, get member ID from form
    if (is_admin() || is_trainer()) {
        $form_data['member_id'] = (int)($_POST['member_id'] ?? 0);
    }
    
    // Validation
    if (empty($form_data['food_name'])) {
        $errors[] = 'Food name is required';
    }
    
    if ($form_data['calories'] <= 0) {
        $errors[] = 'Calories must be greater than 0';
    }
    
    if (is_admin() || is_trainer()) {
        if (empty($form_data['member_id'])) {
            $errors[] = 'Please select a member';
        }
    }
    
    // Check member exists
    if (empty($errors) && $form_data['member_id']) {
        $stmt = $pdo->prepare("SELECT id FROM members WHERE id = ?");
        $stmt->execute([$form_data['member_id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Selected member not found';
        }
    }
    
    // If no errors, save the nutrition log
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO nutrition_logs (
                    member_id, meal_type, food_name, calories, 
                    protein_g, carbs_g, fat_g, serving_size,
                    log_date, log_time, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $form_data['member_id'],
                $form_data['meal_type'],
                $form_data['food_name'],
                $form_data['calories'],
                $form_data['protein_g'],
                $form_data['carbs_g'],
                $form_data['fat_g'],
                $form_data['serving_size'],
                $form_data['log_date'],
                $form_data['log_time'],
                $form_data['notes']
            ]);
            
            $log_id = $pdo->lastInsertId();
            
            // Get member name for success message
            $stmt = $pdo->prepare("SELECT first_name, last_name FROM members WHERE id = ?");
            $stmt->execute([$form_data['member_id']]);
            $member = $stmt->fetch();
            
            $member_name = $member ? $member['first_name'] . ' ' . $member['last_name'] : 'You';
            
            $_SESSION['success'] = "Meal logged successfully for $member_name! (Log ID: #$log_id)";
            redirect('index.php');
            
        } catch (Exception $e) {
            $errors[] = 'Failed to log meal: ' . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-plus"></i> Log Meal</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Nutrition Logs
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-utensils"></i> Meal Information</h2>
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
            <?php if (is_admin() || is_trainer()): ?>
            <div class="form-section">
                <h3><i class="fas fa-user"></i> Member Selection</h3>
                
                <div class="form-group">
                    <label for="member_id">Select Member *</label>
                    <select id="member_id" name="member_id" required>
                        <option value="">-- Select a Member --</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo e($member['id']); ?>" 
                                <?php echo $form_data['member_id'] == $member['id'] ? 'selected' : ''; ?>>
                                <?php echo e($member['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-section">
                <h3><i class="fas fa-calendar-alt"></i> Date & Time</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="log_date">Date *</label>
                        <input type="date" id="log_date" name="log_date" 
                               value="<?php echo e($form_data['log_date']); ?>" required
                               max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="log_time">Time *</label>
                        <input type="time" id="log_time" name="log_time" 
                               value="<?php echo e($form_data['log_time']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-utensils"></i> Meal Details</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="meal_type">Meal Type *</label>
                        <select id="meal_type" name="meal_type" required>
                            <option value="Breakfast" <?php echo $form_data['meal_type'] == 'Breakfast' ? 'selected' : ''; ?>>Breakfast</option>
                            <option value="Lunch" <?php echo $form_data['meal_type'] == 'Lunch' ? 'selected' : ''; ?>>Lunch</option>
                            <option value="Dinner" <?php echo $form_data['meal_type'] == 'Dinner' ? 'selected' : ''; ?>>Dinner</option>
                            <option value="Snack" <?php echo $form_data['meal_type'] == 'Snack' ? 'selected' : ''; ?>>Snack</option>
                            <option value="Pre-workout" <?php echo $form_data['meal_type'] == 'Pre-workout' ? 'selected' : ''; ?>>Pre-workout</option>
                            <option value="Post-workout" <?php echo $form_data['meal_type'] == 'Post-workout' ? 'selected' : ''; ?>>Post-workout</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="serving_size">Serving Size</label>
                        <input type="text" id="serving_size" name="serving_size" 
                               value="<?php echo e($form_data['serving_size']); ?>"
                               placeholder="e.g., 1 cup, 100g, 2 slices">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="food_name">Food Name *</label>
                    <input type="text" id="food_name" name="food_name" 
                           value="<?php echo e($form_data['food_name']); ?>" required
                           placeholder="e.g., Grilled Chicken Salad, Protein Shake">
                </div>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-chart-pie"></i> Nutrition Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="calories">Calories *</label>
                        <input type="number" id="calories" name="calories" 
                               value="<?php echo e($form_data['calories']); ?>" min="1" required
                               placeholder="e.g., 350">
                        <small class="form-text">Total calories</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="protein_g">Protein (g)</label>
                        <input type="number" id="protein_g" name="protein_g" 
                               value="<?php echo e($form_data['protein_g']); ?>" min="0" step="0.1"
                               placeholder="e.g., 25.5">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="carbs_g">Carbohydrates (g)</label>
                        <input type="number" id="carbs_g" name="carbs_g" 
                               value="<?php echo e($form_data['carbs_g']); ?>" min="0" step="0.1"
                               placeholder="e.g., 45.2">
                    </div>
                    
                    <div class="form-group">
                        <label for="fat_g">Fat (g)</label>
                        <input type="number" id="fat_g" name="fat_g" 
                               value="<?php echo e($form_data['fat_g']); ?>" min="0" step="0.1"
                               placeholder="e.g., 12.8">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-sticky-note"></i> Additional Notes</h3>
                
                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="3"
                              placeholder="Any additional notes about this meal..."><?php echo e($form_data['notes']); ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Log Meal
                </button>
                <button type="reset" class="btn btn-secondary">Reset Form</button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>