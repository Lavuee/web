<?php
require_once '../actions/auth.php';
check_student();
require_once '../config/db.php';

try {
    $stmt = $pdo->prepare("
        SELECT e.tracking_no, e.total_assessment, e.balance, e.grade_level, e.section,
               s.first_name, s.last_name, s.lrn,
               sy.year_string
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN school_years sy ON e.school_year_id = sy.school_year_id
        WHERE e.enrollment_id = :id
    ");
    $stmt->execute([':id' => $_SESSION['enrollment_id']]);
    $student = $stmt->fetch();

    if (!$student) {
        die("Enrollment record not found.");
    }
    
    // Math computations
    $total = (float) $student['total_assessment'];
    $q_fee = $total > 0 ? $total * 0.25 : 0;
    $q4_fee = $total > 0 ? $total - ($q_fee * 3) : 0;

    $startYear = explode('-', $student['year_string'])[0];
    $endYear = explode('-', $student['year_string'])[1] ?? ($startYear + 1);

} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installment Schedule - <?= htmlspecialchars($student['tracking_no']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 40px 20px;
            background: #f1f5f9;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .document-container {
            background: #ffffff;
            width: 100%;
            max-width: 600px;
            padding: 40px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-top: 6px solid #3b82f6;
            border-radius: 8px;
            color: #0f172a;
        }
        .text-center { text-align: center; }
        .school-name { font-weight: 800; font-size: 1.4rem; margin-bottom: 5px; font-family: Arial, sans-serif; letter-spacing: -0.5px; }
        .school-address { font-size: 0.85rem; margin-bottom: 25px; color: #475569; font-family: Arial, sans-serif; }
        .doc-title { font-size: 1.2rem; font-weight: bold; margin: 20px 0; border-bottom: 2px solid #cbd5e1; padding-bottom: 15px; letter-spacing: 1px; font-family: Arial, sans-serif; text-transform: uppercase; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; font-size: 0.95rem; font-family: Arial, sans-serif; }
        .info-label { color: #64748b; font-size: 0.85rem; display: block; margin-bottom: 2px; }
        .info-value { font-weight: 600; color: #0f172a; }
        
        .schedule-table { width: 100%; border-collapse: collapse; margin: 30px 0; font-family: Arial, sans-serif; }
        .schedule-table th, .schedule-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .schedule-table th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        .schedule-table td { font-size: 0.95rem; color: #0f172a; }
        .schedule-table .amount { text-align: right; font-weight: 700; }
        
        .total-row { border-top: 2px solid #cbd5e1; background: #f8fafc; }
        .total-row td { font-weight: 800; font-size: 1.1rem; }
        
        .footer { text-align: left; margin-top: 35px; font-size: 0.85rem; color: #64748b; line-height: 1.6; font-family: Arial, sans-serif; }
        
        .controls {
            margin-top: 30px;
            width: 100%;
            max-width: 600px;
            display: flex;
            gap: 15px;
        }
        
        .btn { flex: 1; padding: 12px; text-align: center; text-decoration: none; font-weight: 600; border-radius: 8px; cursor: pointer; border: none; font-family: Arial, sans-serif; font-size: 0.95rem; transition: 0.2s; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
        .btn-outline:hover { background: #e2e8f0; color: #0f172a; }

        @media print {
            body { display: block; background: #fff; padding: 0; margin: 0; }
            .document-container { box-shadow: none; border: none; max-width: 100%; width: 100%; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>

    <div class="document-container">
        <div class="text-center">
            <div class="school-name">PINES NATIONAL HIGH SCHOOL</div>
            <div class="school-address">Magsaysay Ave., Baguio City, Philippines<br>S.Y. <?= htmlspecialchars($student['year_string']) ?></div>
        </div>

        <div class="text-center doc-title">
            Official Installment Schedule
        </div>

        <div class="info-grid">
            <div><span class="info-label">Student Name</span><span class="info-value"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></span></div>
            <div><span class="info-label">Student ID</span><span class="info-value"><?= htmlspecialchars($student['tracking_no']) ?></span></div>
            <div><span class="info-label">Grade Level</span><span class="info-value"><?= htmlspecialchars($student['grade_level']) ?></span></div>
            <div><span class="info-label">Section</span><span class="info-value"><?= htmlspecialchars($student['section'] ?: 'TBA') ?></span></div>
        </div>

        <table class="schedule-table">
            <thead><tr><th>Quarter</th><th>Due Date</th><th class="amount">Amount Due</th></tr></thead>
            <tbody>
                <tr><td>1st Quarter (Downpayment)</td><td>Upon Enrollment</td><td class="amount">PHP <?= number_format($q_fee, 2) ?></td></tr>
                <tr><td>2nd Quarter</td><td>October 15, <?= $startYear ?></td><td class="amount">PHP <?= number_format($q_fee, 2) ?></td></tr>
                <tr><td>3rd Quarter</td><td>December 15, <?= $startYear ?></td><td class="amount">PHP <?= number_format($q_fee, 2) ?></td></tr>
                <tr><td>4th Quarter</td><td>March 15, <?= $endYear ?></td><td class="amount">PHP <?= number_format($q4_fee, 2) ?></td></tr>
                <tr class="total-row"><td colspan="2" style="text-align: right;">TOTAL ASSESSMENT:</td><td class="amount">PHP <?= number_format($total, 2) ?></td></tr>
            </tbody>
        </table>

        <div class="footer">
            <strong>Note:</strong> This breakdown serves as your payment guide for the current school year. Prompt settlement of accounts before the deadline avoids processing delays during examination periods.<br><br>
            <em>System Generated Document. No signature required.</em>
        </div>
    </div>

    <div class="controls no-print">
        <a href="assessment.php" class="btn btn-outline">Close</a>
        <button class="btn btn-primary" onclick="window.print()">Save as PDF / Print</button>
    </div>

</body>
</html>