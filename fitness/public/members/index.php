<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can access

$page_title = 'Manage Members';
include '../../includes/header.php';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ? OR m.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_clauses[] = "m.status = ?";
    $params[] = $status_filter;
}

$where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM members m $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_members = $stmt->fetch()['total'];
$total_pages = ceil($total_members / $limit);

// Get members with pagination
$sql = "
    SELECT m.*, 
           u.username,
           (SELECT COUNT(*) FROM attendance WHERE member_id = m.id) as total_visits,
           (SELECT COUNT(*) FROM memberships WHERE member_id = m.id AND expiry_date >= CURDATE() AND payment_status = 'Paid') as active_memberships
    FROM members m
    LEFT JOIN users u ON m.user_id = u.id
    $where_sql
    ORDER BY m.created_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();
?>

<div class="content-header">
    <h1><i class="fas fa-users"></i> Manage Members</h1>
    <div class="header-actions">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add New Member
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-filter"></i> Filter Members</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="search"><i class="fas fa-search"></i> Search</label>
                    <input type="text" id="search" name="search" value="<?php echo e($search); ?>"
                        placeholder="Search by name, email, or phone...">
                </div>

                <div class="form-group">
                    <label for="status"><i class="fas fa-circle"></i> Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive
                        </option>
                        <option value="Suspended" <?php echo $status_filter == 'Suspended' ? 'selected' : ''; ?>>Suspended
                        </option>
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
        <h2><i class="fas fa-list"></i> Members List (<?php echo e($total_members); ?> total)</h2>
    </div>
    <div class="card-body">
        <?php if ($members): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Age/Gender</th>
                            <th>Join Date</th>
                            <th>Visits</th>
                            <th>Membership</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member):
                            $age = calculate_age($member['date_of_birth']);
                            ?>
                            <tr>
                                <td>#<?php echo e($member['id']); ?></td>
                                <td>
                                    <strong><?php echo e($member['first_name'] . ' ' . $member['last_name']); ?></strong><br>
                                    <small class="text-muted">@<?php echo e($member['username'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <div><?php echo e($member['email']); ?></div>
                                    <div><?php echo e($member['phone'] ?? 'No phone'); ?></div>
                                </td>
                                <td>
                                    <div><?php echo $age !== 'N/A' ? $age . ' yrs' : 'N/A'; ?></div>
                                    <div><?php echo e($member['gender'] ?? 'N/A'); ?></div>
                                </td>
                                <td><?php echo e(format_date($member['join_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $member['total_visits'] > 0 ? 'info' : 'secondary'; ?>">
                                        <?php echo e($member['total_visits']); ?> visits
                                    </span>
                                </td>
                                <td>
                                    <?php if ($member['active_memberships'] > 0): ?>
                                        <span class="badge success">Active</span>
                                    <?php else: ?>
                                        <span class="badge warning">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo get_status_badge($member['status']); ?>">
                                        <?php echo e($member['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-sm btn-info" title="Validate Membership"
                                            onclick="quickValidate(<?php echo $member['id']; ?>)">
                                            <i class="fas fa-id-card-alt"></i>
                                        </button>
                                        <a href="view.php?id=<?php echo e($member['id']); ?>" class="btn btn-sm btn-outline"
                                            title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo e($member['id']); ?>" class="btn btn-sm btn-warning"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (is_admin()): ?>
                                            <a href="delete.php?id=<?php echo e($member['id']); ?>" class="btn btn-sm btn-danger"
                                                title="Delete" onclick="return confirm('Delete this member?')">
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
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo e($search); ?>&status=<?php echo e($status_filter); ?>"
                            class="page-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <span class="page-info">
                        Page <?php echo e($page); ?> of <?php echo e($total_pages); ?>
                    </span>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo e($search); ?>&status=<?php echo e($status_filter); ?>"
                            class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-user-slash fa-3x"></i>
                <h3>No Members Found</h3>
                <p><?php echo empty($search) && empty($status_filter) ?
                    'No members in the system yet.' :
                    'No members match your search criteria.'; ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add First Member
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Validation Modal -->
<div id="validationResultModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-clipboard-check"></i> Membership Validation</h3>
            <span class="close-modal" onclick="closeValidationModal()">&times;</span>
        </div>
        <div class="modal-body" id="validationResultContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
    function quickValidate(memberId) {
        const modal = document.getElementById('validationResultModal');
        const content = document.getElementById('validationResultContent');

        content.innerHTML = '<div class="ajax-loading"></div> Validating...';
        modal.style.display = 'block';

        fetch(`../ajax/validate-membership.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `member_id=${memberId}&action=check`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <strong>Membership Valid</strong>
                </div>
                <div class="validation-details">
                    <p><strong>Member:</strong> ${data.member.name}</p>
                    <p><strong>Plan:</strong> ${data.membership.plan_name}</p>
                    <p><strong>Expiry:</strong> ${data.membership.expiry_date}</p>
                    <p><strong>Status:</strong> <span class="badge ${data.membership.status}">${data.membership.days_left} days left</span></p>
                </div>
            `;
                } else {
                    content.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <strong>Validation Failed</strong>
                    <p>${data.message}</p>
                </div>
            `;
                }
            })
            .catch(error => {
                content.innerHTML = '<div class="alert alert-error">An error occurred during validation.</div>';
                console.error('Validation error:', error);
            });
    }

    function closeValidationModal() {
        document.getElementById('validationResultModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function (event) {
        const modal = document.getElementById('validationResultModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

<style>
    .modal {
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background-color: #fefefe;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .close-modal {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close-modal:hover {
        color: black;
    }

    .validation-details p {
        margin: 8px 0;
        border-bottom: 1px solid #f9f9f9;
        padding-bottom: 4px;
    }
</style>

<?php include '../../includes/footer.php'; ?>