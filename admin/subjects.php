<?php
// Secures the module strictly for administrative access.
require_once '../actions/auth.php';
check_admin();

// Establishes the database connection.
require_once '../config/db.php';

// 1. Capture the selected filters and search query from the URL
$gradeFilter = $_GET['grade'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');
$sortFilter  = $_GET['sort'] ?? 'grade_asc';
$successMsg  = $_GET['success'] ?? '';
$errorMsg    = $_GET['error'] ?? '';

try {
    // 2. Build the base query
    $query = "
        SELECT s.subject_id, s.subject_code, s.subject_name, s.grade_level, s.faculty_id, 
               u.email AS faculty_email 
        FROM subjects s
        LEFT JOIN faculty f ON s.faculty_id = f.faculty_id
        LEFT JOIN users u ON f.user_id = u.user_id
    ";
    
    $conditions = [];
    $params = [];

    // 3. Add Grade Filter Condition
    if (!empty($gradeFilter)) {
        $conditions[] = "s.grade_level = ?";
        $params[] = $gradeFilter;
    }

    // 4. Add Search Query Condition (Searches Subject Name or Faculty Email)
    if (!empty($searchQuery)) {
        $conditions[] = "(s.subject_name LIKE ? OR u.email LIKE ?)";
        $searchTerm = "%" . $searchQuery . "%";
        array_push($params, $searchTerm, $searchTerm);
    }

    // 5. Append conditions to the query if any exist
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    // 6. Apply Sorting Logic
    switch ($sortFilter) {
        case 'name_asc':
            $query .= " ORDER BY s.subject_name ASC";
            break;
        case 'name_desc':
            $query .= " ORDER BY s.subject_name DESC";
            break;
        case 'grade_desc':
            $query .= " ORDER BY s.grade_level DESC, s.subject_name ASC";
            break;
        case 'grade_asc':
        default:
            $query .= " ORDER BY s.grade_level ASC, s.subject_name ASC";
            break;
    }

    // Prepare and execute with the safe parameters
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $subjects = $stmt->fetchAll();

    // 7. Fetch all registered Faculty members to populate the modal assignment dropdown
    $facultyStmt = $pdo->query("
    SELECT f.faculty_id, u.email 
    FROM faculty f 
    JOIN users u ON f.user_id = u.user_id 
    WHERE u.role = 'Faculty' AND f.is_active = 1 
    ORDER BY u.email ASC
    ");
    $faculty_members = $facultyStmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Dynamic badge colors for different grade levels
$gradeColors = [
    'Grade 7'  => 'background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);',
    'Grade 8'  => 'background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);',
    'Grade 9'  => 'background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);',
    'Grade 10' => 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);',
];

// Include the modular header
include 'includes/admin_header.php'; 
?>
<body>
    <div class="layout">
        
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2 style="margin-bottom: 5px;">Subject Management</h2>
                    <p class="text-muted" style="font-size: 0.9rem; margin: 0;">Build the curriculum and assign faculty to specific classes.</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('add')">
                    + Add New Subject
                </button>
            </div>

            <?php if (!empty($successMsg)): ?>
                <div style="padding: 12px; margin-bottom: 20px; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e; border-radius: 8px;">
                    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($successMsg) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div style="padding: 12px; margin-bottom: 20px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; border-radius: 8px;">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 50px;">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0; color: var(--text-main);">Curriculum List</h3>
                    
                    <form method="GET" action="subjects.php" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        
                        <div style="display: flex; align-items: center; position: relative;">
                            <input type="text" name="search" placeholder="Search subject or faculty..." value="<?= htmlspecialchars($searchQuery) ?>" style="padding: 8px 12px 8px 32px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; width: 240px; outline: none; transition: 0.3s;">
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <select name="grade" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
                                <option value="">All Grades</option>
                                <option value="Grade 7" <?= $gradeFilter === 'Grade 7' ? 'selected' : '' ?>>Grade 7</option>
                                <option value="Grade 8" <?= $gradeFilter === 'Grade 8' ? 'selected' : '' ?>>Grade 8</option>
                                <option value="Grade 9" <?= $gradeFilter === 'Grade 9' ? 'selected' : '' ?>>Grade 9</option>
                                <option value="Grade 10" <?= $gradeFilter === 'Grade 10' ? 'selected' : '' ?>>Grade 10</option>
                            </select>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <select name="sort" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
                                <option value="grade_asc" <?= $sortFilter === 'grade_asc' ? 'selected' : '' ?>>Sort: Grade (Low to High)</option>
                                <option value="grade_desc" <?= $sortFilter === 'grade_desc' ? 'selected' : '' ?>>Sort: Grade (High to Low)</option>
                                <option value="name_asc" <?= $sortFilter === 'name_asc' ? 'selected' : '' ?>>Sort: Name (A to Z)</option>
                                <option value="name_desc" <?= $sortFilter === 'name_desc' ? 'selected' : '' ?>>Sort: Name (Z to A)</option>
                            </select>
                        </div>

                        <a href="../actions/export_subjects.php?search=<?= urlencode($searchQuery) ?>&grade=<?= urlencode($gradeFilter) ?>&sort=<?= urlencode($sortFilter) ?>" class="btn btn-outline" style="color: #10b981; border-color: rgba(16, 185, 129, 0.4);">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </a>

                        <button type="submit" style="display: none;"></button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Grade Level</th>
                            <th>Assigned Faculty</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $row): ?>
                            <tr>
                                <td style="font-family: monospace; font-size: 0.95rem; font-weight: 600; color: var(--primary-color);">
                                    <?= htmlspecialchars($row['subject_code']) ?>
                                </td>
                                <td style="font-weight: 600; color: var(--text-main);">
                                    <?= htmlspecialchars($row['subject_name']) ?>
                                </td>
                                <td>
                                    <span class="badge" style="<?= $gradeColors[$row['grade_level']] ?? 'background: var(--glass-border); color: var(--text-main);' ?>">
                                        <?= htmlspecialchars($row['grade_level']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['faculty_email']): ?>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="bi bi-person-badge text-primary" style="font-size: 1.1rem;"></i>
                                            <span class="text-muted" style="font-size: 0.9rem;"><?= htmlspecialchars($row['faculty_email']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px dashed rgba(239, 68, 68, 0.4);">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" onclick='openModal("edit", <?= json_encode($row) ?>)'>
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <form action="../actions/delete_subject.php" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to permanently delete this subject?');">
                                            <input type="hidden" name="subject_id" value="<?= $row['subject_id'] ?>">
                                            <button type="submit" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);"><i class="bi bi-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($subjects)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    <?php if ($searchQuery || $gradeFilter): ?>
                                        No subjects found matching your search or filters.
                                    <?php else: ?>
                                        No subjects have been added to the curriculum yet.
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

    <div id="subjectModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="glass-panel" style="width:100%; max-width:450px; padding:30px; position:relative;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="modalTitle" style="margin: 0;">Add Subject</h3>
                <button type="button" onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
            </div>
            
            <form action="../actions/save_subject_admin.php" method="POST">
                <input type="hidden" name="subject_id" id="modalSubjectId">

                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Subject Code</label>
                    <input type="text" name="subject_code" id="modalSubjectCode" required placeholder="e.g. MATH7" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Subject Name</label>
                    <input type="text" name="subject_name" id="modalSubjectName" required placeholder="e.g. Advanced Algebra" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Target Grade Level</label>
                    <select name="grade_level" id="modalGradeLevel" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                        <option value="">Select Grade...</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                    </select>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">Assign Faculty (Optional)</label>
                    <select name="faculty_id" id="modalFacultyId" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                        <option value="">-- Leave Unassigned --</option>
                        <?php foreach($faculty_members as $fac): ?>
                            <option value="<?= $fac['faculty_id'] ?>">
                                <?= htmlspecialchars($fac['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Curriculum</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        const modal = document.getElementById('subjectModal');
        
        function openModal(type, data = null) {
            document.getElementById('modalTitle').innerText = type === 'add' ? 'Add New Subject' : 'Edit Subject';
            
            if (type === 'edit') {
                document.getElementById('modalSubjectId').value = data.subject_id;
                document.getElementById('modalSubjectCode').value = data.subject_code;
                document.getElementById('modalSubjectName').value = data.subject_name;
                document.getElementById('modalGradeLevel').value = data.grade_level;
                document.getElementById('modalFacultyId').value = data.faculty_id || '';
            } else {
                document.getElementById('modalSubjectId').value = '';
                document.getElementById('modalSubjectCode').value = '';
                document.getElementById('modalSubjectName').value = '';
                document.getElementById('modalGradeLevel').value = '';
                document.getElementById('modalFacultyId').value = '';
            }
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }
    </script>
</body>
</html>