<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid workout plan ID";
    redirect('index.php');
}

// Get plan data
$stmt = $pdo->prepare("
    SELECT wp.*, u.username as creator
    FROM workout_plans wp
    LEFT JOIN users u ON wp.created_by = u.id
    WHERE wp.id = ?
");
$stmt->execute([$id]);
$plan = $stmt->fetch();

if (!$plan) {
    $_SESSION['error'] = "Workout plan not found";
    redirect('index.php');
}

// Get exercises
$stmt = $pdo->prepare("SELECT * FROM workout_exercises WHERE workout_plan_id = ? ORDER BY day_number, id");
$stmt->execute([$id]);
$exercises = $stmt->fetchAll();

$page_title = 'Workout Plan: ' . $plan['plan_name'];
include '../../includes/header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-running"></i> Workout Plan Details</h1>
    <div class="header-actions">
        <?php if (is_admin() || (is_trainer() && $plan['created_by'] == $_SESSION['user_id'])): ?>
            <a href="manage.php?action=edit&id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Plan
            </a>
        <?php endif; ?>
        <a href="<?php echo is_staff() ? 'index.php' : 'plans.php'; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Plans
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2>
            <?php echo e($plan['plan_name']); ?>
        </h2>
        <span class="status-badge <?php echo strtolower($plan['difficulty_level']); ?>">
            <?php echo e($plan['difficulty_level']); ?>
        </span>
    </div>
    <div class="card-body">
        <div class="plan-overview">
            <div class="overview-item">
                <i class="fas fa-calendar-week"></i>
                <div>
                    <strong>Duration</strong>
                    <p>
                        <?php echo e($plan['duration_weeks']); ?> Weeks
                    </p>
                </div>
            </div>
            <div class="overview-item">
                <i class="fas fa-bullseye"></i>
                <div>
                    <strong>Target Area</strong>
                    <p>
                        <?php echo e($plan['target_area'] ?: 'Full Body'); ?>
                    </p>
                </div>
            </div>
            <div class="overview-item">
                <i class="fas fa-user-edit"></i>
                <div>
                    <strong>Created By</strong>
                    <p>
                        <?php echo e($plan['creator'] ?: 'System'); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if ($plan['description']): ?>
            <div class="description-box">
                <h3><i class="fas fa-info-circle"></i> Description</h3>
                <p>
                    <?php echo nl2br(e($plan['description'])); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($plan['equipment_needed']): ?>
            <div class="equipment-box">
                <h3><i class="fas fa-tools"></i> Equipment Needed</h3>
                <p>
                    <?php echo e($plan['equipment_needed']); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-20">
    <h3><i class="fas fa-list-ol"></i> Plan Exercises</h3>
    <?php
    $current_day = 0;
    foreach ($exercises as $ex):
        if ($ex['day_number'] != $current_day):
            if ($current_day != 0)
                echo '</div>'; // Close previous day group
            $current_day = $ex['day_number'];
            echo "<h4 class='day-header'>Day $current_day</h4><div class='exercise-grid'>";
        endif;
        ?>
        <div class="exercise-card">
            <h5>
                <?php echo e($ex['exercise_name']); ?>
            </h5>
            <div class="ex-meta">
                <span><strong>Sets:</strong>
                    <?php echo e($ex['sets']); ?>
                </span>
                <span><strong>Reps:</strong>
                    <?php echo e($ex['reps']); ?>
                </span>
                <span><strong>Rest:</strong>
                    <?php echo e($ex['rest_seconds']); ?>s
                </span>
            </div>
            <?php if ($ex['muscle_group']): ?>
                <small class="muscle">Group:
                    <?php echo e($ex['muscle_group']); ?>
                </small>
            <?php endif; ?>
            <?php if ($ex['instructions']): ?>
                <p class="instr">
                    <?php echo e($ex['instructions']); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (count($exercises) > 0)
        echo '</div>'; ?>
</div>

<style>
    .plan-overview {
        display: flex;
        gap: 40px;
        margin-bottom: 30px;
        border-bottom: 1px solid #eee;
        padding-bottom: 20px;
    }

    .overview-item {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .overview-item i {
        font-size: 1.5rem;
        color: #3498db;
    }

    .overview-item p {
        margin: 0;
        color: #666;
    }

    .day-header {
        background: #2c3e50;
        color: white;
        padding: 8px 15px;
        border-radius: 4px;
        margin: 25px 0 15px 0;
    }

    .exercise-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
    }

    .exercise-card {
        background: #fff;
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .exercise-card h5 {
        margin-top: 0;
        color: #2980b9;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 8px;
    }

    .ex-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        color: #444;
        margin-bottom: 10px;
    }

    .instr {
        font-size: 0.85rem;
        color: #777;
        margin-top: 8px;
    }

    .mt-20 {
        margin-top: 20px;
    }
</style>

<?php include '../../includes/footer.php'; ?>