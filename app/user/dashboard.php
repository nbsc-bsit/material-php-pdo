<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';

requireRole('user');

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['email'];

/*
|--------------------------------------------------------------------------
| User Activity Statistics
|--------------------------------------------------------------------------
*/

// Total activities
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM activity_logs
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$totalActivities = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Successful activities
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM activity_logs
    WHERE user_id = ?
    AND status = 'success'
");
$stmt->execute([$userId]);
$successfulActivities = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Failed activities
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM activity_logs
    WHERE user_id = ?
    AND status = 'failed'
");
$stmt->execute([$userId]);
$failedActivities = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Action types
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT action) AS total
    FROM activity_logs
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$actionTypes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Last successful login
$stmt = $pdo->prepare("
    SELECT created_at
    FROM activity_logs
    WHERE user_id = ?
    AND action = 'login'
    AND status = 'success'
    ORDER BY created_at DESC
    LIMIT 1, 1
");
$stmt->execute([$userId]);
$lastLogin = $stmt->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Daily Activity - Last 7 Days
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        DATE(created_at) AS date,
        COUNT(*) AS count
    FROM activity_logs
    WHERE user_id = ?
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$userId]);
$dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Action Statistics - Last 30 Days
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        action,
        COUNT(*) AS count
    FROM activity_logs
    WHERE user_id = ?
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY action
    ORDER BY count DESC
    LIMIT 10
");
$stmt->execute([$userId]);
$actionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderHeader('User Dashboard');
?>

<link rel="stylesheet" href="../../assets/css/user.css">

<?php include '../../includes/user-navbar.php'; ?>

<div class="user-layout">

    <?php include '../../includes/user-sidebar.php'; ?>
    <h1>YAWA</h1>
    <main class="user-main">

        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <header class="user-page-header">

            <div>
                <span class="user-overline">
                    MY ACCOUNT
                </span>

                <h1>User Dashboard</h1>

                <p>
                    Monitor your account activity and manage your profile.
                </p>
            </div>

        </header>


        <!-- =====================================================
             WELCOME / ACCOUNT STATUS
        ====================================================== -->

        <section class="user-welcome">

            <div class="user-welcome-content">

                <div class="user-success-icon">
                    ✓
                </div>

                <div>
                    <span class="user-label">
                        Welcome back
                    </span>

                    <h2>
                        <?php echo htmlspecialchars($userEmail); ?>
                    </h2>

                    <p>
                        Your account is active and ready to use.
                    </p>
                </div>

            </div>

            <div class="user-account-status">
                <span class="user-status-dot"></span>
                Active
            </div>

        </section>


        <!-- =====================================================
             QUICK STATISTICS
        ====================================================== -->

        <section class="user-section">

            <div class="user-section-header">

                <div>
                    <h2>Account Activity</h2>

                    <p>
                        A quick overview of your recent activity.
                    </p>
                </div>

            </div>


            <div class="user-stat-grid">

                <!-- Total -->
                <article class="user-stat-card">

                    <div class="user-stat-icon success">
                        ✓
                    </div>

                    <div class="user-stat-content">

                        <span>
                            Total Activities
                        </span>

                        <strong>
                            <?php echo $totalActivities; ?>
                        </strong>

                    </div>

                </article>


                <!-- Successful -->
                <article class="user-stat-card">

                    <div class="user-stat-icon success">
                        ✓
                    </div>

                    <div class="user-stat-content">

                        <span>
                            Successful
                        </span>

                        <strong>
                            <?php echo $successfulActivities; ?>
                        </strong>

                    </div>

                </article>


                <!-- Failed -->
                <article class="user-stat-card">

                    <div class="user-stat-icon warning">
                        !
                    </div>

                    <div class="user-stat-content">

                        <span>
                            Failed
                        </span>

                        <strong>
                            <?php echo $failedActivities; ?>
                        </strong>

                    </div>

                </article>


                <!-- Action Types -->
                <article class="user-stat-card">

                    <div class="user-stat-icon blue">
                        A
                    </div>

                    <div class="user-stat-content">

                        <span>
                            Action Types
                        </span>

                        <strong>
                            <?php echo $actionTypes; ?>
                        </strong>

                    </div>

                </article>

            </div>

        </section>


        <!-- =====================================================
             ANALYTICS
        ====================================================== -->

        <section class="user-section">

            <div class="user-section-header">

                <div>
                    <h2>Activity Analytics</h2>

                    <p>
                        Your account activity over time.
                    </p>
                </div>

                <span class="user-period">
                    Last 30 days
                </span>

            </div>


            <div class="user-chart-grid">

                <!-- Daily Activity -->
                <article class="user-card">

                    <div class="user-card-header">

                        <div>
                            <h3>Daily Activity</h3>

                            <p>
                                Activity during the last 7 days
                            </p>
                        </div>

                    </div>

                    <div class="user-chart">
                        <canvas id="dailyActivityChart"></canvas>
                    </div>

                </article>


                <!-- Actions -->
                <article class="user-card">

                    <div class="user-card-header">

                        <div>
                            <h3>Activity by Action</h3>

                            <p>
                                Your most common activities
                            </p>
                        </div>

                    </div>

                    <div class="user-chart">
                        <canvas id="actionChart"></canvas>
                    </div>

                </article>

            </div>

        </section>


        <!-- =====================================================
             ACCOUNT INFORMATION
        ====================================================== -->

        <section class="user-section">

            <div class="user-section-header">

                <div>
                    <h2>Account Information</h2>

                    <p>
                        Your current account details.
                    </p>
                </div>

            </div>


            <div class="user-account-grid">

                <!-- Account Details -->
                <article class="user-card user-account-card">

                    <div class="user-info-row">

                        <span>Email</span>

                        <strong>
                            <?php echo htmlspecialchars($userEmail); ?>
                        </strong>

                    </div>


                    <div class="user-info-row">

                        <span>Role</span>

                        <span class="user-role">
                            User
                        </span>

                    </div>


                    <div class="user-info-row">

                        <span>Account Status</span>

                        <span class="user-status">

                            <span class="user-status-dot"></span>

                            Active

                        </span>

                    </div>


                    <div class="user-info-row">

                        <span>Last Login</span>

                        <strong>

                            <?php
                            echo $lastLogin
                                ? date(
                                    'M d, Y h:i A',
                                    strtotime($lastLogin['created_at'])
                                )
                                : 'N/A';
                            ?>

                        </strong>

                    </div>

                </article>


                <!-- Quick Actions -->
                <article class="user-card">

                    <div class="user-card-header">

                        <div>
                            <h3>Quick Actions</h3>

                            <p>
                                Common account functions
                            </p>
                        </div>

                    </div>


                    <div class="user-action-list">

                        <a
                            href="<?php echo BASE_URL; ?>/app/user/profile.php"
                            class="user-action"
                        >

                            <span class="user-action-icon">
                                👤
                            </span>

                            <span>
                                <strong>My Profile</strong>

                                <small>
                                    View and update your profile
                                </small>
                            </span>

                            <span class="user-action-arrow">
                                →
                            </span>

                        </a>


                        <a
                            href="<?php echo BASE_URL; ?>/app/user/activity.php"
                            class="user-action"
                        >

                            <span class="user-action-icon">
                                ✓
                            </span>

                            <span>
                                <strong>Activity History</strong>

                                <small>
                                    Review your account activity
                                </small>
                            </span>

                            <span class="user-action-arrow">
                                →
                            </span>

                        </a>


                        <a
                            href="<?php echo BASE_URL; ?>/app/user/change-password.php"
                            class="user-action"
                        >

                            <span class="user-action-icon">
                                🔒
                            </span>

                            <span>
                                <strong>Change Password</strong>

                                <small>
                                    Update your account password
                                </small>
                            </span>

                            <span class="user-action-arrow">
                                →
                            </span>

                        </a>

                    </div>

                </article>

            </div>

        </section>

    </main>

</div>

<?php include '../../includes/user-footer.php'; ?>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>

const dailyData = <?php echo json_encode($dailyActivity); ?>;
const actionData = <?php echo json_encode($actionStats); ?>;


/*
|--------------------------------------------------------------------------
| Daily Activity Chart
|--------------------------------------------------------------------------
*/

new Chart(document.getElementById('dailyActivityChart'), {

    type: 'line',

    data: {

        labels: dailyData.map(item => item.date),

        datasets: [{
            data: dailyData.map(item => item.count),

            borderColor: '#2E7D32',

            backgroundColor:
                'rgba(46, 125, 50, 0.08)',

            borderWidth: 2,

            pointRadius: 3,

            pointHoverRadius: 5,

            tension: 0.3,

            fill: true
        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            }

        },

        scales: {

            x: {
                grid: {
                    display: false
                }
            },

            y: {
                beginAtZero: true,

                ticks: {
                    precision: 0
                }
            }

        }

    }

});


/*
|--------------------------------------------------------------------------
| Action Chart
|--------------------------------------------------------------------------
*/

new Chart(document.getElementById('actionChart'), {

    type: 'bar',

    data: {

        labels: actionData.map(item => item.action),

        datasets: [{

            data: actionData.map(item => item.count),

            backgroundColor: '#2E7D32',

            borderRadius: 4,

            borderSkipped: false

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            }

        },

        scales: {

            x: {
                grid: {
                    display: false
                }
            },

            y: {
                beginAtZero: true,

                ticks: {
                    precision: 0
                }
            }

        }

    }

});

</script>