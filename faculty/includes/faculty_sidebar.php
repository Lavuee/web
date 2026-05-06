<?php
// Safely fetch and store the Faculty's name and ID in the session if not already set
if (!isset($_SESSION['faculty_name'])) {
    $facStmt = $pdo->prepare("SELECT faculty_id, first_name, last_name FROM faculty WHERE user_id = ?");
    $facStmt->execute([$_SESSION['user_id']]);
    $facData = $facStmt->fetch();
    if ($facData) {
        $_SESSION['faculty_name'] = trim($facData['first_name'] . ' ' . $facData['last_name']);
        $_SESSION['faculty_id']   = $facData['faculty_id'];
    } else {
        $_SESSION['faculty_name'] = 'Faculty Member';
        $_SESSION['faculty_id']   = 0;
    }
}
?>
<aside class="sidebar">
    <div class="logo-container" style="margin-bottom: 40px; display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 1.25rem; color: var(--text-main); letter-spacing: -0.5px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="var(--primary-color)" viewBox="0 0 16 16">
            <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917z"/>
            <path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466z"/>
        </svg>
        Pines NHS
    </div>
         
    <nav style="flex: 1;">
        <a href="dashboard.php" class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <a href="manage_grades.php" class="nav-link <?= ($current_page == 'manage_grades.php') ? 'active' : '' ?>">
            <i class="bi bi-journal-bookmark-fill"></i> My Classes & Grades
        </a>
        <a href="schedules.php" class="nav-link <?= ($current_page == 'schedules.php') ? 'active' : '' ?>">
            <i class="bi bi-calendar-week-fill"></i> My Schedule
        </a>
    </nav>
         
    <div style="border-top: 1px solid var(--glass-border); padding-top: 20px; margin-top: auto;">
        
        <!-- Theme Switcher -->
        <div class="theme-switch-wrapper" style="margin-bottom: 20px;">
            <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Dark Mode</span>
            <label class="theme-switch">
                <input type="checkbox" id="theme-toggle-checkbox" onchange="toggleTheme()">
                <span class="slider"></span>
            </label>
        </div>

        <!-- Dynamic User Avatar & Info -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <?php
                $displayName = $_SESSION['faculty_name'];
                $initial = strtoupper(substr($displayName, 0, 1));
            ?>
            <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--primary-color); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);">
                <?= $initial ?>
            </div>
            <div style="overflow: hidden;">
                <p style="font-size: 0.85rem; margin: 0 0 3px 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-main);">
                    <?= htmlspecialchars($displayName) ?>
                </p>
                <a href="../logout.php" class="text-muted" style="font-size: 0.8rem; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-muted)'">
                    Sign Out &rarr;
                </a>
            </div>
        </div>
    </div>
</aside>

<script>
    function toggleTheme() {
        const html = document.documentElement;
        const checkbox = document.getElementById('theme-toggle-checkbox');
                 
        if (html.hasAttribute('data-theme')) {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            if (checkbox) checkbox.checked = false;
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            if (checkbox) checkbox.checked = true;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const checkbox = document.getElementById('theme-toggle-checkbox');
        if (localStorage.getItem('theme') === 'dark' || 
         (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            if (checkbox) checkbox.checked = true; 
        }
    });
</script>