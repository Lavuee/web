<?php
// Secure the action for Cashier or Admin roles
require_once 'auth.php';
check_auth();

if (!in_array(strtolower($_SESSION['role']), ['admin', 'cashier'])) {
    die("Unauthorized Access.");
}

require_once '../config/db.php';

// 1. Fetch all processed payments
$searchQuery = trim($_GET['search'] ?? '');
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Updated JOINs to match the new strict CMS schema
$query = "
    SELECT p.payment_id, p.amount, p.payment_method, p.status, p.payment_date, p.official_receipt_no, p.reference_no,
           e.grade_level, sec.section_name,
           s.first_name, s.last_name, s.lrn,
           u.uid AS student_uid,
           c.uid AS cashier_uid
    FROM payments p
    JOIN enrollments e ON p.enrollment_id = e.enrollment_id
    JOIN students s ON e.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN sections sec ON e.section_id = sec.section_id
    LEFT JOIN users c ON p.cashier_id = c.user_id
    WHERE p.status != 'Pending' 
";

$conditions = [];
$params = [];

if (!empty($searchQuery)) {
    $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR u.uid LIKE ? OR s.lrn LIKE ?)";
    $searchTerm = "%" . $searchQuery . "%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

if (!empty($startDate) && !empty($endDate)) {
    $conditions[] = "DATE(p.payment_date) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY p.payment_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Set HTTP Headers to force a file download
$filename = "Payment_History_" . date('Ymd_His') . ".csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen('php://output', 'w');
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); 

// 3. Write the CSV headers (Updated for UID instead of email)
fputcsv($output, [
    'Payment ID', 'S.I. No.', 'Reference No.', 'Student UID', 'Student Name', 'LRN',
    'Grade Level', 'Section', 'Amount Paid', 'Payment Method', 'Status',
    'Date Processed', 'Processed By (Cashier UID)'
]);

// 4. Write the payment data
foreach ($payments as $row) {
    $studentName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
    $cashierUid = htmlspecialchars($row['cashier_uid'] ?? 'N/A');
    $sectionName = htmlspecialchars($row['section_name'] ?? 'TBA');
    
    fputcsv($output, [
        $row['payment_id'], $row['official_receipt_no'] ?? '', $row['reference_no'] ?? '', 
        $row['student_uid'], $studentName, $row['lrn'],
        $row['grade_level'], $sectionName, $row['amount'], $row['payment_method'], $row['status'],
        " " . date('M d, Y h:i A', strtotime($row['payment_date'])), $cashierUid
    ]);
}

fclose($output);
exit();
?>