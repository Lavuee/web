<?php
require_once '../actions/auth.php';
check_faculty();
require_once '../config/db.php';

$class_id = $_GET['class_id'] ?? null;
if (!$class_id) die("Class ID required.");

try {
    // Verify teacher ownership of this class
    $stmtCheck = $pdo->prepare("SELECT co.*, s.subject_name, sec.section_name 
                                FROM class_offerings co 
                                JOIN subjects s ON co.subject_id = s.subject_id
                                JOIN sections sec ON co.section_id = sec.section_id
                                WHERE co.class_id = ? AND co.teacher_id = (SELECT teacher_id FROM teachers WHERE user_id = ?)");
    $stmtCheck->execute([$class_id, $_SESSION['user_id']]);
    $class_info = $stmtCheck->fetch();

    if (!$class_info) die("Unauthorized access to this class.");

    // Fetch students in this class via student_class link
    $stmtStudents = $pdo->prepare("
        SELECT sc.student_class_id, st.first_name, st.last_name, st.lrn
        FROM student_class sc
        JOIN enrollments e ON sc.enrollment_id = e.enrollment_id
        JOIN students st ON e.student_id = st.student_id
        WHERE sc.class_id = ?
        ORDER BY st.last_name ASC
    ");
    $stmtStudents->execute([$class_id]);
    $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grading Sheet | <?= htmlspecialchars($class_info['subject_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div style="padding: 40px; max-width: 1200px; margin: 0 auto;">
        <header style="margin-bottom: 20px;">
            <a href="dashboard.php" class="btn btn-outline">&larr; Back to Dashboard</a>
            <h2 style="margin-top: 20px;"><?= htmlspecialchars($class_info['subject_name']) ?> - Section <?= htmlspecialchars($class_info['section_name']) ?></h2>
        </header>

        <div class="glass-panel">
            <table class="table-wrapper">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>1st Qtr</th>
                        <th>2nd Qtr</th>
                        <th>3rd Qtr</th>
                        <th>4th Qtr</th>
                        <th>Final</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $s): 
                        // Fetch existing grades for this student-class link
                        $stmtG = $pdo->prepare("SELECT grading_period, grade_value FROM grades WHERE student_class_id = ?");
                        $stmtG->execute([$s['student_class_id']]);
                        $grades_raw = $stmtG->fetchAll(PDO::FETCH_KEY_PAIR);
                    ?>
                    <form action="../actions/save_grade_v2.php" method="POST">
                        <input type="hidden" name="student_class_id" value="<?= $s['student_class_id'] ?>">
                        <input type="hidden" name="class_id" value="<?= $class_id ?>">
                        <tr>
                            <td><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?></td>
                            <td><input type="number" name="grade[1st]" value="<?= $grades_raw['1st'] ?? '' ?>" step="0.1" style="width: 60px;"></td>
                            <td><input type="number" name="grade[2nd]" value="<?= $grades_raw['2nd'] ?? '' ?>" step="0.1" style="width: 60px;"></td>
                            <td><input type="number" name="grade[3rd]" value="<?= $grades_raw['3rd'] ?? '' ?>" step="0.1" style="width: 60px;"></td>
                            <td><input type="number" name="grade[4th]" value="<?= $grades_raw['4th'] ?? '' ?>" step="0.1" style="width: 60px;"></td>
                            <td style="font-weight: bold;"><?= $grades_raw['Final'] ?? '-' ?></td>
                            <td><button type="submit" class="btn btn-primary" style="padding: 5px 10px;">Save</button></td>
                        </tr>
                    </form>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>