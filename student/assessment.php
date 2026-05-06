<?php
// Secures the page for student access only using the centralized authentication script.
require_once '../actions/auth.php';
check_student();

// Establishes the database connection.
require_once '../config/db.php';

try {
    $stmt = $pdo->prepare("SELECT total_assessment, balance, status FROM enrollments WHERE enrollment_id = :id");
    $stmt->execute([':id' => $_SESSION['enrollment_id']]);
    $finances = $stmt->fetch();

    $payStmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE enrollment_id = :id AND status = 'Verified'");
    $payStmt->execute([':id' => $_SESSION['enrollment_id']]);
    $amount_paid = (float) $payStmt->fetchColumn();

    $pendingStmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE enrollment_id = :id AND status = 'Pending'");
    $pendingStmt->execute([':id' => $_SESSION['enrollment_id']]);
    $amount_pending = (float) $pendingStmt->fetchColumn();

    $subjStmt = $pdo->prepare("SELECT subject_name FROM enrollment_subjects WHERE enrollment_id = :id");
    $subjStmt->execute([':id' => $_SESSION['enrollment_id']]);
    $has_subjects = (count($subjStmt->fetchAll()) > 0);

    // Fetch active school year to determine installment dates
    $syStmt = $pdo->query("SELECT year_string FROM school_years WHERE is_active = 1 LIMIT 1");
    $sy = $syStmt->fetchColumn() ?: date('Y') . '-' . (date('Y') + 1);
    $startYear = explode('-', $sy)[0];
    $endYear = explode('-', $sy)[1] ?? ($startYear + 1);

    // Calculate Installments (25% per quarter)
    $total_tuition = (float) $finances['total_assessment'];
    $q_fee = $total_tuition > 0 ? $total_tuition * 0.25 : 0;
    $q4_fee = $total_tuition > 0 ? $total_tuition - ($q_fee * 3) : 0; // Remainder to avoid rounding loss
    
    $net_balance = $finances['balance'] - $amount_pending;

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment & Payment | Pines NHS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; border-right: 1px solid var(--glass-border); padding: 24px; display: flex; flex-direction: column; background: var(--glass-bg); backdrop-filter: blur(15px); position: sticky; top: 0; height: 100vh; z-index: 50;}
        .main-content { flex: 1; padding: 40px; }
        .nav-link { display: block; padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; color: var(--text-main); font-weight: 500; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        .breakdown-row { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--glass-border); font-size: 0.95rem; }
        .breakdown-row:last-child { border-bottom: none; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border); background: transparent; color: var(--text-main); }
        .form-control:focus { outline: 2px solid var(--primary-color); }
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
                <a href="assessment.php" class="nav-link active">Assessment & Payment</a>
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
            <h2 style="margin-bottom: 5px;">Assessment & Payments</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">View tuition breakdown and submit payments.</p>
            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="padding: 25px; border: 1px solid var(--glass-border); border-radius: 8px;">
                        <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 20px; color: var(--text-main); text-transform: uppercase;">Tuition Assessment</h3>
                        <div class="breakdown-row" style="font-weight: 600;">
                            <span>Total Assessment</span>
                            <span>₱<?= number_format($finances['total_assessment'], 2) ?></span>
                        </div>
                        <div class="breakdown-row text-primary">
                            <span>Amount Paid</span>
                            <span>₱<?= number_format($amount_paid, 2) ?></span>
                        </div>
                        <?php if ($amount_pending > 0): ?>
                        <div class="breakdown-row" style="color: #eab308;">
                            <span>Pending Verification</span>
                            <span>- ₱<?= number_format($amount_pending, 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="breakdown-row" style="font-weight: 700; color: #ea580c; font-size: 1.1rem;">
                            <span>Remaining Balance</span>
                            <span>₱<?= number_format($finances['balance'], 2) ?></span>
                        </div>
                    </div>
                    
                    <?php if ($total_tuition > 0): ?>
                    <div style="padding: 25px; border: 1px solid var(--glass-border); border-radius: 8px; background: rgba(59, 130, 246, 0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                            <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0; color: var(--text-main); text-transform: uppercase;">Installment Schedule</h3>
                            <button type="button" onclick="downloadPDF()" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; color: #3b82f6; border-color: rgba(59, 130, 246, 0.4); background: transparent; cursor: pointer;"><i class="bi bi-file-earmark-pdf"></i> Save as PDF</button>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">You may pay in full, or follow this standard quarterly schedule.</p>
                        
                        <div class="breakdown-row">
                            <span><strong>1st Quarter</strong> <br><span style="font-size:0.8rem; color:var(--text-muted);">Upon Enrollment</span></span>
                            <span style="font-weight: 600;">₱<?= number_format($q_fee, 2) ?></span>
                        </div>
                        <div class="breakdown-row">
                            <span><strong>2nd Quarter</strong> <br><span style="font-size:0.8rem; color:var(--text-muted);">Due: Oct 15, <?= $startYear ?></span></span>
                            <span style="font-weight: 600;">₱<?= number_format($q_fee, 2) ?></span>
                        </div>
                        <div class="breakdown-row">
                            <span><strong>3rd Quarter</strong> <br><span style="font-size:0.8rem; color:var(--text-muted);">Due: Dec 15, <?= $startYear ?></span></span>
                            <span style="font-weight: 600;">₱<?= number_format($q_fee, 2) ?></span>
                        </div>
                        <div class="breakdown-row">
                            <span><strong>4th Quarter</strong> <br><span style="font-size:0.8rem; color:var(--text-muted);">Due: Mar 15, <?= $endYear ?></span></span>
                            <span style="font-weight: 600;">₱<?= number_format($q4_fee, 2) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($finances['status'] === 'Pending'): ?>
                    <div style="padding: 25px; display: flex; align-items: center; justify-content: center; text-align: center; border: 1px dashed var(--glass-border); border-radius: 8px;">
                        <div>
                            <span style="font-size: 3rem; display: block; margin-bottom: 10px;">🕐</span>
                            <h3 style="color: var(--text-main);">Assessment Pending</h3>
                            <p class="text-muted" style="font-size: 0.9rem;">Your application is still under review. Payment will be enabled once your status changes to <strong>Assessed</strong>.</p>
                        </div>
                    </div>
                <?php elseif (!$has_subjects): ?>
                    <div style="padding: 25px; display: flex; align-items: center; justify-content: center; text-align: center; border: 1px dashed var(--glass-border); border-radius: 8px;">
                        <div>
                            <span style="font-size: 3rem; display: block; margin-bottom: 10px;">📚</span>
                            <h3 style="color: var(--text-main);">Action Required</h3>
                            <p class="text-muted" style="font-size: 0.9rem;">Please <a href="dashboard.php" class="text-primary" style="font-weight: 600; text-decoration: none;">select your subjects and section</a> before proceeding with payment.</p>
                        </div>
                    </div>
                <?php elseif ($finances['balance'] > 0 && $net_balance > 0): ?>
                <div style="padding: 25px; border: 1px solid var(--glass-border); border-radius: 8px; background: rgba(34, 197, 94, 0.05);">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 20px; color: var(--text-main); text-transform: uppercase;">Submit Payment</h3>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 10px;">Select Mode of Payment:</label>
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-main); font-weight: 500;">
                                <input type="radio" name="pay_mode" value="Cash" onchange="togglePaymentMode()" style="accent-color: var(--primary-color); width: 18px; height: 18px;"> Cash (Over-the-counter)
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-main); font-weight: 500;">
                                <input type="radio" name="pay_mode" value="GCash" onchange="togglePaymentMode()" style="accent-color: var(--primary-color); width: 18px; height: 18px;"> GCash (Online)
                            </label>
                        </div>
                    </div>

                    <div id="cash-info-section" style="display: none; background: rgba(100, 116, 139, 0.1); padding: 20px; border-radius: 8px; border: 1px solid var(--glass-border);">
                        <p style="margin: 0; font-size: 0.95rem; color: var(--text-main); line-height: 1.6;">
                            <strong><i class="bi bi-info-circle-fill text-primary"></i> Paying in person?</strong><br>
                            Please visit the Cashier's office at the Pines NHS campus (Mon-Fri, 8AM to 4PM) to pay over-the-counter. You do not need to submit an online form for walk-in cash payments.
                        </p>
                    </div>

                    <div id="gcash-form-section" style="display: none;">
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.3); margin-bottom: 20px; font-size: 0.9rem; color: var(--text-main); line-height: 1.6;">
                            <strong>GCash Payment Instructions:</strong><br>
                            1. Open your GCash app and send the amount to <strong>0917-123-4567</strong> (Pines NHS Cashier).<br>
                            2. Save the 13-digit Reference Number from your GCash receipt.<br>
                            3. Fill out the form below to verify your payment.
                        </div>

                        <form action="../actions/submit_payment.php" method="POST">
                            <input type="hidden" name="payment_method" value="GCash">
                            <div class="form-group">
                                <label>Amount Paid (₱)</label>
                                <?php $min_payment = min($q_fee > 0 ? $q_fee : 500, $net_balance); ?>
                                <input type="number" step="0.01" name="amount" class="form-control" min="<?= $min_payment ?>" max="<?= $net_balance ?>" placeholder="Minimum ₱<?= number_format($min_payment, 2) ?>" required>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">Minimum required payment is your downpayment/installment (₱<?= number_format($min_payment, 2) ?>).</p>
                            </div>
                            <div class="form-group">
                                <label>GCash Reference Number</label>
                                <input type="text" name="reference_no" class="form-control" placeholder="13-digit reference no." required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; padding: 12px;">Submit Payment</button>
                        </form>
                    </div>
                </div>
                <?php elseif($finances['balance'] > 0 && $net_balance <= 0): ?>
                    <div style="padding: 25px; display: flex; align-items: center; justify-content: center; text-align: center; border: 1px dashed var(--glass-border); border-radius: 8px; background: rgba(234, 179, 8, 0.05);">
                        <div>
                            <span style="font-size: 3rem; display: block; margin-bottom: 10px; color: #eab308;"><i class="bi bi-hourglass-split"></i></span>
                            <h3 style="color: var(--text-main);">Payment Processing</h3>
                            <p class="text-muted" style="font-size: 0.9rem;">You have submitted payments that cover your remaining balance. Please wait for the Cashier to verify them.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="padding: 25px; display: flex; align-items: center; justify-content: center; text-align: center; border: 1px solid var(--glass-border); border-radius: 8px; background: rgba(34, 197, 94, 0.05);">
                        <div>
                            <span style="font-size: 3rem; display: block; margin-bottom: 10px;">🎉</span>
                            <h3 style="color: var(--primary-color);">Fully Paid</h3>
                            <p class="text-muted" style="font-size: 0.9rem;">No outstanding balance remains on this account.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
    <script>
        // Loads the document invisibly and directly triggers the Save as PDF / Print dialog
        function downloadPDF() {
            let printFrame = document.getElementById('print-frame');
            if (!printFrame) {
                printFrame = document.createElement('iframe');
                printFrame.id = 'print-frame';
                printFrame.style.position = 'absolute';
                printFrame.style.width = '0';
                printFrame.style.height = '0';
                printFrame.style.border = 'none';
                document.body.appendChild(printFrame);
            }
            printFrame.onload = function() {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
            };
            printFrame.src = 'print_installment.php';
        }

        // Toggles visibility between Cash Instructions and GCash Form
        function togglePaymentMode() {
            const mode = document.querySelector('input[name="pay_mode"]:checked').value;
            const cashSection = document.getElementById('cash-info-section');
            const gcashSection = document.getElementById('gcash-form-section');

            if (mode === 'Cash') {
                cashSection.style.display = 'block';
                gcashSection.style.display = 'none';
            } else if (mode === 'GCash') {
                cashSection.style.display = 'none';
                gcashSection.style.display = 'block';
            }
        }
    </script>
</body>
</html>