<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$member_id = is_member() ? get_member_id() : (isset($_GET['member_id']) ? (int) $_GET['member_id'] : 0);

if (!$member_id) {
    $_SESSION['error'] = "Invalid access.";
    redirect('../dashboard.php');
}

$page_title = 'Add Measurement';
include '../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = $_POST['weight_kg'] ?? null;
    $body_fat = $_POST['body_fat_percentage'] ?? null;
    $chest = $_POST['chest_cm'] ?? null;
    $waist = $_POST['waist_cm'] ?? null;
    $biceps = $_POST['biceps_cm'] ?? null;
    $notes = $_POST['notes'] ?? '';

    if (!$weight) {
        $_SESSION['error'] = "Weight is required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO progress_logs (member_id, weight_kg, body_fat_percentage, chest_cm, waist_cm, biceps_cm, notes, logged_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$member_id, $weight, $body_fat, $chest, $waist, $biceps, $notes, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Measurement added successfully!";
            redirect('index.php' . (is_staff() ? '?member_id=' . $member_id : ''));
        } else {
            $_SESSION['error'] = "Failed to add measurement.";
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-plus-circle"></i> New Measurement</h1>
    <div class="header-actions">
        <a href="index.php<?php echo is_staff() ? '?member_id=' . $member_id : ''; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to History
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
                        <label for="weight_kg">Weight (kg) *</label>
                        <input type="number" step="0.1" id="weight_kg" name="weight_kg" required>
                    </div>
                    <div class="form-group">
                        <label for="body_fat_percentage">Body Fat %</label>
                        <input type="number" step="0.1" id="body_fat_percentage" name="body_fat_percentage">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-ruler-horizontal"></i> Measurements (cm)</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="chest_cm">Chest</label>
                        <input type="number" step="0.1" id="chest_cm" name="chest_cm">
                    </div>
                    <div class="form-group">
                        <label for="waist_cm">Waist</label>
                        <input type="number" step="0.1" id="waist_cm" name="waist_cm">
                    </div>
                    <div class="form-group">
                        <label for="biceps_cm">Biceps</label>
                        <input type="number" step="0.1" id="biceps_cm" name="biceps_cm">
                    </div>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                    placeholder="How was your workout? How do you feel?"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">Save Measurement</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>