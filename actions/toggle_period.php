<?php
require_once 'auth.php';
check_admin();
require_once '../config/db.php';

if (isset($_GET['id'])) {
    $sy_id = $_GET['id'];

    try {
        $pdo->beginTransaction();

        // 1. Deactivate all existing periods
        $pdo->query("UPDATE school_years SET is_active = 0");

        // 2. Activate the selected period
        $stmt = $pdo->prepare("UPDATE school_years SET is_active = 1 WHERE school_year_id = ?");
        $stmt->execute([$sy_id]);

        $pdo->commit();
        header("Location: ../admin/enrollment_period.php?msg=Period Activated");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}