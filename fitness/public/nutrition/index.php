<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login(); // All logged in users can access

$page_title = 'Nutrition Tracking';
include '../../includes/header.php';

// Check if member is viewing their own nutrition
$member_id = is_member() ? get_member_id() : null;

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$member_filter = isset($_GET['member_id']) ? (int)$_GET['member_id'] : '';
$meal_filter = isset($_GET['meal_type']) ? $_GET['meal_type'] : '';

// Build query
$where_clauses = [];
$params = [];

// If admin/trainer can view all, members can only view their own
if (is_member() && $member_id) {
    $where_clauses[] = "nl.member_id = ?";
    $params[] = $member_id;
} elseif (!empty($member_filter) && (is_admin() || is_trainer())) {
    $where_clauses[] = "nl.member_id = ?";
    $params[] = $member_filter;
}

if (!empty($date_filter)) {
    $where_clauses[] = "nl.log_date = ?";
    $params[] = $date_filter;
}

if (!empty($meal_filter)) {
    $where_clauses[] = "nl.meal_type = ?";
    $params[] = $meal_filter;
}

$where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM nutrition_logs nl $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_logs = $stmt->fetch()['total'];
$total_pages = ceil($total_logs / $limit);

// Get nutrition logs with pagination
$sql = "
    SELECT nl.*, 
           m.first_name, m.last_name, m.email
    FROM nutrition_logs nl
    LEFT JOIN members m ON nl.member_id = m.id
    $where_sql
    ORDER BY nl.log_date DESC, nl.log_time DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$nutrition_logs = $stmt->fetchAll();

// Get members for filter (admin/trainer only)
$members = [];
if (is_admin() || is_trainer()) {
    $stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM members WHERE status = 'Active' ORDER BY first_name");
    $members = $stmt->fetchAll();
}

// Calculate totals for today
$today_totals = [];
if ($member_id) {
    $stmt = $pdo->prepare("
        SELECT 
            SUM(calories) as total_calories,
            SUM(protein_g) as total_protein,
            SUM(carbs_g) as total_carbs,
            SUM(fat_g) as total_fat
        FROM nutrition_logs 
        WHERE member_id = ? AND log_date = CURDATE()
    ");
    $stmt->execute([$member_id]);
    $today_totals = $stmt->fetch();
}
?>

<div class="content-header">
    <h1><i class="fas fa-apple-alt"></i> Nutrition Tracking</h1>
    <div class="header-actions">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Log Meal
        </a>
        <?php if (is_admin() || is_trainer()): ?>
        <a href="report.php" class="btn btn-info">
            <i class="fas fa-chart-bar"></i> Nutrition Report
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (is_member() && $today_totals): ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-fire"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo e($today_totals['total_calories'] ?? 0); ?></h3>
            <p>Calories Today</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-drumstick-bite"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo e($today_totals['total_protein'] ?? 0); ?>g</h3>
            <p>Protein Today</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-bread-slice"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo e($today_totals['total_carbs'] ?? 0); ?>g</h3>
            <p>Carbs Today</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-oil-can"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo e($today_totals['total_fat'] ?? 0); ?>g</h3>
            <p>Fat Today</p>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-filter"></i> Filter Nutrition Logs</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" id="date" name="date" 
                           value="<?php echo e($date_filter); ?>">
                </div>
                
                <?php if (is_admin() || is_trainer()): ?>
                <div class="form-group">
                    <label for="member_id"><i class="fas fa-user"></i> Member</label>
                    <select id="member_id" name="member_id">
                        <option value="">All Members</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo e($member['id']); ?>" 
                                <?php echo $member_filter == $member['id'] ? 'selected' : ''; ?>>
                                <?php echo e($member['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="meal_type"><i class="fas fa-utensils"></i> Meal Type</label>
                    <select id="meal_type" name="meal_type">
                        <option value="">All Meals</option>
                        <option value="Breakfast" <?php echo $meal_filter == 'Breakfast' ? 'selected' : ''; ?>>Breakfast</option>
                        <option value="Lunch" <?php echo $meal_filter == 'Lunch' ? 'selected' : ''; ?>>Lunch</option>
                        <option value="Dinner" <?php echo $meal_filter == 'Dinner' ? 'selected' : ''; ?>>Dinner</option>
                        <option value="Snack" <?php echo $meal_filter == 'Snack' ? 'selected' : ''; ?>>Snack</option>
                        <option value="Pre-workout" <?php echo $meal_filter == 'Pre-workout' ? 'selected' : ''; ?>>Pre-workout</option>
                        <option value="Post-workout" <?php echo $meal_filter == 'Post-workout' ? 'selected' : ''; ?>>Post-workout</option>
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
        <h2><i class="fas fa-list"></i> Nutrition Logs (<?php echo e($total_logs); ?> total)</h2>
    </div>
    <div class="card-body">
        <?php if ($nutrition_logs): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php if (is_admin() || is_trainer()): ?>
                            <th>Member</th>
                            <?php endif; ?>
                            <th>Date & Time</th>
                            <th>Meal Type</th>
                            <th>Food Item</th>
                            <th>Nutrition Info</th>
                            <th>Calories</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nutrition_logs as $log): ?>
                        <tr>
                            <?php if (is_admin() || is_trainer()): ?>
                            <td>
                                <?php echo e($log['first_name'] . ' ' . $log['last_name']); ?>
                                <br><small><?php echo e($log['email']); ?></small>
                            </td>
                            <?php endif; ?>
                            <td>
                                <div><?php echo e(format_date($log['log_date'])); ?></div>
                                <small><?php echo e(date('H:i', strtotime($log['log_time']))); ?></small>
                            </td>
                            <td>
                                <span class="badge 
                                    <?php echo $log['meal_type'] == 'Breakfast' ? 'warning' : 
                                           ($log['meal_type'] == 'Lunch' ? 'info' : 
                                           ($log['meal_type'] == 'Dinner' ? 'primary' : 'secondary')); ?>">
                                    <?php echo e($log['meal_type']); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo e($log['food_name']); ?></strong>
                                <?php if ($log['serving_size']): ?>
                                <br><small><?php echo e($log['serving_size']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['protein_g']): ?><div>Protein: <?php echo e($log['protein_g']); ?>g</div><?php endif; ?>
                                <?php if ($log['carbs_g']): ?><div>Carbs: <?php echo e($log['carbs_g']); ?>g</div><?php endif; ?>
                                <?php if ($log['fat_g']): ?><div>Fat: <?php echo e($log['fat_g']); ?>g</div><?php endif; ?>
                            </td>
                            <td>
                                <span class="badge 
                                    <?php echo $log['calories'] < 200 ? 'success' : 
                                           ($log['calories'] < 500 ? 'warning' : 'danger'); ?>">
                                    <?php echo e($log['calories']); ?> cal
                                </span>
                            </td>
                            <td>
                                <?php if ($log['notes']): ?>
                                    <small><?php echo e(substr($log['notes'], 0, 50)); ?>...</small>
                                <?php else: ?>
                                    <span class="text-muted">No notes</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit.php?id=<?php echo e($log['id']); ?>" class="btn btn-sm btn-warning"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo e($log['id']); ?>" class="btn btn-sm btn-danger"
                                       title="Delete" onclick="return confirm('Delete this nutrition log?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
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
                    <a href="?page=<?php echo $page-1; ?>&date=<?php echo e($date_filter); ?>&member_id=<?php echo e($member_filter); ?>&meal_type=<?php echo e($meal_filter); ?>" 
                       class="page-link">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <span class="page-info">
                    Page <?php echo e($page); ?> of <?php echo e($total_pages); ?>
                </span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&date=<?php echo e($date_filter); ?>&member_id=<?php echo e($member_filter); ?>&meal_type=<?php echo e($meal_filter); ?>" 
                       class="page-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-apple-alt fa-3x"></i>
                <h3>No Nutrition Logs Found</h3>
                <p>No nutrition logs match your filter criteria.</p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Log Your First Meal
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>