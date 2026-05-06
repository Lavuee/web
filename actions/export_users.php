<?php
require_once 'auth.php';
check_admin();
require_once '../config/db.php';

$search      = trim($_GET['search'] ?? '');
$filter_role = $_GET['role'] ?? '';
$where       = ["u.role != 'Student'"];
$params      = [];

if ($search !== '') { 
    $where[] = "u.uid LIKE ?"; 
    $params[] = "%{$search}%"; 
}
if ($filter_role !== '') { 
    $where[] = "u.role = ?"; 
    $params[] = $filter_role; 
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Joined with 'teachers' table instead of 'faculty'
$query = "
    SELECT u.user_id, u.uid, u.role, u.status, u.created_at, 
           t.first_name, t.last_name, t.department 
    FROM users u 
    LEFT JOIN teachers t ON u.user_id = t.user_id 
    {$where_sql} 
    ORDER BY u.role, u.uid
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "Staff_Accounts_" . date('Ymd_His') . ".csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen('php://output', 'w');
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); 

fputcsv($output, ['User ID', 'UID / Username', 'Role', 'Status', 'First Name', 'Last Name', 'Department', 'Date Created']);
foreach ($users as $row) {
    fputcsv($output, [$row['user_id'], $row['uid'], $row['role'], $row['status'], $row['first_name'] ?? 'N/A', $row['last_name'] ?? 'N/A', $row['department'] ?? 'N/A', " " . date('M d, Y h:i A', strtotime($row['created_at']))]);
}

fclose($output);
exit();
?>