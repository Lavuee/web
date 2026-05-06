<?php
require_once '../actions/auth.php';
check_registrar();
require_once '../config/db.php';

$searchQuery = trim($_GET['search'] ?? '');

try {
    $query = "
        SELECT e.enrollment_id, e.grade_level, s.first_name, s.last_name, u.email as student_email 
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        WHERE e.status = 'Enrolled'
    ";
    
    $params = [];
    if (!empty($searchQuery)) {
        $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR u.email LIKE ?)";
        $searchTerm = "%" . $searchQuery . "%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
    }
    
    $query .= " ORDER BY s.last_name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include 'includes/registrar_header.php'; 
?>
<body>
    <div class="layout">
        <?php include 'includes/registrar_sidebar.php'; ?>
        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Grades & Transcripts</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Access academic records and generate official transcripts.</p>

            <div style="margin-bottom: 50px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0; color: var(--text-main);">Enrolled Students</h3>
                    <form method="GET" action="grades_transcripts.php" style="display: flex; align-items: center;">
                        <input type="text" name="search" placeholder="Search student name..." value="<?= htmlspecialchars($searchQuery) ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; width: 260px; outline: none;">
                        <button type="submit" style="display: none;"></button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Grade Level</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $row): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($row['student_email']) ?></td>
                                    <td><?= htmlspecialchars($row['grade_level']) ?></td>
                                    <td>
                                        <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" onclick="alert('Transcript generation print module will be available soon.')"><i class="bi bi-printer"></i> Transcript</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($students)): ?>
                                <tr><td colspan="4" style="text-align: center; padding: 30px;">No enrolled students found.</td></tr>
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