<?php
require_once 'auth.php';
check_registrar();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)$_POST['student_id'];
    $document_key = $_POST['document_key'];
    $return_to = $_POST['return_to'] ?? 'student_records.php';

    // Whitelist allowed columns to prevent SQL injection
    $allowed_keys = ['psa_birth_cert', 'form_138', 'good_moral'];

    if (in_array($document_key, $allowed_keys) && $student_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET {$document_key} = NULL WHERE student_id = ?");
            $stmt->execute([$student_id]);
        } catch (\PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
    
    header("Location: ../registrar/" . $return_to);
    exit();
} else {
    header("Location: ../registrar/dashboard.php");
    exit();
}
?>