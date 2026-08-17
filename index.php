<?php

require_once 'config/config.php';
require_once 'config/functions.php';
require_once 'includes/activity-logger.php';


// Uncomment on deployment
/*
require_once $_SERVER['DOCUMENT_ROOT'] . '/test/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/test/config/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/test/includes/activity-logger.php';
*/


// --------------------------------------------------
// CHECK IF USER IS ALREADY LOGGED IN
// --------------------------------------------------

if (isLoggedIn()) {

    switch ($_SESSION['role']) {

        case 'admin':
            redirect('/app/admin/dashboard.php');
            break;

        case 'manager':
            redirect('/app/manager/dashboard.php');
            break;

        case 'user':
            redirect('/app/user/dashboard.php');
            break;
    }
}


$error = '';


// --------------------------------------------------
// PROCESS LOGIN
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    // --------------------------------------------------
    // VALIDATE INPUT
    // --------------------------------------------------

    if ($email === '' || $password === '') {

        $error = 'Email and password are required.';

    } else {

        // --------------------------------------------------
        // FIND VERIFIED USER
        // --------------------------------------------------

        $stmt = $pdo->prepare("
            SELECT
                user_id,
                user_email,
                user_password,
                user_role
            FROM users
            WHERE user_email = ?
            AND user_is_verified = 1
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);


        // --------------------------------------------------
        // VERIFY PASSWORD
        // --------------------------------------------------

        if (
            $user &&
            password_verify($password, $user['user_password'])
        ) {

            // --------------------------------------------------
            // PREVENT SESSION FIXATION
            // --------------------------------------------------

            session_regenerate_id(true);


            // --------------------------------------------------
            // CREATE SESSION
            // --------------------------------------------------

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email']   = $user['user_email'];
            $_SESSION['role']    = $user['user_role'];


            // --------------------------------------------------
            // LOG SUCCESSFUL LOGIN
            // --------------------------------------------------

            logActivity(
                $pdo,
                $user['user_id'],
                $user['user_email'],
                'login',
                'success'
            );


            // --------------------------------------------------
            // REDIRECT BASED ON ROLE
            // --------------------------------------------------

            switch ($user['user_role']) {

                case 'admin':
                    redirect('/app/admin/dashboard.php');
                    break;

                case 'manager':
                    redirect('/app/manager/dashboard.php');
                    break;

                case 'user':
                    redirect('/app/user/dashboard.php');
                    break;

                default:
                    $error = 'Invalid user role.';
                    break;
            }


        } else {

            // --------------------------------------------------
            // FAILED LOGIN
            // --------------------------------------------------

            $error = 'Invalid email or password.';


            // --------------------------------------------------
            // LOG FAILED LOGIN
            // --------------------------------------------------

            logActivity(
                $pdo,
                null,
                $email,
                'login',
                'failed'
            );
        }
    }
}


renderHeader('Login');

?>


<link rel="stylesheet" href="assets/css/auth.css">


<main class="auth-page">

    <section class="auth-card">


        <!-- ========================================== -->
        <!-- HEADER -->
        <!-- ========================================== -->

        <div class="auth-header">

            <div class="auth-logo">
                A
            </div>

            <h1>
                Welcome back
            </h1>

            <p>
                Sign in to continue to your account
            </p>

        </div>


        <!-- ========================================== -->
        <!-- ERROR MESSAGE -->
        <!-- ========================================== -->

        <?php if ($error): ?>

            <div
                class="auth-alert auth-alert-error"
                role="alert"
            >

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <!-- ========================================== -->
        <!-- LOGIN FORM -->
        <!-- ========================================== -->

        <form
            method="POST"
            class="auth-form"
        >


            <!-- EMAIL -->

            <div class="auth-field">

                <label for="email">
                    Email address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php
                        echo htmlspecialchars(
                            $_POST['email'] ?? ''
                        );
                    ?>"
                    autocomplete="email"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="auth-field">

                <label for="password">
                    Password
                </label>


                <div class="password-input-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >


                    <button
                        type="button"
                        id="togglePassword"
                        class="password-toggle"
                        aria-label="Show password"
                    >
                        Show
                    </button>

                </div>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="auth-button"
            >
                Sign in
            </button>


        </form>


        <!-- ========================================== -->
        <!-- TEST ACCOUNTS -->
        <!-- ========================================== -->

        <div class="auth-demo">

            <div class="auth-demo-title">
                Test Accounts
            </div>

            <p>
                Password:
                <code>password123</code>
            </p>

            <div class="auth-demo-accounts">

                <span>
                    admin@example.com
                </span>

                <span>
                    manager@example.com
                </span>

                <span>
                    user@example.com
                </span>

            </div>

        </div>


    </section>

</main>


<!-- ========================================== -->
<!-- PASSWORD TOGGLE -->
<!-- ========================================== -->

<script>

const passwordInput =
    document.getElementById('password');

const togglePassword =
    document.getElementById('togglePassword');


togglePassword.addEventListener(
    'click',
    function () {

        const isPassword =
            passwordInput.type === 'password';


        passwordInput.type =
            isPassword ? 'text' : 'password';


        this.textContent =
            isPassword ? 'Hide' : 'Show';


        this.setAttribute(
            'aria-label',
            isPassword
                ? 'Hide password'
                : 'Show password'
        );

    }
);

</script>


<?php renderFooter(); ?>