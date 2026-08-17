<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="user-navbar">

    <div class="user-navbar-left">

        <button
            type="button"
            class="user-menu-toggle"
            id="userMenuToggle"
            aria-label="Toggle navigation"
        >
            ☰
        </button>

        <a
            href="<?php echo BASE_URL; ?>/app/user/dashboard.php"
            class="user-brand"
        >
            <span class="user-brand-icon">
                ✓
            </span>

            <span class="user-brand-text">
                User Portal
            </span>
        </a>

    </div>


    <div class="user-navbar-right">

        <div class="user-navbar-account">

            <div class="user-navbar-avatar">
                <?php
                echo strtoupper(
                    substr($_SESSION['email'], 0, 1)
                );
                ?>
            </div>

            <div class="user-navbar-user">

                <strong>
                    <?php echo htmlspecialchars($_SESSION['email']); ?>
                </strong>

                <span>
                    User
                </span>

            </div>

        </div>


        <a
            href="<?php echo BASE_URL; ?>/app/auth/signout.php"
            class="user-logout"
        >
            Logout
        </a>

    </div>

</header>