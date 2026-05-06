<?php
require_once '../actions/auth.php';
check_cashier();

require_once '../config/db.php';

try {
    // Fetch Cashier-specific statistics
    $countPending = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'")->fetchColumn();
    $countVerified = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Verified'")->fetchColumn();
    $totalVerifiedAmount = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'Verified'")->fetchColumn();

    // Fetch assessed students awaiting payment
    $stmtAssessed = $pdo->query("
        SELECT e.enrollment_id, e.tracking_no, e.total_assessment, e.balance,
               s.first_name, s.last_name, 
               u.email AS student_email,
               (SELECT GROUP_CONCAT(subject_name SEPARATOR ', ') FROM enrollment_subjects es WHERE es.enrollment_id = e.enrollment_id) as enrolled_subjects
        FROM enrollments e 
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE e.status IN ('Assessed', 'Enrolled') AND e.balance > 0
        ORDER BY s.last_name ASC
    ");
    $assessedStudents = $stmtAssessed->fetchAll();

    // Fetch pending online payments
    $stmtPending = $pdo->query("
        SELECT p.payment_id, p.enrollment_id, p.amount, p.payment_method, p.reference_no, p.status, p.payment_date,
               s.first_name, s.last_name, 
               u.email AS student_email,
               e.total_assessment, e.balance,
               (SELECT GROUP_CONCAT(subject_name SEPARATOR ', ') FROM enrollment_subjects es WHERE es.enrollment_id = e.enrollment_id) as enrolled_subjects
        FROM payments p 
        JOIN enrollments e ON p.enrollment_id = e.enrollment_id 
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE p.status = 'Pending'
        ORDER BY p.payment_date ASC
    ");
    $pendingPayments = $stmtPending->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$methodColors = [
    'GCash' => 'background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);',
    'Cash'  => 'background: rgba(100, 116, 139, 0.15); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.3);'
];

include 'includes/cashier_header.php'; 
?>
<body>

    <div class="layout">
        
        <?php include 'includes/cashier_sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Cashier Dashboard</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Review and process pending student payments.</p>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 45px; border-bottom: 1px solid var(--glass-border); padding-bottom: 25px;">
                <div style="flex: 1; min-width: 200px;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin: 0;">Pending Online</p>
                    <h3 style="font-size: 2.8rem; margin: 5px 0 0 0; font-weight: 300; color: #eab308;"><?= number_format($countPending) ?></h3>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin: 0;">Verified Transactions</p>
                    <h3 style="font-size: 2.8rem; margin: 5px 0 0 0; font-weight: 300; color: var(--badge-verified-color);"><?= number_format($countVerified) ?></h3>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin: 0;">Total Collected</p>
                    <h3 style="font-size: 2.8rem; margin: 5px 0 0 0; font-weight: 300; color: var(--primary-color);">₱<?= number_format((float)$totalVerifiedAmount, 2) ?></h3>
                </div>
            </div>

            <div style="margin-bottom: 50px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0;">Online Payments (Awaiting Verification)</h3>
                </div>
                <div class="table-responsive">
                    <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Method</th>
                            <th>Ref No.</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingPayments as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($row['student_email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge" style="<?= $methodColors[$row['payment_method']] ?? '' ?>">
                                        <?= htmlspecialchars($row['payment_method']) ?>
                                    </span>
                                </td>
                                <td style="font-family: monospace; font-size: 0.9rem;"><?= htmlspecialchars($row['reference_no'] ?: 'N/A') ?></td>
                                <td style="font-weight: 600;">₱<?= number_format($row['amount'], 2) ?></td>
                                <td>
                                    <span class="badge" style="background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('m/d/Y', strtotime($row['payment_date'])) ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button type="button" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;" onclick='openOnlinePaymentModal(<?= json_encode($row, JSON_HEX_APOS) ?>)'>Record</button>
                                        <form action="../actions/verify_payment.php" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to reject this payment?');">
                                            <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                            <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                            <input type="hidden" name="return_to" value="dashboard.php">
                                            <button type="submit" name="action" value="Reject" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($pendingPayments)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No pending online payments at this time.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <div style="margin-bottom: 50px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0;">Students with Outstanding Balances</h3>
                </div>
                <div class="table-responsive">
                    <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Tracking No.</th>
                            <th>Total Assessment</th>
                            <th>Remaining Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assessedStudents as $row): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars($row['student_email']) ?></div>
                                </td>
                                <td style="font-family: monospace;"><?= htmlspecialchars($row['tracking_no']) ?></td>
                                <td>₱<?= number_format($row['total_assessment'], 2) ?></td>
                                <td style="font-weight: 600; color: #ef4444;">₱<?= number_format($row['balance'], 2) ?></td>
                                <td>
                                    <button class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;" onclick='openPaymentModal(<?= json_encode($row) ?>)'>Record Payment</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($assessedStudents)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No assessed students awaiting payment.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Verify Online Payment Modal -->
    <div id="onlinePaymentModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="glass-panel" style="width:100%; max-width:400px; padding:30px;">
            <h3 style="margin-bottom:20px;">Verify Online Payment</h3>
            <form action="../actions/verify_payment.php" method="POST">
                <input type="hidden" name="payment_id" id="opay_payment_id">
                <input type="hidden" name="enrollment_id" id="opay_enrollment_id">
                <input type="hidden" name="action" value="Approve">
                <input type="hidden" name="return_to" value="dashboard.php">
                
                <div style="margin-bottom: 15px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Student</label>
                    <input type="text" id="opay_student_name" class="form-control" disabled style="background: rgba(100, 116, 139, 0.1); color: var(--text-muted); border: 1px solid var(--glass-border);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Remaining Balance (₱)</label>
                    <input type="text" id="opay_balance" class="form-control" disabled style="background: rgba(100, 116, 139, 0.1); color: var(--text-muted); border: 1px solid var(--glass-border);">
                </div>

                <div style="text-align: right; margin-bottom: 15px;">
                    <button type="button" id="oToggleBreakdownBtn" onclick="toggleOnlineBreakdown()" style="background: none; border: none; color: var(--primary-color); font-size: 0.8rem; font-weight: 600; cursor: pointer; text-decoration: underline;">View Full Breakdown</button>
                </div>

                <div id="oBreakdownSection" style="display:none; background: rgba(100, 116, 139, 0.1); border: 1px solid var(--glass-border); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <h4 style="font-size: 0.9rem; margin-top: 0; margin-bottom: 10px; color: var(--text-main);">Assessment Breakdown</h4>
                    <div id="oBreakdownContent" style="font-size: 0.85rem; color: var(--text-main);"></div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Reference Number</label>
                    <input type="text" id="opay_ref_no" class="form-control" disabled style="background: rgba(100, 116, 139, 0.1); color: var(--text-muted); border: 1px solid var(--glass-border); font-family: monospace;">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Amount Paid (₱)</label>
                    <input type="text" id="opay_amount" class="form-control" disabled style="background: rgba(100, 116, 139, 0.1); color: var(--text-muted); border: 1px solid var(--glass-border); font-weight: 600;">
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('onlinePaymentModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Verify Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Record Over-The-Counter Payment Modal -->
    <div id="paymentModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="glass-panel" style="width:100%; max-width:400px; padding:30px;">
            <h3 style="margin-bottom:20px;">Record Direct Payment</h3>
            <form action="../actions/record_cashier_payment.php" method="POST">
                <input type="hidden" name="enrollment_id" id="pay_enrollment_id">
                
                <div style="margin-bottom: 15px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Student</label>
                    <input type="text" id="pay_student_name" class="form-control" disabled style="background: rgba(100, 116, 139, 0.1); color: var(--text-muted); border: 1px solid var(--glass-border);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Remaining Balance (₱)</label>
                    <input type="text" id="pay_balance" class="form-control" disabled style="background: rgba(100, 116, 139, 0.1); color: var(--text-muted); border: 1px solid var(--glass-border);">
                </div>

                <div style="text-align: right; margin-bottom: 15px;">
                    <button type="button" id="toggleBreakdownBtn" onclick="toggleBreakdown()" style="background: none; border: none; color: var(--primary-color); font-size: 0.8rem; font-weight: 600; cursor: pointer; text-decoration: underline;">View Full Breakdown</button>
                </div>

                <div id="breakdownSection" style="display:none; background: rgba(100, 116, 139, 0.1); border: 1px solid var(--glass-border); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <h4 style="font-size: 0.9rem; margin-top: 0; margin-bottom: 10px; color: var(--text-main);">Assessment Breakdown</h4>
                    <div id="breakdownContent" style="font-size: 0.85rem; color: var(--text-main);"></div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Amount Paid (₱)</label>
                    <input type="number" step="0.01" name="amount" id="pay_amount" required class="form-control" placeholder="Enter amount received" style="border: 1px solid var(--glass-border); background: transparent; color: var(--text-main);">
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Payment Method</label>
                    <select name="payment_method" required class="form-control" style="border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main);">
                        <option value="Cash">Cash (Over-the-counter)</option>
                        <option value="GCash">GCash</option>
                    </select>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('paymentModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPaymentModal(data) {
            document.getElementById('pay_enrollment_id').value = data.enrollment_id;
            document.getElementById('pay_student_name').value = data.first_name + ' ' + data.last_name;
            document.getElementById('pay_balance').value = parseFloat(data.balance).toFixed(2);
            
            const breakdownContainer = document.getElementById('breakdownContent');
            const subjectPrices = {
                'Mathematics': 350,
                'Science': 350,
                'English': 300,
                'Filipino': 300,
                'Araling Panlipunan': 300,
                'MAPEH': 400,
                'TLE': 450,
                'Values Education': 250
            };

            let breakdownHTML = '<ul style="list-style: none; padding: 0; margin: 0;">';
            let subjectTotal = 0;
            
            if (data.enrolled_subjects) {
                const subjects = data.enrolled_subjects.split(', ');
                subjects.forEach(subj => {
                    const price = subjectPrices[subj.trim()] || 0;
                    subjectTotal += price;
                    breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span>${subj}</span><span>₱${price.toFixed(2)}</span></li>`;
                });
            } else {
                breakdownHTML += `<li style="color: var(--text-muted);">No subjects selected.</li>`;
            }
            
            breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-bottom: 5px; border-top: 1px solid var(--glass-border); padding-top: 5px; font-weight: bold;"><span>Subject Total</span><span>₱${subjectTotal.toFixed(2)}</span></li>`;
            
            let totalAssessed = parseFloat(data.total_assessment) || 0;
            let balance = parseFloat(data.balance) || 0;
            let paid = totalAssessed - balance;
            
            if (totalAssessed > 0) {
                let baseFee = totalAssessed - subjectTotal;
                breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-bottom: 5px; border-top: 1px solid var(--glass-border); padding-top: 5px;"><span>Base Tuition & Misc</span><span>₱${baseFee.toFixed(2)}</span></li>`;
                breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-top: 10px; font-weight: bold; color: var(--text-main);"><span>Total Assessment</span><span>₱${totalAssessed.toFixed(2)}</span></li>`;
                if (paid > 0) {
                    breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-top: 5px; color: #22c55e;"><span>Total Paid</span><span>- ₱${paid.toFixed(2)}</span></li>`;
                }
                breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-top: 10px; font-weight: bold; color: var(--primary-color);"><span>Remaining Balance</span><span>₱${balance.toFixed(2)}</span></li>`;
            }
            
            breakdownHTML += '</ul>';
            breakdownContainer.innerHTML = breakdownHTML;
            document.getElementById('breakdownSection').style.display = 'none';
            document.getElementById('toggleBreakdownBtn').textContent = 'View Full Breakdown';
            
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function toggleBreakdown() {
            const section = document.getElementById('breakdownSection');
            const btn = document.getElementById('toggleBreakdownBtn');
            if (section.style.display === 'none') {
                section.style.display = 'block';
                btn.textContent = 'Hide Breakdown';
            } else {
                section.style.display = 'none';
                btn.textContent = 'View Full Breakdown';
            }
        }

        function openOnlinePaymentModal(data) {
            document.getElementById('opay_payment_id').value = data.payment_id;
            document.getElementById('opay_enrollment_id').value = data.enrollment_id;
            document.getElementById('opay_student_name').value = data.first_name + ' ' + data.last_name;
            document.getElementById('opay_balance').value = parseFloat(data.balance).toFixed(2);
            document.getElementById('opay_ref_no').value = data.reference_no || 'N/A';
            document.getElementById('opay_amount').value = parseFloat(data.amount).toFixed(2);
            
            const breakdownContainer = document.getElementById('oBreakdownContent');
            const subjectPrices = {
                'Mathematics': 350,
                'Science': 350,
                'English': 300,
                'Filipino': 300,
                'Araling Panlipunan': 300,
                'MAPEH': 400,
                'TLE': 450,
                'Values Education': 250
            };

            let breakdownHTML = '<ul style="list-style: none; padding: 0; margin: 0;">';
            let subjectTotal = 0;
            
            if (data.enrolled_subjects) {
                const subjects = data.enrolled_subjects.split(', ');
                subjects.forEach(subj => {
                    const price = subjectPrices[subj.trim()] || 0;
                    subjectTotal += price;
                    breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span>${subj}</span><span>₱${price.toFixed(2)}</span></li>`;
                });
            } else {
                breakdownHTML += `<li style="color: var(--text-muted);">No subjects selected.</li>`;
            }
            
            breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-bottom: 5px; border-top: 1px solid var(--glass-border); padding-top: 5px; font-weight: bold;"><span>Subject Total</span><span>₱${subjectTotal.toFixed(2)}</span></li>`;
            
            let totalAssessed = parseFloat(data.total_assessment) || 0;
            let balance = parseFloat(data.balance) || 0;
            let paid = totalAssessed - balance;
            
            if (totalAssessed > 0) {
                let baseFee = totalAssessed - subjectTotal;
                breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-bottom: 5px; border-top: 1px solid var(--glass-border); padding-top: 5px;"><span>Base Tuition & Misc</span><span>₱${baseFee.toFixed(2)}</span></li>`;
                breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-top: 10px; font-weight: bold; color: var(--text-main);"><span>Total Assessment</span><span>₱${totalAssessed.toFixed(2)}</span></li>`;
                if (paid > 0) {
                    breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-top: 5px; color: #22c55e;"><span>Total Paid</span><span>- ₱${paid.toFixed(2)}</span></li>`;
                }
                breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-top: 10px; font-weight: bold; color: var(--primary-color);"><span>Remaining Balance</span><span>₱${balance.toFixed(2)}</span></li>`;
            }
            
            breakdownHTML += '</ul>';
            breakdownContainer.innerHTML = breakdownHTML;
            document.getElementById('oBreakdownSection').style.display = 'none';
            document.getElementById('oToggleBreakdownBtn').textContent = 'View Full Breakdown';
            
            document.getElementById('onlinePaymentModal').style.display = 'flex';
        }

        function toggleOnlineBreakdown() {
            const section = document.getElementById('oBreakdownSection');
            const btn = document.getElementById('oToggleBreakdownBtn');
            if (section.style.display === 'none') {
                section.style.display = 'block';
                btn.textContent = 'Hide Breakdown';
            } else {
                section.style.display = 'none';
                btn.textContent = 'View Full Breakdown';
            }
        }
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>