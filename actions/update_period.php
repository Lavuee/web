<?php
// 1. Secure the action for Admins only
require_once 'auth.php';
check_admin();

// 2. Connect to the database
require_once '../config/db.php';

// 3. Check if the form was actually submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Grab the data from the modal's input fields
    $sy_id = $_POST['sy_id'] ?? null;
    $year_string = trim($_POST['year_string'] ?? '');

    // Validate that we have the required information
    if ($sy_id && !empty($year_string)) {
        try {
            // Prepare the SQL statement to update the specific school year
            $stmt = $pdo->prepare("UPDATE school_years SET year_string = ? WHERE school_year_id = ?");
            
            // Execute the update
            $stmt->execute([$year_string, $sy_id]);
            
            // Redirect back to the enrollment periods page upon success
            header("Location: ../admin/enrollment_period.php?msg=Period Updated");
            exit();
            
        } catch (\PDOException $e) {
            // Catch and display any database errors
            die("Database error: " . $e->getMessage());
        }
    } else {
        // Redirect back if the input was empty
        header("Location: ../admin/enrollment_period.php?error=Missing Information");
        exit();
    }
} else {
    // If someone tries to access this file directly via URL, send them back
    header("Location: ../admin/enrollment_period.php");
    exit();
}