<?php
require_once 'config/db.php';

// Get tracking info
$short_id    = isset($_GET['short_id']) ? trim($_GET['short_id']) : '';
$dob         = isset($_GET['dob']) ? trim($_GET['dob']) : '';
$enrollment  = null;
$subjects    = [];
$error       = null;
$success     = null;

// Reconstruct the full Tracking Number dynamically (e.g., '0001' becomes 'PCNH20260001')
$tracking_no = '';
if ($short_id !== '') {
    $current_year = date('Y');
    // Pads the input with leading zeros to ensure it is exactly 4 digits
    $padded_id = str_pad($short_id, 4, '0', STR_PAD_LEFT);
    $tracking_no = "PCNH" . $current_year . $padded_id;
}

// Subject pricing
$subjectPrices = [
    'Mathematics'       => 350,
    'Science'           => 350,
    'English'           => 300,
    'Filipino'          => 300,
    'Araling Panlipunan'=> 300,
    'MAPEH'             => 400,
    'TLE'               => 450,
    'Values Education'  => 250,
];

// ─── Handle Payment Submission ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_id   = trim($_POST['enrollment_id'] ?? '');
    $payment_method  = trim($_POST['payment_method'] ?? '');
    $reference_no    = trim($_POST['reference_no'] ?? '');
    $amount_paid     = floatval($_POST['amount_paid'] ?? 0);

    if (empty($enrollment_id) || empty($payment_method) || empty($reference_no)) {
        $error = "All payment fields are required.";
    } else {
        try {
            // Fetch enrollment + student info
            $stmt = $pdo->prepare("
                SELECT e.enrollment_id, e.tracking_no, e.student_id, e.status,
                       e.section, e.grade_level, e.total_assessment, e.balance,
                       s.first_name, s.last_name
                FROM enrollments e
                JOIN students s ON e.student_id = s.student_id
                WHERE e.enrollment_id = :eid
            ");
            $stmt->execute([':eid' => $enrollment_id]);
            $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$enrollment) {
                $error = "Enrollment record not found.";
            } elseif ($enrollment['status'] !== 'Assessed') {
                $error = "Payment is only available for Assessed applications.";
            } else {
                $subjStmt = $pdo->prepare("SELECT COUNT(*) FROM enrollment_subjects WHERE enrollment_id = :eid");
                $subjStmt->execute([':eid' => $enrollment_id]);
                if ($subjStmt->fetchColumn() == 0) {
                    $error = "You must select your subjects in the Student Portal before proceeding with payment.";
                } else {
                $db_payment_method = in_array($payment_method, ['Cash', 'GCash']) ? $payment_method : 'GCash';

                // Insert payment record
                $pstmt = $pdo->prepare("
                    INSERT INTO payments (enrollment_id, payment_method, reference_no, amount, payment_date, status)
                    VALUES (:eid, :method, :ref, :amount, NOW(), 'Pending')
                ");
                $pstmt->execute([
                    ':eid'    => $enrollment_id,
                    ':method' => $db_payment_method,
                    ':ref'    => $reference_no,
                    ':amount' => $amount_paid,
                ]);

                $success = "Payment submitted successfully. Please allow processing time for Cashier verification.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// ─── Fetch enrollment on GET ──────────────────────────────────────────────────
if (!$enrollment && !empty($tracking_no) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($dob)) {
        // We require DOB to proceed
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT e.enrollment_id, e.tracking_no, e.student_id, e.status,
                       e.section, e.grade_level, e.total_assessment, e.balance,
                       s.first_name, s.last_name
                FROM enrollments e
                JOIN students s ON e.student_id = s.student_id
                WHERE e.tracking_no = :tracking_no AND s.date_of_birth = :dob
            ");
            $stmt->execute([':tracking_no' => $tracking_no, ':dob' => $dob]);
            $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$enrollment) {
                $error = "Access denied. The Student ID or Date of Birth is incorrect.";
            } elseif ($enrollment['status'] === 'Enrolled') {
                echo "<script>
                        alert('You are officially enrolled! Please log in to your Student Portal to manage your account.');
                        window.location.href = 'login.php';
                      </script>";
                exit();
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Build subject list and totals
if ($enrollment) {
    $subjStmt = $pdo->prepare("SELECT subject_name FROM enrollment_subjects WHERE enrollment_id = :eid");
    $subjStmt->execute([':eid' => $enrollment['enrollment_id']]);
    $subjects = $subjStmt->fetchAll(PDO::FETCH_COLUMN);

    $pendingStmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE enrollment_id = :eid AND status = 'Pending'");
    $pendingStmt->execute([':eid' => $enrollment['enrollment_id']]);
    $amount_pending = (float) $pendingStmt->fetchColumn();

    $totalAmount = (float) $enrollment['balance'];
    $net_balance = $totalAmount - $amount_pending;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment & Status | Pines NHS Enrollment</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&display=swap" rel="stylesheet">
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
        /* ── Reset & Base ───────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 40px 20px 60px;
            background: var(--bg-color);
            color: var(--text-main);
        }

        h1, h2, h3 {
            font-family: 'DM Serif Display', serif;
            font-weight: 400;
        }

        /* ── Layout ─────────────────────────────────────── */
        .pay-wrapper {
            max-width: 700px;
            width: 100%;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Page Header ─────────────────────────────────── */
        .pay-header {
            margin-bottom: 32px;
        }

        .pay-header .eyebrow {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--primary-color);
            margin-bottom: 6px;
        }

        .pay-header h2 {
            font-size: 2.2rem;
            margin: 0 0 6px;
            line-height: 1.1;
        }

        .pay-header p {
            font-size: 0.92rem;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.6;
        }

        /* ── Glass Panel Override ─────────────────────────── */
        .pay-card {
            background: var(--glass-bg, rgba(255,255,255,0.06));
            border: 1px solid var(--glass-border, rgba(255,255,255,0.12));
            border-radius: 16px;
            padding: 28px 30px;
            margin-bottom: 18px;
            backdrop-filter: blur(10px);
        }

        .section-title {
            font-family: 'DM Serif Display', serif;
            font-size: 1.15rem;
            margin: 0 0 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--glass-border, rgba(0,0,0,0.08));
        }

        /* ── Subject Breakdown Table ─────────────────────── */
        .fee-table {
            width: 100%;
            border-collapse: collapse;
        }

        .fee-table tr td {
            padding: 9px 4px;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--glass-border, rgba(0,0,0,0.06));
        }

        .fee-table tr:last-child td { border-bottom: none; }

        .fee-table .subject-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .fee-table .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--primary-color);
            flex-shrink: 0;
            opacity: 0.7;
        }

        .fee-table td.price {
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: 500;
            color: var(--text-main);
        }

        .fee-table .subtotal-row td {
            padding-top: 12px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .fee-table .misc-row td {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* ── Total Bar ───────────────────────────────────── */
        .total-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: linear-gradient(135deg, rgba(22,101,52,0.18) 0%, rgba(34,197,94,0.08) 100%);
            border: 1px solid rgba(34,197,94,0.25);
            border-radius: 12px;
            margin-top: 4px;
        }

        .total-bar .label {
            font-family: 'DM Serif Display', serif;
            font-size: 1rem;
            color: var(--text-main);
        }

        .total-bar .amount {
            font-family: 'DM Serif Display', serif;
            font-size: 1.7rem;
            color: #22c55e;
            letter-spacing: -0.5px;
        }

        /* ── Payment Method Pills ────────────────────────── */
        .method-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            margin-bottom: 22px;
        }

        .method-pill {
            position: relative;
            cursor: pointer;
        }

        .method-pill input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .method-pill label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 10px;
            border: 2px solid var(--glass-border, rgba(0,0,0,0.1));
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--bg-color);
            text-align: center;
        }

        .method-pill label:hover {
            border-color: var(--primary-color);
            background: rgba(22,101,52,0.04);
        }

        .method-pill input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background: rgba(22,101,52,0.1);
            box-shadow: 0 0 0 3px rgba(22,101,52,0.12);
        }

        .method-logo {
            width: 44px;
            height: 28px;
            object-fit: contain;
            border-radius: 4px;
        }

        .method-name {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: 0.3px;
        }

        /* Inline SVG logos */
        .logo-gcash   { background: #007aff; color: #fff; border-radius: 6px; display:flex; align-items:center; justify-content:center; font-size:0.65rem; font-weight:800; letter-spacing:-0.5px; padding: 3px 5px; }

        /* QR Hint Block */
        .qr-hint {
            display: none;
            padding: 14px 16px;
            border-radius: 10px;
            background: rgba(59,130,246,0.08);
            border: 1px solid rgba(59,130,246,0.2);
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .qr-hint strong { color: var(--text-main); }
        .qr-hint.visible { display: block; }

        /* ── Form Controls ───────────────────────────────── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 14px;
        }

        .form-label {
            font-size: 0.83rem;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: 0.2px;
        }

        .form-label .req { color: #ef4444; margin-left: 2px; }

        .form-control {
            width: 100%;
            padding: 11px 14px;
            border-radius: 9px;
            border: 1.5px solid var(--glass-border, rgba(0,0,0,0.12));
            background: var(--bg-color);
            color: var(--text-main);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.93rem;
            transition: all 0.25s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(22,101,52,0.13);
        }

        /* ── Submit Button ───────────────────────────────── */
        .btn-pay {
            width: 100%;
            padding: 16px;
            font-size: 1.05rem;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #166534 0%, #15803d 60%, #22c55e 100%);
            color: #fff;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 20px rgba(22,101,52,0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-pay:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(22,101,52,0.4);
        }

        .btn-pay:active { transform: translateY(0); }

        /* ── Alerts ──────────────────────────────────────── */
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #ef4444;
        }

        .alert-success {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.3);
            color: #22c55e;
        }

        /* ── Success State ───────────────────────────────── */
        .success-panel {
            text-align: center;
            padding: 40px 30px;
        }

        .success-panel h3 {
            font-size: 1.6rem;
            margin: 0 0 8px;
        }

        .success-panel p {
            color: var(--text-muted);
            font-size: 0.93rem;
            margin: 0 0 28px;
            line-height: 1.6;
        }

        /* ── Info chips (student info row) ───────────────── */
        .student-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 4px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            background: rgba(100,116,139,0.1);
            color: var(--text-muted);
            border: 1px solid var(--glass-border, rgba(0,0,0,0.07));
        }

        .chip strong { color: var(--text-main); font-weight: 600; }

        /* ── Status Banners ───────────────────────────────────── */
        .status-banner {
            text-align: center;
            padding: 50px 20px;
        }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 520px) {
            .method-grid { grid-template-columns: 1fr 1fr; }
            .pay-header h2 { font-size: 1.75rem; }
        }
    </style>
</head>
<body>

    <!-- Theme Toggle -->
    <div class="theme-switch-wrapper" style="position: fixed; top: 20px; right: 20px; z-index: 100;" title="Toggle Theme">
        <label class="theme-switch">
            <input type="checkbox" id="theme-toggle-checkbox" onchange="toggleTheme()">
            <span class="slider"></span>
        </label>
    </div>

    <div class="pay-wrapper">

        <!-- Back -->
        <div style="margin-bottom: 28px;">
            <a href="index.php" class="btn btn-outline" style="padding: 8px 16px; font-family:'DM Sans',sans-serif;">&larr; Back to Home</a>
        </div>

        <!-- Header -->
        <div class="pay-header">
            <p class="eyebrow">Pines NHS &middot; Enrollment <?= date('Y') ?></p>
            <h2>Track Application & Payment</h2>
            <p>Monitor your enrollment status and complete required tuition payments.</p>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="pay-card success-panel">
                <h3>Payment Verification in Progress</h3>
                <p>Your payment details have been successfully recorded. The administration is currently verifying your transaction. You may track your full enrollment status in the Student Portal.</p>
                <a href="login.php" class="btn btn-primary" style="font-family:'DM Sans',sans-serif; padding: 13px 30px; font-size:0.95rem;">
                    Go to Student Portal &rarr;
                </a>
            </div>

        <?php elseif ($enrollment && $enrollment['status'] === 'Assessed' && $net_balance > 0): ?>

            <?php if (empty($subjects)): ?>
                <div class="pay-card status-banner">
                    <h3 style="font-size:1.4rem; margin:0 0 12px; color: var(--text-main);">Subject Selection Required</h3>
                    <p style="color:var(--text-muted); font-size:0.95rem; margin:0 0 24px; line-height: 1.6;">
                        Your application has been officially assessed by the administration. However, you must log into your Student Portal to finalize your subject selection before tuition payment can be calculated and processed.
                    </p>
                    <a href="login.php" class="btn btn-primary" style="font-family:'DM Sans',sans-serif; padding: 13px 30px;">
                        Go to Student Portal &rarr;
                    </a>
                </div>
            <?php else: ?>
            <!-- ── Student Info ───────────────────────── -->
            <div class="pay-card">
                <div class="student-chips">
                    <span class="chip">Name: <strong><?= htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></strong></span>
                    <span class="chip">Grade: <strong><?= htmlspecialchars($enrollment['grade_level']) ?></strong></span>
                    <span class="chip">Section: <strong><?= htmlspecialchars($enrollment['section'] ?: 'TBA') ?></strong></span>
                    <span class="chip">Student ID: <strong style="font-family:monospace;"><?= htmlspecialchars($enrollment['tracking_no']) ?></strong></span>
                </div>
            </div>

            <!-- ── Fee Breakdown ─────────────────────── -->
            <div class="pay-card">
                <table class="fee-table">
                    <thead>
                        <tr>
                            <td style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-muted); padding-bottom:10px;">Subject</td>
                            <td style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-muted); padding-bottom:10px; text-align:right;">Amount</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subjectTotal = 0;
                        foreach ($subjects as $subj): 
                            $price = $subjectPrices[$subj] ?? 0;
                            $subjectTotal += $price;
                        ?>
                        <tr>
                            <td>
                                <span class="subject-badge">
                                    <span class="dot"></span>
                                    <?= htmlspecialchars($subj) ?>
                                </span>
                            </td>
                            <td class="price">₱<?= number_format($price, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>

                        <tr class="subtotal-row">
                            <td style="padding-top:14px;">Subject Total</td>
                            <td class="price" style="padding-top:14px; color:var(--text-muted);">₱<?= number_format($subjectTotal, 2) ?></td>
                        </tr>
                        
                        <?php 
                        $baseTuition = $enrollment['total_assessment'] - $subjectTotal;
                        $paidAmount = $enrollment['total_assessment'] - $enrollment['balance'];
                        ?>
                        <tr class="misc-row">
                            <td style="padding-top:14px;">Base Tuition & Misc</td>
                            <td class="price" style="padding-top:14px;">₱<?= number_format($baseTuition, 2) ?></td>
                        </tr>
                        <tr class="misc-row" style="font-weight: 600; color: var(--text-main);">
                            <td>Total Assessment</td>
                            <td class="price">₱<?= number_format($enrollment['total_assessment'], 2) ?></td>
                        </tr>
                        <?php if ($paidAmount > 0): ?>
                        <tr class="misc-row">
                            <td>Payments Verified</td>
                            <td class="price" style="color: #22c55e;">- ₱<?= number_format($paidAmount, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($amount_pending > 0): ?>
                        <tr class="misc-row">
                            <td>Pending Verification</td>
                            <td class="price" style="color: #eab308;">- ₱<?= number_format($amount_pending, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="total-bar" style="margin-top: 18px;">
                    <span class="label">Remaining Balance</span>
                    <span class="amount">₱<?= number_format($totalAmount, 2) ?></span>
                </div>
            </div>

            <!-- ── Payment Form ───────────────────────── -->
            <div class="pay-card">
                <div class="section-title">
                    Payment Method
                </div>

                <form method="POST" action="payment.php" id="paymentForm">
                    <input type="hidden" name="enrollment_id" value="<?= htmlspecialchars($enrollment['enrollment_id']) ?>">

                    <!-- Method Selector -->
                    <div class="method-grid" style="margin-bottom:18px;">
                        <div class="method-pill">
                            <input type="radio" name="payment_method" id="m_gcash" value="GCash" required>
                            <label for="m_gcash">
                                <span class="logo-gcash method-logo">GCash</span>
                                <span class="method-name">GCash</span>
                            </label>
                        </div>
                    </div>

                    <!-- QR / Account Hint -->
                    <div class="qr-hint" id="paymentHint"></div>

                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px; text-align: center; background: rgba(100, 116, 139, 0.05); padding: 10px; border-radius: 8px;">
                        <strong>Paying in person?</strong> Visit the school Cashier to pay over-the-counter. No need to submit this online form.
                    </p>

                    <div class="form-group">
                        <label class="form-label" for="reference_no">
                            Reference / Transaction Number <span class="req">*</span>
                        </label>
                        <input type="text" id="reference_no" name="reference_no" class="form-control"
                               placeholder="e.g., GCash ref, transfer ref, OR number…" required>
                        <span style="font-size:0.78rem; color:var(--text-muted);">Enter the reference number from your payment confirmation or receipt.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Amount Paid <span class="req">*</span>
                        </label>
                        <input type="number" step="0.01" name="amount_paid" class="form-control" 
                               placeholder="Enter amount (Min 500)"
                               min="500" max="<?= number_format($net_balance, 2, '.', '') ?>" 
                               required style="font-weight:600;">
                        <span style="font-size:0.78rem; color:var(--text-muted);">You can pay in full or submit a partial installment (Minimum ₱500).</span>
                    </div>

                    <button type="submit" class="btn-pay">
                        Submit Payment &rarr;
                    </button>
                </form>
            </div>
            <?php endif; ?>

        <?php elseif ($enrollment && $enrollment['status'] === 'Assessed' && $enrollment['balance'] > 0 && $net_balance <= 0): ?>
            <!-- Balance is theoretically cleared but pending Cashier verification -->
            <div class="pay-card success-panel" style="border-color: #eab308; background: rgba(234, 179, 8, 0.05);">
                <h3 style="color: var(--text-main);">Payment Verification in Progress</h3>
                <p>You have submitted payments that cover your remaining balance. Please wait for the Cashier to review and verify them before your status is updated.</p>
                <a href="login.php" class="btn btn-outline" style="font-family:'DM Sans',sans-serif; padding: 13px 30px;">
                    Go to Student Portal &rarr;
                </a>
            </div>

        <?php elseif ($enrollment): ?>
            <!-- Wrong status (e.g. Pending, Rejected) -->
            <div class="pay-card status-banner">
                <h3 style="font-size:1.4rem; margin:0 0 12px; color: var(--text-main);">Application Under Review</h3>
                <p style="color:var(--text-muted); font-size:0.95rem; margin:0 0 24px; line-height: 1.6;">
                    Your enrollment application is currently marked as <strong><?= htmlspecialchars($enrollment['status']) ?></strong>. The administration is still processing your documents. Tuition assessment and payment options will become available once your status changes to <strong>Assessed</strong>.
                </p>
                <a href="payment.php" class="btn btn-outline" style="font-family:'DM Sans',sans-serif; color: #22c55e; ">
                    Back to Application Tracker
                </a>
            </div>

        <?php else: ?>
            <!-- No ID provided — show lookup form -->
            <div class="pay-card">
                <div class="section-title">
                     Track Application Status
                </div>
                <form method="GET" action="payment.php" style="display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <label class="form-label" style="margin-bottom: 5px; display: block;">Student ID (Last 4 Digits)</label>
                        <input type="text" name="short_id" class="form-control" placeholder="e.g. 0001" value="<?= htmlspecialchars($short_id) ?>" maxlength="4" required>
                    </div>
                    <div>
                        <label class="form-label" style="margin-bottom: 5px; display: block;">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" title="Date of Birth" value="<?= htmlspecialchars($dob) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 12px; font-family:'DM Sans',sans-serif; margin-top: 5px;">Check Status &amp; Pay</button>
                </form>
            </div>
        <?php endif; ?>

    </div><!-- /.pay-wrapper -->

    <script>
        /* ── Theme Toggle ─────────────────────────────── */
        function toggleTheme() {
            const html = document.documentElement;
            const cb   = document.getElementById('theme-toggle-checkbox');
            if (html.hasAttribute('data-theme')) {
                html.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                if (cb) cb.checked = false;
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                if (cb) cb.checked = true;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const cb = document.getElementById('theme-toggle-checkbox');
            if (localStorage.getItem('theme') === 'dark' ||
               (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                if (cb) cb.checked = true;
            }

            /* ── Payment Method Hints ───────────────── */
            const hints = {
                'GCash': `<strong>GCash:</strong> Send to <strong>0917-XXX-XXXX</strong> (Pines NHS Cashier).<br>Screenshot your receipt and note the 13-digit reference number.`,
            };

            const hintBox  = document.getElementById('paymentHint');
            const radios   = document.querySelectorAll('input[name="payment_method"]');

            radios.forEach(r => {
                r.addEventListener('change', () => {
                    if (hints[r.value]) {
                        hintBox.innerHTML = hints[r.value];
                        hintBox.classList.add('visible');
                    } else {
                        hintBox.classList.remove('visible');
                    }
                });
            });

            /* ── Subject item highlight on hover ──── */
            document.querySelectorAll('.subject-item').forEach(el => {
                el.addEventListener('mouseenter', () => el.style.transform = 'translateX(3px)');
                el.addEventListener('mouseleave', () => el.style.transform = '');
            });
        });
    </script>
</body>
</html>