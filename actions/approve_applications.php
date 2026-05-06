<?php
require_once 'auth.php';
check_admin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = (int)$_POST['application_id'];

    try {
        $pdo->beginTransaction();

        // 1. Fetch Application Data
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE application_id = ? AND status = 'Pending'");
        $stmt->execute([$app_id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app) throw new Exception("Application not found or already processed.");

        // 2. Generate Student UID (Format: PCNH-YEAR-ID)
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM students");
        $count = $stmtCount->fetchColumn() + 1;
        $student_uid = "PCNH-" . date('Y') . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);

        // 3. Create User Account (Default password is the LRN)
        $password_hash = password_hash($app['lrn'], PASSWORD_DEFAULT);
        $stmtUser = $pdo->prepare("INSERT INTO users (uid, password, role, status) VALUES (?, ?, 'Student', 'Active')");
        $stmtUser->execute([$student_uid, $password_hash]);
        $user_id = $pdo->lastInsertId();

        // 4. Create Student Profile linked to Application
        $stmtStudent = $pdo->prepare("
            INSERT INTO students (application_id, user_id, lrn, first_name, middle_name, last_name, suffix, birthdate, sex, phone, address, guardian_name, guardian_contact, guardian_relationship)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtStudent->execute([
            $app_id, $user_id, $app['lrn'], $app['first_name'], $app['middle_name'], $app['last_name'], 
            $app['suffix'], $app['birthdate'], $app['sex'], $app['phone'], $app['address'], 
            $app['guardian_name'], $app['guardian_contact'], $app['guardian_relationship']
        ]);
        $student_id = $pdo->lastInsertId();

        // 5. Create Enrollment Record
        $syStmt = $pdo->query("SELECT school_year FROM enrollment_settings LIMIT 1");
        $school_year = $syStmt->fetchColumn() ?: date('Y') . '-' . (date('Y') + 1);

        $stmtEnr = $pdo->prepare("INSERT INTO enrollments (student_id, grade_level, school_year, enrollment_status) VALUES (?, ?, ?, 'Pending')");
        $stmtEnr->execute([$student_id, $app['grade_applying_for'], $school_year]);

        // 6. Finalize Application status
        $stmtUpdateApp = $pdo->prepare("UPDATE applications SET status = 'Approved' WHERE application_id = ?");
        $stmtUpdateApp->execute([$app_id]);

        $pdo->commit();
        header("Location: ../admin/applications.php?success=Applicant converted to Student successfully. ID: $student_uid");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}