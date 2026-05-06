<?php
// Terminates active authentication tokens and purges the session.
session_start();
session_unset();
session_destroy();

// Invalidates the session cookie within the browser parameters.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirects traffic back to the authentication portal with a confirmed logout parameter.
header("Location: login.php?logged_out=1");
exit();
?>