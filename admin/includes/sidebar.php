<aside class="sidebar">
    <div class="logo-container" style="margin-bottom: 40px; display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 1.25rem; color: var(--text-main); letter-spacing: -0.5px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="var(--primary-color)" viewBox="0 0 16 16">
            <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917z"/>
            <path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466z"/>
        </svg>
        Pines NHS
    </div>
         
    <nav style="flex: 1;">
        <a href="dashboard.php"
        class="nav-link <?= ($current_page == 'dashboard.php')
        ? 'active' : '' ?>">Dashboard</a>
        <a href="enrollment_period.php" class="nav-link <?= ($current_page == 'enrollment_period.php') ? 'active' : '' ?>">Enrollment Periods</a>
        <a href="students.php" 
         class="nav-link <?= ($current_page == 'students.php') 
         ? 'active' : '' ?>">Student Management</a>
        <a href="payments.php" 
         class="nav-link <?= ($current_page == 'payments.php') 
         ? 'active' : '' ?>">Payment Verification</a>
        <a href="grades.php"
           class="nav-link <?= ($current_page == 'grades.php')
           ? 'active' : '' ?>">Grade Management</a>
        <a href="users.php" 
            class="nav-link <?= ($current_page == 'users.php') 
            ? 'active' : '' ?>">User Accounts</a>
        <a href="subjects.php" 
         class="nav-link <?= ($current_page == 'subjects.php') 
         ? 'active' : '' ?>">Subjects Management</a>
         <a href="fees.php" 
         class="nav-link <?= ($current_page == 'fees.php') 
         ? 'active' : '' ?>">Tuition Fees</a>
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

        <!-- User Avatar & Info -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <?php
                // Assigns a clean title since Admins don't have First/Last names in the DB
                $displayName = 'System Admin';
                $initial = 'S'; 
            ?>
            <a href="manage_profile.php" style="text-decoration: none; width: 42px; height: 42px; border-radius: 50%; background: var(--primary-color); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; flex-shrink: 0; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);">
                <?= $initial ?>
            </a>
            <div style="overflow: hidden;">
                <p style="font-size: 0.85rem; margin: 0 0 3px 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <a href="manage_profile.php" style="color: var(--text-main); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-main)'">
                        <?= htmlspecialchars($displayName) ?>
                    </a>
                </p>
                <a href="../logout.php" class="text-muted" style="font-size: 0.8rem; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-muted)'">
                    Sign Out &rarr;
                </a>
            </div>
        </div>
    </div>
</aside>

<script>
    // Toggle function triggered when the slider is clicked
    function toggleTheme() {
        const html = document.documentElement;
        const checkbox = document.getElementById('theme-toggle-checkbox');
                 
        if (html.hasAttribute('data-theme')) {
            // Switch to Light Mode
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            if (checkbox) checkbox.checked = false;
        } else {
            // Switch to Dark Mode
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            if (checkbox) checkbox.checked = true;
        }
    }

    // Run immediately on page load to ensure the slider matches the saved theme
    document.addEventListener('DOMContentLoaded', () => {
        const checkbox = document.getElementById('theme-toggle-checkbox');
        if (localStorage.getItem('theme') === 'dark' || 
         (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            if (checkbox) checkbox.checked = true; // Slide to the right if dark mode is active
        }
    });
</script>