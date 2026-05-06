<?php
// Enforces standard authentication protocols for student access.
require_once '../actions/auth.php';
check_student();

// Prepares the database connection.
require_once '../config/db.php';

try {
    $stmt = $pdo->prepare("
        SELECT s.*, e.grade_level, e.strand, e.tracking_no, u.email as student_email 
        FROM students s 
        JOIN enrollments e ON s.student_id = e.student_id 
        JOIN users u ON s.user_id = u.user_id
        WHERE e.enrollment_id = :id
    ");
    $stmt->execute([':id' => $_SESSION['enrollment_id']]);
    $student = $stmt->fetch();

    $gradeStmt = $pdo->prepare("
        SELECT g.*, sub.subject_name 
        FROM grades g 
        JOIN subjects sub ON g.subject_id = sub.subject_id 
        WHERE g.enrollment_id = :id 
        ORDER BY sub.subject_name ASC
    ");
    $gradeStmt->execute([':id' => $_SESSION['enrollment_id']]);
    $grades = $gradeStmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Records | Pines NHS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; border-right: 1px solid var(--glass-border); padding: 24px; display: flex; flex-direction: column; background: var(--glass-bg); backdrop-filter: blur(15px); position: sticky; top: 0; height: 100vh; z-index: 50;}
        .main-content { flex: 1; padding: 40px; }
        .nav-link { display: block; padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; color: var(--text-main); font-weight: 500; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; font-size: 0.9rem; }
        .detail-item { margin-bottom: 15px; }
        .detail-label { color: var(--text-muted); font-size: 0.8rem; margin-bottom: 3px; display: block; }
        .table-wrapper { width: 100%; border-collapse: collapse; margin-top: 15px; text-align: left; }
        .table-wrapper th, .table-wrapper td { padding: 15px 10px; border-bottom: 1px solid var(--glass-border); font-size: 0.9rem; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-passed { border: 1px solid var(--glass-border); color: var(--text-main); }
        .badge-failed { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo-container" style="margin-bottom: 40px; display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 1.25rem; color: var(--text-main); letter-spacing: -0.5px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="var(--primary-color)" viewBox="0 0 16 16">
                    <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917z"/>
                    <path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466z"/>
                </svg>
                Pines NHS
            </div>
            <nav style="flex: 1;">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="assessment.php" class="nav-link">Assessment & Payment</a>
                <a href="records.php" class="nav-link active">My Records</a>
            </nav>
            <div style="border-top: 1px solid var(--glass-border); padding-top: 20px; margin-top: auto;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <?php
                        $displayName = $_SESSION['student_name'] ?? 'Student';
                        $initial = strtoupper(substr(trim($displayName), 0, 1));
                    ?>
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--primary-color); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);">
                        <?= $initial ?>
                    </div>
                    <div style="overflow: hidden;">
                        <p style="font-size: 0.85rem; margin: 0 0 3px 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-main);">
                            <?= htmlspecialchars($displayName) ?>
                        </p>
                        <a href="../logout.php" class="text-muted" style="font-size: 0.8rem; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-muted)'">
                            Sign Out &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">My Records</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Review registered demographic information and academic performance.</p>

            <div style="margin-bottom: 45px; border-bottom: 1px solid var(--glass-border); padding-bottom: 25px;">
                <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 20px; color: var(--text-main); text-transform: uppercase;">Personal Information</h3>
                <div class="detail-grid">
                    <div>
                        <div class="detail-item"><span class="detail-label">Full Name</span><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                        <div class="detail-item"><span class="detail-label">Date of Birth</span><?= $student['date_of_birth'] ? date('Y-m-d', strtotime($student['date_of_birth'])) : 'N/A' ?></div>
                    </div>
                    <div>
                        <div class="detail-item"><span class="detail-label">Account Email</span><?= htmlspecialchars($student['student_email']) ?></div>
                        <div class="detail-item"><span class="detail-label">Gender</span><?= htmlspecialchars($student['gender'] ?: 'N/A') ?></div>
                        <div class="detail-item"><span class="detail-label">Grade Level</span><?= htmlspecialchars($student['grade_level']) ?></div>
                    </div>
                    <div>
                        <div class="detail-item"><span class="detail-label">LRN</span><?= htmlspecialchars($student['lrn'] ?: 'N/A') ?></div>
                        <div class="detail-item"><span class="detail-label">Contact Reference</span><?= htmlspecialchars($student['contact_number'] ?: 'N/A') ?></div>
                        <div class="detail-item"><span class="detail-label">Registered Address</span><?= htmlspecialchars($student['address'] ?: 'N/A') ?></div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 50px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0; color: var(--text-main); text-transform: uppercase;">Academic Grades</h3>
                </div>
                <?php if(empty($grades)): ?>
                    <div style="text-align: center; padding: 40px 0; border: 1px dashed var(--glass-border); border-radius: 8px;">
                        <span style="font-size: 2rem; display: block; margin-bottom: 10px; color: var(--text-muted);">📖</span>
                        <p class="text-muted" style="font-size: 0.9rem;">Quarterly assessments are not currently available.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Q1</th>
                                <th>Q2</th>
                                <th>Q3</th>
                                <th>Q4</th>
                                <th>Final Computed</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grades as $row): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($row['subject_name']) ?></td>
                                    <td><?= $row['q1'] ?? '-' ?></td>
                                    <td><?= $row['q2'] ?? '-' ?></td>
                                    <td><?= $row['q3'] ?? '-' ?></td>
                                    <td><?= $row['q4'] ?? '-' ?></td>
                                    <td style="font-weight: 700;"><?= $row['final_grade'] ?? '-' ?></td>
                                    <td>
                                        <?php if($row['remarks']): ?>
                                            <span class="badge badge-<?= strtolower($row['remarks']) ?>"><?= htmlspecialchars($row['remarks']) ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>