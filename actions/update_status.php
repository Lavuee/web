<?php
require_once 'auth.php';
check_auth();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enrollment_id = $_POST['enrollment_id'];
    $status = $_POST['status'];
    $total_assessment = (isset($_POST['total_assessment']) && trim($_POST['total_assessment']) !== '') ? (float)$_POST['total_assessment'] : null;
    $return_to = $_POST['return_to'] ?? '';

    try {
        $pdo->beginTransaction();

        if ($status === 'Pending' || $status === 'Rejected') {
            $total_assessment = 0;
            $balance = 0;

            // Clean up any pending payments so they disappear from Cashier queues
            $stmtRej = $pdo->prepare("UPDATE payments SET status = 'Rejected' WHERE enrollment_id = :id AND status = 'Pending'");
            $stmtRej->execute([':id' => $enrollment_id]);
            
            $stmtUpdate = $pdo->prepare("UPDATE enrollments SET status = :status, total_assessment = :tot, balance = :bal WHERE enrollment_id = :id");
            $stmtUpdate->execute([
                ':status' => $status,
                ':tot' => $total_assessment,
                ':bal' => $balance,
                ':id' => $enrollment_id
            ]);
        } elseif ($status === 'Assessed' || $total_assessment !== null) {
            
            // INDUSTRY STANDARD: Auto-calculate fees if manual input is empty or 0
            if (empty($total_assessment)) {
                // 1. Fetch the student's specific grade and strand
                $enrStmt = $pdo->prepare("SELECT grade_level, strand FROM enrollments WHERE enrollment_id = :id");
                $enrStmt->execute([':id' => $enrollment_id]);
                $studentData = $enrStmt->fetch();

                // 2. Query the fee structure matrix
                $feeStmt = $pdo->prepare("
                    SELECT SUM(amount) FROM fee_structures 
                    WHERE (grade_level = :grade OR grade_level = 'All') 
                    AND (strand = :strand OR strand = 'All')
                ");
                $feeStmt->execute([
                    ':grade' => $studentData['grade_level'],
                    ':strand' => $studentData['strand'] ?? 'N/A'
                ]);
                
                $base_fees = (float) $feeStmt->fetchColumn();
                if ($base_fees == 0) {
                    $base_fees = 700; // 500 Misc + 200 Reg fallback
                }

                // Add Subject-based Fees
                $subjectPrices = [
                    'Mathematics'       => 350,
                    'Science'           => 350,
                    'English'           => 300,
                    'Filipino'          => 300,
                    'Araling Panlipunan'=> 300,
                    'MAPEH'             => 400,
                    'TLE'               => 450,
                    'Values Education'  => 250,
                ];
                $subjStmt = $pdo->prepare("SELECT subject_name FROM enrollment_subjects WHERE enrollment_id = :id");
                $subjStmt->execute([':id' => $enrollment_id]);
                $selectedSubjects = $subjStmt->fetchAll(PDO::FETCH_COLUMN);
                $subjectTotal = 0;
                foreach ($selectedSubjects as $subj) {
                    $subjectTotal += $subjectPrices[$subj] ?? 0;
                }
                
                $total_assessment = $base_fees + $subjectTotal;
            }

            // Check paid amount to calculate new balance
            $payStmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE enrollment_id = :id AND status = 'Verified'");
            $payStmt->execute([':id' => $enrollment_id]);
            $paid = (float) $payStmt->fetchColumn();

            $balance = max(0, $total_assessment - $paid);

            $stmtUpdate = $pdo->prepare("UPDATE enrollments SET status = :status, total_assessment = :tot, balance = :bal WHERE enrollment_id = :id");
            $stmtUpdate->execute([
                ':status' => $status,
                ':tot' => $total_assessment,
                ':bal' => $balance,
                ':id' => $enrollment_id
            ]);
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE enrollments SET status = :status WHERE enrollment_id = :id");
            $stmtUpdate->execute([':status' => $status, ':id' => $enrollment_id]);
        }

        $pdo->commit();

        $redirect = !empty($return_to) ? "../registrar/{$return_to}" : "../registrar/student_records.php";
        header("Location: {$redirect}");
        exit();
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>
