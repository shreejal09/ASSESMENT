<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can view workouts

$page_title = 'Workout Plans';
include '../../includes/header.php';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$difficulty_filter = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_clauses = [];
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

// If trainer is viewing, show only their plans
if (is_trainer() && isset($_SESSION['user_id'])) {
    $where_clauses[] = "(wp.created_by = ? OR wp.created_by IS NULL)";
    $params[] = $_SESSION['user_id'];
}

$where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM workout_plans wp $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_plans = $stmt->fetch()['total'];
$total_pages = ceil($total_plans / $limit);

// Get workout plans with pagination
$sql = "
    SELECT wp.*, 
           u.username as created_by_name,
           (SELECT COUNT(*) FROM workout_exercises WHERE workout_plan_id = wp.id) as total_exercises,
           (SELECT COUNT(*) FROM member_workouts WHERE workout_plan_id = wp.id AND status = 'Active') as active_assignments
    FROM workout_plans wp
    LEFT JOIN users u ON wp.created_by = u.id
    $where_sql
    ORDER BY wp.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$workout_plans = $stmt->fetchAll();

// Get difficulty stats
$difficulty_stats = $pdo->query("
    SELECT difficulty_level, COUNT(*) as count 
    FROM workout_plans 
    GROUP BY difficulty_level 
    ORDER BY FIELD(difficulty_level, 'Beginner', 'Intermediate', 'Advanced')
")->fetchAll();

// Get total active assignments
$total_assignments = $pdo->query("
    SELECT COUNT(*) as total FROM member_workouts WHERE status = 'Active'
")->fetch()['total'];
?>

<div class="content-header">
    <h1><i class="fas fa-running"></i> Manage Workout Plans</h1>
    <div class="header-actions">
        <a href="manage.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Create New Plan
        </a>
        <a href="plans.php" class="btn btn-info">
            <i class="fas fa-eye"></i> View Member Plans
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-dumbbell"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo e($total_plans); ?></h3>
            <p>Total Workout Plans</p>
        </div>
    </div>

    <?php foreach ($difficulty_stats as $stat):
        $difficulty_class = strtolower($stat['difficulty_level']);
        ?>
        <div class="stat-card">
            <div class="stat-icon <?php echo $difficulty_class; ?>">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo e($stat['count']); ?></h3>
                <p><?php echo e($stat['difficulty_level']); ?> Plans</p>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo e($total_assignments); ?></h3>
            <p>Active Assignments</p>
        </div>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-filter"></i> Filter Workout Plans</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Search</label>
                    <input type="text" id="search" name="search" value="<?php echo e($search); ?>"
                        placeholder="Search by plan name, description, or target area...">
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
                    <a href="index.php" class="btn btn-outline">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-list"></i> Workout Plans List (<?php echo e($total_plans); ?> total)</h2>
    </div>
    <div class="card-body">
        <?php if ($workout_plans): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Plan Name</th>
                            <th>Difficulty</th>
                            <th>Duration</th>
                            <th>Target Area</th>
                            <th>Exercises</th>
                            <th>Assignments</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workout_plans as $plan):
                            $difficulty_class = strtolower($plan['difficulty_level']);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($plan['plan_name']); ?></strong>
                                    <?php if ($plan['description']): ?>
                                        <br><small
                                            class="text-muted"><?php echo e(substr($plan['description'], 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $difficulty_class; ?>">
                                        <?php echo e($plan['difficulty_level']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge info">
                                        <?php echo e($plan['duration_weeks']); ?> weeks
                                    </span>
                                </td>
                                <td><?php echo e($plan['target_area'] ?: 'Full Body'); ?></td>
                                <td>
                                    <span class="badge <?php echo $plan['total_exercises'] > 0 ? 'success' : 'warning'; ?>">
                                        <?php echo e($plan['total_exercises']); ?> exercises
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $plan['active_assignments'] > 0 ? 'info' : 'secondary'; ?>">
                                        <?php echo e($plan['active_assignments']); ?> active
                                    </span>
                                </td>
                                <td>
                                    <?php echo e($plan['created_by_name'] ?: 'System'); ?>
                                    <?php if ($plan['created_at']): ?>
                                        <br><small class="text-muted"><?php echo e(format_date($plan['created_at'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($plan['is_active']): ?>
                                        <span class="status-badge success">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge error">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view.php?id=<?php echo e($plan['id']); ?>" class="btn btn-sm btn-info"
                                            title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (is_admin() || ($plan['created_by'] == $_SESSION['user_id'])): ?>
                                            <a href="manage.php?action=edit&id=<?php echo e($plan['id']); ?>"
                                                class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage.php?action=copy&id=<?php echo e($plan['id']); ?>"
                                                class="btn btn-sm btn-secondary" title="Copy Plan">
                                                <i class="fas fa-copy"></i>
                                            </a>
                                            <a href="manage.php?action=delete&id=<?php echo e($plan['id']); ?>"
                                                class="btn btn-sm btn-danger" title="Delete"
                                                onclick="return confirm('Delete this workout plan?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                <h3>No Workout Plans Found</h3>
                <p><?php echo empty($search) && empty($difficulty_filter) ?
                    'No workout plans created yet.' :
                    'No workout plans match your search criteria.'; ?></p>
                <a href="manage.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Create First Workout Plan
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .status-badge.beginner {
        background-color: #d4edda;
        color: #155724;
    }

    .status-badge.intermediate {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .status-badge.advanced {
        background-color: #f8d7da;
        color: #721c24;
    }

    .stat-icon.beginner {
        background-color: #27ae60;
    }

    .stat-icon.intermediate {
        background-color: #3498db;
    }

    .stat-icon.advanced {
        background-color: #e74c3c;
    }
</style>

<?php include '../../includes/footer.php'; ?>