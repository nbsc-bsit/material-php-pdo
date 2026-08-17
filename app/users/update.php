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
    die("Access denied. Only administrators and managers can update users.");
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


$message = '';
$success = false;


// ==================================================
// GET USER DETAILS
// ==================================================

$stmt = $pdo->prepare("
    SELECT
        user_id,
        user_email,
        user_password,
        user_role,
        user_is_verified,
        user_created_at,
        user_updated_at
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
// ROLE RESTRICTIONS
// ==================================================

// Managers can only update regular users
if (
    $currentRole === 'manager' &&
    $user['user_role'] !== 'user'
) {
    die(
        "Access denied. Managers can only update regular users."
    );
}


// ==================================================
// UPDATE USER
// ==================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';


    $updates = [];
    $params = [];
    $changes = [];


    // ==================================================
    // EMAIL
    // ==================================================

    if (
        $email &&
        $email !== $user['user_email']
    ) {

        $updates[] = "user_email = ?";
        $params[] = $email;

        $changes[] = 'email';
    }


    // ==================================================
    // PASSWORD
    // ==================================================

    if ($password) {

        $updates[] = "user_password = ?";

        $params[] = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $changes[] = 'password';
    }


    // ==================================================
    // ROLE
    // ==================================================

    if (
        $role &&
        in_array(
            $role,
            ['admin', 'manager', 'user'],
            true
        ) &&
        $role !== $user['user_role']
    ) {

        // Managers cannot assign another role
        if ($currentRole === 'manager') {

            $role = 'user';

        } else {

            $updates[] = "user_role = ?";
            $params[] = $role;

            $changes[] = 'role';
        }
    }


    // ==================================================
    // EXECUTE UPDATE
    // ==================================================

    if (!empty($updates)) {

        $params[] = $userId;


        $sql = "
            UPDATE users
            SET " . implode(', ', $updates) . "
            WHERE user_id = ?
        ";


        try {

            $stmt = $pdo->prepare($sql);

            $stmt->execute($params);


            // ==================================================
            // SUCCESS MESSAGE
            // ==================================================

            $message =
                "User updated successfully!";

            $success = true;


            // ==================================================
            // LOG ACTION BY ADMIN/MANAGER
            // ==================================================

            logActivity(
                $pdo,
                $_SESSION['user_id'],
                $_SESSION['email'],
                'user_updated',
                'success'
            );


            // ==================================================
            // LOG ACTIVITY FOR AFFECTED USER
            // ==================================================

            logActivity(
                $pdo,
                $userId,
                $email ?: $user['user_email'],
                'profile_updated',
                'success'
            );


            // ==================================================
            // REFRESH USER DATA
            // ==================================================

            $stmt = $pdo->prepare("
                SELECT
                    user_id,
                    user_email,
                    user_password,
                    user_role,
                    user_is_verified,
                    user_created_at,
                    user_updated_at
                FROM users
                WHERE user_id = ?
                LIMIT 1
            ");

            $stmt->execute([$userId]);

            $user =
                $stmt->fetch(PDO::FETCH_ASSOC);


        } catch (PDOException $e) {

            $message =
                "Error updating user: " .
                $e->getMessage();


            // ==================================================
            // LOG FAILED UPDATE
            // ==================================================

            logActivity(
                $pdo,
                $_SESSION['user_id'],
                $_SESSION['email'],
                'user_updated',
                'failed'
            );
        }


    } else {

        $message =
            "No changes were made.";

    }
}


renderHeader('Update User');

?>


<div class="nav">

    <a
        href="<?php echo BASE_URL; ?>/app/users/dashboard.php"
    >
        Back to Users
    </a>


    <a
        href="<?php echo BASE_URL; ?>/app/auth/signout.php"
    >
        Logout
    </a>

</div>


<h1>
    Update User
</h1>


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

    <form method="POST">


        <!-- =================================================
             EMAIL
        ================================================== -->

        <div class="form-group">

            <label for="email">
                Email:
            </label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?php
                    echo htmlspecialchars(
                        $user['user_email']
                    );
                ?>"
                required
            >

        </div>


        <!-- =================================================
             PASSWORD
        ================================================== -->

        <div class="form-group">

            <label for="password">

                Password
                (leave empty to keep current):

            </label>

            <input
                type="password"
                id="password"
                name="password"
                autocomplete="new-password"
            >

        </div>


        <!-- =================================================
             ROLE
        ================================================== -->

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

                <option
                    value="user"
                    <?php
                    echo $user['user_role'] === 'user'
                        ? 'selected'
                        : '';
                    ?>
                >
                    User
                </option>


                <?php if ($currentRole === 'admin'): ?>

                    <option
                        value="manager"
                        <?php
                        echo $user['user_role'] === 'manager'
                            ? 'selected'
                            : '';
                        ?>
                    >
                        Manager
                    </option>


                    <option
                        value="admin"
                        <?php
                        echo $user['user_role'] === 'admin'
                            ? 'selected'
                            : '';
                        ?>
                    >
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

                    Managers can only update
                    regular users.

                </small>

            <?php endif; ?>

        </div>


        <!-- =================================================
             VERIFICATION STATUS
        ================================================== -->

        <div class="form-group">

            <label>
                Email Verification:
            </label>

            <strong>

                <?php
                echo (int) $user['user_is_verified'] === 1
                    ? 'Verified'
                    : 'Not Verified';
                ?>

            </strong>

        </div>


        <!-- =================================================
             SUBMIT
        ================================================== -->

        <button type="submit">
            Update User
        </button>


    </form>


<?php else: ?>

    <p>
        User not found.
    </p>

<?php endif; ?>


<?php renderFooter(); ?>