<?php
require_once '../actions/auth.php';
check_admin();
require_once '../config/db.php';

// 1. Fetch Students who are 'Enrolled' but might need class assignments
try {
    $stmt = $pdo->prepare("
        SELECT e.enrollment_id, s.first_name, s.last_name, s.lrn, 
               e.grade_level, sec.section_name, e.section_id
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN sections sec ON e.section_id = sec.section_id
        WHERE e.enrollment_status = 'Enrolled'
        ORDER BY e.grade_level, sec.section_name
    ");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

include 'includes/admin_header.php';
?>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <header style="margin-bottom: 30px;">
            <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Student Class Assignment</h2>
            <p class="text-muted">Link enrolled students to the subjects offered in their sections.</p>
        </header>

        <div class="glass-panel">
            <table class="table-wrapper">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>LRN</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $row): 
                        // Check if already assigned to any classes
                        $check = $pdo->prepare("SELECT COUNT(*) FROM student_class WHERE enrollment_id = ?");
                        $check->execute([$row['enrollment_id']]);
                        $assigned_count = $check->fetchColumn();
                    ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) ?></td>
                        <td><?= htmlspecialchars($row['lrn']) ?></td>
                        <td>Grade <?= $row['grade_level'] ?> - <?= htmlspecialchars($row['section_name']) ?></td>
                        <td>
                            <?php if ($assigned_count > 0): ?>
                                <span class="status-badge status-approved">Assigned (<?= $assigned_count ?>)</span>
                            <?php else: ?>
                                <span class="status-badge status-pending">No Classes</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <form action="../actions/bulk_assign_classes.php" method="POST" style="display:inline;">
                                <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                <input type="hidden" name="section_id" value="<?= $row['section_id'] ?>">
                                <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;">
                                    Auto-Assign Subjects
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>