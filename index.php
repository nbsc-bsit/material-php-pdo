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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE email = ? AND is_verified = 1"
    );

    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        // Successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Log successful login
        logActivity(
            $pdo,
            $user['id'],
            $user['email'],
            'login',
            'success'
        );

        switch ($user['role']) {
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

    } else {

        // Failed login
        $error = 'Invalid credentials or email not verified';

        // Log failed login attempt
        logActivity(
            $pdo,
            null,
            $email,
            'login',
            'failed'
        );
    }
}

renderHeader('Login');
?>

<link rel="stylesheet" href="assets/css/auth.css">

<main class="auth-page">

    <section class="auth-card">

        <div class="auth-header">

            <div class="auth-logo">
                A
            </div>

            <h1>Welcome back</h1>

            <p>
                Sign in to continue to your account
            </p>

        </div>

        <?php if ($error): ?>

            <div class="auth-alert auth-alert-error" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST" class="auth-form">

            <div class="auth-field">
                <label for="email">
                    Email address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    autocomplete="email"
                    required
                >
            </div>

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

            <button
                type="submit"
                class="auth-button"
            >
                Sign in
            </button>

        </form>

        <div class="auth-demo">

            <div class="auth-demo-title">
                Test Accounts
            </div>

            <p>
                Password:
                <code>password123</code>
            </p>

            <div class="auth-demo-accounts">
                <span>admin@example.com</span>
                <span>manager@example.com</span>
                <span>user@example.com</span>
            </div>

        </div>

    </section>

</main>

<script>
const passwordInput = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');

togglePassword.addEventListener('click', function () {
    const isPassword = passwordInput.type === 'password';

    passwordInput.type = isPassword ? 'text' : 'password';

    this.textContent = isPassword ? 'Hide' : 'Show';
    this.setAttribute(
        'aria-label',
        isPassword ? 'Hide password' : 'Show password'
    );
});
</script>

<?php renderFooter(); ?>

