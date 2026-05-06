<?php
require_once '../actions/auth.php';
check_auth();

// Allow both Admin and Cashier roles to print receipts
if (!in_array(strtolower($_SESSION['role']), ['admin', 'cashier'])) {
    die("Unauthorized Access.");
}

require_once '../config/db.php';

$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$payment_id) {
    die("Invalid Payment ID.");
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, e.tracking_no, e.grade_level, e.section, s.first_name, s.last_name, s.lrn, u.email as cashier_email
        FROM payments p
        JOIN enrollments e ON p.enrollment_id = e.enrollment_id
        JOIN students s ON e.student_id = s.student_id
        LEFT JOIN users u ON p.cashier_id = u.user_id
        WHERE p.payment_id = ?
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        die("Payment record not found.");
    }
    
    // Fetch active school year
    $syStmt = $pdo->query("SELECT year_string FROM school_years WHERE is_active = 1 LIMIT 1");
    $sy = $syStmt->fetchColumn() ?: 'TBA';
    
} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt - <?= htmlspecialchars($payment['tracking_no']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 40px 20px;
            background: #f1f5f9;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .receipt-container {
            background: #ffffff;
            width: 100%;
            max-width: 400px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-top: 6px solid #15803d;
            border-radius: 8px 8px 0 0;
            position: relative;
            color: #0f172a;
        }
        /* Jagged bottom edge effect for receipt */
        .receipt-container::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 10px;
            background-size: 20px 20px;
            background-image: radial-gradient(circle at 10px 0, transparent 10px, #ffffff 11px);
        }
        .text-center { text-align: center; }
        .school-name { font-weight: 800; font-size: 1.3rem; margin-bottom: 5px; font-family: Arial, sans-serif; letter-spacing: -0.5px; }
        .school-address { font-size: 0.85rem; margin-bottom: 15px; color: #475569; font-family: Arial, sans-serif; }
        .receipt-title { font-size: 1.1rem; font-weight: bold; margin: 20px 0; border-bottom: 1px dashed #cbd5e1; padding-bottom: 15px; letter-spacing: 2px; }
        
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.95rem; }
        .info-label { color: #64748b; }
        .info-value { font-weight: 600; text-align: right; }
        
        .divider { border-bottom: 1px dashed #cbd5e1; margin: 18px 0; }
        
        .amount-row { display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 800; margin-top: 20px; font-family: Arial, sans-serif; }
        
        .footer { text-align: center; margin-top: 35px; font-size: 0.8rem; color: #64748b; line-height: 1.6; font-family: Arial, sans-serif; }
        
        .controls {
            margin-top: 30px;
            width: 100%;
            max-width: 400px;
            display: flex;
            gap: 15px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            font-family: Arial, sans-serif;
            font-size: 0.95rem;
            transition: 0.2s;
        }
        .btn-primary { background: #15803d; color: #fff; }
        .btn-primary:hover { background: #166534; }
        .btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
        .btn-outline:hover { background: #e2e8f0; color: #0f172a; }

        @media print {
            body { display: block; background: #fff; padding: 0; margin: 0; }
            .receipt-container { box-shadow: none; border-top: none; max-width: 100%; width: 100%; padding: 0; margin: 0; border-radius: 0; }
            .receipt-container::after { display: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        <div class="text-center">
            <div class="school-name">PINES NATIONAL HIGH SCHOOL</div>
            <div class="school-address">Magsaysay Ave., Baguio City, Philippines<br>S.Y. <?= htmlspecialchars($sy) ?></div>
        </div>

        <div class="text-center receipt-title">
            OFFICIAL RECEIPT
        </div>

        <div class="info-row">
            <span class="info-label">Receipt No:</span>
            <span class="info-value">#<?= str_pad($payment['payment_id'], 6, '0', STR_PAD_LEFT) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value"><?= date('m/d/Y h:i A', strtotime($payment['payment_date'])) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Cashier:</span>
            <span class="info-value"><?= htmlspecialchars(explode('@', $payment['cashier_email'] ?? 'System')[0]) ?></span>
        </div>

        <div class="divider"></div>

        <div class="info-row">
            <span class="info-label">Student Name:</span>
            <span class="info-value"><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Student ID:</span>
            <span class="info-value"><?= htmlspecialchars($payment['tracking_no']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">LRN:</span>
            <span class="info-value"><?= htmlspecialchars($payment['lrn'] ?: 'N/A') ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Grade & Section:</span>
            <span class="info-value"><?= htmlspecialchars($payment['grade_level']) ?><br><span style="font-size: 0.8rem; font-weight: normal;"><?= htmlspecialchars($payment['section'] ?: 'TBA') ?></span></span>
        </div>

        <div class="divider"></div>

        <div class="info-row">
            <span class="info-label">Payment Method:</span>
            <span class="info-value"><?= htmlspecialchars($payment['payment_method']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value"><?= htmlspecialchars($payment['status']) ?></span>
        </div>

        <div class="amount-row">
            <span>AMOUNT PAID:</span>
            <span>PHP <?= number_format($payment['amount'], 2) ?></span>
        </div>

        <div class="divider"></div>

        <div class="footer">
            This document serves as your official proof of payment.<br>
            Please keep this receipt for your records.
        </div>
    </div>

    <div class="controls no-print">
        <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
            <a href="../admin/payments.php" class="btn btn-outline">Return to Dashboard</a>
        <?php else: ?>
            <a href="dashboard.php" class="btn btn-outline">Return to Dashboard</a>
        <?php endif; ?>
        <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
    </div>

</body>
</html>