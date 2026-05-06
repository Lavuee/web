<?php
// Secures the module for Admin access and connects to the database.
require_once '../actions/auth.php';
check_admin(); 
require_once '../config/db.php';

try {
    // 1. Fetch all enrollment periods and count total students in each.
    $periods = $pdo->query("
        SELECT sy.*, 
        (SELECT COUNT(*) FROM enrollments e WHERE e.school_year_id = sy.school_year_id) as total_enrolled
        FROM school_years sy 
        ORDER BY sy.year_string DESC
    ")->fetchAll();

    // 2. Get breakdown of the CURRENT ACTIVE period by Grade Level.
    $gradeBreakdown = $pdo->query("
        SELECT grade_level, COUNT(*) as count 
        FROM enrollments e
        JOIN school_years sy ON e.school_year_id = sy.school_year_id
        WHERE sy.is_active = 1
        GROUP BY grade_level
    ")->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// 3. Include the modular header (Handles Theme persistence and CSS links)
include 'includes/admin_header.php'; 
?>
<body>
    <div class="layout">
        
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Enrollment Periods</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Manage school years and track enrollment cycles.</p>

            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 45px; border-bottom: 1px solid var(--glass-border); padding-bottom: 25px;">
                <?php if (empty($gradeBreakdown)): ?>
                    <p class="text-muted" style="width: 100%;">No active enrollment data found.</p>
                <?php else: ?>
                    <?php foreach (array_slice($gradeBreakdown, 0, 4) as $stat): ?>
                        <div style="flex: 1; min-width: 150px;">
                            <p class="text-muted" style="font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin: 0;"><?= htmlspecialchars($stat['grade_level']) ?></p>
                            <h3 style="font-size: 2.8rem; margin: 5px 0 0 0; font-weight: 300; color: var(--primary-color);"><?= number_format($stat['count']) ?></h3>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 50px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0;">Academic Year Catalog</h3>
                    <button class="btn btn-primary" onclick="document.getElementById('addPeriodModal').style.display='flex'">
                        + Create New Period
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                    <thead>
                        <tr>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Total Students</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periods as $period): ?>
                            <tr>
                                <td style="font-weight: 500;"><?= htmlspecialchars($period['year_string']) ?></td>
                                <td><?= htmlspecialchars($period['semester']) ?></td>
                                <td><?= number_format($period['total_enrolled']) ?> Students</td>
                                <td>
                                    <?php if ($period['is_active']): ?>
                                        <span class="badge" style="background: rgba(34, 197, 94, 0.2); color: #22c55e;">Active</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: rgba(100, 116, 139, 0.2); color: #64748b;">Closed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        
                                    <button type="button" class="btn btn-outline" onclick='openEditModal(<?= json_encode($period) ?>)'>
                                        <i class="bi bi-pencil-square"></i> Manage
                                    </button>

                                    <?php if (!$period['is_active']): ?>
                                        <a href="../actions/toggle_period.php?id=<?= $period['school_year_id'] ?>" 
                                        class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem; color: #22c55e; border-color: rgba(34, 197, 94, 0.4); text-decoration: none;" 
                                        onclick="return confirm('Set this as the active enrollment period?')">
                                            <i class="bi bi-check-circle"></i> Set Active
                                        </a>
                                    <?php endif; ?>
                                </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <div id="editPeriodModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
    <div class="glass-panel" style="width:400px; padding:30px;">
        <h3 style="margin-bottom:20px;">Edit Period</h3>
        <form action="../actions/update_period.php" method="POST">
            <input type="hidden" name="sy_id" id="edit_sy_id">
            <label style="font-size:0.85rem; font-weight:600;">School Year String</label>
            <input type="text" name="year_string" id="edit_year_string" class="btn btn-outline" style="width:100%; margin:10px 0 20px; text-align:left; cursor:text;">
            
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('editPeriodModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
        </div>
    </div>

        <div id="addPeriodModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
        <div class="glass-panel" style="width:400px; padding:30px;">
            <h3 style="margin-bottom:20px;">Create New Period</h3>
            <form action="../actions/add_period.php" method="POST">
                
                <label style="font-size:0.85rem; font-weight:600;">School Year (e.g., 2026-2027)</label>
                <input type="text" name="year_string" required placeholder="YYYY-YYYY" style="width:100%; padding:10px; margin:10px 0 15px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
                
                <label style="font-size:0.85rem; font-weight:600;">Semester</label>
                <select name="semester" required style="width:100%; padding:10px; margin:10px 0 20px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                    <option value="Full Year">Full Year</option>
                    <option value="1st">1st Semester</option>
                    <option value="2nd">2nd Semester</option>
                </select>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('addPeriodModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Period</button>
                </div>
            </form>
        </div>
    </div>

        <script>
        function openEditModal(data) {
            document.getElementById('edit_sy_id').value = data.school_year_id;
            document.getElementById('edit_year_string').value = data.year_string;
            document.getElementById('editPeriodModal').style.display = 'flex';
        }
</script>
</body>
</html>