<?php
require_once '../actions/auth.php';
check_registrar();
require_once '../config/db.php';

$searchQuery = trim($_GET['search'] ?? '');
$gradeFilter = $_GET['grade'] ?? '';
$statusFilter = $_GET['status'] ?? '';

try {
    $query = "
        SELECT e.enrollment_id, e.tracking_no, e.status, e.grade_level, e.strand, e.total_assessment,
               s.*, 
               u.email as student_email 
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
    ";
    
    $conditions = [];
    $params = [];

    if (!empty($gradeFilter)) {
        $conditions[] = "e.grade_level = ?";
        $params[] = $gradeFilter;
    }

    if (!empty($statusFilter)) {
        $conditions[] = "e.status = ?";
        $params[] = $statusFilter;
    }

    if (!empty($searchQuery)) {
        $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR u.email LIKE ? OR e.tracking_no LIKE ?)";
        $searchTerm = "%" . $searchQuery . "%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY e.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Group the students by their status for separate tables
$groupedStudents = [
    'Pending' => [],
    'Assessed' => [],
    'Enrolled' => [],
    'Rejected' => []
];
foreach ($students as $student) {
    $groupedStudents[$student['status']][] = $student;
}

$badgeColors = [
    'Pending'  => 'background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);',
    'Assessed' => 'background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);',
    'Enrolled' => 'background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);',
    'Rejected' => 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);'
];

include 'includes/registrar_header.php'; 
?>
<body>
    <div class="layout">
        <?php include 'includes/registrar_sidebar.php'; ?>
        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Student Records</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">View and manage registered student profiles and their enrollment data.</p>

            <div class="glass-panel" style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <h3 style="font-size: 1.1rem; margin: 0; color: var(--text-main);">Filter Records</h3>
                    <form method="GET" action="student_records.php" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <input type="text" name="search" placeholder="Search name, email, or Student ID..." value="<?= htmlspecialchars($searchQuery) ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; width: 260px; outline: none;">
                        <select name="grade" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
                            <option value="">All Grades</option>
                            <option value="Grade 7" <?= $gradeFilter === 'Grade 7' ? 'selected' : '' ?>>Grade 7</option>
                            <option value="Grade 8" <?= $gradeFilter === 'Grade 8' ? 'selected' : '' ?>>Grade 8</option>
                            <option value="Grade 9" <?= $gradeFilter === 'Grade 9' ? 'selected' : '' ?>>Grade 9</option>
                            <option value="Grade 10" <?= $gradeFilter === 'Grade 10' ? 'selected' : '' ?>>Grade 10</option>
                            <option value="Grade 11" <?= $gradeFilter === 'Grade 11' ? 'selected' : '' ?>>Grade 11</option>
                            <option value="Grade 12" <?= $gradeFilter === 'Grade 12' ? 'selected' : '' ?>>Grade 12</option>
                        </select>
                        <select name="status" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Assessed" <?= $statusFilter === 'Assessed' ? 'selected' : '' ?>>Assessed</option>
                            <option value="Enrolled" <?= $statusFilter === 'Enrolled' ? 'selected' : '' ?>>Enrolled</option>
                            <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                        <button type="submit" style="display: none;"></button>
                    </form>
                </div>
            </div>

            <?php foreach (['Pending', 'Assessed', 'Enrolled', 'Rejected'] as $statusGrp): ?>
                <?php if (!empty($statusFilter) && $statusFilter !== $statusGrp) continue; ?>
                <div class="glass-panel" style="margin-bottom: 30px;">
                    <h3 style="font-size: 1.1rem; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;"><?= $statusGrp ?> Students</h3>
                    <div class="table-responsive">
                        <table class="table-wrapper">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Grade</th>
                                    <th>Student ID</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groupedStudents[$statusGrp] as $row): ?>
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
                                        <td>
                                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" onclick='openViewModal(<?= json_encode($row) ?>)'>View Details</button>
                                                <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;" onclick='openStatusModal(<?= json_encode($row) ?>)'>Update Status</button>
                                                <?php if ($statusGrp === 'Rejected'): ?>
                                                    <form action="../actions/delete_student.php" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to permanently delete this rejected application?');">
                                                        <input type="hidden" name="enrollment_id" value="<?= $row['enrollment_id'] ?>">
                                                        <input type="hidden" name="return_to" value="student_records.php">
                                                        <button type="submit" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($groupedStudents[$statusGrp])): ?>
                                    <tr><td colspan="6" style="text-align: center; padding: 30px;">No <?= strtolower($statusGrp) ?> students found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="glass-panel" style="width:100%; max-width:400px; padding:30px;">
            <h3 style="margin-bottom:20px;">Update Status</h3>
            <form action="../actions/update_status.php" method="POST">
                <input type="hidden" name="enrollment_id" id="m_id">
                <input type="hidden" name="return_to" value="student_records.php">
                
                <label style="font-size:0.85rem; font-weight:600;">Status</label>
                <select name="status" id="m_status" style="width:100%; padding:10px; margin:10px 0 25px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                    <option value="Pending">Pending</option>
                    <option value="Assessed">Assessed</option>
                </select>
                
                <label style="font-size:0.85rem; font-weight:600;">Total Tuition Assessment (₱)</label>
                <input type="number" step="0.01" name="total_assessment" id="m_total" style="width:100%; padding:10px; margin:10px 0 25px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <button type="button" class="btn btn-outline" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4);" onclick="confirmDelete()">Delete</button>
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('statusModal').style.display='none'">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Change</button>
                    </div>
                </div>
            </form>
            <form method="POST" action="../actions/delete_student.php" id="delete-form" style="display:none;">
                <input type="hidden" name="enrollment_id" id="d_id">
                <input type="hidden" name="return_to" value="student_records.php">
            </form>
        </div>
    </div>

    <!-- View Application Details Modal -->
    <div id="viewModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="glass-panel" style="background: var(--bg-color); backdrop-filter: none; -webkit-backdrop-filter: none; width:100%; max-width:800px; padding:40px; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h3 style="margin:0; font-size: 1.5rem;">Student Details</h3>
                <button class="btn btn-outline" style="border:none; padding:5px; font-size: 1.5rem; line-height: 1;" onclick="document.getElementById('viewModal').style.display='none'">&times;</button>
            </div>
            
            <h4 style="font-size: 1.1rem; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 8px;">Student Information</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; font-size: 1rem; color: var(--text-main);">
                <div><strong class="text-muted">Name:</strong> <span id="v_name"></span></div>
                <div><strong class="text-muted">Email:</strong> <span id="v_email"></span></div>
                <div><strong class="text-muted">LRN:</strong> <span id="v_lrn"></span></div>
                <div><strong class="text-muted">DOB:</strong> <span id="v_dob"></span></div>
                <div><strong class="text-muted">Gender:</strong> <span id="v_gender"></span></div>
                <div><strong class="text-muted">Contact:</strong> <span id="v_contact"></span></div>
                <div style="grid-column: span 2;"><strong class="text-muted">Address:</strong> <span id="v_address"></span></div>
            </div>

            <h4 style="font-size: 1.1rem; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 8px;">Academic Details</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; font-size: 1rem; color: var(--text-main);">
                <div><strong class="text-muted">Tracking No:</strong> <span id="v_tracking"></span></div>
                <div><strong class="text-muted">Incoming Grade:</strong> <span id="v_grade"></span></div>
                <div><strong class="text-muted">Track / Strand:</strong> <span id="v_strand"></span></div>
                <div><strong class="text-muted">Previous School:</strong> <span id="v_prev_school"></span></div>
            </div>

            <h4 style="font-size: 1.1rem; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 8px;">Guardian Details</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; font-size: 1rem; color: var(--text-main);">
                <div><strong class="text-muted">Name:</strong> <span id="v_guardian_name"></span></div>
                <div><strong class="text-muted">Relationship:</strong> <span id="v_guardian_rel"></span></div>
                <div><strong class="text-muted">Contact:</strong> <span id="v_guardian_contact"></span></div>
            </div>

            <h4 style="font-size: 1.1rem; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 8px;">Uploaded Documents</h4>
            <div id="v_documents" style="font-size: 1rem; margin-bottom: 20px; display: flex; flex-direction: column; gap: 10px;">
                <!-- Document links will be injected here -->
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function openStatusModal(data) {
            document.getElementById('m_id').value = data.enrollment_id;
            document.getElementById('d_id').value = data.enrollment_id;
            document.getElementById('m_status').value = data.status;
            document.getElementById('m_total').value = data.total_assessment;
            document.getElementById('statusModal').style.display = 'flex';
        }

        function confirmDelete() {
            if (confirm("Are you sure you want to permanently delete this student record? This cannot be undone.")) {
                document.getElementById('delete-form').submit();
            }
        }

        function openViewModal(data) {
            document.getElementById('v_name').textContent = data.first_name + ' ' + (data.middle_name ? data.middle_name + ' ' : '') + data.last_name + (data.suffix ? ' ' + data.suffix : '');
            document.getElementById('v_email').textContent = data.student_email;
            document.getElementById('v_lrn').textContent = data.lrn || 'N/A';
            document.getElementById('v_dob').textContent = data.date_of_birth || 'N/A';
            document.getElementById('v_gender').textContent = data.gender || 'N/A';
            document.getElementById('v_contact').textContent = data.contact_number || 'N/A';
            document.getElementById('v_address').textContent = data.address || 'N/A';

            document.getElementById('v_tracking').textContent = data.tracking_no || 'N/A';
            document.getElementById('v_grade').textContent = data.grade_level || 'N/A';
            document.getElementById('v_strand').textContent = data.strand || 'N/A';
            document.getElementById('v_prev_school').textContent = data.previous_school || 'N/A';

            document.getElementById('v_guardian_name').textContent = data.guardian_name || 'N/A';
            document.getElementById('v_guardian_rel').textContent = data.guardian_relationship || 'N/A';
            document.getElementById('v_guardian_contact').textContent = data.guardian_contact || 'N/A';

            const docsContainer = document.getElementById('v_documents');
            docsContainer.innerHTML = '';
            
            const docFields = [
                { key: 'psa_birth_cert', label: 'PSA Birth Certificate' },
                { key: 'form_138', label: 'Form 138 (Report Card)' },
                { key: 'good_moral', label: 'Certificate of Good Moral' },
                { key: 'photo', label: '2x2 ID Picture' }
            ];

            let hasDocs = false;
            for (let key in data) {
                if (key.includes('file') || key.includes('doc') || key.includes('cert') || key.includes('photo') || key.includes('form') || key.includes('moral')) {
                    if (data[key] && typeof data[key] === 'string') {
                        hasDocs = true;
                        const knownField = docFields.find(df => df.key === key);
                        const label = knownField ? knownField.label : key.replace(/_/g, ' ').toUpperCase();
                        const filePath = data[key].includes('/') ? data[key] : `../uploads/${data[key]}`;
                        docsContainer.innerHTML += `<a href="${filePath}" target="_blank" class="text-primary" style="text-decoration: none; display: inline-block; background: rgba(59, 130, 246, 0.1); padding: 10px 15px; font-size: 1rem; border-radius: 6px; border: 1px solid rgba(59, 130, 246, 0.2);"><i class="bi bi-file-earmark-text"></i> View ${label}</a>`;
                    }
                }
            }

            if (!hasDocs) {
                docsContainer.innerHTML = '<span class="text-muted">No documents attached to this application.</span>';
            }

            document.getElementById('viewModal').style.display = 'flex';
        }
    </script>
</body>
</html>