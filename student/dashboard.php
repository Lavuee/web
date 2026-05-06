<?php
// Secures the page for student access only using the centralized authentication script.
require_once '../actions/auth.php';
check_student();

// Establishes the database connection.
require_once '../config/db.php';

try {
    // Retrieves the student's enrollment and personal data from the DB
    $stmt = $pdo->prepare("
        SELECT s.first_name, s.last_name, s.lrn, s.psa_birth_cert, s.form_138, s.good_moral,
               e.status, e.total_assessment, e.balance, e.grade_level, e.strand, e.tracking_no, e.section,
               sy.year_string as school_year
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN school_years sy ON e.school_year_id = sy.school_year_id
        WHERE e.enrollment_id = :id
    ");
    $stmt->execute([':id' => $_SESSION['enrollment_id']]);
    $student = $stmt->fetch();

    if (!$student) {
        $student = [
            'first_name' => 'Student', 'last_name' => '', 'lrn' => 'N/A', 'tracking_no' => 'N/A',
            'status' => 'No Enrollment', 'total_assessment' => 0, 'balance' => 0,
            'grade_level' => 'N/A', 'strand' => 'N/A', 'school_year' => 'N/A', 'section' => 'N/A',
            'psa_birth_cert' => null, 'form_138' => null, 'good_moral' => null
        ];
    }
    
    $full_name = trim($student['first_name'] . ' ' . $student['last_name']);

    $subjStmt = $pdo->prepare("SELECT subject_name FROM enrollment_subjects WHERE enrollment_id = :id");
    $subjStmt->execute([':id' => $_SESSION['enrollment_id']]);
    $enrolled_subjects = $subjStmt->fetchAll(PDO::FETCH_COLUMN);
    $has_subjects = count($enrolled_subjects) > 0;

    $db_subjects = [];
    if (!$has_subjects && $student['grade_level'] !== 'N/A') {
        $subStmt = $pdo->prepare("SELECT subject_name FROM subjects WHERE grade_level = ? ORDER BY subject_name ASC");
        $subStmt->execute([$student['grade_level']]);
        $db_subjects = $subStmt->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Pines NHS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; border-right: 1px solid var(--glass-border); padding: 24px; display: flex; flex-direction: column; background: var(--glass-bg); backdrop-filter: blur(15px); position: sticky; top: 0; height: 100vh; z-index: 50;}
        .main-content { flex: 1; padding: 40px; }
        .nav-link { display: block; padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; color: var(--text-main); font-weight: 500; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 0.9rem; }
        .detail-item { margin-bottom: 15px; }
        .detail-label { color: var(--text-muted); font-size: 0.8rem; margin-bottom: 3px; display: block; }
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
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="assessment.php" class="nav-link">Assessment & Payment</a>
                <a href="records.php" class="nav-link">My Records</a>
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
            <h2 style="margin-bottom: 5px;">Welcome, <?= htmlspecialchars($full_name) ?></h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">View your enrollment status and manage your account.</p>

            <?php if (!$has_subjects): ?>
                <?php if ($student['status'] === 'Assessed'): ?>
                    <div style="padding: 25px; border: 1px solid var(--primary-color); border-radius: 8px; margin-bottom: 30px; background: rgba(34, 197, 94, 0.05);">
                        <h3 style="font-size: 1.2rem; margin-bottom: 15px; color: var(--primary-color);">Action Required: Select Subjects & Section</h3>
                        <p class="text-muted" style="margin-bottom: 20px; font-size: 0.9rem;">Your application has been assessed! Please choose your section and verify your subjects to finalize your tuition and unlock the payment portal.</p>
                        <form action="../actions/save_subject_enroll.php" method="POST">
                            <input type="hidden" name="enrollment_id" value="<?= htmlspecialchars($student['tracking_no']) ?>">
                            <input type="hidden" name="source" value="dashboard">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Choose Section *</label>
                                <select name="section" class="form-control" required style="max-width: 400px;">
                                    <option value="">Select a section...</option>
                                    <option value="Section A (Rizal)">Section A (Rizal)</option>
                                    <option value="Section B (Bonifacio)">Section B (Bonifacio)</option>
                                    <option value="Section C (Mabini)">Section C (Mabini)</option>
                                    <option value="Section D (Luna)">Section D (Luna)</option>
                                </select>
                            </div>

                            <label class="form-label" style="display: block; font-weight: 600; margin-bottom: 8px;">Select Subjects</label>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; margin-bottom: 25px;">
                                <?php if (empty($db_subjects)): ?>
                                    <p class="text-muted" style="grid-column: 1 / -1; margin: 10px 0;">No subjects configured for this grade level yet.</p>
                                <?php else: ?>
                                    <?php foreach ($db_subjects as $subj): ?>
                                        <label style="display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid var(--glass-border); border-radius: 8px; cursor: pointer; background: rgba(100, 116, 139, 0.05);">
                                            <input type="checkbox" name="subjects[]" value="<?= htmlspecialchars($subj) ?>" checked style="width: 16px; height: 16px; accent-color: var(--primary-color);"> 
                                            <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-main);"><?= htmlspecialchars($subj) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Save Subjects</button>
                        </form>
                    </div>
                <?php elseif ($student['status'] === 'Pending'): ?>
                    <div style="padding: 25px; border: 1px dashed var(--glass-border); border-radius: 8px; margin-bottom: 30px; background: rgba(100, 116, 139, 0.05);">
                        <h3 style="font-size: 1.2rem; margin-bottom: 15px; color: var(--text-main);">Subject Selection Locked</h3>
                        <p class="text-muted" style="margin-bottom: 0; font-size: 0.9rem;">You will be able to select your subjects and section once your application has been officially assessed by the Registrar. Please check back later.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 45px; border-bottom: 1px solid var(--glass-border); padding-bottom: 25px;">
                <div style="flex: 1; min-width: 200px;">
                    <div>
                        <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin: 0;">Enrollment Status</p>
                        <h3 style="font-size: 2.8rem; margin: 5px 0 0 0; font-weight: 300; color: var(--primary-color);"><?= htmlspecialchars($student['status']) ?></h3>
                    </div>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <div>
                        <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin: 0;">Total Assessment</p>
                        <h3 style="font-size: 2.8rem; margin: 5px 0 0 0; font-weight: 300; color: var(--text-main);">₱<?= number_format($student['total_assessment'], 2) ?></h3>
                    </div>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <div>
                        <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin: 0;">Balance</p>
                        <h3 style="font-size: 2.8rem; margin: 5px 0 0 0; font-weight: 300; color: #ef4444;">₱<?= number_format($student['balance'], 2) ?></h3>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 50px;">
                <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 20px; color: var(--text-main); text-transform: uppercase;">Enrollment Details</h3>
                <div class="detail-grid">
                    <div>
                        <div class="detail-item"><span class="detail-label">Full Name</span><?= htmlspecialchars($full_name) ?></div>
                        <div class="detail-item"><span class="detail-label">Student ID</span><span style="font-family: monospace;"><?= htmlspecialchars($student['tracking_no']) ?></span></div>
                        <div class="detail-item"><span class="detail-label">LRN</span><?= htmlspecialchars($student['lrn'] ?: 'N/A') ?></div>
                    </div>
                    <div>
                        <div class="detail-item"><span class="detail-label">Grade Level</span><?= htmlspecialchars($student['grade_level']) ?></div>
                        <div class="detail-item"><span class="detail-label">Section</span><?= htmlspecialchars($student['section'] ?: 'Not assigned yet') ?></div>
                        <div class="detail-item"><span class="detail-label">Status</span><?= htmlspecialchars($student['status']) ?></div>
                    </div>
                    <div>
                        <div class="detail-item"><span class="detail-label">School Year</span><?= htmlspecialchars($student['school_year']) ?></div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 50px;">
                <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 20px; color: var(--text-main); text-transform: uppercase;">Document Status</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid var(--glass-border); border-radius: 8px; background: rgba(100, 116, 139, 0.05);">
                        <span style="font-weight: 500; color: var(--text-main);">PSA Birth Certificate</span>
                        <?php if (!empty($student['psa_birth_cert'])): ?>
                            <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);">Submitted</span>
                        <?php else: ?>
                            <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Missing</span>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid var(--glass-border); border-radius: 8px; background: rgba(100, 116, 139, 0.05);">
                        <span style="font-weight: 500; color: var(--text-main);">Form 138 (Report Card)</span>
                        <?php if (!empty($student['form_138'])): ?>
                            <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);">Submitted</span>
                        <?php else: ?>
                            <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Missing</span>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid var(--glass-border); border-radius: 8px; background: rgba(100, 116, 139, 0.05);">
                        <span style="font-weight: 500; color: var(--text-main);">Certificate of Good Moral</span>
                        <?php if (!empty($student['good_moral'])): ?>
                            <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);">Submitted</span>
                        <?php else: ?>
                            <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Missing</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>