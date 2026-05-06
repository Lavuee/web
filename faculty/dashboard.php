<?php
require_once '../actions/auth.php';
check_faculty(); // Validates Teacher role[cite: 4]
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

try {
    // Fetch Teacher details
    $stmtT = $pdo->prepare("SELECT * FROM teachers WHERE user_id = ?");
    $stmtT->execute([$user_id]);
    $teacher = $stmtT->fetch();
    $teacher_id = $teacher['teacher_id'];

    // Count assigned classes from class_offerings
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM class_offerings WHERE teacher_id = ?");
    $stmtCount->execute([$teacher_id]);
    $class_count = $stmtCount->fetchColumn();

    // Fetch assigned classes list
    $stmtClasses = $pdo->prepare("
        SELECT co.class_id, s.subject_name, sec.section_name, sec.grade_level
        FROM class_offerings co
        JOIN subjects s ON co.subject_id = s.subject_id
        JOIN sections sec ON co.section_id = sec.section_id
        WHERE co.teacher_id = ?
    ");
    $stmtClasses->execute([$teacher_id]);
    $my_classes = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard | Pines AMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="layout" style="display: flex;">
        <!-- Sidebar logic remains same, adjust links to point to new pages -->
        <?php include 'includes/faculty_sidebar.php'; ?>

        <main class="main-content" style="flex: 1; padding: 30px;">
            <header style="margin-bottom: 30px;">
                <h2>Welcome, Teacher <?= htmlspecialchars($teacher['last_name']) ?>!</h2>
                <p class="text-muted">You are currently handling <?= $class_count ?> active classes.</p>
            </header>

            <div class="glass-panel">
                <h3>My Assigned Classes</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    <?php foreach($my_classes as $class): ?>
                        <div class="glass-panel" style="padding: 20px; border-left: 5px solid var(--primary-color);">
                            <h4 style="margin: 0;"><?= htmlspecialchars($class['subject_name']) ?></h4>
                            <p style="font-size: 0.9rem; margin: 10px 0;">
                                Grade <?= $class['grade_level'] ?> - Section <?= htmlspecialchars($class['section_name']) ?>
                            </p>
                            <a href="manage_grades.php?class_id=<?= $class['class_id'] ?>" class="btn btn-primary" style="font-size: 0.8rem;">
                                Manage Grades
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>