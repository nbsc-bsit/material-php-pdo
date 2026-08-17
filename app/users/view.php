<?php

require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';

requireLogin();


// ==================================================
// CURRENT USER
// ==================================================

$currentUserId = $_SESSION['user_id'];
$currentEmail  = $_SESSION['email'];
$currentRole   = $_SESSION['role'];


// ==================================================
// GET TARGET USER ID
// ==================================================

$userId = filter_input(
    INPUT_GET,
    'user_id',
    FILTER_VALIDATE_INT
);

if (!$userId) {
    die("Invalid user ID.");
}


$message = '';
$success = false;


// ==================================================
// RESEND VERIFICATION EMAIL
// ==================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['resend_verification'])
) {

    $verificationToken = bin2hex(random_bytes(32));

    $verificationExpires = date(
        'Y-m-d H:i:s',
        strtotime('+24 hours')
    );


    try {

        // --------------------------------------------------
        // Update verification token
        // --------------------------------------------------

        $stmt = $pdo->prepare("
            UPDATE users
            SET
                user_verification_token = :token,
                user_email_verification_expires = :expires
            WHERE user_id = :id
        ");

        $stmt->execute([
            ':token'   => $verificationToken,
            ':expires' => $verificationExpires,
            ':id'      => $userId
        ]);


        // --------------------------------------------------
        // Get user email
        // --------------------------------------------------

        $stmt = $pdo->prepare("
            SELECT
                user_email
            FROM users
            WHERE user_id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId]);

        $userEmail = $stmt->fetchColumn();


        if ($userEmail) {

            // --------------------------------------------------
            // Verification link
            // --------------------------------------------------

            $verificationLink =
                BASE_URL .
                "/app/auth/verify-email.php?token=" .
                urlencode($verificationToken);


            // --------------------------------------------------
            // Email
            // --------------------------------------------------

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
                        <h2>Email Verification</h2>
                    </div>

                    <div class='content'>

                        <p>Hello,</p>

                        <p>
                            You requested a new verification link.
                            Please verify your email address.
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
                            If the button doesn't work,
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
                            &copy; " . date('Y') . "
                            User Management System
                        </p>

                    </div>

                </div>

            </body>
            </html>
            ";


            // --------------------------------------------------
            // Email headers
            // --------------------------------------------------

            $headers =
                "MIME-Version: 1.0\r\n";

            $headers .=
                "Content-type:text/html;charset=UTF-8\r\n";

            $headers .=
                "From: noreply@ics-dev.io\r\n";


            // --------------------------------------------------
            // Send email
            // --------------------------------------------------

            if (
                mail(
                    $userEmail,
                    $emailSubject,
                    $emailBody,
                    $headers
                )
            ) {

                $message =
                    "Verification email has been resent successfully.";

                $success = true;


                // --------------------------------------------------
                // Log successful resend
                // --------------------------------------------------

                logActivity(
                    $pdo,
                    $currentUserId,
                    $currentEmail,
                    'verification_email_resent',
                    'success'
                );


                // --------------------------------------------------
                // Log affected user
                // --------------------------------------------------

                logActivity(
                    $pdo,
                    $userId,
                    $userEmail,
                    'verification_email_received',
                    'success'
                );


            } else {

                $message =
                    "Verification email failed to send.";

                $success = false;


                // --------------------------------------------------
                // Log failed email
                // --------------------------------------------------

                logActivity(
                    $pdo,
                    $currentUserId,
                    $currentEmail,
                    'verification_email_resent',
                    'failed'
                );
            }


        } else {

            $message =
                "User email could not be found.";

            $success = false;


            logActivity(
                $pdo,
                $currentUserId,
                $currentEmail,
                'verification_email_resent',
                'failed'
            );
        }


    } catch (PDOException $e) {

        $message =
            "Error resending verification.";

        $success = false;


        // --------------------------------------------------
        // Log database error
        // --------------------------------------------------

        logActivity(
            $pdo,
            $currentUserId,
            $currentEmail,
            'verification_email_resent',
            'failed'
        );
    }
}


// ==================================================
// GET USER DETAILS
// ==================================================

$stmt = $pdo->prepare("
    SELECT
        user_id,
        user_email,
        user_role,
        user_is_verified,
        user_verification_token,
        user_email_verification_expires,
        user_created_at,
        user_updated_at
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


// ==================================================
// LOG USER VIEW
// ==================================================

if ($user) {

    logActivity(
        $pdo,
        $currentUserId,
        $currentEmail,
        'user_viewed',
        'success'
    );
}


// ==================================================
// CHECK VERIFICATION EXPIRATION
// ==================================================

$verificationExpired = false;

if (
    $user &&
    (int)$user['user_is_verified'] === 0 &&
    !empty($user['user_email_verification_expires'])
) {

    $verificationExpired =
        strtotime(
            $user['user_email_verification_expires']
        ) < time();
}


// ==================================================
// PAGE
// ==================================================

$title = 'View User';

renderHeader($title);

?>


<div class="card">

    <h2>
        User Details
    </h2>


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


    <?php if ($user): ?>


        <!-- ================================================
             USER INFORMATION
        ================================================= -->

        <table>

            <tr>

                <th style="width: 200px;">
                    ID
                </th>

                <td>
                    <?php
                    echo (int)$user['user_id'];
                    ?>
                </td>

            </tr>


            <tr>

                <th>
                    Email
                </th>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $user['user_email']
                    );
                    ?>
                </td>

            </tr>


            <tr>

                <th>
                    Role
                </th>

                <td>

                    <span
                        class="badge badge-<?php
                            echo htmlspecialchars(
                                $user['user_role']
                            );
                        ?>"
                    >

                        <?php
                        echo ucfirst(
                            $user['user_role']
                        );
                        ?>

                    </span>

                </td>

            </tr>


            <!-- ============================================
                 VERIFICATION STATUS
            ============================================= -->

            <tr>

                <th>
                    Verified
                </th>

                <td>

                    <span
                        class="badge badge-<?php
                            echo (int)$user['user_is_verified'] === 1
                                ? 'verified'
                                : 'unverified';
                        ?>"
                    >

                        <?php
                        echo (int)$user['user_is_verified'] === 1
                            ? 'Yes'
                            : 'No';
                        ?>

                    </span>


                    <?php
                    if (
                        (int)$user['user_is_verified'] === 0 &&
                        $verificationExpired
                    ):
                    ?>

                        <span
                            class="badge"
                            style="
                                background: #f44336;
                                margin-left: 10px;
                            "
                        >
                            Expired
                        </span>

                    <?php endif; ?>

                </td>

            </tr>


            <!-- ============================================
                 VERIFICATION EXPIRATION
            ============================================= -->

            <?php
            if (
                (int)$user['user_is_verified'] === 0 &&
                !empty(
                    $user[
                        'user_email_verification_expires'
                    ]
                )
            ):
            ?>

                <tr>

                    <th>
                        Verification Expires
                    </th>

                    <td>

                        <?php

                        $expiresTime = strtotime(
                            $user[
                                'user_email_verification_expires'
                            ]
                        );

                        echo date(
                            'F j, Y, g:i a',
                            $expiresTime
                        );


                        if ($verificationExpired) {

                            echo
                                ' <span style="color: #f44336;">
                                    (Expired)
                                </span>';

                        } else {

                            $timeLeft =
                                $expiresTime - time();

                            $hoursLeft =
                                floor(
                                    $timeLeft / 3600
                                );

                            echo
                                ' <span style="color: #ffa726;">
                                    (' .
                                    $hoursLeft .
                                    ' hours remaining)
                                </span>';
                        }

                        ?>

                    </td>

                </tr>

            <?php endif; ?>


            <!-- ============================================
                 CREATED
            ============================================= -->

            <tr>

                <th>
                    Created
                </th>

                <td>

                    <?php

                    echo date(
                        'F j, Y, g:i a',
                        strtotime(
                            $user['user_created_at']
                        )
                    );

                    ?>

                </td>

            </tr>


            <!-- ============================================
                 UPDATED
            ============================================= -->

            <tr>

                <th>
                    Last Updated
                </th>

                <td>

                    <?php

                    echo date(
                        'F j, Y, g:i a',
                        strtotime(
                            $user['user_updated_at']
                        )
                    );

                    ?>

                </td>

            </tr>

        </table>


        <!-- ================================================
             RESEND VERIFICATION
        ================================================= -->

        <?php
        if (
            (int)$user['user_is_verified'] === 0
        ):
        ?>

            <div
                class="info-box"
                style="
                    background:
                        <?php
                        echo $verificationExpired
                            ? '#ffebee'
                            : '#fff9c4';
                        ?>;

                    border-left-color:
                        <?php
                        echo $verificationExpired
                            ? '#f44336'
                            : '#ffa726';
                        ?>;

                    margin-top: 20px;
                "
            >

                <strong>

                    <?php

                    echo $verificationExpired
                        ? '⚠️ Verification Link Expired'
                        : '📧 Email Not Verified';

                    ?>

                </strong>

                <br>


                <?php
                if ($verificationExpired):
                ?>

                    This user's verification link has
                    expired. Click the button below to
                    send a new verification email.

                <?php else: ?>

                    This user has not verified their
                    email address yet. You can resend
                    the verification email if needed.

                <?php endif; ?>


                <form
                    method="POST"
                    style="margin-top: 15px;"
                >

                    <button
                        type="submit"
                        name="resend_verification"
                        style="
                            background:
                            <?php
                            echo $verificationExpired
                                ? '#f44336'
                                : '#ffa726';
                            ?>;
                        "
                    >

                        <span
                            class="material-icons"
                            style="
                                vertical-align: middle;
                                font-size: 18px;
                            "
                        >
                            email
                        </span>

                        Resend Verification Email

                    </button>

                </form>

            </div>

        <?php endif; ?>


        <!-- ================================================
             ACTIONS
        ================================================= -->

        <div
            style="
                margin-top: 20px;
                display: flex;
                gap: 10px;
            "
        >

            <a
                href="<?php
                    echo BASE_URL;
                ?>/app/users/update.php?user_id=<?php
                    echo (int)$user['user_id'];
                ?>"
            >

                <button type="button">

                    <span
                        class="material-icons"
                        style="
                            vertical-align: middle;
                            font-size: 18px;
                        "
                    >
                        edit
                    </span>

                    Edit User

                </button>

            </a>


            <a
                href="<?php
                    echo BASE_URL;
                ?>/app/users/dashboard.php"
            >

                <button
                    type="button"
                    style="background: #757575;"
                >

                    <span
                        class="material-icons"
                        style="
                            vertical-align: middle;
                            font-size: 18px;
                        "
                    >
                        arrow_back
                    </span>

                    Back to List

                </button>

            </a>

        </div>


    <?php else: ?>


        <div class="error">

            User not found.

        </div>


    <?php endif; ?>

</div>


<?php renderFooter(); ?>