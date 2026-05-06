<?php
require_once 'auth.php';
check_auth();

// Only Admin or Cashier can log direct payments
if (!in_array(strtolower($_SESSION['role']), ['admin', 'cashier'])) {
    header("Location: ../login.php?ref=forbidden");
    exit();
}

require_once '../config/db.php';
require_once 'mailer.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enrollment_id = $_POST['enrollment_id'];
    $amount = (float) $_POST['amount'];
    $method = $_POST['payment_method'];
    $cashier_id = $_SESSION['user_id'];
    $official_receipt_no = !empty($_POST['official_receipt_no']) ? trim($_POST['official_receipt_no']) : null;

    try {
        $pdo->beginTransaction();

        // 1. Insert as 'Verified' payment
        $stmt = $pdo->prepare("
            INSERT INTO payments (enrollment_id, amount, payment_method, status, cashier_id, official_receipt_no) 
            VALUES (:eid, :amt, :method, 'Verified', :cashier, :or_no)
        ");
        $stmt->execute([
            ':eid' => $enrollment_id,
            ':amt' => $amount,
            ':method' => $method,
            ':cashier' => $cashier_id,
            ':or_no' => $official_receipt_no
        ]);
        $payment_id = $pdo->lastInsertId();

        // 2. Update the student's Enrollment record to 'Enrolled' since a payment was made
        $updateEnr = $pdo->prepare("UPDATE enrollments SET enrollment_status = 'Enrolled' WHERE enrollment_id = :eid");
        $updateEnr->execute([':eid' => $enrollment_id]);

        // 3. Send Email Notification
        $stmtDetails = $pdo->prepare("
            SELECT s.first_name, s.lrn, u.uid
            FROM enrollments e
            JOIN students s ON e.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            WHERE e.enrollment_id = :eid
        ");
        $stmtDetails->execute([':eid' => $enrollment_id]);
        $studentDetails = $stmtDetails->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();
        
        header("Location: ../cashier/print_receipt.php?id=" . $payment_id);
        exit();
    } catch (\PDOException $e) {
        $pdo->rollBack();
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../cashier/dashboard.php");
    exit();
}
?>