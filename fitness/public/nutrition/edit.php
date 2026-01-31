<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid log ID";
    redirect('index.php');
}

// Get log data
$stmt = $pdo->prepare("SELECT * FROM nutrition_logs WHERE id = ?");
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log) {
    $_SESSION['error'] = "Log not found";
    redirect('index.php');
}

// Security: members can only edit their own logs
if (is_member() && $log['member_id'] != get_member_id()) {
    $_SESSION['error'] = "Access denied";
    redirect('index.php');
}

$page_title = 'Edit Nutrition Log';
include '../../includes/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $food_name = trim($_POST['food_name']);
    $calories = (int) $_POST['calories'];
    $meal_type = $_POST['meal_type'];
    $log_date = $_POST['log_date'];

    if (empty($food_name))
        $errors[] = "Food name is required";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE nutrition_logs SET 
                    food_name = ?, calories = ?, meal_type = ?, 
                    log_date = ?, serving_size = ?, protein_g = ?, 
                    carbs_g = ?, fat_g = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $food_name,
                $calories,
                $meal_type,
                $log_date,
                $_POST['serving_size'],
                $_POST['protein_g'],
                $_POST['carbs_g'],
                $_POST['fat_g'],
                $_POST['notes'],
                $id
            ]);

            $_SESSION['success'] = "Log updated successfully";
            redirect('index.php');
        } catch (Exception $e) {
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-edit"></i> Edit Meal Log</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Logs
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error)
                        echo "<li>$error</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form-grid">
            <div class="form-group">
                <label for="food_name">Food Name *</label>
                <input type="text" id="food_name" name="food_name" value="<?php echo e($log['food_name']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="meal_type">Meal Type</label>
                    <select name="meal_type" id="meal_type">
                        <option value="Breakfast" <?php echo $log['meal_type'] == 'Breakfast' ? 'selected' : ''; ?>
                            >Breakfast</option>
                        <option value="Lunch" <?php echo $log['meal_type'] == 'Lunch' ? 'selected' : ''; ?>>Lunch
                        </option>
                        <option value="Dinner" <?php echo $log['meal_type'] == 'Dinner' ? 'selected' : ''; ?>>Dinner
                        </option>
                        <option value="Snack" <?php echo $log['meal_type'] == 'Snack' ? 'selected' : ''; ?>>Snack
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="log_date">Date</label>
                    <input type="date" id="log_date" name="log_date" value="<?php echo e($log['log_date']); ?>"
                        required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="calories">Calories</label>
                    <input type="number" id="calories" name="calories" value="<?php echo e($log['calories']); ?>">
                </div>
                <div class="form-group">
                    <label for="serving_size">Serving Size</label>
                    <input type="text" id="serving_size" name="serving_size"
                        value="<?php echo e($log['serving_size']); ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>