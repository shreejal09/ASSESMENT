<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - ' : ''; ?><?php echo e(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><i class="fas fa-dumbbell"></i> <?php echo e(SITE_NAME); ?></h1>
                </div>
                <div class="user-info">
                    <?php if (is_logged_in()): ?>
                        <span class="welcome">Welcome, <?php echo e($_SESSION['user_name'] ?? 'User'); ?></span>
                        <span class="role-badge <?php echo e($_SESSION['user_role'] ?? 'member'); ?>">
                            <?php echo e(ucfirst($_SESSION['user_role'] ?? 'member')); ?>
                        </span>
                        <a href="<?php echo BASE_URL; ?>/public/logout.php" class="logout-btn"><i
                                class="fas fa-sign-out-alt"></i> Logout</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/public/login.php" class="login-btn">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <?php if (is_logged_in()): ?>
        <nav class="main-nav">
            <div class="container">
                <ul class="nav-menu">
                    <li><a href="<?php echo BASE_URL; ?>/public/dashboard.php"><i class="fas fa-tachometer-alt"></i>
                            Dashboard</a></li>

                    <?php if (is_admin()): ?>
                        <!-- Admin Navigation -->
                        <li><a href="<?php echo BASE_URL; ?>/public/members/index.php"><i class="fas fa-users"></i> Members</a>
                        </li>
                        <li><a href="<?php echo BASE_URL; ?>/public/trainers/index.php"><i class="fas fa-user-tie"></i>
                                Trainers</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/memberships/index.php"><i class="fas fa-id-card"></i>
                                Memberships</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/attendance/index.php"><i class="fas fa-clipboard-check"></i>
                                Attendance</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/workouts/index.php"><i class="fas fa-running"></i>
                                Workouts</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/nutrition/index.php"><i class="fas fa-apple-alt"></i>
                                Nutrition</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/payments/index.php"><i class="fas fa-credit-card"></i>
                                Payments</a></li>

                    <?php elseif (is_trainer()): ?>
                        <!-- Trainer Navigation -->
                        <li><a href="<?php echo BASE_URL; ?>/public/members/index.php"><i class="fas fa-users"></i> My
                                Clients</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/workouts/index.php"><i class="fas fa-running"></i> Workout
                                Plans</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/attendance/index.php"><i class="fas fa-clipboard-check"></i>
                                Attendance</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/progress/index.php"><i class="fas fa-chart-line"></i>
                                Progress Tracking</a></li>

                    <?php else: ?>
                        <!-- Member Navigation -->
                        <li><a href="<?php echo BASE_URL; ?>/public/memberships/index.php"><i class="fas fa-id-card"></i> My
                                Membership</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/attendance/checkin.php"><i class="fas fa-check-circle"></i>
                                Check-in</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/workouts/plans.php"><i class="fas fa-running"></i> My
                                Workouts</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/nutrition/index.php"><i class="fas fa-apple-alt"></i>
                                Nutrition Log</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/progress/index.php"><i class="fas fa-chart-line"></i> My
                                Progress</a></li>
                    <?php endif; ?>

                    <!-- Common for all logged in users -->
                    <li><a href="<?php echo BASE_URL; ?>/public/profile/profile.php"><i class="fas fa-user-circle"></i>
                            Profile</a></li>
                </ul>
            </div>
        </nav>
    <?php endif; ?>

    <main class="container">
        <!-- Flash messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo e($_SESSION['success']);
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo e($_SESSION['error']);
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning">
                <?php echo e($_SESSION['warning']);
                unset($_SESSION['warning']); ?>
            </div>
        <?php endif; ?>

        <!-- Notifications -->
        <?php
        if (is_logged_in()) {
            $notifications = get_unread_notifications($pdo, $_SESSION['user_id']);
            if (!empty($notifications)) {
                foreach ($notifications as $notification) {
                    $notif_id = 'notif_' . $notification['id'];
                    echo '<div id="' . $notif_id . '" class="alert alert-notification alert-dismissible fade show" role="alert" style="background-color: #d4edda; border-color: #c3e6cb; color: #155724; font-size: 1.1rem; padding: 1rem 1.5rem; margin-bottom: 1rem; border-left: 4px solid #28a745;">';
                    echo '<i class="fas fa-bell" style="color: #28a745; margin-right: 8px;"></i> ' . e($notification['message']);
                    echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="this.parentElement.style.display=\'none\';" style="font-size: 1.5rem;">';
                    echo '<span aria-hidden="true">&times;</span>';
                    echo '</button>';
                    echo '</div>';
                    echo '<script>setTimeout(function(){ var elem = document.getElementById("' . $notif_id . '"); if(elem) elem.style.display = "none"; }, 25000);</script>';
                }
                // Mark displayed notifications as read
                mark_notifications_read($pdo, $_SESSION['user_id']);
            }
        }
        ?>