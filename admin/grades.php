<?php
// Secures the module strictly for administrative access.
require_once '../actions/auth.php';
check_admin();

// Establishes the database connection.
require_once '../config/db.php';

try {
    // Constructs a comprehensive query to join grades with student and subject data.
    $stmt = $pdo->query("
        SELECT g.grade_id, g.enrollment_id, g.subject_id, g.q1, g.q2, g.q3, g.q4, g.final_grade, g.remarks,
               s.first_name, s.last_name, 
               u.email AS student_email,
               sub.subject_name
        FROM grades g 
        JOIN enrollments e ON g.enrollment_id = e.enrollment_id 
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        JOIN subjects sub ON g.subject_id = sub.subject_id
        ORDER BY s.last_name ASC, sub.subject_name ASC
    ");
    $grades = $stmt->fetchAll();

    // Retrieves active enrollments to populate the student selection dropdown.
    $studentStmt = $pdo->query("
        SELECT e.enrollment_id, s.first_name, s.last_name, e.grade_level 
        FROM enrollments e 
        JOIN students s ON e.student_id = s.student_id 
        WHERE e.status IN ('Enrolled', 'Assessed')
    ");
    $enrolled_students = $studentStmt->fetchAll();

    // Retrieves the academic catalog to populate the subject selection dropdown.
    $subjectStmt = $pdo->query("SELECT subject_id, subject_name, grade_level FROM subjects ORDER BY subject_name ASC");
    $subjects = $subjectStmt->fetchAll();

} catch (\PDOException $e) {
    // Halts execution upon database failure to maintain system stability.
    die("Database error: " . $e->getMessage());
}

// Badge color mapping for dynamic status styling
$remarksColors = [
    'Passed' => 'background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);',
    'Failed' => 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);'
];

// Include the modular header (Handles Theme persistence and CSS links)
include 'includes/admin_header.php'; 
?>
<body>

    <div class="layout">
        
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="margin-bottom: 5px;">Grade Management</h2>
                    <p class="text-muted" style="font-size: 0.9rem; margin: 0;">Maintain academic records and input quarterly assessments.</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('add')">
                    + Record Grade
                </button>
            </div>

            <div style="margin-bottom: 50px;">
                <div class="table-responsive">
                    <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Q1</th>
                            <th>Q2</th>
                            <th>Q3</th>
                            <th>Q4</th>
                            <th>Final</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($row['student_email']) ?></div>
                                </td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($row['subject_name']) ?></td>
                                <td><?= $row['q1'] ?? '-' ?></td>
                                <td><?= $row['q2'] ?? '-' ?></td>
                                <td><?= $row['q3'] ?? '-' ?></td>
                                <td><?= $row['q4'] ?? '-' ?></td>
                                <td style="font-weight: 700;"><?= $row['final_grade'] ?? '-' ?></td>
                                <td>
                                    <?php if($row['remarks']): ?>
                                        <span class="badge" style="<?= $remarksColors[$row['remarks']] ?? '' ?>">
                                            <?= htmlspecialchars($row['remarks']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" onclick='openModal("edit", <?= json_encode($row) ?>)'>
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($grades)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No academic records have been posted.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </main>
    </div>

    <div id="gradeModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="glass-panel" style="width:100%; max-width:500px; padding:30px; position:relative;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="modalTitle" style="margin: 0;">Record Grade</h3>
                <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
            </div>
            
            <form action="../actions/save_grade.php" method="POST">
                <input type="hidden" name="grade_id" id="modalGradeId">

                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Student Enrollment</label>
                    <select name="enrollment_id" id="modalEnrollmentId" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                        <option value="">Select Student...</option>
                        <?php foreach($enrolled_students as $st): ?>
                            <option value="<?= $st['enrollment_id'] ?>">
                                <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?> (<?= htmlspecialchars($st['grade_level']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Academic Subject</label>
                    <select name="subject_id" id="modalSubjectId" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                        <option value="">Select Subject...</option>
                        <?php foreach($subjects as $sub): ?>
                            <option value="<?= $sub['subject_id'] ?>">
                                <?= htmlspecialchars($sub['subject_name']) ?> (<?= htmlspecialchars($sub['grade_level']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px;">
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Q1</label>
                        <input type="number" step="0.01" name="q1" id="modalQ1" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Q2</label>
                        <input type="number" step="0.01" name="q2" id="modalQ2" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Q3</label>
                        <input type="number" step="0.01" name="q3" id="modalQ3" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Q4</label>
                        <input type="number" step="0.01" name="q4" id="modalQ4" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        const modal = document.getElementById('gradeModal');
        
        function openModal(type, data = null) {
            document.getElementById('modalTitle').innerText = type === 'add' ? 'Record Grade' : 'Modify Grade';
            
            if (type === 'edit') {
                document.getElementById('modalGradeId').value = data.grade_id;
                document.getElementById('modalEnrollmentId').value = data.enrollment_id;
                document.getElementById('modalEnrollmentId').style.pointerEvents = 'none'; 
                document.getElementById('modalEnrollmentId').style.opacity = '0.6';
                document.getElementById('modalSubjectId').value = data.subject_id;
                document.getElementById('modalQ1').value = data.q1;
                document.getElementById('modalQ2').value = data.q2;
                document.getElementById('modalQ3').value = data.q3;
                document.getElementById('modalQ4').value = data.q4;
            } else {
                document.getElementById('modalGradeId').value = '';
                document.getElementById('modalEnrollmentId').value = '';
                document.getElementById('modalEnrollmentId').style.pointerEvents = 'auto';
                document.getElementById('modalEnrollmentId').style.opacity = '1';
                document.getElementById('modalSubjectId').value = '';
                document.getElementById('modalQ1').value = '';
                document.getElementById('modalQ2').value = '';
                document.getElementById('modalQ3').value = '';
                document.getElementById('modalQ4').value = '';
            }
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }
    </script>
</body>
</html>