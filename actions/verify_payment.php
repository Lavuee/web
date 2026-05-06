<?php
require_once '../actions/auth.php';
check_auth();

// Allow Admin or Cashier to verify payments
if (!in_array(strtolower($_SESSION['role']), ['admin', 'cashier'])) {
    header("Location: ../login.php?ref=forbidden");
    exit();
}

// Initializes the database connection.
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $payment_id = $_POST['payment_id'];
    $enrollment_id = $_POST['enrollment_id'];
    $action = $_POST['action']; 
    $return_to = $_POST['return_to'] ?? '';
    $cashier_id = $_SESSION['user_id']; 

    try {
        // Initiates a database transaction to ensure data integrity.
        $pdo->beginTransaction();

        if ($action === 'Approve') {
            $status = 'Verified';
        } elseif ($action === 'Revert') {
            $status = 'Pending';
        } else {
            $status = 'Rejected';
        }
        
        // Updates the transaction status and logs the reviewing cashier.
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = :st, cashier_id = :cashier 
            WHERE payment_id = :id
        ");
        $stmt->execute([
            ':st' => $status, 
            ':cashier' => $cashier_id, 
            ':id' => $payment_id
        ]);

        // Always recalculate financial balance to ensure total synchronization
        // Retrieves the base assessment for the enrollment.
        $fetchStmt = $pdo->prepare("SELECT total_assessment FROM enrollments WHERE enrollment_id = :eid");
        $fetchStmt->execute([':eid' => $enrollment_id]);
        $total = (float) $fetchStmt->fetchColumn();

        // Sums all successfully verified payments for this enrollment.
        $payStmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE enrollment_id = :eid AND status = 'Verified'");
        $payStmt->execute([':eid' => $enrollment_id]);
        $paid = (float) $payStmt->fetchColumn();

        // Calculates the remaining balance, ensuring it does not drop below zero.
        $new_balance = $total - $paid;
        if ($new_balance < 0) {
            $new_balance = 0; 
        }
        
        // Automatically elevates the enrollment status to 'Enrolled' upon any partial or full payment.
        $new_status = ($paid > 0) ? 'Enrolled' : 'Assessed';

        // Applies the recalculated financial data to the enrollment record.
        $updateEnr = $pdo->prepare("
            UPDATE enrollments 
            SET balance = :bal, status = :st 
            WHERE enrollment_id = :eid
        ");
        $updateEnr->execute([
            ':bal' => $new_balance, 
            ':st' => $new_status, 
            ':eid' => $enrollment_id
        ]);

        // Commits the transaction and redirects the administrator.
        $pdo->commit();
        
        // If approving a payment, automatically show the printable receipt
        if ($action === 'Approve') {
            header("Location: ../cashier/print_receipt.php?id=" . $payment_id);
            exit();
        }
        
        if (strtolower($_SESSION['role']) === 'admin') {
            $redirect = !empty($return_to) ? "../admin/{$return_to}" : "../admin/payments.php";
        } else {
            $redirect = !empty($return_to) ? "../cashier/{$return_to}" : "../cashier/dashboard.php";
        }
        header("Location: {$redirect}");
        exit();

    } catch (\PDOException $e) {
        // Rolls back all changes to prevent partial data updates.
        $pdo->rollBack();
        die("Transaction Error: " . $e->getMessage());
    }
} else {
    // Redirects anomalous direct access attempts.
    if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin') {
        header("Location: ../admin/payments.php");
    } else {
        header("Location: ../cashier/dashboard.php");
    }
    exit();
}
?>