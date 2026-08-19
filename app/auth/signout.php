<?php

require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';


// ==================================================
// LOG LOGOUT
// ==================================================

if (
    isset($_SESSION['user_id']) &&
    isset($_SESSION['email'])
) {

    logActivity(
        $pdo,
        $_SESSION['user_id'],
        $_SESSION['email'],
        'logout',
        'success'
    );
}


// ==================================================
// DESTROY SESSION
// ==================================================

$_SESSION = [];

session_destroy();


// ==================================================
// REDIRECT TO LOGIN
// ==================================================

redirect('/index.php');

?>