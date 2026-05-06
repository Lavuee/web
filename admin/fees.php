<?php
require_once '../actions/auth.php';
check_admin();
require_once '../config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);
$flash = null;

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $fee_name    = trim($_POST['fee_name'] ?? '');
        $grade_level = $_POST['grade_level'] ?? 'All';
        $strand      = 'All'; // Hardcoded to 'All' since SHS was removed
        $amount      = (float)($_POST['amount'] ?? 0);

        if (empty($fee_name) || $amount < 0) {
            $flash = ['type' => 'error', 'msg' => 'Invalid fee details provided.'];
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO fee_structures (grade_level, strand, fee_name, amount) VALUES (?, ?, ?, ?)");
                $stmt->execute([$grade_level, $strand, $fee_name, $amount]);
                $flash = ['type' => 'success', 'msg' => "Fee structure '{$fee_name}' added successfully."];
            } catch (Exception $e) {
                $flash = ['type' => 'error', 'msg' => "System Error: " . $e->getMessage()];
            }
        }
    } elseif ($action === 'update') {
        $fee_id      = (int)($_POST['fee_id'] ?? 0);
        $fee_name    = trim($_POST['fee_name'] ?? '');
        $grade_level = $_POST['grade_level'] ?? 'All';
        $strand      = 'All'; // Hardcoded to 'All' since SHS was removed
        $amount      = (float)($_POST['amount'] ?? 0);

        if (empty($fee_name) || $amount < 0 || $fee_id <= 0) {
            $flash = ['type' => 'error', 'msg' => 'Invalid fee details provided.'];
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE fee_structures SET grade_level = ?, strand = ?, fee_name = ?, amount = ? WHERE fee_id = ?");
                $stmt->execute([$grade_level, $strand, $fee_name, $amount, $fee_id]);
                $flash = ['type' => 'success', 'msg' => "Fee structure updated successfully."];
            } catch (Exception $e) {
                $flash = ['type' => 'error', 'msg' => 'Update failed: ' . $e->getMessage()];
            }
        }
    } elseif ($action === 'delete') {
        $fee_id = (int)($_POST['fee_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM fee_structures WHERE fee_id = ?");
            $stmt->execute([$fee_id]);
            $flash = ['type' => 'success', 'msg' => 'Fee structure deleted successfully.'];
        } catch (Exception $e) {
            $flash = ['type' => 'error', 'msg' => 'Delete failed: ' . $e->getMessage()];
        }
    }
    
    header("Location: fees.php" . ($flash ? '?flash=' . urlencode(json_encode($flash)) : ''));
    exit();
}

if (!$flash && isset($_GET['flash'])) {
    $flash = json_decode(urldecode($_GET['flash']), true);
}

// --- Fetch Fees ---
$search = trim($_GET['search'] ?? '');
$filter_grade = $_GET['grade'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "fee_name LIKE ?";
    $params[] = "%{$search}%";
}
if ($filter_grade !== '') {
    $where[] = "grade_level = ?";
    $params[] = $filter_grade;
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("SELECT * FROM fee_structures {$where_sql} ORDER BY FIELD(grade_level, 'All', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'), fee_name");
$stmt->execute($params);
$fees = $stmt->fetchAll();

// Group fees by grade level
$groupedFees = [];
foreach ($fees as $fee) {
    $groupedFees[$fee['grade_level']][] = $fee;
}

include 'includes/admin_header.php';
?>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <h2 style="margin-bottom: 5px;">Fee Structure Management</h2>
        <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Configure standardized tuition and assessment rates.</p>

        <?php if ($flash): ?>
        <div style="padding: 12px; margin-bottom: 20px; background: <?= $flash['type'] === 'success' ? 'rgba(34, 197, 94, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; border: 1px solid <?= $flash['type'] === 'success' ? 'rgba(34, 197, 94, 0.3)' : 'rgba(239, 68, 68, 0.3)' ?>; color: <?= $flash['type'] === 'success' ? '#22c55e' : '#ef4444' ?>; border-radius: 8px;">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>"></i> <?= htmlspecialchars($flash['msg']) ?>
        </div>
        <?php endif; ?>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
            <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                <input type="text" name="search" placeholder="Search fee name..." value="<?= htmlspecialchars($search) ?>" class="form-control" style="width:250px; padding:8px 12px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                <select name="grade" class="form-control" style="width:150px; padding:8px 12px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                    <option value="">All Grades</option>
                    <option value="All" <?= $filter_grade === 'All' ? 'selected' : '' ?>>All (Universal)</option>
                    <option value="Grade 7" <?= $filter_grade === 'Grade 7' ? 'selected' : '' ?>>Grade 7</option>
                    <option value="Grade 8" <?= $filter_grade === 'Grade 8' ? 'selected' : '' ?>>Grade 8</option>
                    <option value="Grade 9" <?= $filter_grade === 'Grade 9' ? 'selected' : '' ?>>Grade 9</option>
                    <option value="Grade 10" <?= $filter_grade === 'Grade 10' ? 'selected' : '' ?>>Grade 10</option>
                </select>
                <button type="submit" class="btn btn-outline" style="padding:8px 16px;">Filter</button>
            </form>
            <button type="button" class="btn btn-primary" onclick="openModal('create', null)">+ Add New Fee</button>
        </div>

        <?php if (empty($groupedFees)): ?>
            <div style="text-align:center; padding:40px; color:var(--text-muted); border: 1px dashed var(--glass-border); border-radius: 8px;">
                <i class="bi bi-cash-stack" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                No fee structures defined yet.
            </div>
        <?php else: ?>
            <?php foreach ($groupedFees as $grade => $gradeFees): ?>
                <div style="margin-bottom: 40px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 15px; color: var(--text-main); border-bottom: 2px solid var(--primary-color); display: inline-block; padding-bottom: 5px;">
                        <?= htmlspecialchars($grade === 'All' ? 'Universal Fees (Applies to All Grades)' : $grade) ?>
                    </h3>
                    <div style="margin-bottom: 15px;">
                        <div class="table-responsive">
                            <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                                <thead>
                                    <tr>
                                        <th style="width: 60%;">Fee Description</th>
                                        <th style="width: 20%;">Amount (₱)</th>
                                        <th style="width: 20%; text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gradeFees as $fee): ?>
                                    <tr>
                                        <td style="font-weight:600;"><?= htmlspecialchars($fee['fee_name']) ?></td>
                                        <td style="font-weight:700; color:var(--primary-color);">₱<?= number_format($fee['amount'], 2) ?></td>
                                        <td style="text-align:right;">
                                            <div style="display:flex; gap:8px; justify-content:flex-end;">
                                                <button class="btn btn-outline" style="padding:6px 12px; font-size:0.8rem;" onclick='openModal("edit", <?= json_encode($fee, JSON_HEX_APOS) ?>)'>
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Delete this fee? It will not update already assessed students, but will affect future assessments.');" style="margin:0;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="fee_id" value="<?= $fee['fee_id'] ?>">
                                                    <button type="submit" class="btn btn-outline" style="padding:6px 12px; font-size:0.8rem; color:#ef4444; border-color:rgba(239,68,68,0.4);">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>

<!-- Modal for Create / Edit -->
<div id="feeModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
    <div class="glass-panel" style="width:100%; max-width:450px; padding:35px;">
        <h3 id="modalTitle" style="margin-bottom:20px;">Manage Fee</h3>
        <form method="POST">
            <input type="hidden" name="action" id="modalAction" value="create">
            <input type="hidden" name="fee_id" id="modalFeeId">
            
            <div class="form-group" style="margin-bottom:15px;">
                <label class="form-label" style="font-weight:600; font-size:0.85rem; margin-bottom:5px; display:block;">Fee Description / Name</label>
                <input type="text" name="fee_name" id="modalFeeName" class="form-control" required placeholder="e.g. Science Laboratory Fee" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
            </div>
            
            <div class="form-group" style="margin-bottom:15px;">
                <label class="form-label" style="font-weight:600; font-size:0.85rem; margin-bottom:5px; display:block;">Grade Level</label>
                <select name="grade_level" id="modalGradeLevel" class="form-control" required style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:var(--bg-color); color:var(--text-main);">
                    <option value="All">All Grades</option>
                    <option value="Grade 7">Grade 7</option>
                    <option value="Grade 8">Grade 8</option>
                    <option value="Grade 9">Grade 9</option>
                    <option value="Grade 10">Grade 10</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:25px;">
                <label class="form-label" style="font-weight:600; font-size:0.85rem; margin-bottom:5px; display:block;">Amount (₱)</label>
                <input type="number" name="amount" id="modalAmount" class="form-control" required min="0" step="0.01" placeholder="0.00" style="width:100%; padding:10px; border-radius:8px; border:1px solid var(--glass-border); background:transparent; color:var(--text-main);">
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Save Fee</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(mode, data) {
    document.getElementById('feeModal').style.display = 'flex';
    if (mode === 'edit') {
        document.getElementById('modalTitle').textContent = 'Edit Fee Structure';
        document.getElementById('modalAction').value = 'update';
        document.getElementById('modalFeeId').value = data.fee_id;
        document.getElementById('modalFeeName').value = data.fee_name;
        document.getElementById('modalGradeLevel').value = data.grade_level;
        document.getElementById('modalAmount').value = data.amount;
        document.getElementById('modalSubmitBtn').textContent = 'Update Fee';
    } else {
        document.getElementById('modalTitle').textContent = 'Add New Fee Structure';
        document.getElementById('modalAction').value = 'create';
        document.getElementById('modalFeeId').value = '';
        document.getElementById('modalFeeName').value = '';
        document.getElementById('modalGradeLevel').value = 'All';
        document.getElementById('modalAmount').value = '';
        document.getElementById('modalSubmitBtn').textContent = 'Save Fee';
    }
}

function closeModal() {
    document.getElementById('feeModal').style.display = 'none';
}
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>