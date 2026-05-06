<?php
require_once 'auth.php';
check_admin();
require_once '../config/db.php';

$gradeFilter = $_GET['grade'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');
$sortFilter  = $_GET['sort'] ?? 'grade_asc';

// Look at the subjects table only, as teacher assignments now happen per section in class_offerings
$query = "SELECT subject_code, subject_name, grade_level FROM subjects";

$conditions = [];
$params = [];

if (!empty($gradeFilter)) {
    $conditions[] = "grade_level = ?";
    $params[] = $gradeFilter;
}

if (!empty($searchQuery)) {
    $conditions[] = "(subject_name LIKE ? OR subject_code LIKE ?)";
    $searchTerm = "%" . $searchQuery . "%";
    array_push($params, $searchTerm, $searchTerm);
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

switch ($sortFilter) {
    case 'name_asc': $query .= " ORDER BY subject_name ASC"; break;
    case 'name_desc': $query .= " ORDER BY subject_name DESC"; break;
    case 'grade_desc': $query .= " ORDER BY grade_level DESC, subject_name ASC"; break;
    case 'grade_asc':
    default: $query .= " ORDER BY grade_level ASC, subject_name ASC"; break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "Curriculum_Subjects_" . date('Ymd_His') . ".csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen('php://output', 'w');
fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); 

fputcsv($output, ['Subject Code', 'Subject Name', 'Grade Level']);
foreach ($subjects as $row) {
    fputcsv($output, [$row['subject_code'], $row['subject_name'], $row['grade_level']]);
}

fclose($output);
exit();
?>