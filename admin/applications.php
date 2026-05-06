<?php
require_once '../actions/auth.php';
check_admin(); // Secure the route for administrative personnel[cite: 9]
require_once '../config/db.php';

// Fetch all applications sorted by date
try {
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY date_applied DESC");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h2 style="font-size: 1.8rem; margin-bottom: 5px;">Admission Applications</h2>
            <p class="text-muted">Review and process incoming student applications.</p>
        </header>

        <div class="glass-panel">
            <div class="table-responsive">
                <table class="table-wrapper">
                    <thead>
                        <tr>
                            <th>Tracking No.</th>
                            <th>Applicant Name</th>
                            <th>Grade Level</th>
                            <th>Date Applied</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 40px;">No applications found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700;"><?= $app['tracking_no'] ?></td>
                                <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                                <td>Grade <?= htmlspecialchars($app['grade_applying_for']) ?></td>
                                <td><?= date('M d, Y', strtotime($app['date_applied'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($app['status']) ?>">
                                        <?= $app['status'] ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="view_application.php?id=<?= $app['application_id'] ?>" class="btn btn-outline" style="padding: 6px 15px; font-size: 0.85rem;">
                                        Review Files
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>