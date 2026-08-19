<?php

require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';

requireLogin();

$currentRole = $_SESSION['role'];


// ==================================================
// ACCESS CONTROL
// ==================================================

// Only administrators and managers can delete users
if ($currentRole !== 'admin' && $currentRole !== 'manager') {
    die("Access denied. Only administrators and managers can delete users.");
}


// ==================================================
// GET USER ID
// ==================================================

$userId = filter_input(
    INPUT_GET,
    'user_id',
    FILTER_VALIDATE_INT
);

if (!$userId) {
    die("Invalid user ID.");
}


// ==================================================
// GET USER INFORMATION BEFORE DELETION
// ==================================================

$stmt = $pdo->prepare("
    SELECT
        user_id,
        user_email,
        user_role
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {
    die("User not found.");
}


// ==================================================
// PREVENT SELF-DELETION
// ==================================================

if ((int) $user['user_id'] === (int) $_SESSION['user_id']) {
    die("You cannot delete your own account.");
}


// ==================================================
// ROLE RESTRICTIONS
// ==================================================

// Managers can only delete regular users
if (
    $currentRole === 'manager' &&
    $user['user_role'] !== 'user'
) {
    die(
        "Access denied. Managers can only delete regular users."
    );
}


// ==================================================
// DELETE USER
// ==================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction();


        // --------------------------------------------------
        // Save information for logging
        // --------------------------------------------------

        $deletedUserId =
            $user['user_id'];

        $deletedUserEmail =
            $user['user_email'];


        // --------------------------------------------------
        // Delete user
        // --------------------------------------------------

        $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE user_id = ?
        ");

        $stmt->execute([
            $deletedUserId
        ]);


        // --------------------------------------------------
        // Log deletion
        // --------------------------------------------------

        logActivity(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['email'],
            'user_deleted',
            'success'
        );


        // --------------------------------------------------
        // Commit transaction
        // --------------------------------------------------

        $pdo->commit();


        // --------------------------------------------------
        // Redirect
        // --------------------------------------------------

        redirect('/app/users/dashboard.php');

        exit;


    } catch (Exception $e) {

        // Rollback if something failed
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }


        // Log failed deletion
        logActivity(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['email'],
            'user_deleted',
            'failed'
        );


        die(
            "Error deleting user: " .
            htmlspecialchars($e->getMessage())
        );
    }
}


// ==================================================
// PAGE
// ==================================================

renderHeader('Delete User');

?>

<div class="card">

    <h2>
        Delete User
    </h2>


    <div class="info-box">

        <strong>
            Are you sure you want to delete this user?
        </strong>

        <p>
            This action cannot be undone.
        </p>

    </div>


    <!-- User Information -->

    <div class="form-group">

        <label>
            User ID:
        </label>

        <strong>
            <?php
            echo htmlspecialchars(
                $user['user_id']
            );
            ?>
        </strong>

    </div>


    <div class="form-group">

        <label>
            Email:
        </label>

        <strong>
            <?php
            echo htmlspecialchars(
                $user['user_email']
            );
            ?>
        </strong>

    </div>


    <div class="form-group">

        <label>
            Role:
        </label>

        <strong>
            <?php
            echo htmlspecialchars(
                ucfirst($user['user_role'])
            );
            ?>
        </strong>

    </div>


    <!-- Confirmation -->

    <form method="POST">

        <button
            type="submit"
            onclick="
                return confirm(
                    'Are you sure you want to delete this user?'
                );
            "
        >
            Delete User
        </button>


        <a
            href="<?php
                echo BASE_URL;
            ?>/app/users/index.php"
        >
            Cancel
        </a>

    </form>

</div>


<?php renderFooter(); ?>