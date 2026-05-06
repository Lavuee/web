<?php
require_once '../actions/auth.php';
check_cashier();

require_once '../config/db.php';

try {
    // Capture filters from GET request
    $searchQuery = trim($_GET['search'] ?? '');
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';

    // Fetch daily collection summary
    $today = date('Y-m-d');
    
    // Adjust daily summary query to respect date filters if present, otherwise use today
    $dailySummaryConditions = ["status = 'Verified'"];
    $dailySummaryParams = [];
    if (!empty($startDate) && !empty($endDate)) {
        $dailySummaryConditions[] = "DATE(payment_date) BETWEEN ? AND ?";
        $dailySummaryParams[] = $startDate;
        $dailySummaryParams[] = $endDate;
    } else {
        $dailySummaryConditions[] = "DATE(payment_date) = ?";
        $dailySummaryParams[] = $today;
    }
    $dailySummaryStmt = $pdo->prepare("SELECT SUM(amount) as total, COUNT(*) as count FROM payments WHERE " . implode(' AND ', $dailySummaryConditions));
    $dailySummaryStmt->execute($dailySummaryParams);
    $dailySummary = $dailySummaryStmt->fetch();
    $totalCollectedToday = $dailySummary['total'] ?? 0;
    $countCollectedToday = $dailySummary['count'] ?? 0;

    // Fetch non-pending payments
    $query = "
        SELECT p.payment_id, p.enrollment_id, p.amount, p.payment_method, p.status, p.payment_date,
               s.first_name, s.last_name, 
               u.email AS student_email 
        FROM payments p 
        JOIN enrollments e ON p.enrollment_id = e.enrollment_id 
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE p.status != 'Pending'
    ";

    $conditions = [];
    $params = [];

    if (!empty($searchQuery)) {
        $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR u.email LIKE ? OR e.tracking_no LIKE ?)";
        $searchTerm = "%" . $searchQuery . "%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    if (!empty($startDate) && !empty($endDate)) {
        $conditions[] = "DATE(p.payment_date) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    }

    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions); // This line was causing the error
    }

    $query .= "
        ORDER BY p.payment_date DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$statusColors = [
    'Verified' => 'background: var(--badge-verified-bg); color: var(--badge-verified-color); border: 1px solid var(--badge-verified-border);',
    'Rejected' => 'background: var(--badge-rejected-bg); color: var(--badge-rejected-color); border: 1px solid var(--badge-rejected-border);'
];

$methodColors = [
    'GCash' => 'background: var(--badge-gcash-bg); color: var(--badge-gcash-color); border: 1px solid var(--badge-gcash-border);',
    'Cash'  => 'background: var(--badge-cash-bg); color: var(--badge-cash-color); border: 1px solid var(--badge-cash-border);'
];

include 'includes/cashier_header.php'; 
?>
<body>

    <div class="layout">
        
        <?php include 'includes/cashier_sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Payment History</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Log of verified and rejected financial transactions.</p>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 45px; border-bottom: 1px solid var(--glass-border); padding-bottom: 25px;">
                <div style="flex: 1; min-width: 200px;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin: 0;">Collected Today</p>
                    <h3 style="font-size: 2.8rem; margin: 5px 0 0 0; font-weight: 300; color: var(--badge-verified-color);">₱<?= number_format($totalCollectedToday, 2) ?></h3>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin: 0;">Transactions Today</p>
                    <h3 style="font-size: 2.8rem; margin: 5px 0 0 0; font-weight: 300; color: var(--primary-color);"><?= number_format($countCollectedToday) ?></h3>
                </div>
            </div>

            <div style="margin-bottom: 50px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0;">All Processed Payments</h3>
                    <a href="../actions/export_payments.php?search=<?= urlencode($searchQuery) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn btn-outline" style="color: var(--primary-color); border-color: var(--primary-color)55;">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Export to CSV
                    </a>
                </div>
                <form method="GET" action="payment_history.php" style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="search" placeholder="Search student, email, or tracking no..." value="<?= htmlspecialchars($searchQuery) ?>" class="form-control" style="width:280px;">
                    <label for="start_date" style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">From:</label>
                    <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-control" style="width:150px; color-scheme: var(--theme-color-scheme, light);">
                    <label for="end_date" style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">To:</label>
                    <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-control" style="width:150px; color-scheme: var(--theme-color-scheme, light);">
                    <button type="submit" class="btn btn-outline" style="padding:8px 16px;">Filter</button>
                    <?php if (!empty($searchQuery) || !empty($startDate) || !empty($endDate)): ?> 
                        <a href="payment_history.php" class="btn btn-outline" style="padding:8px 16px; color: var(--badge-rejected-color); border-color: var(--badge-rejected-border);">Clear Filters</a>
                    <?php endif; ?>
                </form>
                <div class="table-responsive">
                    <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date Processed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $row): ?>
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
                                <td style="font-weight: 600;">₱<?= number_format($row['amount'], 2) ?></td>
                                <td>
                                    <span class="badge" style="<?= $statusColors[$row['status']] ?? '' ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('m/d/Y h:i A', strtotime($row['payment_date'])) ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <?php if ($row['status'] === 'Verified'): ?>
                                            <a href="print_receipt.php?id=<?= $row['payment_id'] ?>" target="_blank" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; color: #22c55e; border-color: rgba(34, 197, 94, 0.4); text-decoration: none;"><i class="bi bi-printer"></i> Print</a>
                                        <?php endif; ?>
                                        <form action="../actions/verify_payment.php" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to revert this payment to Pending?');">
                                            <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                            <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                            <input type="hidden" name="return_to" value="payment_history.php">
                                            <button type="submit" name="action" value="Revert" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; color: #f59e0b; border-color: rgba(245, 158, 11, 0.4);"><i class="bi bi-arrow-counterclockwise"></i> Revert</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No processed payments found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>