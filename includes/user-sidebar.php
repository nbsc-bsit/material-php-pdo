<aside class="user-sidebar" id="userSidebar">

    <nav class="user-sidebar-nav">

        <span class="user-nav-label">
            MAIN
        </span>


        <a
            href="<?php echo BASE_URL; ?>/app/user/dashboard.php"
            class="user-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>"
        >

            <span class="user-nav-icon">
                ▦
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <span class="user-nav-label">
            ACCOUNT
        </span>


        <a
            href="<?php echo BASE_URL; ?>/app/user/profile.php"
            class="user-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>"
        >

            <span class="user-nav-icon">
                👤
            </span>

            <span>
                My Profile
            </span>

        </a>


        <a
            href="<?php echo BASE_URL; ?>/app/user/activity.php"
            class="user-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'activity.php' ? 'active' : ''; ?>"
        >

            <span class="user-nav-icon">
                ✓
            </span>

            <span>
                Activity History
            </span>

        </a>


        <a
            href="<?php echo BASE_URL; ?>/app/user/change-password.php"
            class="user-nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'change-password.php' ? 'active' : ''; ?>"
        >

            <span class="user-nav-icon">
                🔒
            </span>

            <span>
                Change Password
            </span>

        </a>

    </nav>


    <div class="user-sidebar-bottom">

        <div class="user-sidebar-status">

            <span class="user-status-dot"></span>

            <div>

                <strong>
                    Account Active
                </strong>

                <small>
                    Your account is secure
                </small>

            </div>

        </div>

    </div>

</aside>