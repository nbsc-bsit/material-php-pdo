<?php

require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';

requireRole('manager');


// ==================================================
// USER STATISTICS
// ==================================================

// Total regular users
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE user_role = 'user'
");

$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


// Unverified regular users
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE user_role = 'user'
    AND user_is_verified = 0
");

$unverifiedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];


// ==================================================
// DAILY ACTIVITY - LAST 7 DAYS
// ==================================================

$stmt = $pdo->query("
    SELECT
        DATE(al.activity_log_created_at) AS date,
        COUNT(*) AS count
    FROM activity_logs al

    LEFT JOIN users u
        ON al.user_id = u.user_id

    WHERE al.activity_log_created_at >=
        DATE_SUB(NOW(), INTERVAL 7 DAY)

    AND (
        u.user_role IN ('manager', 'user')
        OR u.user_role IS NULL
    )

    GROUP BY DATE(al.activity_log_created_at)

    ORDER BY date ASC
");

$dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==================================================
// ACTION DISTRIBUTION - LAST 30 DAYS
// ==================================================

$stmt = $pdo->query("
    SELECT
        al.activity_log_action AS action,
        COUNT(*) AS count
    FROM activity_logs al

    LEFT JOIN users u
        ON al.user_id = u.user_id

    WHERE al.activity_log_created_at >=
        DATE_SUB(NOW(), INTERVAL 30 DAY)

    AND (
        u.user_role IN ('manager', 'user')
        OR u.user_role IS NULL
    )

    GROUP BY al.activity_log_action

    ORDER BY count DESC

    LIMIT 10
");

$actionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);


$title = 'Manager Dashboard';

renderHeader($title);

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
                    Manager Dashboard
                </h1>

                <p class="page-subtitle">
                    User management and team activity overview
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


            <span class="mui-chip mui-chip-manager">
                Manager
            </span>

        </section>


        <!-- ==========================================
             USER STATISTICS
        =========================================== -->

        <section>

            <div class="section-header">

                <h2>
                    User Statistics
                </h2>

            </div>


            <div class="stat-grid manager-stat-grid">


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

                <div>

                    <h2>
                        Activity Analytics
                    </h2>

                </div>

                <span class="section-meta">
                    Manager &amp; Users · Last 30 days
                </span>

            </div>


            <div class="chart-grid">


                <!-- ==================================
                     DAILY ACTIVITY
                =================================== -->

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


                <!-- ==================================
                     TOP ACTIONS
                =================================== -->

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


            </div>

        </section>


        <!-- ==========================================
             MANAGER CAPABILITIES
        =========================================== -->

        <section>

            <div class="section-header">

                <h2>
                    Manager Capabilities
                </h2>

            </div>


            <div class="capability-grid">


                <!-- MANAGE USERS -->

                <div class="mui-card capability-card">

                    <div class="capability-icon">
                        Users
                    </div>

                    <div>

                        <h3>
                            Manage Users
                        </h3>

                        <p>
                            Create, edit, and delete regular
                            user accounts.
                        </p>

                    </div>

                </div>


                <!-- REPORTS -->

                <div class="mui-card capability-card">

                    <div class="capability-icon">
                        Reports
                    </div>

                    <div>

                        <h3>
                            View Reports
                        </h3>

                        <p>
                            Access user information and
                            activity analytics.
                        </p>

                    </div>

                </div>


                <!-- TEAM -->

                <div class="mui-card capability-card">

                    <div class="capability-icon">
                        Team
                    </div>

                    <div>

                        <h3>
                            Team Management
                        </h3>

                        <p>
                            Monitor and oversee regular
                            user activities.
                        </p>

                    </div>

                </div>


                <!-- RESTRICTED -->

                <div class="mui-card capability-card capability-card-restricted">

                    <div class="capability-icon">
                        Restricted
                    </div>

                    <div>

                        <h3>
                            Access Restrictions
                        </h3>

                        <p>
                            Admin activities and administrator
                            or manager accounts are outside
                            your management scope.
                        </p>

                    </div>

                </div>


            </div>

        </section>


    </main>

</div>


<?php include '../../includes/footer.php'; ?>


<!-- ==========================================
     CHART.JS
=========================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>


<script>


// ==================================================
// PHP DATA
// ==================================================

const dailyData =
    <?php echo json_encode($dailyActivity); ?>;

const actionData =
    <?php echo json_encode($actionStats); ?>;


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


</script>