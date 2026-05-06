<?php
require_once '../actions/auth.php';
check_admin();
require_once '../config/db.php';

// Add logic to process profile updates here

$current_page = 'manage_profile.php';
include 'includes/admin_header.php';
?>
<body>
    <div class="layout">
        
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <h2 style="margin-bottom: 5px;">Manage Profile</h2>
            <p class="text-muted" style="margin-bottom: 30px; font-size: 0.9rem;">Update your personal details and account preferences.</p>

            <div class="glass-panel">
                <p style="color: var(--text-muted);">Profile settings form goes here...</p>
                <!-- Form for updating email, password, etc. -->
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>