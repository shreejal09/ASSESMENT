<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can add memberships

$page_title = 'Add Membership';
include '../../includes/header.php';

$errors = [];
$success = '';
$form_data = [
    'member_id' => '',
    'plan_name' => '',
    'plan_type' => 'Monthly',
    'price' => '',
    'start_date' => date('Y-m-d'),
    'expiry_date' => '',
    'payment_status' => 'Paid',
    'payment_method' => 'Cash',
    'auto_renew' => 1
];

// Get members for dropdown
$members_stmt = $pdo->query("SELECT id, first_name, last_name, email FROM members WHERE status = 'Active' ORDER BY first_name");
$members = $members_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $form_data = [
        'member_id' => (int) ($_POST['member_id'] ?? 0),
        'plan_name' => trim($_POST['plan_name'] ?? ''),
        'plan_type' => $_POST['plan_type'] ?? 'Monthly',
        'price' => trim($_POST['price'] ?? ''),
        'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
        'expiry_date' => $_POST['expiry_date'] ?? '',
        'payment_status' => $_POST['payment_status'] ?? 'Pending',
        'payment_method' => trim($_POST['payment_method'] ?? ''),
        'auto_renew' => isset($_POST['auto_renew']) ? 1 : 0
    ];

    // Validation
    if (empty($form_data['member_id'])) {
        $errors[] = 'Member is required';
    }

    if (empty($form_data['plan_name'])) {
        $errors[] = 'Plan name is required';
    }

    if (empty($form_data['price'])) {
        $errors[] = 'Price is required';
    } elseif (!is_numeric($form_data['price'])) {
        $errors[] = 'Price must be a number';
    }

    if (empty($form_data['start_date'])) {
        $errors[] = 'Start date is required';
    }

    if (empty($form_data['expiry_date'])) {
        $errors[] = 'Expiry date is required';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO memberships (
                    member_id, plan_name, plan_type, price, start_date, 
                    expiry_date, payment_status, payment_method, auto_renew
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $form_data['member_id'],
                $form_data['plan_name'],
                $form_data['plan_type'],
                $form_data['price'],
                $form_data['start_date'],
                $form_data['expiry_date'],
                $form_data['payment_status'],
                $form_data['payment_method'],
                $form_data['auto_renew']
            ]);

            $_SESSION['success'] = "Membership added successfully!";
            redirect('index.php');

        } catch (Exception $e) {
            $errors[] = 'Failed to add membership: ' . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-plus-circle"></i> Add New Membership</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Memberships
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-id-card"></i> Membership Details</h2>
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
                <h3><i class="fas fa-user"></i> Member Selection</h3>
                <div class="form-group">
                    <label for="member_id">Select Member *</label>
                    <select id="member_id" name="member_id" required>
                        <option value="">-- Select Member --</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?php echo e($member['id']); ?>" <?php echo $form_data['member_id'] == $member['id'] ? 'selected' : ''; ?>>
                                <?php echo e($member['first_name'] . ' ' . $member['last_name']); ?> (
                                <?php echo e($member['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-tag"></i> Plan Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="plan_name">Plan Name *</label>
                        <input type="text" id="plan_name" name="plan_name"
                            value="<?php echo e($form_data['plan_name']); ?>" required placeholder="e.g. Gold Plan">
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
                        value="<?php echo e($form_data['price']); ?>" required placeholder="0.00">
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
                            value="<?php echo e($form_data['payment_method']); ?>" placeholder="e.g. Cash, Card">
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
                    <i class="fas fa-save"></i> Save Membership
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>