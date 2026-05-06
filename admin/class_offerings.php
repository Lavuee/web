<?php
require_once '../actions/auth.php';
check_admin();
require_once '../config/db.php';

// Fetch Active School Year from settings
$syStmt = $pdo->query("SELECT school_year FROM enrollment_settings LIMIT 1");
$active_sy = $syStmt->fetchColumn() ?: '2025-2026';

try {
    // 1. Fetch existing Class Offerings with joined names for readability
    $stmtOfferings = $pdo->prepare("
        SELECT co.class_id, sub.subject_name, sec.section_name, sec.grade_level, 
               t.first_name, t.last_name, co.school_year
        FROM class_offerings co
        JOIN subjects sub ON co.subject_id = sub.subject_id
        JOIN sections sec ON co.section_id = sec.section_id
        LEFT JOIN teachers t ON co.teacher_id = t.teacher_id
        WHERE co.school_year = ?
        ORDER BY sec.grade_level ASC, sec.section_name ASC
    ");
    $stmtOfferings->execute([$active_sy]);
    $offerings = $stmtOfferings->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Subjects, Sections, and Teachers for the "Add New Class" form
    $subjects = $pdo->query("SELECT * FROM subjects ORDER BY grade_level, subject_name")->fetchAll();
    $sections = $pdo->query("SELECT * FROM sections ORDER BY grade_level, section_name")->fetchAll();
    $teachers = $pdo->query("SELECT * FROM teachers WHERE status = 'Active' ORDER BY last_name")->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

include 'includes/admin_header.php';
?>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <header style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Class Offerings</h2>
                <p class="text-muted">Manage subject assignments for School Year <?= htmlspecialchars($active_sy) ?></p>
            </div>
            <button onclick="document.getElementById('addClassModal').style.display='block'" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Create New Class
            </button>
        </header>

        <!-- Classes Table -->
        <div class="glass-panel">
            <table class="table-wrapper">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Grade</th>
                        <th>Assigned Teacher</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($offerings)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 30px;">No class offerings defined for this year.</td></tr>
                    <?php else: ?>
                        <?php foreach ($offerings as $row): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($row['subject_name']) ?></td>
                            <td><?= htmlspecialchars($row['section_name']) ?></td>
                            <td>Grade <?= htmlspecialchars($row['grade_level']) ?></td>
                            <td><?= $row['first_name'] ? htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
                            <td style="text-align: right;">
                                <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Simple Modal for Adding Classes -->
        <div id="addClassModal" class="glass-panel" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; width: 400px; padding: 30px;">
            <h3 style="margin-bottom: 20px;">Create New Class</h3>
            <form action="../actions/save_class_offering.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-control" required>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?= $s['subject_id'] ?>">[G<?= $s['grade_level'] ?>] <?= $s['subject_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Section</label>
                    <select name="section_id" class="form-control" required>
                        <?php foreach($sections as $sec): ?>
                            <option value="<?= $sec['section_id'] ?>">[G<?= $sec['grade_level'] ?>] Section <?= $sec['section_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Teacher</label>
                    <select name="teacher_id" class="form-control">
                        <option value="">-- Select Teacher (Optional) --</option>
                        <?php foreach($teachers as $t): ?>
                            <option value="<?= $t['teacher_id'] ?>"><?= $t['last_name'] . ', ' . $t['first_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="school_year" value="<?= $active_sy ?>">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Save Class</button>
                    <button type="button" onclick="document.getElementById('addClassModal').style.display='none'" class="btn btn-outline" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>