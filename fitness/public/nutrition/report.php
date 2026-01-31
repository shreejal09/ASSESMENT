<?php
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../includes/functions.php';

require_staff(); // Admin or trainer can view nutrition reports

$page_title = 'Nutrition Analytics Report';
include '../../includes/header.php';

// Get statistics
$stats = $pdo->query("
    SELECT 
        AVG(calories) as avg_calories,
        MAX(calories) as max_calories,
        COUNT(*) as total_logs,
        COUNT(DISTINCT member_id) as active_trackers
    FROM nutrition_logs
    WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetch();

// Get top foods
$top_foods = $pdo->query("
    SELECT food_name, COUNT(*) as frequency, AVG(calories) as avg_cal
    FROM nutrition_logs
    GROUP BY food_name
    ORDER BY frequency DESC
    LIMIT 5
")->fetchAll();

// Get daily trends
$trends = $pdo->query("
    SELECT log_date, SUM(calories) as total_cal
    FROM nutrition_logs
    WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY log_date
    ORDER BY log_date ASC
")->fetchAll();
?>

<div class="content-header">
    <h1><i class="fas fa-chart-pie"></i> Nutrition Analytics</h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Logs
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-fire"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo number_format($stats['avg_calories'] ?? 0); ?>
            </h3>
            <p>Avg Cal/Meal (30d)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo e($stats['active_trackers'] ?? 0); ?>
            </h3>
            <p>Members Tracking</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-file-invoice"></i></div>
        <div class="stat-info">
            <h3>
                <?php echo e($stats['total_logs'] ?? 0); ?>
            </h3>
            <p>Total Logs (30d)</p>
        </div>
    </div>
</div>

<div class="dashboard-row mt-20">
    <div class="dashboard-section">
        <h3><i class="fas fa-trophy"></i> Most Common Foods</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Food Item</th>
                        <th>Frequency</th>
                        <th>Avg Calories</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_foods as $food): ?>
                        <tr>
                            <td>
                                <?php echo e($food['food_name']); ?>
                            </td>
                            <td>
                                <?php echo e($food['frequency']); ?> times
                            </td>
                            <td>
                                <?php echo number_format($food['avg_cal']); ?> cal
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="dashboard-section">
        <h3><i class="fas fa-history"></i> Last 7 Days (System-wide)</h3>
        <?php if ($trends): ?>
            <div class="trend-list">
                <?php foreach ($trends as $day): ?>
                    <div class="trend-item">
                        <strong>
                            <?php echo format_date($day['log_date']); ?>
                        </strong>
                        <span>
                            <?php echo number_format($day['total_cal']); ?> total calories consumed
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-data">No data for the last 7 days.</p>
        <?php endif; ?>
    </div>
</div>

<style>
    .mt-20 {
        margin-top: 20px;
    }

    .trend-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .trend-item {
        display: flex;
        justify-content: space-between;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 4px;
        border-left: 4px solid #3498db;
    }
</style>

<?php include '../../includes/footer.php'; ?>