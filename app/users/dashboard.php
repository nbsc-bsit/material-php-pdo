<?php

require_once '../../config/config.php';
require_once '../../config/functions.php';

requireLogin();

$currentRole   = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Get Users Based on Role
|--------------------------------------------------------------------------
*/

if ($currentRole === 'admin') {

    // Admin can see all users
    $stmt = $pdo->prepare("
        SELECT
            user_id,
            user_email,
            user_role,
            user_is_verified,
            user_created_at
        FROM users
        ORDER BY
            CASE user_role
                WHEN 'admin' THEN 1
                WHEN 'manager' THEN 2
                WHEN 'user' THEN 3
                ELSE 4
            END,
            user_created_at DESC
    ");

    $stmt->execute();

} elseif ($currentRole === 'manager') {

    // Manager can only see regular users
    $stmt = $pdo->prepare("
        SELECT
            user_id,
            user_email,
            user_role,
            user_is_verified,
            user_created_at
        FROM users
        WHERE user_role = 'user'
        ORDER BY user_created_at DESC
    ");

    $stmt->execute();

} else {

    // Regular users can only see themselves
    $stmt = $pdo->prepare("
        SELECT
            user_id,
            user_email,
            user_role,
            user_is_verified,
            user_created_at
        FROM users
        WHERE user_id = ?
        ORDER BY user_created_at DESC
    ");

    $stmt->execute([$currentUserId]);
}

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Permission Functions
|--------------------------------------------------------------------------
*/

function canEdit($targetRole, $targetUserId)
{
    global $currentRole, $currentUserId;

    // Users can edit their own profile
    if ($targetUserId == $currentUserId) {
        return true;
    }

    // Admin can edit everyone
    if ($currentRole === 'admin') {
        return true;
    }

    // Manager can edit regular users only
    if ($currentRole === 'manager') {
        return $targetRole === 'user';
    }

    return false;
}


function canDelete($targetRole, $targetUserId)
{
    global $currentRole, $currentUserId;

    // Nobody can delete their own account
    if ($targetUserId == $currentUserId) {
        return false;
    }

    // Admin can delete anyone except themselves
    if ($currentRole === 'admin') {
        return true;
    }

    // Manager can delete regular users only
    if ($currentRole === 'manager') {
        return $targetRole === 'user';
    }

    return false;
}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

renderHeader('User Management');

?>

<link rel="stylesheet" href="../../assets/css/users.css">

<?php include '../../includes/navbar.php'; ?>


<div class="users-layout">

    <?php include '../../includes/sidebar.php'; ?>


    <main class="users-main">

        <!-- Page Header -->
        <header class="users-page-header">

            <div>

                <span class="users-overline">
                    USER MANAGEMENT
                </span>

                <h1>
                    User Management
                </h1>

                <p>
                    Manage user accounts according to your access level.
                </p>

            </div>

        </header>


        <!-- Role Information -->
        <section class="users-info-card">

            <div class="users-info-content">

                <span class="users-info-label">
                    Your Role
                </span>

                <span class="badge badge-<?php echo htmlspecialchars($currentRole); ?>">
                    <?php echo ucfirst($currentRole); ?>
                </span>

            </div>


            <p>

                <?php if ($currentRole === 'admin'): ?>

                    You have full access to manage all user accounts.

                <?php elseif ($currentRole === 'manager'): ?>

                    You can manage regular users only.
                    Administrator and manager accounts are hidden from your view.

                <?php else: ?>

                    You can only view and manage your own profile.

                <?php endif; ?>

            </p>

        </section>


        <!-- User List -->
        <section class="users-section">

            <div class="users-section-header">

                <div>

                    <h2>
                        Users
                    </h2>

                    <p>

                        <?php if ($currentRole === 'admin'): ?>

                            Showing all users

                        <?php elseif ($currentRole === 'manager'): ?>

                            Showing regular users you can manage

                        <?php else: ?>

                            Showing your profile

                        <?php endif; ?>

                    </p>

                </div>


                <div class="users-count">

                    <?php echo count($users); ?>

                    <span>
                        <?php echo count($users) === 1 ? 'user' : 'users'; ?>
                    </span>

                </div>

            </div>


            <?php if (!empty($users)): ?>

                <div class="users-table-wrapper">

                    <table class="users-table">

                        <thead>

                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Verified</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($users as $user): ?>

                                <tr>

                                    <!-- ID -->
                                    <td data-label="ID">

                                        <?php
                                        echo (int) $user['user_id'];
                                        ?>

                                    </td>


                                    <!-- Email -->
                                    <td data-label="Email">

                                        <div class="user-email">

                                            <?php
                                            echo htmlspecialchars(
                                                $user['user_email'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                        </div>

                                    </td>


                                    <!-- Role -->
                                    <td data-label="Role">

                                        <span class="badge badge-<?php echo htmlspecialchars($user['user_role']); ?>">

                                            <?php
                                            echo ucfirst(
                                                htmlspecialchars(
                                                    $user['user_role']
                                                )
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <!-- Verification -->
                                    <td data-label="Verified">

                                        <?php if ((int) $user['user_is_verified'] === 1): ?>

                                            <span class="badge badge-verified">
                                                Verified
                                            </span>

                                        <?php else: ?>

                                            <span class="badge badge-unverified">
                                                Unverified
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Created -->
                                    <td data-label="Created">

                                        <?php
                                        echo date(
                                            'M d, Y H:i',
                                            strtotime(
                                                $user['user_created_at']
                                            )
                                        );
                                        ?>

                                    </td>


                                    <!-- Actions -->
                                    <td data-label="Actions">

                                        <div class="users-actions">

                                            <!-- View -->
                                            <a
                                                href="view.php?user_id=<?php echo (int) $user['user_id']; ?>"
                                                class="users-action users-action-view"
                                            >
                                                View
                                            </a>


                                            <!-- Edit -->
                                            <?php if (
                                                canEdit(
                                                    $user['user_role'],
                                                    $user['user_id']
                                                )
                                            ): ?>

                                                <a
                                                    href="update.php?user_id=<?php echo (int) $user['user_id']; ?>"
                                                    class="users-action users-action-edit"
                                                >
                                                    Edit
                                                </a>

                                            <?php endif; ?>


                                            <!-- Delete -->
                                            <?php if (
                                                canDelete(
                                                    $user['user_role'],
                                                    $user['user_id']
                                                )
                                            ): ?>

                                                <a
                                                    href="delete.php?user_id=<?php echo (int) $user['user_id']; ?>"
                                                    class="users-action users-action-delete"
                                                    onclick="return confirm('Are you sure you want to delete this user?');"
                                                >
                                                    Delete
                                                </a>

                                            <?php elseif (
                                                $user['user_id'] == $currentUserId
                                            ): ?>

                                                <span
                                                    class="users-action users-action-disabled"
                                                    title="You cannot delete your own account"
                                                >
                                                    Delete
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <!-- Empty State -->
                <div class="users-empty">

                    <div class="users-empty-icon">
                        —
                    </div>

                    <h3>
                        No Users Found
                    </h3>

                    <p>
                        No users are available based on your current
                        permissions.
                    </p>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>


<?php include '../../includes/footer.php'; ?>

<?php renderFooter(); ?>