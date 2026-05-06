<?php
require_once 'auth.php';
check_auth();

// Allow Admin or Registrar to delete records
if (!in_array(strtolower($_SESSION['role']), ['admin', 'registrar'])) {
    header("Location: ../login.php?ref=forbidden");
    exit();
}

require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Changed to student_id to target the exact profile
    $student_id = (int)($_POST['student_id'] ?? 0); 
    $return_to = $_POST['return_to'] ?? '';

    if ($student_id > 0) {
        try {
            // Find user_id and application_id to completely wipe the record cleanly
            $stmt = $pdo->prepare("SELECT user_id, application_id FROM students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();

            if ($student) {
                $pdo->beginTransaction();
                
                // 1. Because of ON DELETE CASCADE, deleting the User will automatically 
                // delete the Student, Enrollments, Student_Classes, and Grades
                if (!empty($student['user_id'])) {
                    $delUser = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $delUser->execute([$student['user_id']]);
                } else {
                    // Fallback if no user account exists
                    $delStudent = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
                    $delStudent->execute([$student_id]);
                }

                // 2. Delete the original application record to prevent orphan data
                if (!empty($student['application_id'])) {
                    $delApp = $pdo->prepare("DELETE FROM applications WHERE application_id = ?");
                    $delApp->execute([$student['application_id']]);
                }
                
                $pdo->commit();
            }
        } catch (\PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            die("Error deleting student: " . $e->getMessage());
        }
    }
    
    if (strtolower($_SESSION['role']) === 'registrar') {
        $redirect = !empty($return_to) ? "../registrar/{$return_to}" : "../registrar/student_records.php";
    } else {
        $redirect = "../admin/students.php";
    }
    
    header("Location: {$redirect}");
    exit();
} else {
    header("Location: ../index.php");
    exit();
}
?>