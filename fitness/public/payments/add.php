<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_admin(); // Only admin can record payments

$page_title = 'Record Payment';
include '../../includes/header.php';

$errors = [];
$form_data = [
    'membership_id' => '',
    'amount' => '',
    'payment_date' => date('Y-m-d'),
    'payment_method' => 'Cash',
    'status' => 'Completed',
    'transaction_id' => '',
    'notes' => ''
];

// Get all memberships for payment recording
$stmt = $pdo->query("
    SELECT ms.id, ms.plan_name, ms.price, ms.payment_status, m.first_name, m.last_name 
    FROM memberships ms
    JOIN members m ON ms.member_id = m.id
    ORDER BY ms.created_at DESC
");
$memberships = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF Token');
    }

    $form_data = [
        'membership_id' => (int) ($_POST['membership_id'] ?? 0),
        'amount' => trim($_POST['amount'] ?? ''),
        'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
        'payment_method' => trim($_POST['payment_method'] ?? 'Cash'),
        'status' => $_POST['status'] ?? 'Completed',
        'transaction_id' => trim($_POST['transaction_id'] ?? ''),
        'notes' => trim($_POST['notes'] ?? '')
    ];

    if (empty($form_data['membership_id']))
        $errors[] = "Please select a membership";
    if (empty($form_data['amount']))
        $errors[] = "Amount is required";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Record payment
            $stmt = $pdo->prepare("
                INSERT INTO payments (membership_id, amount, payment_date, payment_method, status, transaction_id, notes, processed_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $form_data['membership_id'],
                $form_data['amount'],
                $form_data['payment_date'],
                $form_data['payment_method'],
                $form_data['status'],
                $form_data['transaction_id'],
                $form_data['notes'],
                $_SESSION['user_id']
            ]);

            // Update membership status if payment is completed
            if ($form_data['status'] === 'Completed') {
                $stmt = $pdo->prepare("UPDATE memberships SET payment_status = 'Paid' WHERE id = ?");
                $stmt->execute([$form_data['membership_id']]);
            }

            $pdo->commit();
            $_SESSION['success'] = "Payment recorded successfully";
            redirect('index.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to record payment: " . $e->getMessage();
        }
    }
}
?>

<div class="content-header">
    <h1><i class="fas fa-plus"></i> Record Payment</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Payments
        </a>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2><i class="fas fa-money-check-alt"></i> Payment Information</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error)
                        echo "<li>" . e($error) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="form-grid">
            <div class="form-group">
                <label for="membership_id">Select Membership *</label>
                <select id="membership_id" name="membership_id" required onchange="updateAmount(this)">
                    <option value="">-- Select Pending Membership --</option>
                    <?php foreach ($memberships as $ms): ?>
                        <option value="<?php echo $ms['id']; ?>" data-price="<?php echo $ms['price']; ?>" <?php echo $form_data['membership_id'] == $ms['id'] ? 'selected' : ''; ?>>
                            <?php echo e($ms['first_name'] . ' ' . $ms['last_name'] . ' - ' . $ms['plan_name'] . ' ($' . $ms['price'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="amount">Amount ($) *</label>
                    <input type="number" id="amount" name="amount" step="0.01"
                        value="<?php echo e($form_data['amount']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="payment_date">Payment Date</label>
                    <input type="date" id="payment_date" name="payment_date"
                        value="<?php echo e($form_data['payment_date']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method">
                        <option value="Cash" <?php echo $form_data['payment_method'] == 'Cash' ? 'selected' : ''; ?>>Cash
                        </option>
                        <option value="Card" <?php echo $form_data['payment_method'] == 'Card' ? 'selected' : ''; ?>>Card
                        </option>
                        <option value="Bank Transfer" <?php echo $form_data['payment_method'] == 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Payment Status</label>
                    <select id="status" name="status">
                        <option value="Completed" <?php echo $form_data['status'] == 'Completed' ? 'selected' : ''; ?>>
                            Completed</option>
                        <option value="Pending" <?php echo $form_data['status'] == 'Pending' ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="Failed" <?php echo $form_data['status'] == 'Failed' ? 'selected' : ''; ?>>Failed
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="transaction_id">Transaction ID (Optional)</label>
                <input type="text" id="transaction_id" name="transaction_id"
                    value="<?php echo e($form_data['transaction_id']); ?>">
            </div>

            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3"><?php echo e($form_data['notes']); ?></textarea>
            </div>

            <div class="form-actions">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Record Payment
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function updateAmount(select) {
        const selectedOption = select.options[select.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        if (price) {
            document.getElementById('amount').value = price;
        }
    }
</script>

<?php include '../../includes/footer.php'; ?>