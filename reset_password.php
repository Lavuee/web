<?php
require_once 'config/db.php';
$token = $_GET['token'] ?? '';
$error = '';
$success = false;

$stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
$stmt->execute([$token]);
$resetRequest = $stmt->fetch();

if (!$resetRequest) {
    die("Invalid or expired verification link. Please request a new one.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pw = $_POST['password'];
    $confirm_pw = $_POST['confirm_password'];

    if ($new_pw !== $confirm_pw) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_pw) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $email = $resetRequest['email'];

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $stmt->execute([$hashed, $email]);
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            $pdo->commit();
            $success = true;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "System error occurred.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Pines EMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; background-color: var(--bg-color);">
    <div class="login-container" style="width: 100%; max-width: 420px;">
        <div class="glass-panel" style="padding: 40px 30px;">
            <?php if ($success): ?>
                <div style="text-align: center;">
                    <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="bi bi-check2-all" style="font-size: 2rem; color: white;"></i>
                    </div>
                    <h2 style="color: var(--text-main); font-weight: 700; margin-bottom: 10px;">Security Updated</h2>
                    <p class="text-muted" style="margin-bottom: 30px; font-size: 0.95rem;">Your new password has been successfully established.</p>
                    <a href="login.php" class="btn btn-primary" style="width: 100%;">Sign In Now</a>
                </div>
            <?php else: ?>
                <h2 style="text-align: center; font-weight: 700; color: var(--text-main); margin-bottom: 25px;">Set New Password</h2>
                <?php if ($error): ?>
                    <div style="padding: 12px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; margin-bottom: 20px; font-size: 0.85rem;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8" placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Establish Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>