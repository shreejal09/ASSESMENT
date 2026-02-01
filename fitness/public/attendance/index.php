<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login(); // Members can view their own attendance

$page_title = 'Attendance Records';
include '../../includes/header.php';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$member_filter = isset($_GET['member_id']) ? (int) $_GET['member_id'] : '';
$trainer_filter = isset($_GET['trainer_id']) ? (int) $_GET['trainer_id'] : '';

// Build query
$where_clauses = [];
$params = [];

if (!empty($date_filter)) {
    $where_clauses[] = "DATE(a.check_in) = ?";
    $params[] = $date_filter;
}

if (!empty($member_filter)) {
    $where_clauses[] = "a.member_id = ?";
    $params[] = $member_filter;
}

if (!empty($trainer_filter)) {
    $where_clauses[] = "a.trainer_id = ?";
    $params[] = $trainer_filter;
}

// If trainer is viewing, only show their sessions
if (is_trainer() && isset($_SESSION['trainer_id'])) {
    $where_clauses[] = "a.trainer_id = ?";
    $params[] = $_SESSION['trainer_id'];
}

// If member is viewing, only show their own attendance
if (!is_staff()) {
    $member_id = get_member_id();
    if ($member_id) {
        $where_clauses[] = "a.member_id = ?";
        $params[] = $member_id;
    }
}

$where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM attendance a $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get attendance records with pagination
$sql = "
    SELECT a.*, 
           m.first_name as member_first, m.last_name as member_last, m.email as member_email,
           t.full_name as trainer_name,
           TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) as calculated_duration
    FROM attendance a
    LEFT JOIN members m ON a.member_id = m.id
    LEFT JOIN trainers t ON a.trainer_id = t.id
    $where_sql
    ORDER BY a.check_in DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendance = $stmt->fetchAll();

// Get members for filter dropdown
$members_stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM members WHERE status = 'Active' ORDER BY first_name");
$members = $members_stmt->fetchAll();

// Get trainers for filter dropdown
$trainers_stmt = $pdo->query("SELECT id, full_name as name FROM trainers ORDER BY full_name");
$trainers = $trainers_stmt->fetchAll();
?>

<div class="content-header">
    <h1><i class="fas fa-clipboard-check"></i> Attendance Records</h1>
    <div class="header-actions">
        <a href="checkin.php" class="btn btn-primary">
            <i class="fas fa-check-circle"></i> Manual Check-in
        </a>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-filter"></i> Filter Attendance</h2>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" id="date" name="date" value="<?php echo e($date_filter); ?>"
                        max="<?php echo date('Y-m-d'); ?>">
                </div>

                <?php if (is_staff()): ?>
                    <div class="form-group">
                        <label for="member_id"><i class="fas fa-user"></i> Member</label>
                        <select id="member_id" name="member_id">
                            <option value="">All Members</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo e($member['id']); ?>" <?php echo $member_filter == $member['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($member['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (is_admin()): ?>
                    <div class="form-group">
                        <label for="trainer_id"><i class="fas fa-user-tie"></i> Trainer</label>
                        <select id="trainer_id" name="trainer_id">
                            <option value="">All Trainers</option>
                            <?php foreach ($trainers as $trainer): ?>
                                <option value="<?php echo e($trainer['id']); ?>" <?php echo $trainer_filter == $trainer['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($trainer['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

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
        <h2><i class="fas fa-list"></i> Attendance List (<?php echo e($total_records); ?> records)</h2>
        <div class="stats">
            <span class="badge info">Today: <?php echo e(date('M j, Y', strtotime($date_filter))); ?></span>
        </div>
    </div>
    <div class="card-body">
        <?php if ($attendance): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Member</th>
                            <th>Workout Type</th>
                            <th>Trainer</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $record):
                            $duration = $record['duration_minutes'] ?: $record['calculated_duration'];
                            $check_out_time = $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : 'N/A';
                            ?>
                            <tr>
                                <td>
                                    <div><strong>In:</strong> <?php echo e(date('H:i', strtotime($record['check_in']))); ?>
                                    </div>
                                    <div><strong>Out:</strong> <?php echo e($check_out_time); ?></div>
                                </td>
                                <td>
                                    <strong><?php echo e($record['member_first'] . ' ' . $record['member_last']); ?></strong><br>
                                    <small><?php echo e($record['member_email']); ?></small>
                                </td>
                                <td><?php echo e($record['workout_type'] ?? 'General'); ?></td>
                                <td>
                                    <?php if ($record['trainer_name']): ?>
                                        <span class="badge info"><?php echo e($record['trainer_name']); ?></span>
                                    <?php else: ?>
                                        <span class="badge secondary">No trainer</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($duration): ?>
                                        <span class="badge <?php echo $duration >= 60 ? 'success' : 'warning'; ?>">
                                            <?php echo e($duration); ?> min
                                        </span>
                                    <?php else: ?>
                                        <span class="badge error">Not checked out</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['check_out']): ?>
                                        <span class="status-badge success">Completed</span>
                                    <?php else: ?>
                                        <span class="status-badge warning">In Progress</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" onclick="viewAttendanceDetails(<?php echo e($record['id']); ?>)"
                                            class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (!$record['check_out']): ?>
                                            <a href="checkout.php?id=<?php echo e($record['id']); ?>" class="btn btn-sm btn-success"
                                                title="Check Out">
                                                <i class="fas fa-sign-out-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="delete.php?id=<?php echo e($record['id']); ?>" class="btn btn-sm btn-danger"
                                            title="Delete" onclick="return confirm('Delete this record?')">
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
                        <a href="?page=<?php echo $page - 1; ?>&date=<?php echo e($date_filter); ?>&member_id=<?php echo e($member_filter); ?>&trainer_id=<?php echo e($trainer_filter); ?>"
                            class="page-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <span class="page-info">
                        Page <?php echo e($page); ?> of <?php echo e($total_pages); ?>
                    </span>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&date=<?php echo e($date_filter); ?>&member_id=<?php echo e($member_filter); ?>&trainer_id=<?php echo e($trainer_filter); ?>"
                            class="page-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-clipboard-list fa-3x"></i>
                <h3>No Attendance Records Found</h3>
                <p>No attendance records match your filter criteria.</p>
                <a href="checkin.php" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Record First Check-in
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Attendance Details Modal -->
<div id="attendanceModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Attendance Details</h3>
            <button onclick="closeModal()" class="close">&times;</button>
        </div>
        <div class="modal-body" id="attendanceDetails">
            Loading...
        </div>
    </div>
</div>

<style>
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-body {
        padding: 20px;
    }

    .close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
    }

    .close:hover {
        color: #333;
    }
</style>

<script>
    function viewAttendanceDetails(id) {
        fetch(`get_attendance.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = document.getElementById('attendanceModal');
                    const details = document.getElementById('attendanceDetails');

                    details.innerHTML = `
                    <div class="attendance-detail-section">
                        <h4><i class="fas fa-user"></i> Member Information</h4>
                        <p><strong>Name:</strong> ${data.member.name}</p>
                        <p><strong>Email:</strong> ${data.member.email}</p>
                    </div>
                    
                    <div class="attendance-detail-section">
                        <h4><i class="fas fa-clock"></i> Session Information</h4>
                        <p><strong>Check-in:</strong> ${data.check_in_formatted}</p>
                        <p><strong>Check-out:</strong> ${data.check_out_formatted}</p>
                        <p><strong>Duration:</strong> <span class="badge ${data.duration >= 60 ? 'success' : 'warning'}">${data.duration} minutes</span></p>
                    </div>
                    
                    ${data.trainer ? `
                    <div class="attendance-detail-section">
                        <h4><i class="fas fa-user-tie"></i> Trainer</h4>
                        <p><strong>Name:</strong> ${data.trainer.name}</p>
                        <p><strong>Specialization:</strong> ${data.trainer.specialization}</p>
                    </div>
                    ` : ''}
                    
                    <div class="attendance-detail-section">
                        <h4><i class="fas fa-running"></i> Workout Details</h4>
                        <p><strong>Type:</strong> ${data.workout_type || 'General'}</p>
                        <p><strong>Notes:</strong> ${data.notes || 'No notes'}</p>
                    </div>
                `;

                    modal.style.display = 'flex';
                } else {
                    alert('Failed to load attendance details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading details');
            });
    }

    function closeModal() {
        document.getElementById('attendanceModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function (event) {
        const modal = document.getElementById('attendanceModal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>