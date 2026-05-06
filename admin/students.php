<?php
// Secures the module for Admin access
require_once '../actions/auth.php';
check_admin();

// Connect to the database
require_once '../config/db.php';

// 1. Capture the selected filters from the URL
$gradeFilter = $_GET['grade'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

try {
    // 2. Build the base query
    $query = "
        SELECT e.enrollment_id, e.tracking_no, e.status, e.balance, e.total_assessment, e.grade_level,
               s.student_id, s.first_name, s.last_name, 
               u.email as student_email 
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
    ";
    
    $conditions = [];
    $params = [];

    // 3. Add Grade Filter Condition
    if (!empty($gradeFilter)) {
        $conditions[] = "e.grade_level = ?";
        $params[] = $gradeFilter;
    }

    // Add Status Filter Condition
    if (!empty($statusFilter)) {
        $conditions[] = "e.status = ?";
        $params[] = $statusFilter;
    }

    // 4. Add Search Query Condition (Searches Name, Email, or Tracking No)
    if (!empty($searchQuery)) {
        $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR u.email LIKE ? OR e.tracking_no LIKE ?)";
        $searchTerm = "%" . $searchQuery . "%";
        // Push the search term 4 times for the 4 different OR conditions
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    // 5. Append conditions to the query if any exist
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    // Finish the query by ordering it
    $query .= " ORDER BY e.created_at DESC";

    // Prepare and execute with the safe parameters
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Badge color mapping for dynamic status styling
$badgeColors = [
    'Pending'  => 'background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);',
    'Assessed' => 'background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);',
    'Enrolled' => 'background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);',
    'Rejected' => 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);'
];

// Include the modular header
include 'includes/admin_header.php'; 
?>
<body>
    <div class="layout">
        
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Student Management</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Manage student enrollments, status, and account records.</p>

            <div style="margin-bottom: 50px;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0; color: var(--text-main);">Enrollment Roster</h3>
                    
                    <form method="GET" action="students.php" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        
                        <div style="display: flex; align-items: center; position: relative;">
                            <input type="text" name="search" placeholder="Search name, email, or Student ID..." value="<?= htmlspecialchars($searchQuery) ?>" style="padding: 8px 12px 8px 32px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; width: 260px; outline: none; transition: 0.3s;">
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label for="grade" style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">Filter:</label>
                            <select name="grade" id="grade" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
                                <option value="">All Grades</option>
                                <option value="Grade 7" <?= $gradeFilter === 'Grade 7' ? 'selected' : '' ?>>Grade 7</option>
                                <option value="Grade 8" <?= $gradeFilter === 'Grade 8' ? 'selected' : '' ?>>Grade 8</option>
                                <option value="Grade 9" <?= $gradeFilter === 'Grade 9' ? 'selected' : '' ?>>Grade 9</option>
                                <option value="Grade 10" <?= $gradeFilter === 'Grade 10' ? 'selected' : '' ?>>Grade 10</option>
                            </select>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            <select name="status" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Assessed" <?= $statusFilter === 'Assessed' ? 'selected' : '' ?>>Assessed</option>
                                <option value="Enrolled" <?= $statusFilter === 'Enrolled' ? 'selected' : '' ?>>Enrolled</option>
                            </select>
                        </div>

                        <button type="submit" style="display: none;"></button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Grade</th>
                            <th>Student ID</th>
                            <th>Status</th>
                            <th>Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $row): ?>
                            <tr>
                                <td style="font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($row['student_email']) ?></td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($row['grade_level'] ?? 'N/A') ?></td>
                                <td style="font-family: monospace; font-size: 0.95rem;"><?= htmlspecialchars($row['tracking_no']) ?></td>
                                <td>
                                    <span class="badge" style="<?= $badgeColors[$row['status']] ?? '' ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600;">₱<?= number_format($row['balance'], 2) ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="view_student.php?id=<?= $row['student_id'] ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; text-decoration: none;">
                                            View
                                        </a>
                                        
                                        <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" onclick='openModal(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    <?php if ($searchQuery || $gradeFilter): ?>
                                        No students found matching your search or filter.
                                    <?php else: ?>
                                        No students found in the database.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </main>
    </div>

    <div id="editModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="glass-panel" style="width:90%; max-width:480px; padding:30px;">
            <h3 style="margin-bottom:20px;">Edit Enrollment</h3>
            <form action="../actions/update_student.php" method="POST">
                <input type="hidden" name="enrollment_id" id="m_id">
                
                <label style="font-size:0.85rem; font-weight:600;">Status</label>
                <select name="status" id="m_status" style="width:100%; padding:10px; margin:10px 0 15px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                    <option value="Pending">Pending</option>
                    <option value="Assessed">Assessed</option>
                    <option value="Enrolled">Enrolled</option>
                    <option value="Rejected">Rejected</option>
                </select>
                
                <label style="font-size:0.85rem; font-weight:600;">Total Tuition Assessment (₱)</label>
                <input type="number" step="0.01" name="total_assessment" id="m_total" style="width:100%; padding:10px; margin:10px 0 25px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                    <button type="button" class="btn btn-outline" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4);" onclick="confirmDelete()">Delete</button>
                    <div style="display:flex; gap:10px; flex:1; justify-content:flex-end;">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
            
            <form method="POST" action="../actions/delete_student.php" id="delete-form" style="display:none;">
                <input type="hidden" name="enrollment_id" id="d_id">
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function openModal(data) {
            document.getElementById('m_id').value = data.enrollment_id;
            document.getElementById('d_id').value = data.enrollment_id;
            document.getElementById('m_status').value = data.status;
            document.getElementById('m_total').value = data.total_assessment;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function confirmDelete() {
            if (confirm("Are you sure you want to permanently delete this enrollment? This will also remove associated grades and payments.")) {
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</body>
</html>