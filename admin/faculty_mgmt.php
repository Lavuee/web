<?php
require_once '../actions/auth.php';
check_admin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $dept  = $_POST['department'];
    // Default password is their last name (lowercase)
    $password = password_hash(strtolower($lname), PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 1. Create the User Account
        $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'Faculty')");
        $stmt->execute([$email, $password]);
        $user_id = $pdo->lastInsertId();

        // 2. Create the Faculty Profile (This fixes your error)
        $stmt = $pdo->prepare("INSERT INTO faculty (user_id, first_name, last_name, department) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $fname, $lname, $dept]);

        $pdo->commit();
        header("Location: faculty_mgmt.php?success=Account Created");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}
?>