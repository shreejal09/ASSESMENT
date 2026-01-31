<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    redirect('index.php');
}

// Get log data
$stmt = $pdo->prepare("SELECT * FROM progress_logs WHERE id = ?");
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log || (!is_staff() && $log['member_id'] != get_member_id())) {
    $_SESSION['error'] = "Access denied or record not found.";
    redirect('index.php');
}

$page_title = 'Edit Measurement';
include '../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = $_POST['weight_kg'] ?? $log['weight_kg'];
    $body_fat = $_POST['body_fat_percentage'] ?? $log['body_fat_percentage'];
    $chest = $_POST['chest_cm'] ?? $log['chest_cm'];
    $waist = $_POST['waist_cm'] ?? $log['waist_cm'];
    $biceps = $_POST['biceps_cm'] ?? $log['biceps_cm'];
    $notes = $_POST['notes'] ?? $log['notes'];

    $stmt = $pdo->prepare("
        UPDATE progress_logs SET 
            weight_kg = ?, body_fat_percentage = ?, 
            chest_cm = ?, waist_cm = ?, biceps_cm = ?, 
            notes = ?
        WHERE id = ?
    ");
    if ($stmt->execute([$weight, $body_fat, $chest, $waist, $biceps, $notes, $id])) {
        $_SESSION['success'] = "Measurement updated!";
        redirect('index.php' . (is_staff() ? '?member_id=' . $log['member_id'] : ''));
    } else {
        $_SESSION['error'] = "Failed to update.";
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-edit"></i> Edit Measurement</h1>
    <div class="header-actions">
        <a href="index.php<?php echo is_staff() ? '?member_id=' . $log['member_id'] : ''; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-body">
        <form method="POST" action="" class="form-grid">
            <div class="form-section">
                <h3><i class="fas fa-weight"></i> Body Metrics</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="weight_kg">Weight (kg)</label>
                        <input type="number" step="0.1" id="weight_kg" name="weight_kg"
                            value="<?php echo e($log['weight_kg']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="body_fat_percentage">Body Fat %</label>
                        <input type="number" step="0.1" id="body_fat_percentage" name="body_fat_percentage"
                            value="<?php echo e($log['body_fat_percentage']); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-ruler-horizontal"></i> Measurements (cm)</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="chest_cm">Chest</label>
                        <input type="number" step="0.1" id="chest_cm" name="chest_cm"
                            value="<?php echo e($log['chest_cm']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="waist_cm">Waist</label>
                        <input type="number" step="0.1" id="waist_cm" name="waist_cm"
                            value="<?php echo e($log['waist_cm']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="biceps_cm">Biceps</label>
                        <input type="number" step="0.1" id="biceps_cm" name="biceps_cm"
                            value="<?php echo e($log['biceps_cm']); ?>">
                    </div>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3"><?php echo e($log['notes']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">Update Measurement</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>