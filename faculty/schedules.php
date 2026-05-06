<?php
require_once '../actions/auth.php';
check_faculty(); 
require_once '../config/db.php';

try {
    $faculty_id = $_SESSION['faculty_id'] ?? 0;

    $schedStmt = $pdo->prepare("
        SELECT cs.*, sub.subject_name, sec.section_name, sec.grade_level 
        FROM class_schedules cs
        JOIN subjects sub ON cs.subject_id = sub.subject_id
        JOIN sections sec ON cs.section_id = sec.section_id
        WHERE cs.faculty_id = ? 
        ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), cs.start_time ASC
    ");
    $schedStmt->execute([$faculty_id]);
    $schedules = $schedStmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include 'includes/faculty_header.php';
?>
<body>
    <div class="layout">
        <?php include 'includes/faculty_sidebar.php'; ?>
        
        <main class="main-content">
            <h2 style="margin-bottom: 5px;">My Teaching Schedule</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">View your class timetable and room assignments.</p>

            <div class="glass-panel">
                <div class="table-responsive">
                    <table class="table-wrapper">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Subject</th>
                                <th>Section & Grade</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $sched): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--primary-color);"><?= htmlspecialchars($sched['day_of_week']) ?></td>
                                    <td style="font-family: monospace; font-size: 0.95rem;">
                                        <?= date("h:i A", strtotime($sched['start_time'])) ?> - <?= date("h:i A", strtotime($sched['end_time'])) ?>
                                    </td>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($sched['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($sched['section_name']) ?> (<?= htmlspecialchars($sched['grade_level']) ?>)</td>
                                    <td><span class="badge" style="border: 1px solid var(--glass-border);"><?= htmlspecialchars($sched['room_number'] ?: 'TBA') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($schedules)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        <i class="bi bi-calendar-x" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                                        No schedule has been assigned to you yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>