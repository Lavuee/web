<?php
// Validates administrative session parameters.
require_once '../actions/auth.php';
check_admin();

// Initializes the database connection.
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Extracts identifiers
    $grade_id = $_POST['grade_id'] ?? null;
    $enrollment_id = $_POST['enrollment_id'];
    $subject_id = $_POST['subject_id'];
    
    // FIX: Admins are not Faculty. We dynamically fetch the actual faculty_id 
    // assigned to this subject from the database, or set it to NULL if unassigned.
    $stmtFac = $pdo->prepare("SELECT faculty_id FROM subjects WHERE subject_id = ?");
    $stmtFac->execute([$subject_id]);
    $faculty_id = $stmtFac->fetchColumn() ?: null;

    // Sanitizes and converts empty strings to null for accurate database storage.
    $q1 = $_POST['q1'] !== '' ? (float)$_POST['q1'] : null;
    $q2 = $_POST['q2'] !== '' ? (float)$_POST['q2'] : null;
    $q3 = $_POST['q3'] !== '' ? (float)$_POST['q3'] : null;
    $q4 = $_POST['q4'] !== '' ? (float)$_POST['q4'] : null;

    $final_grade = null;
    $remarks = 'Incomplete';

    // Computes the final grade and status automatically if all quarterly data is present.
    if ($q1 !== null && $q2 !== null && $q3 !== null && $q4 !== null) {
        $final_grade = round(($q1 + $q2 + $q3 + $q4) / 4, 2);
        $remarks = ($final_grade >= 75) ? 'Passed' : 'Failed';
    }

    try {
        if (empty($grade_id)) {
            // Generates a new academic record for the selected student and subject.
            $stmt = $pdo->prepare("
                INSERT INTO grades (enrollment_id, subject_id, faculty_id, q1, q2, q3, q4, final_grade, remarks)
                VALUES (:eid, :sub_id, :fac_id, :q1, :q2, :q3, :q4, :final, :remarks)
            ");
            $stmt->execute([
                ':eid' => $enrollment_id, ':sub_id' => $subject_id, ':fac_id' => $faculty_id,
                ':q1' => $q1, ':q2' => $q2, ':q3' => $q3, ':q4' => $q4,
                ':final' => $final_grade, ':remarks' => $remarks
            ]);
        } else {
            // Modifies existing academic data for an established record.
            $stmt = $pdo->prepare("
                UPDATE grades 
                SET subject_id = :sub_id, faculty_id = :fac_id, q1 = :q1, q2 = :q2, q3 = :q3, q4 = :q4, final_grade = :final, remarks = :remarks 
                WHERE grade_id = :id
            ");
            $stmt->execute([
                ':sub_id' => $subject_id, ':fac_id' => $faculty_id, ':q1' => $q1, ':q2' => $q2, ':q3' => $q3, ':q4' => $q4,
                ':final' => $final_grade, ':remarks' => $remarks, ':id' => $grade_id
            ]);
        }

        // Returns the user to the management interface upon successful execution.
        header("Location: ../admin/grades.php");
        exit();

    } catch (\PDOException $e) {
        // Logs transaction failures to assist in diagnostics.
        die("Database error: " . $e->getMessage());
    }
}
?>