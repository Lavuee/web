<?php
// Secure the module for Admin access
require_once '../actions/auth.php';
check_admin();

require_once '../config/db.php';

// Check if a student ID was provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = $_GET['id'];

try {
    // 1. Fetch Core Student & User Information
    $stmt = $pdo->prepare("
        SELECT s.*, u.email 
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("Student record not found.");
    }

    // 2. Fetch Enrollment History for this specific student
    $historyStmt = $pdo->prepare("
        SELECT e.*, sy.year_string, sy.semester 
        FROM enrollments e
        LEFT JOIN school_years sy ON e.school_year_id = sy.school_year_id
        WHERE e.student_id = ?
        ORDER BY e.created_at DESC
    ");
    $historyStmt->execute([$student_id]);
    $enrollmentHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

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
            
            <div style="margin-bottom: 20px;">
                <a href="students.php" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem; text-decoration: none;">
                    &larr; Back to Roster
                </a>
            </div>

            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 45px; border-bottom: 1px solid var(--glass-border); padding-bottom: 25px;">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700;">
                    <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                </div>
                <div>
                    <h2 style="margin: 0 0 5px 0; color: var(--text-main); font-size: 2rem; font-weight: 600;">
                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                    </h2>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">
                        <?= htmlspecialchars($student['email']) ?> | Student ID: <?= htmlspecialchars($enrollmentHistory[0]['tracking_no'] ?? 'N/A') ?>
                    </p>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 40px; margin-bottom: 45px; border-bottom: 1px solid var(--glass-border); padding-bottom: 35px;">
                
                <div style="flex: 1; min-width: 250px;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 15px; color: var(--text-main); text-transform: uppercase;">Personal Details</h3>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.95rem; line-height: 1.8; color: var(--text-muted);">
                        <li><strong style="color: var(--text-main);">Phone:</strong> <?= htmlspecialchars($student['phone'] ?? 'Not provided') ?></li>
                        <li><strong style="color: var(--text-main);">Date of Birth:</strong> <?= htmlspecialchars($student['date_of_birth'] ?? 'Not provided') ?></li>
                        <li><strong style="color: var(--text-main);">Address:</strong> <?= htmlspecialchars($student['address'] ?? 'Not provided') ?></li>
                        <li><strong style="color: var(--text-main);">Gender:</strong> <?= htmlspecialchars($student['gender'] ?? 'Not provided') ?></li>
                    </ul>
                </div>

                <div style="flex: 1; min-width: 250px;">
                    <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 15px; color: var(--text-main); text-transform: uppercase;">Emergency Contact</h3>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.95rem; line-height: 1.8; color: var(--text-muted);">
                        <li><strong style="color: var(--text-main);">Guardian Name:</strong> <?= htmlspecialchars($student['guardian_name'] ?? 'Not provided') ?></li>
                        <li><strong style="color: var(--text-main);">Relationship:</strong> <?= htmlspecialchars($student['guardian_relationship'] ?? 'Not provided') ?></li>
                        <li><strong style="color: var(--text-main);">Contact Number:</strong> <?= htmlspecialchars($student['guardian_contact'] ?? 'Not provided') ?></li>
                    </ul>
                </div>
            </div>

            <div style="margin-bottom: 50px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1.2rem; font-weight: 600; margin: 0;">Enrollment History</h3>
                </div>
                <div class="table-responsive">
                    <table class="table-wrapper" style="border-top: 1px solid var(--glass-border);">
                    <thead>
                        <tr>
                            <th>School Year</th>
                            <th>Grade Level</th>
                            <th>Student ID</th>
                            <th>Status</th>
                            <th>Balance</th>
                            <th>Date Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollmentHistory as $history): ?>
                            <tr>
                                <td style="font-weight: 500;"><?= htmlspecialchars($history['year_string'] ?? 'N/A') ?> (<?= htmlspecialchars($history['semester'] ?? '') ?>)</td>
                                <td><?= htmlspecialchars($history['grade_level'] ?? 'N/A') ?></td>
                                <td style="font-family: monospace; font-size: 0.9rem;"><?= htmlspecialchars($history['tracking_no']) ?></td>
                                <td>
                                    <span class="badge" style="<?= $badgeColors[$history['status']] ?? '' ?>">
                                        <?= htmlspecialchars($history['status']) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600;">₱<?= number_format($history['balance'], 2) ?></td>
                                <td style="color: var(--text-muted); font-size: 0.85rem;">
                                    <?= date('M d, Y', strtotime($history['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($enrollmentHistory)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No enrollment records found for this student.
                                </td>
                            </tr>
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