<?php
require_once 'auth.php';
check_faculty();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sc_id = $_POST['student_class_id'];
    $class_id = $_POST['class_id'];
    $grades = $_POST['grade']; // Array of periods

    try {
        $pdo->beginTransaction();

        $sum = 0;
        $count = 0;

        foreach ($grades as $period => $value) {
            if ($value !== "") {
                // Upsert logic for each quarter
                $stmt = $pdo->prepare("
                    INSERT INTO grades (student_class_id, grading_period, grade_value)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE grade_value = VALUES(grade_value)
                ");
                $stmt->execute([$sc_id, $period, $value]);
                
                $sum += (float)$value;
                $count++;
            }
        }

        // Auto-calculate final if all 4 quarters exist
        if ($count === 4) {
            $final = round($sum / 4, 2);
            $stmtFinal = $pdo->prepare("
                INSERT INTO grades (student_class_id, grading_period, grade_value)
                VALUES (?, 'Final', ?)
                ON DUPLICATE KEY UPDATE grade_value = VALUES(grade_value)
            ");
            $stmtFinal->execute([$sc_id, $final]);
        }

        $pdo->commit();
        header("Location: ../faculty/manage_grades.php?class_id=$class_id&success=1");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error saving grades: " . $e->getMessage());
    }
}