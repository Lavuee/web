<?php
// Secure this action file so only logged-in Admins can run it
require_once '../actions/auth.php';
check_admin();

// Connect to our DB
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // We grab the inputs from the hidden form inside our edit modal
    $enrollment_id = $_POST['enrollment_id'];
    $status = $_POST['status'];
    $total_assessment = (float) $_POST['total_assessment'];

    try {
        if ($status === 'Pending' || $status === 'Rejected') {
            $total_assessment = 0;
            $balance = 0;

            // Auto-reject pending payments so they don't linger on cashier/admin boards
            $stmtRej = $pdo->prepare("UPDATE payments SET status = 'Rejected' WHERE enrollment_id = :id AND status = 'Pending'");
            $stmtRej->execute([':id' => $enrollment_id]);
        } else {
            // Because the Admin might be changing the Total Assessment, we need to recalculate their balance.
            $payStmt = $pdo->prepare("SELECT SUM(amount) as paid FROM payments WHERE enrollment_id = :id AND status = 'Verified'");
            $payStmt->execute([':id' => $enrollment_id]);
            $paid = (float) $payStmt->fetchColumn();

            // Now we calculate the new balance. We prevent negative balances just in case.
            $balance = $total_assessment - $paid;
            if ($balance < 0) {
                $balance = 0;
            }
        }

        // Finally, we update the main enrollments table with the new data
        $stmt = $pdo->prepare("
            UPDATE enrollments 
            SET status = :st, 
                total_assessment = :tot, 
                balance = :bal 
            WHERE enrollment_id = :id
        ");
        
        $stmt->execute([
            ':st' => $status, 
            ':tot' => $total_assessment, 
            ':bal' => $balance, 
            ':id' => $enrollment_id
        ]);

        // Send the Admin right back to the student table so they can see the change immediately
        header("Location: ../admin/students.php");
        exit();

    } catch (\PDOException $e) {
        die("Error updating student record: " . $e->getMessage());
    }
} else {
    // If someone tries to access this file directly via URL without submitting the form, kick them back
    header("Location: ../admin/students.php");
    exit();
}
?>