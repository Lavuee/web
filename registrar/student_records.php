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
               u.email as student_email,
               (SELECT GROUP_CONCAT(subject_name SEPARATOR ', ') FROM enrollment_subjects es WHERE es.enrollment_id = e.enrollment_id) as enrolled_subjects
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

            <div style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0; color: var(--text-main);">Filter Records</h3>
                    <form method="GET" action="student_records.php" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <input type="text" name="search" placeholder="Search name, email, or tracking no..." value="<?= htmlspecialchars($searchQuery) ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; width: 260px; outline: none;">
                        <select name="grade" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); font-size: 0.85rem; cursor: pointer;">
                            <option value="">All Grades</option>
                            <option value="Grade 7" <?= $gradeFilter === 'Grade 7' ? 'selected' : '' ?>>Grade 7</option>
                            <option value="Grade 8" <?= $gradeFilter === 'Grade 8' ? 'selected' : '' ?>>Grade 8</option>
                            <option value="Grade 9" <?= $gradeFilter === 'Grade 9' ? 'selected' : '' ?>>Grade 9</option>
                            <option value="Grade 10" <?= $gradeFilter === 'Grade 10' ? 'selected' : '' ?>>Grade 10</option>
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
                <div style="margin-bottom: 50px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;"><?= $statusGrp ?> Students</h3>
                    <div class="table-responsive">
                        <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Grade</th>
                                    <th>Tracking No.</th>
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
                <input type="number" step="0.01" name="total_assessment" id="m_total" style="width:100%; padding:10px; margin:10px 0 5px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                
                <div style="text-align: right; margin-bottom: 25px;">
                    <button type="button" id="toggleBreakdownBtn" onclick="toggleBreakdown()" style="background: none; border: none; color: var(--primary-color); font-size: 0.8rem; font-weight: 600; cursor: pointer; text-decoration: underline;">View Full Breakdown</button>
                </div>

                <div id="breakdownSection" style="display:none; background: rgba(100, 116, 139, 0.1); border: 1px solid var(--glass-border); padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <h4 style="font-size: 0.9rem; margin-top: 0; margin-bottom: 10px; color: var(--text-main);">Assessment Breakdown</h4>
                    <div id="breakdownContent" style="font-size: 0.85rem; color: var(--text-main);"></div>
                </div>

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
                <div><strong class="text-muted">Previous School:</strong> <span id="v_prev_school"></span></div>
            </div>

            <h4 style="font-size: 1.1rem; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 8px;">Selected Subjects</h4>
            <div id="v_subjects" style="margin-bottom: 30px; font-size: 1rem; color: var(--text-main); line-height: 1.6;">
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
            if (totalAssessed > 0) {
                let baseFee = totalAssessed - subjectTotal;
                breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-bottom: 5px; border-top: 1px solid var(--glass-border); padding-top: 5px;"><span>Base Tuition & Misc</span><span>₱${baseFee.toFixed(2)}</span></li>`;
                breakdownHTML += `<li style="display: flex; justify-content: space-between; margin-top: 10px; font-weight: bold; color: var(--primary-color);"><span>Total Assessment</span><span>₱${totalAssessed.toFixed(2)}</span></li>`;
            } else {
                breakdownHTML += `<li style="margin-top: 10px; font-style: italic; color: var(--text-muted); font-size: 0.8rem;">* Base Tuition & Misc will be auto-calculated upon saving.</li>`;
            }
            
            breakdownHTML += '</ul>';
            breakdownContainer.innerHTML = breakdownHTML;
            document.getElementById('breakdownSection').style.display = 'none';
            document.getElementById('toggleBreakdownBtn').textContent = 'View Full Breakdown';
            
            document.getElementById('statusModal').style.display = 'flex';
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
            document.getElementById('v_prev_school').textContent = data.previous_school || 'N/A';

            document.getElementById('v_guardian_name').textContent = data.guardian_name || 'N/A';
            document.getElementById('v_guardian_rel').textContent = data.guardian_relationship || 'N/A';
            document.getElementById('v_guardian_contact').textContent = data.guardian_contact || 'N/A';
            
            document.getElementById('v_subjects').textContent = data.enrolled_subjects ? data.enrolled_subjects : 'No subjects selected';

            const docsContainer = document.getElementById('v_documents');
            docsContainer.innerHTML = '';
            
            const docFields = [
                { key: 'psa_birth_cert', label: 'PSA Birth Certificate' },
                { key: 'form_138', label: 'Form 138 (Report Card)' },
                { key: 'good_moral', label: 'Certificate of Good Moral' }
            ];

            docFields.forEach(df => {
                const val = data[df.key];
                if (val && typeof val === 'string' && val.trim() !== '') {
                    const filePath = val.includes('/') ? val : `../uploads/${val}`;
                    docsContainer.innerHTML += `
                        <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(59, 130, 246, 0.05); padding: 10px 15px; border-radius: 6px; border: 1px solid rgba(59, 130, 246, 0.2);">
                            <div>
                                <span style="font-weight: 500; color: var(--text-main); margin-right: 10px;">${df.label}</span>
                                <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);">Submitted</span>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <a href="${filePath}" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem; color: #3b82f6; border-color: rgba(59, 130, 246, 0.4); text-decoration: none;">View</a>
                                <form action="../actions/remove_document.php" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to flag this document as unsubmitted?');">
                                    <input type="hidden" name="student_id" value="${data.student_id}">
                                    <input type="hidden" name="document_key" value="${df.key}">
                                    <input type="hidden" name="return_to" value="student_records.php">
                                    <button type="submit" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);">Remove</button>
                                </form>
                            </div>
                        </div>
                    `;
                } else {
                    docsContainer.innerHTML += `
                        <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(100, 116, 139, 0.05); padding: 10px 15px; border-radius: 6px; border: 1px solid rgba(100, 116, 139, 0.2);">
                            <div>
                                <span style="font-weight: 500; color: var(--text-main); margin-right: 10px;">${df.label}</span>
                                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Not Submitted</span>
                            </div>
                        </div>
                    `;
                }
            });

            document.getElementById('viewModal').style.display = 'flex';
        }
    </script>
</body>
</html>