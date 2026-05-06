<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function check_auth() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header("Location: " . _login_path() . "?ref=auth");
        exit();
    }
}


function check_admin() {
    check_auth();
    if (strtolower($_SESSION['role']) !== 'admin') {
        header("Location: " . _login_path() . "?ref=forbidden");
        exit();
    }
}


function check_student() {
    check_auth();
    if (strtolower($_SESSION['role']) !== 'student') {
        header("Location: " . _login_path() . "?ref=forbidden");
        exit();
    }
}


function _login_path() {
    $depth = substr_count($_SERVER['PHP_SELF'], '/') - 1;
    return '/pines_ems/login.php'; 
}
?>