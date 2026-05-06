<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Sanitize Core Inputs
    $lrn = trim($_POST['lrn'] ?? '');
    if (empty($lrn)) {
        die("Error: Learner Reference Number (LRN) is required.");
    }

    // 2. Set up file upload directory
    $upload_dir = '../uploads/applications/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Helper function for secure file uploads
    $processUpload = function($fileKey, $prefix) use ($upload_dir) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $upload_dir . $filename)) {
                    return $filename;
                }
            }
        }
        return null;
    };

    // Process the documents
    $docs = [
        'PSA' => $processUpload('doc_psa', 'psa'),
        'Form 138' => $processUpload('doc_f138', 'f138'),
        'Good Moral' => $processUpload('doc_good_moral', 'gmoral')
    ];

    try {
        $pdo->beginTransaction();

        // 3. Generate a Unique Application Tracking Number
        $tracking_no = 'APP-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // 4. Insert strictly into the APPLICATIONS table
        $stmtApp = $pdo->prepare("
            INSERT INTO applications (
                tracking_no, lrn, first_name, middle_name, last_name, suffix, 
                birthdate, sex, phone, address, guardian_name, guardian_contact, 
                guardian_relationship, previous_school, grade_applying_for, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        
        $stmtApp->execute([
            $tracking_no, 
            $lrn, 
            $_POST['first_name'], 
            $_POST['middle_name'] ?? null, 
            $_POST['last_name'], 
            $_POST['suffix'] ?? null, 
            $_POST['birthdate'], 
            $_POST['sex'], 
            $_POST['phone'] ?? null, 
            $_POST['address'], 
            $_POST['guardian_name'], 
            $_POST['guardian_contact'], 
            $_POST['guardian_relationship'], 
            $_POST['previous_school'] ?? null, 
            $_POST['grade_applying_for']
        ]);
        
        $application_id = $pdo->lastInsertId();

        // 5. Store Application Documents separately linked by application_id
        $stmtDoc = $pdo->prepare("INSERT INTO application_documents (application_id, document_type, file_path) VALUES (?, ?, ?)");
        foreach ($docs as $type => $path) {
            if ($path) {
                $stmtDoc->execute([$application_id, $type, $path]);
            }
        }

        $pdo->commit();

        // Redirect to track_status.php with the tracking number
        echo "<script>
                alert('Application Submitted Successfully! Your Tracking Number is: {$tracking_no}. Please save this number to track your status.');
                window.location.href = '../track_status.php?tracking_no={$tracking_no}';
              </script>";
        exit();

    } catch (\PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) { // Integrity constraint violation (Duplicate)
            die("Error: An application with this LRN or Tracking Number already exists.");
        }
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../apply.php");
    exit();
}
?>