<?php
require_once 'auth.php';
check_admin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $year_string = trim($_POST['year_string'] ?? '');
    $semester = $_POST['semester'] ?? 'Full Year';

    if (!empty($year_string)) {
        try {
            // Update the global enrollment settings instead of creating a new row
            // We set enrollment_status to 'Closed' by default so it doesn't interrupt currently active systems
            $stmt = $pdo->prepare("
                UPDATE enrollment_settings 
                SET school_year = ?, semester = ?, enrollment_status = 'Closed' 
                WHERE setting_id = 1
            ");
            $stmt->execute([$year_string, $semester]);
            
            header("Location: ../admin/enrollment_period.php?msg=Period Updated Successfully");
            exit();
            
        } catch (\PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    } else {
        header("Location: ../admin/enrollment_period.php?error=Missing Information");
        exit();
    }
} else {
    header("Location: ../admin/enrollment_period.php");
    exit();
}
?>