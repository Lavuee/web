<?php
require_once '../actions/auth.php';
check_auth();
require_once '../config/db.php';

// Fetch payments requiring verification
try {
    $stmt = $pdo->query("
        SELECT p.*, s.first_name, s.last_name, e.grade_level 
        FROM payments p
        JOIN enrollments e ON p.enrollment_id = e.enrollment_id
        JOIN students s ON e.student_id = s.student_id
        WHERE p.status = 'Pending'
        ORDER BY p.payment_date ASC
    ");
    $pending_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h2 style="font-size: 1.8rem;">Payment Verification</h2>
            <p class="text-muted">Review and confirm student tuition payments.</p>
        </header>

        <div class="glass-panel">
            <table class="table-wrapper">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_list)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 30px;">No payments awaiting verification.</td></tr>
                    <?php else: ?>
                        <?php foreach($pending_list as $pay): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($pay['last_name'] . ', ' . $pay['first_name']) ?></td>
                            <td>₱<?= number_format($pay['amount'], 2) ?></td>
                            <td><?= $pay['payment_method'] ?></td>
                            <td style="font-family: monospace;"><?= htmlspecialchars($pay['reference_no'] ?? 'N/A') ?></td>
                            <td style="text-align: right;">
                                <form action="../actions/verify_payment_v2.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="payment_id" value="<?= $pay['payment_id'] ?>">
                                    <button type="submit" name="action" value="Verified" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;">Approve</button>
                                    <button type="submit" name="action" value="Rejected" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; color: #ef4444;">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>