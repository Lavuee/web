<?php
require_once 'auth.php';
check_admin();

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and capture inputs
    $subject_id   = $_POST['subject_id'] ?? '';
    $subject_code = trim($_POST['subject_code'] ?? '');
    $subject_name = trim($_POST['subject_name'] ?? '');
    $grade_level  = $_POST['grade_level'] ?? '';
    
    // If faculty_id is empty, set it to NULL for the database
    $faculty_id   = !empty($_POST['faculty_id']) ? $_POST['faculty_id'] : null;

    if (!empty($subject_code) && !empty($subject_name) && !empty($grade_level)) {
        try {
            if (!empty($subject_id)) {
                // UPDATE EXISTING SUBJECT
                $stmt = $pdo->prepare("UPDATE subjects SET subject_code = ?, subject_name = ?, grade_level = ?, faculty_id = ? WHERE subject_id = ?");
                $stmt->execute([$subject_code, $subject_name, $grade_level, $faculty_id, $subject_id]);
                $msg = "Subject updated successfully.";
            } else {
                // INSERT NEW SUBJECT
                $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, grade_level, faculty_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$subject_code, $subject_name, $grade_level, $faculty_id]);
                $msg = "New subject added successfully.";
            }
            
            header("Location: ../admin/subjects.php?success=" . urlencode($msg));
            exit();
            
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                header("Location: ../admin/subjects.php?error=" . urlencode("Subject Code '{$subject_code}' already exists. Please choose a unique code."));
                exit();
            }
            die("Database error: " . $e->getMessage());
        }
    } else {
        header("Location: ../admin/subjects.php?error=Missing Fields");
        exit();
    }
} else {
    header("Location: ../admin/subjects.php");
    exit();
}