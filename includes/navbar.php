<header class="admin-navbar">
    <div class="admin-navbar-brand">
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">
            Admin Portal
        </a>
    </div>

    <div class="admin-navbar-user">
        <?php echo htmlspecialchars($_SESSION['email']); ?>
    </div>
</header>