<?php

require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';

requireRole('admin');


// ==================================================
// USER STATISTICS
// ==================================================

// Total users
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM users
");

$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


// Administrators
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE user_role = 'admin'
");

$totalAdmins = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


// Managers
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE user_role = 'manager'
");

$totalManagers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


// Regular users
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE user_role = 'user'
");

$totalRegularUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


// Unverified users
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE user_is_verified = 0
");

$unverifiedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


// ==================================================
// DAILY ACTIVITY - LAST 7 DAYS
// ==================================================

$stmt = $pdo->query("
    SELECT
        DATE(activity_log_created_at) AS date,
        COUNT(*) AS count
    FROM activity_logs
    WHERE activity_log_created_at >=
        DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(activity_log_created_at)
    ORDER BY date ASC
");

$dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==================================================
// ACTION DISTRIBUTION - LAST 30 DAYS
// ==================================================

$stmt = $pdo->query("
    SELECT
        activity_log_action AS action,
        COUNT(*) AS count
    FROM activity_logs
    WHERE activity_log_created_at >=
        DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY activity_log_action
    ORDER BY count DESC
    LIMIT 10
");

$actionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==================================================
// ROLE DISTRIBUTION - LAST 30 DAYS
// ==================================================

$stmt = $pdo->query("
    SELECT
        COALESCE(u.user_role, 'unknown') AS role,
        COUNT(*) AS count
    FROM activity_logs al

    LEFT JOIN users u
        ON al.user_id = u.user_id

    WHERE al.activity_log_created_at >=
        DATE_SUB(NOW(), INTERVAL 30 DAY)

    GROUP BY u.user_role
    ORDER BY count DESC
");

$roleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==================================================
// RENDER PAGE
// ==================================================

renderHeader('Admin Dashboard');

?>

<link rel="stylesheet" href="../../assets/css/admin.css">

<?php include '../../includes/navbar.php'; ?>


<div class="admin-layout">

    <?php include '../../includes/sidebar.php'; ?>


    <main class="admin-main">


        <!-- ==========================================
             PAGE HEADER
        =========================================== -->

        <div class="page-header">

            <div>

                <h1>
                    Admin Dashboard
                </h1>

                <p class="page-subtitle">
                    System overview and activity analytics
                </p>

            </div>

        </div>


        <!-- ==========================================
             WELCOME
        =========================================== -->

        <section class="welcome-card">

            <div>

                <span class="welcome-label">
                    Welcome back
                </span>

                <h2>

                    <?php
                    echo htmlspecialchars(
                        $_SESSION['email']
                    );
                    ?>

                </h2>

            </div>


            <span class="mui-chip mui-chip-admin">
                Admin
            </span>

        </section>


        <!-- ==========================================
             SYSTEM STATISTICS
        =========================================== -->

        <section>

            <div class="section-header">

                <h2>
                    System Statistics
                </h2>

            </div>


            <div class="stat-grid">


                <!-- TOTAL USERS -->

                <div class="stat-card">

                    <div class="stat-content">

                        <span class="stat-label">
                            Total Users
                        </span>

                        <strong class="stat-value">

                            <?php
                            echo $totalUsers;
                            ?>

                        </strong>

                    </div>

                </div>


                <!-- ADMINISTRATORS -->

                <div class="stat-card">

                    <div class="stat-content">

                        <span class="stat-label">
                            Administrators
                        </span>

                        <strong class="stat-value">

                            <?php
                            echo $totalAdmins;
                            ?>

                        </strong>

                    </div>

                </div>


                <!-- MANAGERS -->

                <div class="stat-card">

                    <div class="stat-content">

                        <span class="stat-label">
                            Managers
                        </span>

                        <strong class="stat-value">

                            <?php
                            echo $totalManagers;
                            ?>

                        </strong>

                    </div>

                </div>


                <!-- REGULAR USERS -->

                <div class="stat-card">

                    <div class="stat-content">

                        <span class="stat-label">
                            Regular Users
                        </span>

                        <strong class="stat-value">

                            <?php
                            echo $totalRegularUsers;
                            ?>

                        </strong>

                    </div>

                </div>


                <!-- UNVERIFIED USERS -->

                <div class="stat-card stat-card-warning">

                    <div class="stat-content">

                        <span class="stat-label">
                            Unverified Users
                        </span>

                        <strong class="stat-value">

                            <?php
                            echo $unverifiedUsers;
                            ?>

                        </strong>

                    </div>

                </div>


            </div>

        </section>


        <!-- ==========================================
             ACTIVITY ANALYTICS
        =========================================== -->

        <section>

            <div class="section-header">

                <h2>
                    Activity Analytics
                </h2>

                <span class="section-meta">
                    Last 30 days
                </span>

            </div>


            <div class="chart-grid">


                <!-- DAILY ACTIVITY -->

                <div class="mui-card">

                    <div class="card-header">

                        <h3>
                            Daily Activity
                        </h3>

                        <span>
                            Last 7 days
                        </span>

                    </div>


                    <div class="chart-wrapper">

                        <canvas
                            id="dailyActivityChart"
                        ></canvas>

                    </div>

                </div>


                <!-- TOP ACTIONS -->

                <div class="mui-card">

                    <div class="card-header">

                        <h3>
                            Top Actions
                        </h3>

                        <span>
                            Last 30 days
                        </span>

                    </div>


                    <div class="chart-wrapper">

                        <canvas
                            id="actionChart"
                        ></canvas>

                    </div>

                </div>


                <!-- ACTIVITY BY ROLE -->

                <div class="mui-card">

                    <div class="card-header">

                        <h3>
                            Activity by Role
                        </h3>

                        <span>
                            Last 30 days
                        </span>

                    </div>


                    <div class="chart-wrapper chart-wrapper-small">

                        <canvas
                            id="roleChart"
                        ></canvas>

                    </div>

                </div>


            </div>

        </section>


    </main>

</div>


<?php include '../../includes/footer.php'; ?>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>


<script>

// ==================================================
// PHP DATA
// ==================================================

const dailyData =
    <?php echo json_encode($dailyActivity); ?>;

const actionData =
    <?php echo json_encode($actionStats); ?>;

const roleData =
    <?php echo json_encode($roleStats); ?>;


// ==================================================
// DAILY ACTIVITY
// ==================================================

new Chart(
    document.getElementById('dailyActivityChart'),
    {
        type: 'line',

        data: {

            labels: dailyData.map(
                item => item.date
            ),

            datasets: [

                {
                    label: 'Activities',

                    data: dailyData.map(
                        item => item.count
                    ),

                    tension: 0.3,

                    fill: true
                }

            ]
        },

        options: {

            responsive: true,

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                }

            }

        }
    }
);


// ==================================================
// TOP ACTIONS
// ==================================================

new Chart(
    document.getElementById('actionChart'),
    {
        type: 'bar',

        data: {

            labels: actionData.map(
                item => item.action
            ),

            datasets: [

                {
                    label: 'Count',

                    data: actionData.map(
                        item => item.count
                    )
                }

            ]

        },

        options: {

            responsive: true,

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                }

            }

        }

    }
);


// ==================================================
// ACTIVITY BY ROLE
// ==================================================

new Chart(
    document.getElementById('roleChart'),
    {
        type: 'doughnut',

        data: {

            labels: roleData.map(
                item => item.role.toUpperCase()
            ),

            datasets: [

                {
                    data: roleData.map(
                        item => item.count
                    )
                }

            ]

        },

        options: {

            responsive: true,

            plugins: {

                legend: {

                    position: 'bottom'

                }

            }

        }

    }
);

</script>