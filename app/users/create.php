<?php

require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';

requireLogin();

$currentRole = $_SESSION['role'];


// ==================================================
// ACCESS CONTROL
// ==================================================

if ($currentRole !== 'admin' && $currentRole !== 'manager') {
    die("Access denied. Only administrators and managers can create users.");
}


$message = '';
$success = false;
$verificationLink = '';


// ==================================================
// CREATE USER
// ==================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    $sendEmail =
        isset($_POST['send_verification_email']);


    // ==================================================
    // ROLE RESTRICTIONS
    // ==================================================

    if ($currentRole === 'manager') {

        // Managers can only create regular users
        $role = 'user';

    } elseif ($currentRole === 'admin') {

        // Admin can create all roles
        if (!in_array(
            $role,
            ['admin', 'manager', 'user'],
            true
        )) {
            $role = 'user';
        }
    }


    try {

        // ==================================================
        // EMAIL VERIFICATION DEFAULTS
        // ==================================================

        $emailVerified = 1;

        $verificationToken = null;

        $verificationExpires = null;


        // ==================================================
        // GENERATE VERIFICATION TOKEN
        // ==================================================

        if ($sendEmail) {

            $emailVerified = 0;

            $verificationToken =
                bin2hex(random_bytes(32));

            $verificationExpires =
                date(
                    'Y-m-d H:i:s',
                    strtotime('+24 hours')
                );
        }


        // ==================================================
        // CHECK FOR EXISTING EMAIL
        // ==================================================

        $stmt = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE user_email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            throw new Exception(
                "An account with this email already exists."
            );
        }


        // ==================================================
        // INSERT USER
        // ==================================================

        $stmt = $pdo->prepare("
            INSERT INTO users (
                user_email,
                user_password,
                user_role,
                user_is_verified,
                user_verification_token,
                user_email_verification_expires
            )
            VALUES (
                :email,
                :password,
                :role,
                :verified,
                :token,
                :expires
            )
        ");


        $stmt->execute([

            ':email' =>
                $email,

            ':password' =>
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),

            ':role' =>
                $role,

            ':verified' =>
                $emailVerified,

            ':token' =>
                $verificationToken,

            ':expires' =>
                $verificationExpires

        ]);


        // ==================================================
        // GET NEW USER ID
        // ==================================================

        $newUserId =
            $pdo->lastInsertId();


        // ==================================================
        // LOG USER CREATION
        // ==================================================

        // Activity performed by the administrator/manager
        logActivity(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['email'],
            'user_created',
            'success'
        );


        // Activity belonging to the newly created account
        logActivity(
            $pdo,
            $newUserId,
            $email,
            'account_created',
            'success'
        );


        // ==================================================
        // SEND VERIFICATION EMAIL
        // ==================================================

        if ($sendEmail) {

            $verificationLink =
                BASE_URL .
                "/app/auth/verify-email.php?token=" .
                urlencode($verificationToken);


            $emailSubject =
                "Verify Your Email Address";


            $emailBody = "

            <html>

            <head>

                <style>

                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                    }

                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                    }

                    .header {
                        background: #1976d2;
                        color: white;
                        padding: 20px;
                        text-align: center;
                    }

                    .content {
                        background: #f9f9f9;
                        padding: 30px;
                    }

                    .button {
                        display: inline-block;
                        padding: 12px 30px;
                        background: #4caf50;
                        color: white;
                        text-decoration: none;
                        border-radius: 5px;
                        margin: 20px 0;
                    }

                    .footer {
                        text-align: center;
                        margin-top: 20px;
                        color: #666;
                        font-size: 12px;
                    }

                </style>

            </head>


            <body>

                <div class='container'>

                    <div class='header'>

                        <h2>
                            Email Verification
                        </h2>

                    </div>


                    <div class='content'>

                        <p>
                            Hello,
                        </p>

                        <p>
                            An account has been created
                            using this email address.
                        </p>

                        <p>
                            Please verify your email
                            address to activate your account.
                        </p>


                        <p style='text-align: center;'>

                            <a
                                href='{$verificationLink}'
                                class='button'
                            >
                                Verify Email
                            </a>

                        </p>


                        <p>
                            If the button does not work,
                            copy and paste this link:
                        </p>


                        <p
                            style='
                                word-break: break-all;
                                color: #1976d2;
                            '
                        >
                            {$verificationLink}
                        </p>


                        <p>
                            This link expires in 24 hours.
                        </p>

                    </div>


                    <div class='footer'>

                        <p>
                            &copy;
                            " . date('Y') . "
                            User Management System
                        </p>

                    </div>

                </div>

            </body>

            </html>

            ";


            // ==================================================
            // EMAIL HEADERS
            // ==================================================

            $headers =
                "MIME-Version: 1.0\r\n";

            $headers .=
                "Content-type:text/html;charset=UTF-8\r\n";

            $headers .=
                "From: noreply@ics-dev.io\r\n";


            // ==================================================
            // SEND EMAIL
            // ==================================================

            if (
                mail(
                    $email,
                    $emailSubject,
                    $emailBody,
                    $headers
                )
            ) {

                $message =
                    "User created successfully! " .
                    "Verification email sent.";


                logActivity(
                    $pdo,
                    $newUserId,
                    $email,
                    'verification_email_sent',
                    'success'
                );


            } else {

                $message =
                    "User created successfully! " .
                    "Verification email failed to send.";


                logActivity(
                    $pdo,
                    $newUserId,
                    $email,
                    'verification_email_sent',
                    'failed'
                );
            }


        } else {

            $message =
                "User created successfully! " .
                "(Email verification skipped)";

        }


        $success = true;


    } catch (Exception $e) {

        $message =
            "Error creating user: " .
            $e->getMessage();


        // ==================================================
        // LOG FAILED USER CREATION
        // ==================================================

        logActivity(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['email'],
            'user_created',
            'failed'
        );
    }
}


$title = 'Create User';

renderHeader($title);

?>


<div class="card">


    <div
        style="
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        "
    >

        <h2>
            Create New User
        </h2>


        <span
            class="badge badge-<?php
                echo htmlspecialchars($currentRole);
            ?>"
        >
            <?php
            echo ucfirst(
                htmlspecialchars($currentRole)
            );
            ?>
        </span>

    </div>


    <?php if ($currentRole === 'manager'): ?>

        <div
            class="info-box"
            style="
                background: #fff9c4;
                border-left-color: #ffa726;
            "
        >

            <strong>
                Manager Access:
            </strong>

            You can only create regular users.

        </div>

    <?php endif; ?>


    <?php if ($message): ?>

        <div
            class="<?php
                echo $success
                    ? 'success'
                    : 'error';
            ?>"
        >

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($verificationLink): ?>

        <div class="info-box">

            <strong>
                📧 Email Verification Link
                (for testing):
            </strong>

            <br>


            <a
                href="<?php
                    echo htmlspecialchars(
                        $verificationLink
                    );
                ?>"
                target="_blank"
            >

                <?php
                echo htmlspecialchars(
                    $verificationLink
                );
                ?>

            </a>


            <p
                style="
                    margin-top: 10px;
                    font-size: 13px;
                    color: #666;
                "
            >

                In production, this link will be
                sent via email.

            </p>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         CREATE USER FORM
    ====================================================== -->

    <form method="POST">


        <!-- Email -->

        <div class="form-group">

            <label for="email">
                Email Address:
            </label>

            <input
                type="email"
                id="email"
                name="email"
                required
                placeholder="user@example.com"
                value="<?php
                    echo htmlspecialchars(
                        $_POST['email'] ?? ''
                    );
                ?>"
            >

        </div>


        <!-- Password -->

        <div class="form-group">

            <label for="password">
                Password:
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
                placeholder="Enter password"
            >

        </div>


        <!-- Role -->

        <div class="form-group">

            <label for="role">
                Role:
            </label>


            <select
                id="role"
                name="role"
                <?php
                echo $currentRole === 'manager'
                    ? 'disabled'
                    : '';
                ?>
            >

                <option value="user">
                    User
                </option>


                <?php if ($currentRole === 'admin'): ?>

                    <option value="manager">
                        Manager
                    </option>

                    <option value="admin">
                        Admin
                    </option>

                <?php endif; ?>

            </select>


            <?php if ($currentRole === 'manager'): ?>

                <input
                    type="hidden"
                    name="role"
                    value="user"
                >

                <small style="color: #666;">

                    Managers can only create
                    regular users.

                </small>

            <?php endif; ?>

        </div>


        <!-- Email Verification -->

        <div class="form-group">

            <label
                style="
                    display: flex;
                    align-items: center;
                    cursor: pointer;
                "
            >

                <input
                    type="checkbox"
                    name="send_verification_email"
                    value="1"
                    checked
                    style="
                        width: auto;
                        margin-right: 10px;
                    "
                >

                <span>
                    Send email verification
                    (recommended)
                </span>

            </label>


            <small
                style="
                    color: #666;
                    margin-left: 30px;
                "
            >

                If unchecked, the user will be
                verified immediately without
                email confirmation.

            </small>

        </div>


        <!-- Submit -->

        <button type="submit">

            <span
                class="material-icons"
                style="
                    vertical-align: middle;
                    font-size: 18px;
                "
            >
                person_add
            </span>

            Create User

        </button>


    </form>


</div>


<?php renderFooter(); ?>