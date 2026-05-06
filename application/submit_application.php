<?php
// Initializes session state for the transaction.
session_start();

// Establishes the database connection.
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_email = filter_var($_POST['student_email'], FILTER_SANITIZE_EMAIL);
    
    // Sets up file upload directory and parsing logic
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $processUpload = function($fileKey, $prefix) use ($upload_dir) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            if (in_array($ext, $allowed)) {
                $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $upload_dir . $filename)) {
                    return $filename;
                }
            }
        }
        return null;
    };

    // Process the uploaded requirement documents
    $doc_birth_cert  = $processUpload('doc_birth_cert', 'psa');
    $doc_report_card = $processUpload('doc_report_card', 'f138');
    $doc_good_moral  = $processUpload('doc_good_moral', 'gmoral');

    // Pre-emptive duplicate checks
    $stmtEmail = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmtEmail->execute([$student_email]);
    if ($stmtEmail->fetch()) {
        die("Error: The provided email address is already registered within the system.");
    }

    $lrn = trim($_POST['lrn'] ?? '');
    if (!empty($lrn)) {
        $stmtLrn = $pdo->prepare("SELECT student_id FROM students WHERE lrn = ? LIMIT 1");
        $stmtLrn->execute([$lrn]);
        if ($stmtLrn->fetch()) {
            die("Error: The provided Learner Reference Number (LRN) is already registered to another student.");
        }
    }

    try {
        // Initiates a database transaction to ensure atomicity across multiple table insertions.
        $pdo->beginTransaction();

        // Dynamically fetch the currently 'Active' school year from the database
        $syStmt = $pdo->query("SELECT school_year_id FROM school_years WHERE is_active = 1 LIMIT 1");
        $active_sy = $syStmt->fetchColumn();
        
        if (!$active_sy) {
            throw new Exception("No active school year found. Please contact the administrator.");
        }

        // Provisions a new user account with a temporary placeholder password
        $stmtUser = $pdo->prepare("INSERT INTO users (email, password_hash, role, status) VALUES (?, 'TEMP', 'Student', 'Active')");
        $stmtUser->execute([$student_email]);
        $user_id = $pdo->lastInsertId();

        // Records the permanent demographic profile of the student.
        $stmtStudent = $pdo->prepare("
            INSERT INTO students (user_id, lrn, first_name, middle_name, last_name, suffix, date_of_birth, gender, contact_number, address, guardian_name, guardian_relationship, guardian_contact, previous_school, psa_birth_cert, form_138, good_moral) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtStudent->execute([
            $user_id,
            $_POST['lrn'] ?: null,
            $_POST['first_name'],
            $_POST['middle_name'] ?: null,
            $_POST['last_name'],
            $_POST['suffix'] ?: null,
            $_POST['date_of_birth'] ?: null,
            $_POST['gender'] ?: null,
            $_POST['contact_number'] ?: null,
            $_POST['address'] ?: null,
            $_POST['guardian_name'] ?: null,
            $_POST['guardian_relationship'] ?: null,
            $_POST['guardian_contact'] ?: null,
            $_POST['previous_school'] ?: null,
            $doc_birth_cert,
            $doc_report_card,
            $doc_good_moral
        ]);
        
        // Grab the auto-increment ID and format the Tracking Number (e.g., PCNH20260001)
        $student_id = $pdo->lastInsertId();
        $current_year = date('Y');
        $tracking_no = sprintf("PCNH%s%04d", $current_year, $student_id);

        // Hash the new tracking number and update the user's password so they can log in
        $password_hash = password_hash($tracking_no, PASSWORD_DEFAULT);
        $stmtUpdateUser = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmtUpdateUser->execute([$password_hash, $user_id]);

        // Registers the specific academic enrollment locking status to 'Pending'
        $stmtEnr = $pdo->prepare("
            INSERT INTO enrollments (tracking_no, student_id, school_year_id, grade_level, strand, status) 
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ");
        $stmtEnr->execute([
            $tracking_no,
            $student_id,
            $active_sy,
            $_POST['grade_level'],
            $_POST['strand'] ?? 'N/A'
        ]);

        // Commits the transaction to finalize data storage.
        $pdo->commit();

        // --- EMAIL NOTIFICATION TRIGGER ---
        // Sends an automated email to the student with their new PCNH login credentials
        $to = $student_email;
        $subject = "Pines NHS Enrollment Credentials";
        $message = "Hello " . $_POST['first_name'] . ",\n\n"
                 . "Your application to Pines National High School has been successfully received!\n\n"
                 . "Here are your official Student Portal Login Credentials:\n"
                 . "Student ID: " . $tracking_no . "\n"
                 . "Temporary Password: " . $tracking_no . "\n\n"
                 . "You can log into the system here to track your status and pay your tuition:\n"
                 . "http://" . $_SERVER['HTTP_HOST'] . "/websys1-updated-for-payment/login.php\n\n"
                 . "Thank you,\nPines NHS Admissions";
        $headers = "From: admissions@pines-nhs.edu.ph";
        @mail($to, $subject, $message, $headers); 

        // Redirects to the login page
        echo "<script>
                alert('Application submitted! Your Student ID is {$tracking_no}. Please log in to your dashboard to select your subjects.');
                window.location.href = '../login.php';
              </script>";
        exit();

    } catch (\PDOException $e) {
        // Reverts all database changes if any insertion fails.
        $pdo->rollBack();

        // Intercepts duplicate email registration attempts.
        if ($e->getCode() == 23000) {
            die("Error: A duplicate entry was detected. Please ensure your Email and LRN are unique.");
        }
        die("Database Error: " . $e->getMessage());
    } catch (Exception $e) {
        $pdo->rollBack();
        die("System Error: " . $e->getMessage());
    }
} else {
    header("Location: ../apply.php");
    exit();
}
?>