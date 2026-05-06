<?php
require_once '../config/db.php';

// Capture the tracking number from the URL
$tracking_no = isset($_GET['tracking_no']) ? trim($_GET['tracking_no']) : '';
$dob = isset($_GET['dob']) ? trim($_GET['dob']) : '';
$student = null;
$error = null;

if (!empty($tracking_no) && !empty($dob)) {
    try {
        // Corrected Query: Searching by tracking_no and joining the students table
        $stmt = $pdo->prepare("
            SELECT e.tracking_no, e.status, e.grade_level, e.section, s.date_of_birth,
                   s.first_name, s.last_name 
            FROM enrollments e
            JOIN students s ON e.student_id = s.student_id
            WHERE e.tracking_no = :tracking_no AND s.date_of_birth = :dob
        ");
        $stmt->execute([':tracking_no' => $tracking_no, ':dob' => $dob]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
} elseif (!empty($tracking_no) && empty($dob)) {
    $error = "Please provide your Date of Birth for security verification.";
}

// Universal Badge Colors (Matches Admin Dashboard)
$badgeColors = [
    'Pending'  => 'background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);',
    'Assessed' => 'background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);',
    'Enrolled' => 'background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);',
    'Rejected' => 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Enrollment Status | Pines NHS</title>
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <script>
        if (localStorage.getItem('theme') === 'dark' || 
           (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    </script>

    <style>
        .tracker-container { max-width: 600px; width: 100%; margin: 0 auto; padding: 40px 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-top: 25px; text-align: left; }
        .info-label { color: var(--text-muted); font-size: 0.95rem; font-weight: 500; }
        .info-value { font-weight: 600; color: var(--text-main); }
        
        .form-control {
            width: 100%; padding: 12px; border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--bg-color); color: var(--text-main);
            transition: all 0.3s ease;
        }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.15); }
    </style>
</head>
<body style="display: flex; flex-direction: column; align-items: center; min-height: 100vh;">

    <div class="theme-switch-wrapper" style="position: absolute; top: 20px; right: 20px; margin-bottom: 0;" title="Toggle Theme">
        <label class="theme-switch">
            <input type="checkbox" id="theme-toggle-checkbox" onchange="toggleTheme()">
            <span class="slider"></span>
        </label>
    </div>

    <div class="tracker-container">
        <div style="margin-bottom: 30px;">
            <a href="../index.php" class="btn btn-outline" style="padding: 8px 16px;">&larr; Back to Home</a>
        </div>

        <h2 style="font-size: 1.8rem; margin-bottom: 5px; color: var(--text-main);">Track Status</h2>
        <p class="text-muted" style="margin-bottom: 30px; font-size: 0.95rem;">Enter your unique tracking number to view your application progress.</p>

        <?php if ($error): ?>
            <div class="glass-panel" style="padding: 15px; color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.1); text-align: left; margin-bottom: 20px; border-radius: 8px;">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="GET" action="track_status.php" style="display: flex; gap: 10px; margin-bottom: 40px; flex-wrap: wrap;">
            <input type="text" name="tracking_no" class="form-control" placeholder="Tracking ID (e.g., ENR-2026-...)" value="<?php echo htmlspecialchars($tracking_no); ?>" required style="flex: 2; min-width: 200px;">
            <input type="date" name="dob" class="form-control" title="Date of Birth" value="<?php echo htmlspecialchars($dob); ?>" required style="flex: 1; min-width: 150px;">
            <button type="submit" class="btn btn-primary" style="padding: 0 25px; white-space: nowrap;">Check Status</button>
        </form>

        <?php if (!empty($tracking_no) && !empty($dob) && $student): ?>
            <div class="glass-panel" style="padding: 30px; text-align: center;">
                
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px;">Current Status</p>
                <div style="margin-bottom: 25px;">
                    <span class="badge" style="font-size: 1.1rem; padding: 8px 20px; <?= $badgeColors[$student['status']] ?? '' ?>">
                        <?= htmlspecialchars($student['status']) ?>
                    </span>
                </div>

                <hr style="border: 0; height: 1px; background: var(--glass-border); margin: 20px 0;">

                <div class="info-grid">
                    <div class="info-label">Applicant Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                    
                    <div class="info-label">Tracking No:</div>
                    <div class="info-value" style="font-family: monospace; font-size: 1.05rem;"><?php echo htmlspecialchars($student['tracking_no']); ?></div>
                    
                    <div class="info-label">Grade Level:</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['grade_level']); ?></div>
                    
                    <div class="info-label">Assigned Section:</div>
                    <div class="info-value">
                        <?php echo !empty($student['section']) ? htmlspecialchars($student['section']) : '<span style="color: var(--text-muted); font-weight: normal;">Not assigned yet</span>'; ?>
                    </div>
                </div>
                
                <?php if ($student['status'] === 'Assessed'): ?>
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--glass-border);">
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 15px;">Your application has been assessed. Please log in to your portal to complete your enrollment.</p>
                        <a href="login.php" class="btn btn-primary" style="width: 100%; padding: 12px;">Go to Student Portal &rarr;</a>
                    </div>
                <?php endif; ?>

            </div>
        <?php elseif (!empty($tracking_no) && !empty($dob)): ?>
            <div class="glass-panel" style="padding: 25px; text-align: center;">
                <h3 style="font-size: 1.1rem; margin-bottom: 5px; color: var(--text-main);">Access Denied</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">The Tracking Number or Date of Birth is incorrect. Please try again.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const checkbox = document.getElementById('theme-toggle-checkbox');
            
            if (html.hasAttribute('data-theme')) {
                html.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                if (checkbox) checkbox.checked = false;
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                if (checkbox) checkbox.checked = true;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const checkbox = document.getElementById('theme-toggle-checkbox');
            if (localStorage.getItem('theme') === 'dark' || 
               (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                if (checkbox) checkbox.checked = true;
            }
        });
    </script>
</body>
</html>