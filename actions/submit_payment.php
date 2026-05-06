<?php
// Secures the endpoint to allow only authenticated student sessions.
require_once '../actions/auth.php';
check_student();

// Initializes the database connection.
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Prevent payment submission if the account is still Pending or Rejected
        $checkStmt = $pdo->prepare("SELECT status FROM enrollments WHERE enrollment_id = :eid");
        $checkStmt->execute([':eid' => $_SESSION['enrollment_id']]);
        $currentStatus = $checkStmt->fetchColumn();
        if (!in_array($currentStatus, ['Assessed', 'Enrolled'])) {
            echo "<script>
                    alert('Payment is only available for Assessed or Enrolled applications.');
                    window.location.href = '../student/assessment.php';
                  </script>";
            exit();
        }

        // Inserts the new payment record with a default 'Pending' status.
        $stmt = $pdo->prepare("
            INSERT INTO payments (enrollment_id, amount, payment_method, reference_no, status) 
            VALUES (:eid, :amt, :method, :ref, 'Pending')
        ");
        
        $stmt->execute([
            ':eid' => $_SESSION['enrollment_id'],
            ':amt' => (float) $_POST['amount'],
            ':method' => $_POST['payment_method'],
            ':ref' => trim($_POST['reference_no'] ?? '')
        ]);
        
        // Redirects the user back to the assessment portal with a success prompt.
        echo "<script>
                alert('Payment submitted successfully. Awaiting administrative verification.');
                window.location.href = '../student/assessment.php';
              </script>";
        exit();

    } catch (\PDOException $e) {
        // Halts execution and displays an error message upon database failure.
        die("Database Error: " . $e->getMessage());
    }
} else {
    // Redirects unauthorized direct access attempts back to the portal.
    header("Location: ../student/assessment.php");
    exit();
}
?>