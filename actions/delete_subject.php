<?php
require_once 'auth.php';
check_admin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'] ?? '';

    if (!empty($subject_id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
            $stmt->execute([$subject_id]);
            
            header("Location: ../admin/subjects.php?success=" . urlencode("Subject deleted successfully."));
            exit();
        } catch (\PDOException $e) {
            header("Location: ../admin/subjects.php?error=" . urlencode("Database error: " . $e->getMessage()));
            exit();
        }
    } else {
        header("Location: ../admin/subjects.php?error=Missing Subject ID");
        exit();
    }
} else {
    header("Location: ../admin/subjects.php");
    exit();
}
?>