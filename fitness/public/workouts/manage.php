<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can manage workouts

// Get action and ID
$action = isset($_GET['action']) ? $_GET['action'] : 'add';
$plan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$errors = [];
$success = '';
$form_data = [
    'plan_name' => '',
    'description' => '',
    'difficulty_level' => 'Beginner',
    'duration_weeks' => 4,
    'target_area' => '',
    'equipment_needed' => '',
    'is_active' => 1
];

$exercises = [];
$is_edit = false;

// Handle different actions
if ($action === 'edit' || $action === 'copy' || $action === 'delete') {
    if ($plan_id <= 0) {
        $_SESSION['error'] = 'Invalid workout plan ID';
        redirect('index.php');
    }
    
    // Get workout plan data
    $stmt = $pdo->prepare("SELECT * FROM workout_plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
        $_SESSION['error'] = 'Workout plan not found';
        redirect('index.php');
    }
    
    // Check permissions (trainers can only edit their own plans)
    if (is_trainer() && $plan['created_by'] != $_SESSION['user_id']) {
        $_SESSION['error'] = 'You can only edit your own workout plans';
        redirect('index.php');
    }
    
    if ($action === 'delete') {
        // Handle deletion
        try {
            // Check if plan has active assignments
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM member_workouts WHERE workout_plan_id = ? AND status = 'Active'");
            $stmt->execute([$plan_id]);
            $has_assignments = $stmt->fetch()['total'] > 0;
            
            if ($has_assignments) {
                $_SESSION['error'] = 'Cannot delete workout plan with active assignments. Please reassign members first.';
                redirect('index.php');
            }
            
            // Delete exercises first (cascade should handle this, but being safe)
            $stmt = $pdo->prepare("DELETE FROM workout_exercises WHERE workout_plan_id = ?");
            $stmt->execute([$plan_id]);
            
            // Delete the plan
            $stmt = $pdo->prepare("DELETE FROM workout_plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            
            $_SESSION['success'] = "Workout plan '{$plan['plan_name']}' deleted successfully";
            redirect('index.php');
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to delete workout plan: ' . $e->getMessage();
            redirect('index.php');
        }
    }
    
    // For edit or copy, load the data
    $form_data = [
        'plan_name' => $plan['plan_name'],
        'description' => $plan['description'] ?? '',
        'difficulty_level' => $plan['difficulty_level'],
        'duration_weeks' => $plan['duration_weeks'],
        'target_area' => $plan['target_area'] ?? '',
        'equipment_needed' => $plan['equipment_needed'] ?? '',
        'is_active' => $plan['is_active']
    ];
    
    if ($action === 'copy') {
        $form_data['plan_name'] = $plan['plan_name'] . ' (Copy)';
    }
    
    $is_edit = ($action === 'edit');
    
    // Load exercises for this plan
    $stmt = $pdo->prepare("SELECT * FROM workout_exercises WHERE workout_plan_id = ? ORDER BY day_number, id");
    $stmt->execute([$plan_id]);
    $exercises = $stmt->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $form_data = [
        'plan_name' => trim($_POST['plan_name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'difficulty_level' => $_POST['difficulty_level'] ?? 'Beginner',
        'duration_weeks' => (int)($_POST['duration_weeks'] ?? 4),
        'target_area' => trim($_POST['target_area'] ?? ''),
        'equipment_needed' => trim($_POST['equipment_needed'] ?? ''),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Collect exercise data
    $exercise_data = [];
    if (isset($_POST['exercise_name']) && is_array($_POST['exercise_name'])) {
        foreach ($_POST['exercise_name'] as $index => $name) {
            if (!empty(trim($name))) {
                $exercise_data[] = [
                    'name' => trim($name),
                    'sets' => (int)($_POST['exercise_sets'][$index] ?? 3),
                    'reps' => trim($_POST['exercise_reps'][$index] ?? '8-12'),
                    'rest' => (int)($_POST['exercise_rest'][$index] ?? 60),
                    'instructions' => trim($_POST['exercise_instructions'][$index] ?? ''),
                    'muscle_group' => trim($_POST['exercise_muscle'][$index] ?? ''),
                    'day_number' => (int)($_POST['exercise_day'][$index] ?? 1)
                ];
            }
        }
    }
    
    // Validation
    if (empty($form_data['plan_name'])) {
        $errors[] = 'Plan name is required';
    }
    
    if ($form_data['duration_weeks'] <= 0) {
        $errors[] = 'Duration must be at least 1 week';
    }
    
    if (empty($exercise_data)) {
        $errors[] = 'At least one exercise is required';
    }
    
    // If no errors, save the data
    if (empty($errors)) {
        $pdo->beginTransaction();
        
        try {
            if ($is_edit) {
                // Update existing plan
                $stmt = $pdo->prepare("
                    UPDATE workout_plans SET
                        plan_name = ?,
                        description = ?,
                        difficulty_level = ?,
                        duration_weeks = ?,
                        target_area = ?,
                        equipment_needed = ?,
                        is_active = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $form_data['plan_name'],
                    $form_data['description'],
                    $form_data['difficulty_level'],
                    $form_data['duration_weeks'],
                    $form_data['target_area'],
                    $form_data['equipment_needed'],
                    $form_data['is_active'],
                    $plan_id
                ]);
                
                // Delete old exercises
                $stmt = $pdo->prepare("DELETE FROM workout_exercises WHERE workout_plan_id = ?");
                $stmt->execute([$plan_id]);
                
                $success = "Workout plan updated successfully!";
                
            } else {
                // Create new plan
                $stmt = $pdo->prepare("
                    INSERT INTO workout_plans (
                        plan_name, description, difficulty_level, duration_weeks,
                        target_area, equipment_needed, created_by, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $created_by = is_trainer() ? $_SESSION['user_id'] : null;
                
                $stmt->execute([
                    $form_data['plan_name'],
                    $form_data['description'],
                    $form_data['difficulty_level'],
                    $form_data['duration_weeks'],
                    $form_data['target_area'],
                    $form_data['equipment_needed'],
                    $created_by,
                    $form_data['is_active']
                ]);
                
                $plan_id = $pdo->lastInsertId();
                $success = "New workout plan created successfully! Plan ID: #$plan_id";
            }
            
            // Insert exercises
            foreach ($exercise_data as $exercise) {
                $stmt = $pdo->prepare("
                    INSERT INTO workout_exercises (
                        workout_plan_id, exercise_name, sets, reps,
                        rest_seconds, instructions, muscle_group, day_number
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $plan_id,
                    $exercise['name'],
                    $exercise['sets'],
                    $exercise['reps'],
                    $exercise['rest'],
                    $exercise['instructions'],
                    $exercise['muscle_group'],
                    $exercise['day_number']
                ]);
            }
            
            $pdo->commit();
            
            if (isset($_POST['save_and_continue'])) {
                $_SESSION['success'] = $success;
                redirect("manage.php?action=edit&id=$plan_id");
            } else {
                $_SESSION['success'] = $success;
                redirect('index.php');
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to save workout plan: ' . $e->getMessage();
        }
    }
}

// Set page title based on action
$page_title = ucfirst($action) . ' Workout Plan';
if ($action === 'copy') $page_title = 'Copy Workout Plan';

include '../../includes/header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-running"></i> <?php echo e($page_title); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Plans
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-dumbbell"></i> Plan Information</h2>
    </div>
    <div class="card-body">
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
        
        <form method="POST" action="" id="workoutForm" class="form-grid">
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                
                <div class="form-group">
                    <label for="plan_name">Plan Name *</label>
                    <input type="text" id="plan_name" name="plan_name" 
                           value="<?php echo e($form_data['plan_name']); ?>" required
                           placeholder="e.g., 4-Week Beginner Strength Program">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"
                              placeholder="Describe the workout plan, its goals, and benefits..."><?php echo e($form_data['description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="difficulty_level">Difficulty Level *</label>
                        <select id="difficulty_level" name="difficulty_level" required>
                            <option value="Beginner" <?php echo $form_data['difficulty_level'] == 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo $form_data['difficulty_level'] == 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo $form_data['difficulty_level'] == 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration_weeks">Duration (Weeks) *</label>
                        <input type="number" id="duration_weeks" name="duration_weeks" 
                               value="<?php echo e($form_data['duration_weeks']); ?>" min="1" max="52" required
                               placeholder="e.g., 4">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="target_area">Target Area/Muscle Group</label>
                    <input type="text" id="target_area" name="target_area" 
                           value="<?php echo e($form_data['target_area']); ?>"
                           placeholder="e.g., Full Body, Upper Body, Chest & Triceps">
                </div>
                
                <div class="form-group">
                    <label for="equipment_needed">Equipment Needed</label>
                    <textarea id="equipment_needed" name="equipment_needed" rows="2"
                              placeholder="List required equipment, separated by commas..."><?php echo e($form_data['equipment_needed']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <input type="checkbox" id="is_active" name="is_active" 
                               value="1" <?php echo $form_data['is_active'] ? 'checked' : ''; ?>>
                        <label for="is_active">Plan is active and available for assignment</label>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-list-ol"></i> Exercises</h3>
                <p class="form-text">Add exercises to your workout plan. Each exercise belongs to a specific day of the week.</p>
                
                <div id="exercises-container">
                    <?php if (empty($exercises)): ?>
                        <!-- Default empty exercise -->
                        <div class="exercise-item" data-index="0">
                            <div class="exercise-header">
                                <h4>Exercise #1</h4>
                                <button type="button" class="btn btn-sm btn-danger remove-exercise" onclick="removeExercise(0)">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Exercise Name *</label>
                                    <input type="text" name="exercise_name[]" required
                                           placeholder="e.g., Barbell Squats">
                                </div>
                                <div class="form-group">
                                    <label>Day Number *</label>
                                    <input type="number" name="exercise_day[]" value="1" min="1" max="7" required
                                           placeholder="1-7">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Sets</label>
                                    <input type="number" name="exercise_sets[]" value="3" min="1" max="10"
                                           placeholder="e.g., 3">
                                </div>
                                <div class="form-group">
                                    <label>Reps/Range</label>
                                    <input type="text" name="exercise_reps[]" value="8-12"
                                           placeholder="e.g., 8-12 or 10">
                                </div>
                                <div class="form-group">
                                    <label>Rest (seconds)</label>
                                    <input type="number" name="exercise_rest[]" value="60" min="0" max="300"
                                           placeholder="e.g., 60">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Muscle Group</label>
                                    <input type="text" name="exercise_muscle[]"
                                           placeholder="e.g., Legs, Chest, Back">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Instructions/Notes</label>
                                <textarea name="exercise_instructions[]" rows="2"
                                          placeholder="Detailed instructions, form tips, variations..."></textarea>
                            </div>
                            <hr>
                        </div>
                    <?php else: ?>
                        <?php foreach ($exercises as $index => $exercise): ?>
                        <div class="exercise-item" data-index="<?php echo e($index); ?>">
                            <div class="exercise-header">
                                <h4>Exercise #<?php echo e($index + 1); ?></h4>
                                <button type="button" class="btn btn-sm btn-danger remove-exercise" onclick="removeExercise(<?php echo e($index); ?>)">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Exercise Name *</label>
                                    <input type="text" name="exercise_name[]" 
                                           value="<?php echo e($exercise['exercise_name']); ?>" required
                                           placeholder="e.g., Barbell Squats">
                                </div>
                                <div class="form-group">
                                    <label>Day Number *</label>
                                    <input type="number" name="exercise_day[]" 
                                           value="<?php echo e($exercise['day_number']); ?>" min="1" max="7" required
                                           placeholder="1-7">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Sets</label>
                                    <input type="number" name="exercise_sets[]" 
                                           value="<?php echo e($exercise['sets']); ?>" min="1" max="10"
                                           placeholder="e.g., 3">
                                </div>
                                <div class="form-group">
                                    <label>Reps/Range</label>
                                    <input type="text" name="exercise_reps[]" 
                                           value="<?php echo e($exercise['reps']); ?>"
                                           placeholder="e.g., 8-12 or 10">
                                </div>
                                <div class="form-group">
                                    <label>Rest (seconds)</label>
                                    <input type="number" name="exercise_rest[]" 
                                           value="<?php echo e($exercise['rest_seconds']); ?>" min="0" max="300"
                                           placeholder="e.g., 60">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Muscle Group</label>
                                    <input type="text" name="exercise_muscle[]" 
                                           value="<?php echo e($exercise['muscle_group']); ?>"
                                           placeholder="e.g., Legs, Chest, Back">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Instructions/Notes</label>
                                <textarea name="exercise_instructions[]" rows="2"
                                          placeholder="Detailed instructions, form tips, variations..."><?php echo e($exercise['instructions']); ?></textarea>
                            </div>
                            <hr>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn btn-info" onclick="addExercise()">
                        <i class="fas fa-plus"></i> Add Another Exercise
                    </button>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="save" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Update' : 'Create'; ?> Workout Plan
                </button>
                <button type="submit" name="save_and_continue" class="btn btn-secondary">
                    <i class="fas fa-save"></i> Save & Continue Editing
                </button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.exercise-item {
    background: #f8f9fa;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #dee2e6;
}

.exercise-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.exercise-header h4 {
    margin: 0;
    color: #2c3e50;
}

.remove-exercise {
    font-size: 0.8rem;
}
</style>

<script>
let exerciseCounter = <?php echo count($exercises) ?: 1; ?>;

function addExercise() {
    const container = document.getElementById('exercises-container');
    const newIndex = exerciseCounter++;
    
    const exerciseHtml = `
        <div class="exercise-item" data-index="${newIndex}">
            <div class="exercise-header">
                <h4>Exercise #${newIndex + 1}</h4>
                <button type="button" class="btn btn-sm btn-danger remove-exercise" onclick="removeExercise(${newIndex})">
                    <i class="fas fa-times"></i> Remove
                </button>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Exercise Name *</label>
                    <input type="text" name="exercise_name[]" required
                           placeholder="e.g., Barbell Squats">
                </div>
                <div class="form-group">
                    <label>Day Number *</label>
                    <input type="number" name="exercise_day[]" value="1" min="1" max="7" required
                           placeholder="1-7">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Sets</label>
                    <input type="number" name="exercise_sets[]" value="3" min="1" max="10"
                           placeholder="e.g., 3">
                </div>
                <div class="form-group">
                    <label>Reps/Range</label>
                    <input type="text" name="exercise_reps[]" value="8-12"
                           placeholder="e.g., 8-12 or 10">
                </div>
                <div class="form-group">
                    <label>Rest (seconds)</label>
                    <input type="number" name="exercise_rest[]" value="60" min="0" max="300"
                           placeholder="e.g., 60">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Muscle Group</label>
                    <input type="text" name="exercise_muscle[]"
                           placeholder="e.g., Legs, Chest, Back">
                </div>
            </div>
            <div class="form-group">
                <label>Instructions/Notes</label>
                <textarea name="exercise_instructions[]" rows="2"
                          placeholder="Detailed instructions, form tips, variations..."></textarea>
            </div>
            <hr>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', exerciseHtml);
}

function removeExercise(index) {
    const exercise = document.querySelector(`.exercise-item[data-index="${index}"]`);
    if (exercise && document.querySelectorAll('.exercise-item').length > 1) {
        exercise.remove();
        // Renumber remaining exercises
        const exercises = document.querySelectorAll('.exercise-item');
        exercises.forEach((ex, i) => {
            ex.querySelector('h4').textContent = `Exercise #${i + 1}`;
            ex.setAttribute('data-index', i);
            const button = ex.querySelector('.remove-exercise');
            button.setAttribute('onclick', `removeExercise(${i})`);
        });
        exerciseCounter = exercises.length;
    } else {
        alert('You need at least one exercise in the plan.');
    }
}

// Form validation
document.getElementById('workoutForm').addEventListener('submit', function(e) {
    const exerciseNames = document.querySelectorAll('input[name="exercise_name[]"]');
    let isValid = true;
    
    exerciseNames.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = '#e74c3c';
        } else {
            input.style.borderColor = '';
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all exercise names.');
        return false;
    }
    
    return true;
});
</script>

<?php include '../../includes/footer.php'; ?>