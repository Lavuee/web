<?php
require_once '../actions/auth.php';
check_student(); // Restricts access to student role
require_once '../config/db.php';

$lrn = $_SESSION['uid']; // Student UID is their LRN in the new system

try {
    // 1. Fetch current enrollment details to get the correct enrollment_id
    $stmtEnr = $pdo->prepare("SELECT enrollment_id, grade_level, school_year FROM enrollments WHERE student_id = (SELECT student_id FROM students WHERE lrn = ?) AND enrollment_status = 'Enrolled' LIMIT 1");
    $stmtEnr->execute([$lrn]);
    $enrollment = $stmtEnr->fetch(PDO::FETCH_ASSOC);

    $grades_data = [];
    if ($enrollment) {
        // 2. Fetch all subjects and their associated grades via student_class link
        $stmtGrades = $pdo->prepare("
            SELECT s.subject_name, sc.student_class_id
            FROM student_class sc
            JOIN class_offerings co ON sc.class_id = co.class_id
            JOIN subjects s ON co.subject_id = s.subject_id
            WHERE sc.enrollment_id = ?
        ");
        $stmtGrades->execute([$enrollment['enrollment_id']]);
        $subjects = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

        foreach ($subjects as $sub) {
            $stmtG = $pdo->prepare("SELECT grading_period, grade_value FROM grades WHERE student_class_id = ?");
            $stmtG->execute([$sub['student_class_id']]);
            $raw_grades = $stmtG->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $grades_data[] = [
                'subject' => $sub['subject_name'],
                'q1' => $raw_grades['1st'] ?? '-',
                'q2' => $raw_grades['2nd'] ?? '-',
                'q3' => $raw_grades['3rd'] ?? '-',
                'q4' => $raw_grades['4th'] ?? '-',
                'final' => $raw_grades['Final'] ?? '-'
            ];
        }
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

include 'includes/student_header.php';
?>
<body>
<div class="layout" style="display: flex;">
    <?php include 'includes/student_sidebar.php'; ?>
    <main class="main-content" style="flex: 1; padding: 30px;">
        <header style="margin-bottom: 30px;">
            <h2 style="font-size: 1.8rem; margin-bottom: 5px;">My Academic Grades</h2>
            <p class="text-muted">School Year <?= htmlspecialchars($enrollment['school_year'] ?? 'N/A') ?> | Grade <?= htmlspecialchars($enrollment['grade_level'] ?? 'N/A') ?></p>
        </header>

        <div class="glass-panel">
            <table class="table-wrapper">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th style="text-align: center;">1st</th>
                        <th style="text-align: center;">2nd</th>
                        <th style="text-align: center;">3rd</th>
                        <th style="text-align: center;">4th</th>
                        <th style="text-align: center; font-weight: 800;">Final</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grades_data)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px;">No grades encoded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($grades_data as $row): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($row['subject']) ?></td>
                            <td style="text-align: center;"><?= $row['q1'] ?></td>
                            <td style="text-align: center;"><?= $row['q2'] ?></td>
                            <td style="text-align: center;"><?= $row['q3'] ?></td>
                            <td style="text-align: center;"><?= $row['q4'] ?></td>
                            <td style="text-align: center; font-weight: 800; color: var(--primary-color);">
                                <?= $row['final'] ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button onclick="window.print()" class="btn btn-outline">
                <i class="bi bi-printer"></i> Print Report Card
            </button>
        </div>
    </main>
</div>
</body>
</html>