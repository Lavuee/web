<?php
// Pines Academic Management System - Security Module
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validates active authentication status.
 */
function check_auth() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header("Location: ../login.php?ref=auth");
        exit();
    }
}

/**
 * Restricts module access to administrative personnel.
 */
function check_admin() {
    check_auth();
    if (strtolower($_SESSION['role']) !== 'admin') {
        header("Location: ../login.php?ref=forbidden");
        exit();
    }
}

/**
 * Restricts module access to enrolled students.
 */
function check_student() {
    check_auth();
    if (strtolower($_SESSION['role']) !== 'student') {
        header("Location: ../login.php?ref=forbidden");
        exit();
    }
}

/**
 * Restricts module access to Teachers.
 */
function check_faculty() {
    check_auth();
    if (strtolower($_SESSION['role']) !== 'teacher') {
        header("Location: ../login.php?ref=forbidden");
        exit();
    }
}

/**
 * Restricts module access to registrars.
 */
function check_registrar() {
    check_auth();
    if (strtolower($_SESSION['role']) !== 'registrar') {
        header("Location: ../login.php?ref=forbidden");
        exit();
    }
}

/**
 * Restricts module access to cashiers.
 */
function check_cashier() {
    check_auth();
    if (strtolower($_SESSION['role']) !== 'cashier') {
        header("Location: ../login.php?ref=forbidden");
        exit();
    }
}
?>