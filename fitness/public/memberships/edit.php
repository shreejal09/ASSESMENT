<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can edit memberships

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = "Invalid membership ID";
    redirect('index.php');
}

// Get membership data
$stmt = $pdo->prepare("SELECT * FROM memberships WHERE id = ?");
$stmt->execute([$id]);
$membership = $stmt->fetch();

if (!$membership) {
    $_SESSION['error'] = "Membership not found";
    redirect('index.php');
}

$page_title = 'Edit Membership';
include '../../includes/header.php';

$errors = [];
$success = '';
$form_data = [
    'member_id' => $membership['member_id'],
    'plan_name' => $membership['plan_name'],
    'plan_type' => $membership['plan_type'],
    'price' => $membership['price'],
    'start_date' => $membership['start_date'],
    'expiry_date' => $membership['expiry_date'],
    'payment_status' => $membership['payment_status'],
    'payment_method' => $membership['payment_method'],
    'auto_renew' => $membership['auto_renew']
];

// Get members for reference (though usually we don't change the member of a membership record)
$members_stmt = $pdo->query("SELECT id, first_name, last_name, email FROM members ORDER BY first_name");
$members = $members_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = [
        'member_id' => (int) ($_POST['member_id'] ?? $membership['member_id']),
        'plan_name' => trim($_POST['plan_name'] ?? ''),
        'plan_type' => $_POST['plan_type'] ?? 'Monthly',
        'price' => trim($_POST['price'] ?? ''),
        'start_date' => $_POST['start_date'] ?? '',
        'expiry_date' => $_POST['expiry_date'] ?? '',
        'payment_status' => $_POST['payment_status'] ?? 'Pending',
        'payment_method' => trim($_POST['payment_method'] ?? ''),
        'auto_renew' => isset($_POST['auto_renew']) ? 1 : 0
    ];

    // Validation
    if (empty($form_data['plan_name'])) {
        $errors[] = 'Plan name is required';
    }

    if (empty($form_data['price'])) {
        $errors[] = 'Price is required';
    }

    if (empty($form_data['expiry_date'])) {
        $errors[] = 'Expiry date is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE memberships SET 
                    plan_name = ?, plan_type = ?, price = ?, 
                    start_date = ?, expiry_date = ?, 
                    payment_status = ?, payment_method = ?, auto_renew = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $form_data['plan_name'],
                $form_data['plan_type'],
                $form_data['price'],
                $form_data['start_date'],
                $form_data['expiry_date'],
                $form_data['payment_status'],
                $form_data['payment_method'],
                $form_data['auto_renew'],
                $id
            ]);

            $_SESSION['success'] = "Membership updated successfully!";
            redirect('index.php');

        } catch (Exception $e) {
            $errors[] = 'Failed to update membership: ' . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-edit"></i> Edit Membership</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Memberships
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-id-card"></i> Update Membership Details</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li>
                            <?php echo e($error); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form-grid">
            <div class="form-section">
                <h3><i class="fas fa-user"></i> Member Information</h3>
                <div class="form-group">
                    <label>Member</label>
                    <p><strong>
                            <?php
                            foreach ($members as $m) {
                                if ($m['id'] == $form_data['member_id']) {
                                    echo e($m['first_name'] . ' ' . $m['last_name'] . ' (' . $m['email'] . ')');
                                    break;
                                }
                            }
                            ?>
                        </strong></p>
                    <input type="hidden" name="member_id" value="<?php echo e($form_data['member_id']); ?>">
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-tag"></i> Plan Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="plan_name">Plan Name *</label>
                        <input type="text" id="plan_name" name="plan_name"
                            value="<?php echo e($form_data['plan_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="plan_type">Plan Type</label>
                        <select id="plan_type" name="plan_type">
                            <option value="Monthly" <?php echo $form_data['plan_type'] == 'Monthly' ? 'selected' : ''; ?>>
                                Monthly</option>
                            <option value="Quarterly" <?php echo $form_data['plan_type'] == 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                            <option value="Annual" <?php echo $form_data['plan_type'] == 'Annual' ? 'selected' : ''; ?>>
                                Annual</option>
                            <option value="Pay-as-you-go" <?php echo $form_data['plan_type'] == 'Pay-as-you-go' ? 'selected' : ''; ?>>Pay-as-you-go</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <input type="number" id="price" name="price" step="0.01"
                        value="<?php echo e($form_data['price']); ?>" required>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-calendar-alt"></i> Dates & Payment</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" id="start_date" name="start_date"
                            value="<?php echo e($form_data['start_date']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date *</label>
                        <input type="date" id="expiry_date" name="expiry_date"
                            value="<?php echo e($form_data['expiry_date']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="payment_status">Payment Status</label>
                        <select id="payment_status" name="payment_status">
                            <option value="Paid" <?php echo $form_data['payment_status'] == 'Paid' ? 'selected' : ''; ?>>
                                Paid</option>
                            <option value="Pending" <?php echo $form_data['payment_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Overdue" <?php echo $form_data['payment_status'] == 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <input type="text" id="payment_method" name="payment_method"
                            value="<?php echo e($form_data['payment_method']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="auto_renew" value="1" <?php echo $form_data['auto_renew'] ? 'checked' : ''; ?>> Auto-renew Membership
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Update Membership
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>