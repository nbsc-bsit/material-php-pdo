<?php

require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';


$message = '';
$success = false;


// ==================================================
// GET VERIFICATION TOKEN
// ==================================================

if (isset($_GET['token'])) {

    $token = trim($_GET['token']);


    // ==================================================
    // FIND USER WITH VALID VERIFICATION TOKEN
    // ==================================================

    $stmt = $pdo->prepare("
        SELECT
            user_id,
            user_email,
            user_is_verified,
            user_verification_token,
            user_email_verification_expires
        FROM users
        WHERE user_verification_token = ?
        LIMIT 1
    ");

    $stmt->execute([$token]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    // ==================================================
    // TOKEN FOUND
    // ==================================================

    if ($user) {


        // --------------------------------------------------
        // CHECK IF ALREADY VERIFIED
        // --------------------------------------------------

        if ((int) $user['user_is_verified'] === 1) {

            $message =
                "This email address has already been verified.";

            $success = true;


        // --------------------------------------------------
        // CHECK TOKEN EXPIRATION
        // --------------------------------------------------

        } elseif (
            !empty($user['user_email_verification_expires']) &&
            strtotime($user['user_email_verification_expires']) < time()
        ) {

            $message =
                "This verification link has expired.";


        // --------------------------------------------------
        // VERIFY EMAIL
        // --------------------------------------------------

        } else {

            $stmt = $pdo->prepare("
                UPDATE users
                SET
                    user_is_verified = 1,
                    user_verification_token = NULL,
                    user_email_verification_expires = NULL
                WHERE user_id = ?
            ");

            $stmt->execute([
                $user['user_id']
            ]);


            // --------------------------------------------------
            // LOG EMAIL VERIFICATION
            // --------------------------------------------------

            logActivity(
                $pdo,
                $user['user_id'],
                $user['user_email'],
                'email_verification',
                'success'
            );


            $message =
                "Email verified successfully! You can now login.";

            $success = true;
        }


    // ==================================================
    // INVALID TOKEN
    // ==================================================

    } else {

        $message =
            "Invalid verification token.";

    }


// ==================================================
// NO TOKEN
// ==================================================

} else {

    $message =
        "No verification token provided.";

}


// ==================================================
// PAGE
// ==================================================

renderHeader('Email Verification');

?>

<h1>
    Email Verification
</h1>


<?php if ($success): ?>

    <div class="success">

        <?php
        echo htmlspecialchars($message);
        ?>

    </div>


    <p>

        <a href="<?php echo BASE_URL; ?>/index.php">
            Go to Login
        </a>

    </p>


<?php else: ?>

    <div class="error">

        <?php
        echo htmlspecialchars($message);
        ?>

    </div>

<?php endif; ?>


<?php renderFooter(); ?>