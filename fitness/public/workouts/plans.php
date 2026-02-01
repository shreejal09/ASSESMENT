<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

// All logged in users can view workout plans

$page_title = 'Workout Plans';
include '../../includes/header.php';

// Check if member is viewing their own plans
$member_id = is_member() ? get_member_id() : null;

// Pagination
$limit = 15;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$difficulty_filter = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query for public plans
$where_clauses = ["wp.is_active = 1"];
$params = [];

if (!empty($difficulty_filter)) {
    $where_clauses[] = "wp.difficulty_level = ?";
    $params[] = $difficulty_filter;
}

if (!empty($search)) {
    $where_clauses[] = "(wp.plan_name LIKE ? OR wp.description LIKE ? OR wp.target_area LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// If member is viewing, also show their assigned plans
if ($member_id) {
    $where_clauses = ["(wp.is_active = 1 OR mw.member_id = ?)"];
    $params = [$member_id];

    if (!empty($difficulty_filter)) {
        $where_clauses[0] .= " AND wp.difficulty_level = ?";
        $params[] = $difficulty_filter;
    }

    if (!empty($search)) {
        $where_clauses[0] .= " AND (wp.plan_name LIKE ? OR wp.description LIKE ? OR wp.target_area LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
}

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

// Get total count
$count_sql = "
    SELECT COUNT(DISTINCT wp.id) as total 
    FROM workout_plans wp
    LEFT JOIN member_workouts mw ON wp.id = mw.workout_plan_id AND mw.member_id = ?
    $where_sql
";
$stmt = $pdo->prepare($count_sql);
// We need to add member_id for the JOIN placeholder and then use the existing $params for the WHERE clause
$count_params = array_merge([$member_id], $params);
$stmt->execute($count_params);
$total_plans = $stmt->fetch()['total'];
$total_pages = ceil($total_plans / $limit);

// Get workout plans with pagination
$sql = "
    SELECT DISTINCT wp.*, 
           u.username as created_by_name,
           (SELECT COUNT(*) FROM workout_exercises WHERE workout_plan_id = wp.id) as total_exercises,
           mw.status as assignment_status,
           mw.start_date as assignment_start,
           mw.end_date as assignment_end
    FROM workout_plans wp
    LEFT JOIN users u ON wp.created_by = u.id
    LEFT JOIN member_workouts mw ON wp.id = mw.workout_plan_id AND mw.member_id = ?
    $where_sql
    ORDER BY 
        CASE WHEN mw.status = 'Active' THEN 1 ELSE 2 END,
        wp.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $member_id;
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$workout_plans = $stmt->fetchAll();

// Get member's active assignments if they're a member
$member_assignments = [];
if ($member_id) {
    $stmt = $pdo->prepare("
        SELECT mw.*, wp.plan_name, wp.difficulty_level 
        FROM member_workouts mw
        JOIN workout_plans wp ON mw.workout_plan_id = wp.id
        WHERE mw.member_id = ? AND mw.status = 'Active'
    ");
    $stmt->execute([$member_id]);
    $member_assignments = $stmt->fetchAll();
}
?>

<div class="content-header">
    <h1><i class="fas fa-running"></i> Workout Plans</h1>
    <div class="header-actions">
        <?php if (is_admin() || is_trainer()): ?>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Manage Plans
            </a>
        <?php endif; ?>
        <?php if (is_member() && !empty($member_assignments)): ?>
            <a href="#my-workouts" class="btn btn-primary">
                <i class="fas fa-dumbbell"></i> My Active Workouts
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (is_member() && !empty($member_assignments)): ?>
    <div class="content-card" id="my-workouts">
        <div class="card-header">
            <h2><i class="fas fa-dumbbell"></i> My Active Workouts</h2>
        </div>
        <div class="card-body">
            <div class="workout-grid">
                <?php foreach ($member_assignments as $assignment):
                    $days_left = $assignment['end_date'] ? ceil((strtotime($assignment['end_date']) - time()) / (60 * 60 * 24)) : 'N/A';
                    ?>
                    <div class="workout-card">
                        <div class="workout-card-header">
                            <h3><?php echo e($assignment['plan_name']); ?></h3>
                            <span class="status-badge success">Active</span>
                        </div>
                        <div class="workout-card-body">
                            <p><strong>Difficulty:</strong>
                                <span class="badge <?php echo strtolower($assignment['difficulty_level']); ?>">
                                    <?php echo e($assignment['difficulty_level']); ?>
                                </span>
                            </p>
                            <p><strong>Started:</strong> <?php echo e(format_date($assignment['start_date'])); ?></p>
                            <p><strong>Ends:</strong>
                                <?php echo e($assignment['end_date'] ? format_date($assignment['end_date']) : 'No end date'); ?>
                            </p>
                            <?php if ($days_left !== 'N/A'): ?>
                                <p><strong>Days Left:</strong>
                                    <span class="badge <?php echo $days_left <= 7 ? 'warning' : 'info'; ?>">
                                        <?php echo e($days_left); ?> days
                                    </span>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="workout-card-footer">
                            <a href="view.php?id=<?php echo e($assignment['workout_plan_id']); ?>"
                                class="btn btn-sm btn-block btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-filter"></i> Browse Workout Plans</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Search</label>
                    <input type="text" id="search" name="search" value="<?php echo e($search); ?>"
                        placeholder="Search workout plans...">
                </div>

                <div class="form-group">
                    <label for="difficulty"><i class="fas fa-chart-line"></i> Difficulty Level</label>
                    <select id="difficulty" name="difficulty">
                        <option value="">All Levels</option>
                        <option value="Beginner" <?php echo $difficulty_filter == 'Beginner' ? 'selected' : ''; ?>>
                            Beginner</option>
                        <option value="Intermediate" <?php echo $difficulty_filter == 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                        <option value="Advanced" <?php echo $difficulty_filter == 'Advanced' ? 'selected' : ''; ?>>
                            Advanced</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="plans.php" class="btn btn-outline">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-list"></i> Available Workout Plans (<?php echo e($total_plans); ?> total)</h2>
    </div>
    <div class="card-body">
        <?php if ($workout_plans): ?>
            <div class="workout-grid">
                <?php foreach ($workout_plans as $plan):
                    $difficulty_class = strtolower($plan['difficulty_level']);
                    $is_assigned = $plan['assignment_status'] === 'Active';
                    ?>
                    <div class="workout-card <?php echo $is_assigned ? 'assigned' : ''; ?>">
                        <div class="workout-card-header">
                            <h3><?php echo e($plan['plan_name']); ?></h3>
                            <?php if ($is_assigned): ?>
                                <span class="status-badge success">Assigned to You</span>
                            <?php else: ?>
                                <span class="status-badge <?php echo $difficulty_class; ?>">
                                    <?php echo e($plan['difficulty_level']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="workout-card-body">
                            <?php if ($plan['description']): ?>
                                <p class="workout-description"><?php echo e(substr($plan['description'], 0, 100)); ?>...</p>
                            <?php endif; ?>

                            <div class="workout-details">
                                <p><i class="fas fa-calendar-alt"></i>
                                    <strong>Duration:</strong> <?php echo e($plan['duration_weeks']); ?> weeks
                                </p>
                                <p><i class="fas fa-dumbbell"></i>
                                    <strong>Exercises:</strong> <?php echo e($plan['total_exercises']); ?>
                                </p>
                                <?php if ($plan['target_area']): ?>
                                    <p><i class="fas fa-crosshairs"></i>
                                        <strong>Target:</strong> <?php echo e($plan['target_area']); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($plan['created_by_name']): ?>
                                    <p><i class="fas fa-user-tie"></i>
                                        <strong>Trainer:</strong> <?php echo e($plan['created_by_name']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if ($is_assigned && $plan['assignment_start']): ?>
                                <div class="assignment-info">
                                    <hr>
                                    <p><i class="fas fa-clock"></i>
                                        <strong>Your Assignment:</strong> Started
                                        <?php echo e(format_date($plan['assignment_start'])); ?>
                                        <?php if ($plan['assignment_end']): ?>
                                            <br>Ends <?php echo e(format_date($plan['assignment_end'])); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="workout-card-footer">
                            <a href="view.php?id=<?php echo e($plan['id']); ?>" class="btn btn-sm btn-block btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <?php if (is_admin() || is_trainer()): ?>
                                <a href="manage.php?action=edit&id=<?php echo e($plan['id']); ?>"
                                    class="btn btn-sm btn-block btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
                            <?php if ($is_assigned && is_member()): ?>
                                <form method="POST" action="stop-plan.php" style="margin: 0;"
                                    onsubmit="return confirm('Are you sure you want to stop this workout plan?');">
                                    <input type="hidden" name="workout_plan_id" value="<?php echo e($plan['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-block btn-warning">
                                        <i class="fas fa-stop"></i> Stop This Plan
                                    </button>
                                </form>
                            <?php elseif (is_member()): ?>
                                <form method="POST" action="start-plan.php" style="margin: 0;">
                                    <input type="hidden" name="workout_plan_id" value="<?php echo e($plan['id']); ?>">
                                    <button type="submit" class="btn btn-sm btn-block btn-success">
                                        <i class="fas fa-play"></i> Start This Plan
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo e($search); ?>&difficulty=<?php echo e($difficulty_filter); ?>"
                            class="page-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <span class="page-info">
                        Page <?php echo e($page); ?> of <?php echo e($total_pages); ?>
                    </span>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo e($search); ?>&difficulty=<?php echo e($difficulty_filter); ?>"
                            class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-running fa-3x"></i>
                <h3>No Workout Plans Available</h3>
                <p><?php echo empty($search) && empty($difficulty_filter) ?
                    'No workout plans are currently available.' :
                    'No workout plans match your search criteria.'; ?></p>
                <?php if (is_admin() || is_trainer()): ?>
                    <a href="manage.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Create First Workout Plan
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .workout-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .workout-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #dee2e6;
    }

    .workout-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }

    .workout-card.assigned {
        border-left: 4px solid #27ae60;
    }

    .workout-card-header {
        padding: 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .workout-card-header h3 {
        margin: 0;
        font-size: 1.1rem;
        color: #2c3e50;
        flex: 1;
    }

    .workout-card-body {
        padding: 15px;
    }

    .workout-description {
        color: #666;
        margin-bottom: 15px;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .workout-details p {
        margin: 8px 0;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .workout-details i {
        color: #3498db;
        width: 16px;
    }

    .assignment-info {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px dashed #dee2e6;
    }

    .assignment-info p {
        font-size: 0.85rem;
        color: #666;
        margin: 0;
    }

    .workout-card-footer {
        padding: 15px;
        border-top: 1px solid #dee2e6;
        background: #f8f9fa;
    }

    .workout-card-footer .btn {
        margin-bottom: 8px;
    }

    .workout-card-footer .btn:last-child {
        margin-bottom: 0;
    }

    .badge.beginner {
        background-color: #d4edda;
        color: #155724;
    }

    .badge.intermediate {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .badge.advanced {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>

<?php include '../../includes/footer.php'; ?>