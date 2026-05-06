<?php
require_once '../actions/auth.php';
check_admin(); //[cite: 4]
require_once '../config/db.php';

try {
    // Analytics: Total official students
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn();

    // Analytics: Applications awaiting review
    $pendingApps = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Pending'")->fetchColumn();

    // Analytics: Total payments awaiting verification
    $pendingPayments = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'")->fetchColumn();

    // Analytics: Enrollment status
    $enrollmentStatus = $pdo->query("SELECT enrollment_status FROM enrollment_settings LIMIT 1")->fetchColumn();

} catch (PDOException $e) {
    die("Analytics Error: " . $e->getMessage());
}

include 'includes/admin_header.php';
?>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <header style="margin-bottom: 30px;">
            <h2 style="font-size: 1.8rem;">Command Center</h2>
            <p class="text-muted">Real-time system overview for Pines NHS.</p>
        </header>

        <!-- Analytics Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <div class="glass-panel" style="padding: 25px; border-top: 4px solid var(--primary-color);">
                <p class="text-muted" style="font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Total Students</p>
                <h3 style="font-size: 2rem; margin: 10px 0;"><?= $totalStudents ?></h3>
            </div>
            <div class="glass-panel" style="padding: 25px; border-top: 4px solid #f59e0b;">
                <p class="text-muted" style="font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Pending Applications</p>
                <h3 style="font-size: 2rem; margin: 10px 0;"><?= $pendingApps ?></h3>
            </div>
            <div class="glass-panel" style="padding: 25px; border-top: 4px solid #3b82f6;">
                <p class="text-muted" style="font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Unverified Payments</p>
                <h3 style="font-size: 2rem; margin: 10px 0;"><?= $pendingPayments ?></h3>
            </div>
            <div class="glass-panel" style="padding: 25px; border-top: 4px solid <?= $enrollmentStatus === 'Open' ? 'var(--primary-color)' : '#ef4444' ?>;">
                <p class="text-muted" style="font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Enrollment Portal</p>
                <h3 style="font-size: 1.5rem; margin: 10px 0;"><?= $enrollmentStatus ?></h3>
            </div>
        </div>
    </main>
</div>
</body>