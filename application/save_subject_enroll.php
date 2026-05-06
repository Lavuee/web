<?php
// Establish database connection
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Capture and sanitize the form inputs
    // Note: The hidden input is named 'enrollment_id', but it is actually carrying the Tracking Number (e.g., ENR-2026-...) based on your UI.
    $tracking_input = trim($_POST['enrollment_id'] ?? ''); 
    $section        = trim($_POST['section'] ?? '');
    $subjects       = $_POST['subjects'] ?? [];

    // Basic validation
    if (empty($tracking_input) || empty($section)) {
        die("Error: Missing required fields. Please go back and try again.");
    }

    try {
        // Begin a database transaction to ensure both the section and subjects save safely together
        $pdo->beginTransaction();

        // 2. Look up the actual internal 'enrollment_id' using the provided Tracking Number
        $stmt = $pdo->prepare("SELECT enrollment_id, tracking_no, grade_level, strand FROM enrollments WHERE tracking_no = ? LIMIT 1");
        $stmt->execute([$tracking_input]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fallback: Just in case the hidden field was actually the numeric ID, we check for it here
        if (!$enrollment) {
            $stmt = $pdo->prepare("SELECT enrollment_id, tracking_no, grade_level, strand FROM enrollments WHERE enrollment_id = ? LIMIT 1");
            $stmt->execute([$tracking_input]);
            $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$enrollment) {
                throw new Exception("Enrollment record not found. The tracking number may be invalid.");
            }
        }

        $actual_enrollment_id = $enrollment['enrollment_id'];
        $tracking_no = $enrollment['tracking_no']; // Secure the correct tracking number for the redirect

        // 4. Clear any previously saved subjects (Prevents duplicates if the student clicks 'Back' and submits again)
        $deleteStmt = $pdo->prepare("DELETE FROM enrollment_subjects WHERE enrollment_id = ?");
        $deleteStmt->execute([$actual_enrollment_id]);

        // Subject pricing
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
        $subjectTotal = 0;

        // 5. Insert all checked subjects into the Enrollment_Subjects table
        if (!empty($subjects)) {
            $insertStmt = $pdo->prepare("INSERT INTO enrollment_subjects (enrollment_id, subject_name) VALUES (?, ?)");
            foreach ($subjects as $subject) {
                $cleanSubj = htmlspecialchars($subject);
                $insertStmt->execute([$actual_enrollment_id, $cleanSubj]);
                $subjectTotal += $subjectPrices[$cleanSubj] ?? 0;
            }
        }

        // Calculate base tuition from fee structures
        $feeStmt = $pdo->prepare("
            SELECT SUM(amount) FROM fee_structures 
            WHERE (grade_level = :grade OR grade_level = 'All') 
            AND (strand = :strand OR strand = 'All')
        ");
        $feeStmt->execute([
            ':grade' => $enrollment['grade_level'],
            ':strand' => $enrollment['strand'] ?? 'N/A'
        ]);
        $base_fees = (float) $feeStmt->fetchColumn();
        $total_assessment = ($base_fees > 0 ? $base_fees : 700) + $subjectTotal;

        // 3. Update the 'section' and assessment columns in the main enrollments table
        $updateStmt = $pdo->prepare("UPDATE enrollments SET section = ?, total_assessment = ?, balance = ? WHERE enrollment_id = ?");
        $updateStmt->execute([$section, $total_assessment, $total_assessment, $actual_enrollment_id]);

        // Commit the transaction (Saves everything permanently)
        $pdo->commit();

        // 6. Redirect the student smoothly to the Tracker page using their tracking number
        header("Location: ../payment.php?tracking_no=" . urlencode($tracking_no));
        exit();

    } catch (Exception $e) {
        // If anything fails, rollback the database to prevent partial saves
        $pdo->rollBack();
        die("System Error: " . $e->getMessage());
    }
} else {
    // If someone tries to visit this file directly without submitting the form, send them away
    header("Location: index.php");
    exit();
}