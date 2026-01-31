<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_login(); // All users can access, but logic changes based on role

$page_title = 'Check-in Member';
include '../../includes/header.php';

$errors = [];
$success = '';
$form_data = [
    'notes' => ''
];

$is_member_user = !is_staff();
$current_member_id = $is_member_user ? get_member_id() : null;

if ($is_member_user && !$current_member_id) {
    $_SESSION['error'] = "You must be an active member to check in.";
    redirect('../dashboard.php');
}

// Get members for dropdown
$members_stmt = $pdo->query("
    SELECT m.id, CONCAT(m.first_name, ' ', m.last_name) as name, m.email 
    FROM members m 
    WHERE m.status = 'Active' 
    ORDER BY m.first_name
");
$members = $members_stmt->fetchAll();

// Get trainers for dropdown
$trainers_stmt = $pdo->query("
    SELECT t.id, t.full_name as name, t.specialization 
    FROM trainers t 
    ORDER BY t.full_name
");
$trainers = $trainers_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $form_data['member_id'] = $is_member_user ? $current_member_id : (int)($_POST['member_id'] ?? 0);
    $form_data['workout_type'] = trim($_POST['workout_type'] ?? '');
    $form_data['trainer_id'] = !empty($_POST['trainer_id']) ? (int)$_POST['trainer_id'] : null;
    $form_data['notes'] = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($form_data['member_id'])) {
        $errors[] = 'Please select a member';
    }
    
    // Check if member exists and is active
    if (empty($errors) && $form_data['member_id']) {
        $stmt = $pdo->prepare("SELECT id, status FROM members WHERE id = ?");
        $stmt->execute([$form_data['member_id']]);
        $member = $stmt->fetch();
        
        if (!$member) {
            $errors[] = 'Selected member not found';
        } elseif ($member['status'] !== 'Active') {
            $errors[] = 'Member is not active. Current status: ' . $member['status'];
        }
    }
    
    // Check if member already checked in today
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT id FROM attendance 
            WHERE member_id = ? 
            AND DATE(check_in) = CURDATE()
            AND check_out IS NULL
        ");
        $stmt->execute([$form_data['member_id']]);
        
        if ($stmt->fetch()) {
            $errors[] = 'Member is already checked in today';
        }
    }
    
    // Check member's membership status
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT id FROM memberships 
            WHERE member_id = ? 
            AND expiry_date >= CURDATE() 
            AND payment_status = 'Paid'
            LIMIT 1
        ");
        $stmt->execute([$form_data['member_id']]);
        
        if (!$stmt->fetch()) {
            $errors[] = 'Member does not have an active paid membership';
        }
    }
    
    // If no errors, record check-in
    if (empty($errors)) {
        try {
            // Default workout type if empty
            if (empty($form_data['workout_type'])) {
                $form_data['workout_type'] = 'General';
            }
            
            // Set trainer to current trainer if not specified and user is trainer
            if (empty($form_data['trainer_id']) && is_trainer() && isset($_SESSION['trainer_id'])) {
                $form_data['trainer_id'] = $_SESSION['trainer_id'];
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO attendance (member_id, workout_type, trainer_id, notes, check_in) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $form_data['member_id'],
                $form_data['workout_type'],
                $form_data['trainer_id'],
                $form_data['notes']
            ]);
            
            $attendance_id = $pdo->lastInsertId();
            
            // Get member name for success message
            $stmt = $pdo->prepare("SELECT user_id, first_name, last_name FROM members WHERE id = ?");
            $stmt->execute([$form_data['member_id']]);
            $member = $stmt->fetch();
            
            $success = "Check-in recorded successfully for " . $member['first_name'] . " " . $member['last_name'] . " (Attendance ID: #$attendance_id)";
            
            // Send notification if checked in by staff (and member has a user account)
            if (is_staff() && $member['user_id']) {
                $checker_name = $_SESSION['user_name'];
                $checker_role = ucfirst($_SESSION['user_role']);
                $message = "You were checked in by $checker_role $checker_name at " . date('H:i');
                create_notification($pdo, $member['user_id'], $message);
            }
            
            // Clear form on success
            $form_data = [
                'member_id' => '',
                'workout_type' => '',
                'trainer_id' => '',
                'notes' => ''
            ];
            
        } catch (Exception $e) {
            $errors[] = 'Failed to record check-in: ' . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-check-circle"></i> Check-in Member</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> View Attendance
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-user-check"></i> Member Check-in Form</h2>
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
        
        <form method="POST" action="" id="checkinForm" class="form-grid">
            <div class="form-section">
                <h3><i class="fas fa-user"></i> Member Selection</h3>
                
                <div class="form-group">
                    <label for="member_id">Member *</label>
                    <?php if ($is_member_user): ?>
                        <input type="text" value="<?php echo e($_SESSION['user_name']); ?>" disabled class="disabled-input">
                        <input type="hidden" name="member_id" value="<?php echo e($current_member_id); ?>">
                    <?php else: ?>
                        <select id="member_id" name="member_id" required 
                                onchange="validateMember(this.value)">
                            <option value="">-- Select a Member --</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo e($member['id']); ?>" 
                                    <?php echo $form_data['member_id'] == $member['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($member['name']); ?> (<?php echo e($member['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <div id="memberValidation" class="validation-result" style="margin-top: 10px;"></div>
                </div>
                
                <?php if (!$is_member_user): ?>
                <div class="form-group">
                    <label>Quick Search</label>
                    <input type="text" id="memberSearch" placeholder="Search members by name..." 
                           class="live-search-input" style="width: 100%;">
                    <div id="searchResults" class="live-search-results"></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-running"></i> Session Details</h3>
                
                <div class="form-group">
                    <label for="workout_type">Workout Type</label>
                    <select id="workout_type" name="workout_type">
                        <option value="">-- Select Workout Type --</option>
                        <option value="Cardio" <?php echo $form_data['workout_type'] == 'Cardio' ? 'selected' : ''; ?>>Cardio</option>
                        <option value="Strength Training" <?php echo $form_data['workout_type'] == 'Strength Training' ? 'selected' : ''; ?>>Strength Training</option>
                        <option value="Weightlifting" <?php echo $form_data['workout_type'] == 'Weightlifting' ? 'selected' : ''; ?>>Weightlifting</option>
                        <option value="CrossFit" <?php echo $form_data['workout_type'] == 'CrossFit' ? 'selected' : ''; ?>>CrossFit</option>
                        <option value="Yoga" <?php echo $form_data['workout_type'] == 'Yoga' ? 'selected' : ''; ?>>Yoga</option>
                        <option value="Pilates" <?php echo $form_data['workout_type'] == 'Pilates' ? 'selected' : ''; ?>>Pilates</option>
                        <option value="HIIT" <?php echo $form_data['workout_type'] == 'HIIT' ? 'selected' : ''; ?>>HIIT</option>
                        <option value="Swimming" <?php echo $form_data['workout_type'] == 'Swimming' ? 'selected' : ''; ?>>Swimming</option>
                        <option value="General" <?php echo $form_data['workout_type'] == 'General' ? 'selected' : ''; ?>>General Workout</option>
                    </select>
                </div>
                
                <?php if (is_admin()): ?>
                <div class="form-group">
                    <label for="trainer_id">Assign Trainer (Optional)</label>
                    <select id="trainer_id" name="trainer_id">
                        <option value="">-- No Trainer --</option>
                        <?php foreach ($trainers as $trainer): ?>
                            <option value="<?php echo e($trainer['id']); ?>" 
                                <?php echo $form_data['trainer_id'] == $trainer['id'] ? 'selected' : ''; ?>>
                                <?php echo e($trainer['name']); ?> - <?php echo e($trainer['specialization']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="3" 
                              placeholder="Any special notes about this session..."><?php echo e($form_data['notes']); ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" <?php echo is_staff() ? 'disabled' : ''; ?>>
                    <i class="fas fa-check-circle"></i> Record Check-in
                </button>
                <button type="button" class="btn btn-secondary" onclick="validateBeforeSubmit()">
                    <i class="fas fa-search"></i> Validate Membership
                </button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Validation Result Display -->
<div id="validationResult" class="content-card" style="display: none;">
    <div class="card-header">
        <h2><i class="fas fa-clipboard-check"></i> Validation Result</h2>
    </div>
    <div class="card-body" id="validationResultContent">
        <!-- AJAX content will be loaded here -->
    </div>
</div>

<script>
let memberSearchTimeout;

// Live search for members
document.getElementById('memberSearch').addEventListener('input', function(e) {
    clearTimeout(memberSearchTimeout);
    const query = e.target.value;
    
    if (query.length < 2) {
        document.getElementById('searchResults').innerHTML = '';
        document.getElementById('searchResults').style.display = 'none';
        return;
    }
    
    memberSearchTimeout = setTimeout(() => {
        fetch(`../../ajax/search-members.php?q=${encodeURIComponent(query)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('searchResults');
            if (data.success && data.results.length > 0) {
                let html = '<ul>';
                data.results.forEach(member => {
                    html += `
                        <li onclick="selectMember(${member.id}, '${member.name.replace(/'/g, "\\'")}')"
                            style="cursor: pointer; padding: 10px; border-bottom: 1px solid #eee;">
                            <strong>${member.name}</strong><br>
                            <small>${member.email} • ${member.status}</small>
                        </li>
                    `;
                });
                html += '</ul>';
                resultsDiv.innerHTML = html;
                resultsDiv.style.display = 'block';
                resultsDiv.style.position = 'absolute';
                resultsDiv.style.background = 'white';
                resultsDiv.style.border = '1px solid #ddd';
                resultsDiv.style.width = '100%';
                resultsDiv.style.zIndex = '1000';
                resultsDiv.style.maxHeight = '200px';
                resultsDiv.style.overflowY = 'auto';
            } else {
                resultsDiv.innerHTML = '<div style="padding: 10px; color: #666;">No members found</div>';
                resultsDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        });
    }, 300);
});

// Select member from search results
function selectMember(id, name) {
    document.getElementById('member_id').value = id;
    document.getElementById('memberSearch').value = name;
    document.getElementById('searchResults').innerHTML = '';
    document.getElementById('searchResults').style.display = 'none';
    validateMember(id);
}

// Validate member before submission
function validateMember(memberId) {
    const validationDiv = document.getElementById('memberValidation');
    
    if (!memberId) {
        validationDiv.innerHTML = '';
        return;
    }
    
    validationDiv.innerHTML = '<div class="ajax-loading"></div> Validating...';
    
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
            validationDiv.innerHTML = `
                <div style="color: #27ae60; background: #d4edda; padding: 10px; border-radius: 4px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> 
                    <strong>Valid Member</strong><br>
                    ${data.member.name} • Membership: ${data.membership.plan_name}<br>
                    Expires: ${data.membership.expiry_date} (${data.membership.days_left} days left)
                </div>
            `;
            document.getElementById('submitBtn').disabled = false;
        } else {
            validationDiv.innerHTML = `
                <div style="color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> 
                    <strong>Validation Failed</strong><br>
                    ${data.message}
                </div>
            `;
            document.getElementById('submitBtn').disabled = true;
        }
    })
    .catch(error => {
        validationDiv.innerHTML = `
            <div style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; border: 1px solid #ffeaa7;">
                <i class="fas fa-exclamation-triangle"></i> 
                Validation error. Please try again.
            </div>
        `;
        console.error('Validation error:', error);
    });
}

// Validate before submitting
function validateBeforeSubmit() {
    const memberId = document.getElementById('member_id').value;
    if (!memberId) {
        alert('Please select a member first');
        return;
    }
    
    const resultCard = document.getElementById('validationResult');
    const resultContent = document.getElementById('validationResultContent');
    
    resultContent.innerHTML = '<div class="ajax-loading"></div> Validating membership...';
    resultCard.style.display = 'block';
    
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
            resultContent.innerHTML = `
                <div class="alert alert-success">
                    <h4><i class="fas fa-check-circle"></i> Member Validated Successfully!</h4>
                </div>
                <div class="validation-details">
                    <div class="detail-section">
                        <h5><i class="fas fa-user"></i> Member Details</h5>
                        <p><strong>Name:</strong> ${data.member.name}</p>
                        <p><strong>Status:</strong> <span class="status-badge success">${data.member.status}</span></p>
                    </div>
                    <div class="detail-section">
                        <h5><i class="fas fa-id-card"></i> Membership Details</h5>
                        <p><strong>Plan:</strong> ${data.membership.plan_name} (${data.membership.plan_type})</p>
                        <p><strong>Expiry:</strong> ${data.membership.expiry_date}</p>
                        <p><strong>Status:</strong> <span class="status-badge ${data.membership.status}">${data.membership.days_left} days left</span></p>
                        <p><strong>Payment:</strong> <span class="status-badge success">${data.membership.payment_status}</span></p>
                    </div>
                </div>
                <div class="text-center mt-20">
                    <button class="btn btn-primary" onclick="document.getElementById('submitBtn').click()">
                        <i class="fas fa-check-circle"></i> Proceed with Check-in
                    </button>
                </div>
            `;
        } else {
            resultContent.innerHTML = `
                <div class="alert alert-error">
                    <h4><i class="fas fa-exclamation-circle"></i> Validation Failed</h4>
                    <p>${data.message}</p>
                </div>
                ${data.member_name ? `
                <div class="validation-details">
                    <div class="detail-section">
                        <h5><i class="fas fa-user"></i> Member Details</h5>
                        <p><strong>Name:</strong> ${data.member_name}</p>
                        <p><strong>Status:</strong> <span class="status-badge error">${data.member_status}</span></p>
                    </div>
                </div>
                ` : ''}
            `;
        }
    })
    .catch(error => {
        resultContent.innerHTML = `
            <div class="alert alert-error">
                <h4><i class="fas fa-exclamation-circle"></i> Validation Error</h4>
                <p>Network error. Please try again.</p>
            </div>
        `;
        console.error('Validation error:', error);
    });
}

// Hide search results when clicking elsewhere
document.addEventListener('click', function(e) {
    if (!e.target.closest('#memberSearch') && !e.target.closest('#searchResults')) {
        document.getElementById('searchResults').style.display = 'none';
    }
});

// Initialize validation on page load
document.addEventListener('DOMContentLoaded', function() {
    const memberId = document.getElementById('member_id').value;
    if (memberId) {
        validateMember(memberId);
    }
});
</script>

<?php include '../../includes/footer.php'; ?>