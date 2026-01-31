<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_admin(); // Only admin can manage trainers

$page_title = 'Manage Trainers';
include '../../includes/header.php';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(t.full_name LIKE ? OR t.email LIKE ? OR t.specialization LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM trainers t $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_trainers = $stmt->fetch()['total'];
$total_pages = ceil($total_trainers / $limit);

// Get trainers with pagination
$sql = "
    SELECT t.*, u.username,
           (SELECT COUNT(*) FROM attendance WHERE trainer_id = t.id AND DATE(check_in) = CURDATE()) as sessions_today,
           (SELECT COUNT(DISTINCT member_id) FROM member_workouts WHERE assigned_by = u.id) as assigned_clients
    FROM trainers t
    LEFT JOIN users u ON t.user_id = u.id
    $where_sql
    ORDER BY t.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trainers = $stmt->fetchAll();
?>

<div class="content-header">
    <h1><i class="fas fa-user-tie"></i> Manage Trainers</h1>
    <div class="header-actions">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add New Trainer
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-filter"></i> Filter Trainers</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Search</label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo e($search); ?>" 
                           placeholder="Search by name, email, or specialization...">
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
        <h2><i class="fas fa-list"></i> Trainers List (<?php echo e($total_trainers); ?> total)</h2>
    </div>
    <div class="card-body">
        <?php if ($trainers): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Trainer</th>
                            <th>Contact</th>
                            <th>Specialization</th>
                            <th>Experience</th>
                            <th>Rate</th>
                            <th>Today's Sessions</th>
                            <th>Clients</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trainers as $trainer): ?>
                        <tr>
                            <td>#<?php echo e($trainer['id']); ?></td>
                            <td>
                                <strong><?php echo e($trainer['full_name']); ?></strong><br>
                                <small class="text-muted">@<?php echo e($trainer['username'] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <div><?php echo e($trainer['email']); ?></div>
                                <div><?php echo e($trainer['phone'] ?? 'No phone'); ?></div>
                            </td>
                            <td><?php echo e($trainer['specialization'] ?? 'General'); ?></td>
                            <td>
                                <span class="badge info">
                                    <?php echo e($trainer['experience_years']); ?> years
                                </span>
                            </td>
                            <td>
                                $<?php echo number_format($trainer['hourly_rate'], 2); ?>/hr
                            </td>
                            <td>
                                <span class="badge <?php echo $trainer['sessions_today'] > 0 ? 'success' : 'secondary'; ?>">
                                    <?php echo e($trainer['sessions_today']); ?> sessions
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $trainer['assigned_clients'] > 0 ? 'info' : 'secondary'; ?>">
                                    <?php echo e($trainer['assigned_clients']); ?> clients
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view.php?id=<?php echo e($trainer['id']); ?>" class="btn btn-sm btn-info" 
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo e($trainer['id']); ?>" class="btn btn-sm btn-warning"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo e($trainer['id']); ?>" class="btn btn-sm btn-danger"
                                       title="Delete" onclick="return confirm('Delete this trainer?')">
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
                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo e($search); ?>" 
                       class="page-link">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <span class="page-info">
                    Page <?php echo e($page); ?> of <?php echo e($total_pages); ?>
                </span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo e($search); ?>" 
                       class="page-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-user-tie fa-3x"></i>
                <h3>No Trainers Found</h3>
                <p><?php echo empty($search) ? 
                    'No trainers in the system yet.' : 
                    'No trainers match your search criteria.'; ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add First Trainer
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>