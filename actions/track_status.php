<?php
require_once 'config/db.php';

$tracking_no = isset($_GET['tracking_no']) ? trim(htmlspecialchars($_GET['tracking_no'])) : '';
$application = null;
$error = null;

if (!empty($tracking_no)) {
    try {
        // Look up the application in the new isolated applications table
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE tracking_no = ? LIMIT 1");
        $stmt->execute([$tracking_no]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            $error = "Tracking number not found. Please double-check your entry.";
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Application | Pines NHS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .status-card { padding: 40px; text-align: center; background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 16px; margin-top: 20px; }
        .status-badge { display: inline-block; padding: 8px 20px; border-radius: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem; margin-bottom: 20px; }
        .status-pending { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.4); }
        .status-approved { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.4); }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4); }
    </style>
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">

    <div style="width: 100%; max-width: 500px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="font-size: 1.8rem; margin-bottom: 10px;">Application Tracker</h1>
            <p class="text-muted">Enter your tracking number to see your admission status.</p>
        </div>

        <div class="glass-panel" style="padding: 30px;">
            <form action="track_status.php" method="GET" style="margin-bottom: 20px;">
                <div class="form-group" style="display: flex; gap: 10px;">
                    <input type="text" name="tracking_no" class="form-control" 
                           placeholder="e.g. APP-2026-ABCDE" 
                           value="<?= htmlspecialchars($tracking_no) ?>" required>
                    <button type="submit" class="btn btn-primary">Track</button>
                </div>
            </form>

            <?php if ($error): ?>
                <div style="color: #ef4444; background: rgba(239,68,68,0.1); padding: 12px; border-radius: 8px; font-size: 0.9rem;">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($application): ?>
                <div class="status-card">
                    <?php 
                    $status = $application['status'];
                    $badgeClass = 'status-' . strtolower($status);
                    ?>
                    <div class="status-badge <?= $badgeClass ?>"><?= $status ?></div>
                    
                    <h2 style="font-size: 1.4rem; margin-bottom: 10px;">
                        <?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?>
                    </h2>
                    <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 25px;">
                        Applying for Grade <?= htmlspecialchars($application['grade_applying_for']) ?>
                    </p>

                    <div style="text-align: left; background: rgba(0,0,0,0.05); padding: 20px; border-radius: 12px; font-size: 0.85rem; line-height: 1.6;">
                        <?php if ($status === 'Pending'): ?>
                            <strong>Next Steps:</strong>
                            <ul style="padding-left: 20px; margin-top: 10px;">
                                <li>The Registrar is currently reviewing your uploaded documents.</li>
                                <li>Review typically takes 2-3 working days.</li>
                                <li>Keep this tracking number safe.</li>
                            </ul>
                        <?php elseif ($status === 'Approved'): ?>
                            <strong style="color: #10b981;">Congratulations!</strong>
                            <p style="margin-top: 10px;">Your application has been approved. You may now proceed to the campus for final verification or check your email for your **Student Portal credentials**.</p>
                            <a href="login.php" class="btn btn-primary" style="width: 100%; margin-top: 15px;">Go to Portal</a>
                        <?php else: ?>
                            <strong style="color: #ef4444;">Application Unsuccessful</strong>
                            <p style="margin-top: 10px;">We regret to inform you that your application was not approved at this time. Please contact the Registrar's Office for more details regarding your requirements.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="text-decoration: none; color: var(--primary-color); font-size: 0.9rem;">&larr; Return to Homepage</a>
        </div>
    </div>

</body>
</html>