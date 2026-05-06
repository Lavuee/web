<?php
require_once 'config/db.php';
$tracking_no = $_GET['enrollment_id'] ?? '';
$grade_level = '';
if ($tracking_no) {
    $stmt = $pdo->prepare("SELECT grade_level FROM enrollments WHERE tracking_no = ? LIMIT 1");
    $stmt->execute([$tracking_no]);
    $grade_level = $stmt->fetchColumn();
}

$db_subjects = [];
if ($grade_level) {
    $subStmt = $pdo->prepare("SELECT subject_name FROM subjects WHERE grade_level = ? ORDER BY subject_name ASC");
    $subStmt->execute([$grade_level]);
    $db_subjects = $subStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Subjects & Section | Pines NHS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-section { padding: 25px; margin-bottom: 20px; text-align: left; }
        .form-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 15px; }
        label { font-size: 0.9rem; font-weight: 500; color: var(--text-main); }
        select {
            width: 100%; padding: 10px; border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: transparent; color: var(--text-main);
        }
        .subject-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        .subject-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        input[type="checkbox"] {
            width: 18px; height: 18px;
        }
    </style>
</head>
<body style="align-items: center; padding: 40px 20px;">

    <div style="max-width: 600px; width: 100%;">
        <h2 style="margin-bottom: 5px;">Select Subjects & Section</h2>
        <p class="text-muted" style="margin-bottom: 20px;">Tracking ID: <strong><?php echo htmlspecialchars($_GET['enrollment_id'] ?? ''); ?></strong></p>

        <form action="actions/save_subjects.php" method="POST">
            <input type="hidden" name="enrollment_id" value="<?php echo htmlspecialchars($_GET['enrollment_id'] ?? ''); ?>">

            <div class="glass-panel form-section">
                <div class="form-group">
                    <label>Choose Section</label>
                    <select name="section" required>
                        <option value="">Select a section...</option>
                        <option value="Section A (Rizal)">Section A (Rizal)</option>
                        <option value="Section B (Bonifacio)">Section B (Bonifacio)</option>
                        <option value="Section C (Mabini)">Section C (Mabini)</option>
                        <option value="Section D (Luna)">Section D (Luna)</option>
                    </select>
                </div>
            </div>

            <div class="glass-panel form-section">
                <h3 style="font-size: 1.1rem; margin-bottom: 10px;">Select Subjects</h3>
                <div class="subject-grid">
                    <?php if (empty($db_subjects)): ?>
                        <p class="text-muted" style="grid-column: 1 / -1; margin: 10px 0;">No subjects configured for this grade level yet.</p>
                    <?php else: ?>
                        <?php foreach ($db_subjects as $subj): ?>
                            <label class="subject-item"><input type="checkbox" name="subjects[]" value="<?= htmlspecialchars($subj) ?>" checked> <?= htmlspecialchars($subj) ?></label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem;">Save & View Tracking</button>
        </form>
    </div>

</body>
</html>
