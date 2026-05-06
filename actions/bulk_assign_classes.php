<?php
require_once 'auth.php';
check_admin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_id = $_POST['enrollment_id'];
    $section_id = $_POST['section_id'];

    try {
        $pdo->beginTransaction();

        // 1. Get all Class Offerings for this specific section
        $stmtOfferings = $pdo->prepare("SELECT class_id FROM class_offerings WHERE section_id = ?");
        $stmtOfferings->execute([$section_id]);
        $offerings = $stmtOfferings->fetchAll(PDO::FETCH_COLUMN);

        if (empty($offerings)) {
            header("Location: ../admin/assign_classes.php?error=No subjects have been offered for this section yet.");
            exit();
        }

        // 2. Map the student to each offering
        $stmtAssign = $pdo->prepare("
            INSERT IGNORE INTO student_class (enrollment_id, class_id) 
            VALUES (?, ?)
        ");

        foreach ($offerings as $class_id) {
            $stmtAssign->execute([$enrollment_id, $class_id]);
        }

        $pdo->commit();
        header("Location: ../admin/assign_classes.php?success=Student successfully assigned to " . count($offerings) . " classes.");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database Error: " . $e->getMessage());
    }
}