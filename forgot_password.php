<?php
require_once 'config/db.php';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);

        $resetLink = "http://localhost/pines_ems/reset_password.php?token=" . $token;
        $message = "Verification link generated. <a href='$resetLink' class='text-primary' style='font-weight:600;'>Reset Password Now &rarr;</a>";
    } else {
        $error = "Email address not found in our records.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Pines EMS</title>
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
        <div class="glass-panel" style="text-align: center; padding: 40px 30px;">
            <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);">
                <i class="bi bi-shield-lock-fill" style="font-size: 1.8rem; color: white;"></i>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 8px; color: var(--text-main);">Forgot Password?</h2>
            <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 30px;">Enter your email to receive a secure reset link.</p>
            
            <?php if ($message): ?>
                <div style="padding: 14px; background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; margin-bottom: 25px; font-size: 0.85rem; line-height: 1.5; text-align: left;">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="padding: 14px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; margin-bottom: 25px; font-size: 0.85rem; text-align: left;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" style="text-align: left;">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Send Verification</button>
            </form>
            
            <div style="margin-top: 30px; border-top: 1px solid var(--glass-border); padding-top: 20px;">
                <a href="login.php" class="text-muted" style="font-size: 0.85rem; text-decoration: none; font-weight: 500;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>