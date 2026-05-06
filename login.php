<?php
// Pines EMS Authentication Controller
session_start();

// Redirects authenticated users to their designated dashboards
if (isset($_SESSION['user_id'])) {
    $current_role = strtolower($_SESSION['role']);
    if ($current_role === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($current_role === 'faculty') {
        header("Location: faculty/dashboard.php");
    } elseif ($current_role === 'registrar') {
        header("Location: registrar/dashboard.php");
    } elseif ($current_role === 'cashier') {
        header("Location: cashier/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit();
}

require_once 'config/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id  = trim(htmlspecialchars($_POST['login_id'] ?? ''));
    $password  = $_POST['password'] ?? '';
    $role      = $_POST['role'] ?? 'student';

    if (empty($login_id) || empty($password)) {
        $error = "Missing required authentication parameters.";
    } elseif ($role === 'faculty') {
        // Authenticates all Staff personnel
       // Update the query to match the new 3NF schema
        $stmt = $pdo->prepare("SELECT * FROM users WHERE uid = ? AND status = 'Active' LIMIT 1");
        $stmt->execute([$login_id]); // Ensure $login_id is the variable capturing the user input
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff && password_verify($password, $staff['password_hash'])) {
            $_SESSION['user_id']    = $staff['user_id'];
            $_SESSION['role']       = $staff['role']; 
            $_SESSION['user_email'] = $staff['email'];
            
            $redirect_dir = strtolower($staff['role']);
            header("Location: {$redirect_dir}/dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials. Verify registered email and password.";
        }
    } else {
        // Authenticates student access
        $stmt = $pdo->prepare("
            SELECT u.*, e.enrollment_id, s.first_name, s.last_name 
            FROM enrollments e
            JOIN students s ON e.student_id = s.student_id
            JOIN users u ON s.user_id = u.user_id
            WHERE e.tracking_no = ? LIMIT 1
        ");
        $stmt->execute([$login_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student && password_verify($password, $student['password_hash'])) {
            $_SESSION['user_id']       = $student['user_id'];
            $_SESSION['role']          = 'Student';
            $_SESSION['enrollment_id'] = $student['enrollment_id'];
            $_SESSION['user_email']    = $student['email'];
            $_SESSION['student_name']  = trim($student['first_name'] . ' ' . $student['last_name']);
            header("Location: student/dashboard.php");
            exit();
        } else {
            $error = "Invalid Student ID or password combination.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Pines EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time(); ?>">
    <script>
        if (localStorage.getItem('theme') === 'dark' || 
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    </script>
    <style>
        .login-container {
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .brand-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        }
        .nav-pills-wrap {
            display: flex;
            background: rgba(100, 116, 139, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            padding: 5px;
            margin-bottom: 25px;
        }
        .role-tab {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            background: transparent;
            color: var(--text-muted);
            border: none;
            transition: all 0.3s ease;
        }
        .role-tab.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-main);
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--bg-color);
            color: var(--text-main);
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }
        .pw-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .toggle-pw {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            display: flex;
        }
        .toggle-pw:hover { color: var(--primary-color); }
        .form-hint { font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; }
        .alert {
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }
        .alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; }
    </style>
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">

    <a href="index.php" class="btn btn-outline" style="position: absolute; top: 20px; left: 20px;">
        &larr; Back to Home
    </a>
    
    <button onclick="toggleTheme()" class="btn btn-outline" style="position: absolute; top: 20px; right: 20px; padding: 8px; border-radius: 50%;" title="Toggle Theme">
        <i id="theme-icon-login" class="bi bi-moon-stars-fill"></i>
    </button>
    
    <div class="login-container">
        <div class="brand-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffffff" viewBox="0 0 16 16">
                <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917z"/>
                <path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466z"/>
            </svg>
        </div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 5px; color: var(--text-main);">Pines Enrollment System</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 30px;">School Year <?= date('Y') ?>-<?= date('Y') + 1 ?></p>

        <div class="glass-panel" style="padding: 35px 30px;">
            <h2 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; color: var(--text-main);">Account Access Portal</h2>
            
            <div class="nav-pills-wrap">
                <button class="role-tab active" id="tab-student" type="button" onclick="switchRole('student')">
                    <i class="bi bi-person me-1"></i> Student
                </button>
                <button class="role-tab" id="tab-faculty" type="button" onclick="switchRole('faculty')">
                    <i class="bi bi-person-badge me-1"></i> Staff / Faculty
                </button>
            </div>

            <?php if (isset($_GET['logged_out'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> Secure logout completed successfully.
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <input type="hidden" name="role" id="role-input" value="student">
                
                <div class="form-group">
                    <label for="login_id" class="form-label" id="login-label">Student ID</label>
                    <input type="text" class="form-control" id="login_id" name="login_id" placeholder="e.g. PCNH<?= date('Y') ?>0001" value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>" autocomplete="username" required>
                    <div class="form-hint" id="login-hint">Submit your registered Student ID (e.g. PCNH<?= date('Y') ?>0001)</div>
                </div>

                <div class="form-group" style="margin-bottom: 30px;">
                    <label for="password" class="form-label">Password</label>
                    <div class="pw-wrapper">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter access password" autocomplete="current-password" required>
                        <button class="toggle-pw" type="button" onclick="togglePassword()" title="Toggle password visibility">
                            <i class="bi bi-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="submit-btn" style="width: 100%; padding: 12px; font-size: 1rem;">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Authenticate as Student
                </button>
            </form>
            <div style="text-align: center; margin-top: 15px;">
                <a href="forgot_password.php" class="text-muted" style="font-size: 0.85rem; text-decoration: none;">Forgot your password?</a>
            </div>

            <hr style="border: 0; height: 1px; background: var(--glass-border); margin: 25px 0;">
            
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0;">
                Authentication issues? <a href="mailto:registrar@pines.edu.ph" style="color: var(--primary-color); font-weight: 500; text-decoration: none;">Contact administration</a>
            </p>
        </div>

        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 20px;">
            &copy; <?= date('Y') ?> Pines EMS. Internal system property.
        </p>
    </div>

<script>
    // Theme toggle functionality specifically for the login page layout
    function toggleTheme() {
        const html = document.documentElement;
        const icon = document.getElementById('theme-icon-login');
        
        if (html.hasAttribute('data-theme')) {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            icon.className = 'bi bi-moon-stars-fill';
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            icon.className = 'bi bi-sun-fill';
        }
    }
    
    // Set initial icon on load
    window.onload = () => {
        if (document.documentElement.hasAttribute('data-theme')) {
            document.getElementById('theme-icon-login').className = 'bi bi-sun-fill';
        }
    };

    // Manages dynamic UI rendering based on the selected authorization role
    function switchRole(role) {
        const isFaculty = role === 'faculty';
        document.getElementById('role-input').value = role;
        
        document.getElementById('tab-student').classList.toggle('active', !isFaculty);
        document.getElementById('tab-faculty').classList.toggle('active', isFaculty);
        
        if (isFaculty) {
            document.getElementById('login-label').textContent = 'Email Address';
            document.getElementById('login_id').placeholder    = 'Staff email address';
            document.getElementById('login-hint').textContent  = 'Submit authorized staff email.';
            document.getElementById('submit-btn').innerHTML    = '<i class="bi bi-person-badge me-1"></i> Authenticate as Staff';
        } else {
            document.getElementById('login-label').textContent = 'Student ID';
            document.getElementById('login_id').placeholder    = 'e.g. PCNH<?= date('Y') ?>0001';
            document.getElementById('login-hint').textContent  = 'Submit your registered Student ID (e.g. PCNH<?= date('Y') ?>0001)';
            document.getElementById('submit-btn').innerHTML    = '<i class="bi bi-box-arrow-in-right me-1"></i> Authenticate as Student';
        }
    }

    // Handles password field masking operations
    function togglePassword() {
        const pw   = document.getElementById('password');
        const icon = document.getElementById('eye-icon');
        const isHidden = pw.type === 'password';
        
        pw.type = isHidden ? 'text' : 'password';
        icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    }

    // Restores the correct interface tab if authentication fails
    const savedRole = "<?= htmlspecialchars($_POST['role'] ?? 'student') ?>";
    switchRole(savedRole);
</script>
</body>
</html>