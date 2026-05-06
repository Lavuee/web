<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tracking_input = trim($_POST['enrollment_id'] ?? '');
    $section        = trim($_POST['section'] ?? '');
    $subjects       = $_POST['subjects'] ?? [];

    if (empty($tracking_input) || empty($section)) {
        die("Error: Missing required fields. Please go back and try again.");
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT enrollment_id, tracking_no FROM enrollments WHERE tracking_no = ? LIMIT 1");
        $stmt->execute([$tracking_input]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            $stmt = $pdo->prepare("SELECT enrollment_id, tracking_no FROM enrollments WHERE enrollment_id = ? LIMIT 1");
            $stmt->execute([$tracking_input]);
            $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$enrollment) throw new Exception("Enrollment record not found.");
        }

        $actual_enrollment_id = $enrollment['enrollment_id'];
        $tracking_no = $enrollment['tracking_no']; 

        $updateStmt = $pdo->prepare("UPDATE enrollments SET section = ? WHERE enrollment_id = ?");
        $updateStmt->execute([$section, $actual_enrollment_id]);

        $deleteStmt = $pdo->prepare("DELETE FROM enrollment_subjects WHERE enrollment_id = ?");
        $deleteStmt->execute([$actual_enrollment_id]);

        if (!empty($subjects)) {
            $insertStmt = $pdo->prepare("INSERT INTO enrollment_subjects (enrollment_id, subject_name) VALUES (?, ?)");
            foreach ($subjects as $subject) {
                $insertStmt->execute([$actual_enrollment_id, htmlspecialchars($subject)]);
            }
        }

        $pdo->commit();

        // FIX: Corrected relative path to point back to the root folder
        header("Location: ../track_status.php?tracking_no=" . urlencode($tracking_no));
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("System Error: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit();
}