<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_admin(); // Only admin can delete members

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid member ID';
    redirect('index.php');
}

$member_id = (int)$_GET['id'];

// Check if member exists
$stmt = $pdo->prepare("SELECT id, first_name, last_name FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    $_SESSION['error'] = 'Member not found';
    redirect('index.php');
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Invalid security token';
        redirect('index.php');
    }
    
    try {
        // Get user_id before deletion for audit
        $stmt = $pdo->prepare("SELECT user_id FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $user_data = $stmt->fetch();
        
        // Delete member (cascade will delete user account)
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        
        // If user_id exists and wasn't cascade deleted, delete user
        if ($user_data && $user_data['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_data['user_id']]);
        }
        
        $_SESSION['success'] = "Member '{$member['first_name']} {$member['last_name']}' has been deleted successfully.";
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to delete member: ' . $e->getMessage();
    }
    
    redirect('index.php');
}

$page_title = 'Delete Member';
include '../../includes/header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-user-slash"></i> Delete Member</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Members
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header warning">
        <h2><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h2>
    </div>
    <div class="card-body">
        <div class="warning-message">
            <h3>Warning: This action cannot be undone!</h3>
            <p>You are about to delete the following member:</p>
            
            <div class="member-details">
                <h4><?php echo e($member['first_name'] . ' ' . $member['last_name']); ?></h4>
                <p>Member ID: #<?php echo e($member['id']); ?></p>
            </div>
            
            <div class="deletion-consequences">
                <h4><i class="fas fa-exclamation-circle"></i> This will also delete:</h4>
                <ul>
                    <li>User account and login credentials</li>
                    <li>All membership records</li>
                    <li>All attendance records</li>
                    <li>All workout assignments</li>
                    <li>All nutrition logs</li>
                    <li>All progress tracking data</li>
                    <li>All payment records</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                
                <div class="form-group">
                    <label for="confirm_name">
                        Type the member's full name to confirm:
                        <strong><?php echo e($member['first_name'] . ' ' . $member['last_name']); ?></strong>
                    </label>
                    <input type="text" id="confirm_name" name="confirm_name" 
                           placeholder="Type the full name exactly as shown above"
                           required>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <input type="checkbox" id="confirm_delete" name="confirm_delete" required>
                        <label for="confirm_delete">
                            I understand this action is permanent and cannot be undone
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger btn-lg" 
                            id="deleteBtn" disabled>
                        <i class="fas fa-trash"></i> Permanently Delete Member
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmNameInput = document.getElementById('confirm_name');
    const confirmCheckbox = document.getElementById('confirm_delete');
    const deleteBtn = document.getElementById('deleteBtn');
    const fullName = "<?php echo e($member['first_name'] . ' ' . $member['last_name']); ?>";
    
    function validateForm() {
        const nameMatches = confirmNameInput.value.trim() === fullName;
        const checkboxChecked = confirmCheckbox.checked;
        deleteBtn.disabled = !(nameMatches && checkboxChecked);
    }
    
    confirmNameInput.addEventListener('input', validateForm);
    confirmCheckbox.addEventListener('change', validateForm);
    
    // Initial validation
    validateForm();
});
</script>

<?php include '../../includes/footer.php'; ?>