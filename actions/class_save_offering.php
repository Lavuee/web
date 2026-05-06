<?php
require_once 'auth.php';
check_admin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $section_id = $_POST['section_id'];
    $teacher_id = !empty($_POST['teacher_id']) ? $_POST['teacher_id'] : null;
    $school_year = $_POST['school_year'];

    try {
        // Prevent duplicate offerings for the same subject in the same section for the same year
        $check = $pdo->prepare("SELECT class_id FROM class_offerings WHERE subject_id = ? AND section_id = ? AND school_year = ?");
        $check->execute([$subject_id, $section_id, $school_year]);
        
        if ($check->fetch()) {
            header("Location: ../admin/class_offerings.php?error=Class offering already exists.");
            exit();
        }

        $stmt = $pdo->prepare("
            INSERT INTO class_offerings (subject_id, section_id, teacher_id, school_year) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$subject_id, $section_id, $teacher_id, $school_year]);

        header("Location: ../admin/class_offerings.php?success=Class Offering Created");
        exit();

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}