<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pines City National High School | Enrollment Portal</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- INLINE CSS -->
    <style>
        :root {
            /* Light Mode Palette */
            --bg-color: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #475569; 
            --primary-color: #15803d; 
            --primary-hover: #166534;
            --primary-text: #ffffff;
            --panel-bg: #ffffff;
            --panel-border: #e2e8f0;
            --hero-gradient: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.05);
            --accent-bg: #f0fdf4;
        }

        [data-theme="dark"] {
            /* Dark Mode Palette */
            --bg-color: #0f172a;
            --text-main: #f8fafc;
            --text-muted: #cbd5e1; 
            --primary-color: #22c55e; 
            --primary-hover: #16a34a;
            --primary-text: #0f172a; 
            --panel-bg: #1e293b;
            --panel-border: #334155;
            --hero-gradient: linear-gradient(135deg, #0f172a 0%, #064e3b 100%);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.4);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.5);
            --accent-bg: rgba(34, 197, 94, 0.1);
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Inter', sans-serif; 
            transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease, fill 0.4s ease, box-shadow 0.4s ease; 
        }

        body { 
            background-color: var(--bg-color); 
            color: var(--text-main); 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* UTILITIES */
        .text-primary { color: var(--primary-color) !important; }
        .me-1 { margin-right: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .text-center { text-align: center; }

        /* --- REUSABLE COMPONENTS --- */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; width: 100%; }
        .badge { display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }

        /* --- NAVIGATION --- */
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; background: var(--panel-bg); border-bottom: 1px solid var(--panel-border); position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow-sm); }

        /* --- HERO SECTION --- */
        .hero-section { padding: 90px 20px 110px; text-align: center; background: var(--hero-gradient); border-bottom: 1px solid var(--panel-border); display: flex; flex-direction: column; align-items: center; }
        .section-title { font-size: 2.2rem; font-weight: 800; color: var(--text-main); text-align: center; margin-bottom: 15px; letter-spacing: -0.5px; }
        .section-subtitle { font-size: 1.05rem; color: var(--text-muted); text-align: center; max-width: 700px; margin: 0 auto 50px auto; line-height: 1.6; }

        /* --- CARDS & GRIDS --- */
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 40px; }
        .grid-5 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-bottom: 40px; }

        .card-panel { background: var(--panel-bg); border: 1px solid var(--panel-border); border-radius: 16px; padding: 40px 30px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; transform: translateY(0); transition: all 0.4s ease; }
        .card-panel:hover { transform: translateY(-6px); box-shadow: var(--shadow-md); border-color: var(--primary-color); }

        /* Values Card Specific */
        .value-card { align-items: center; text-align: center; }
        .value-card .icon { height: 64px; width: 64px; border-radius: 16px; background: var(--accent-bg); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin-bottom: 24px; transition: all 0.4s ease; }
        .value-card:hover .icon { background: var(--primary-color); color: var(--primary-text); transform: scale(1.05); }
        .value-card h3 { font-size: 1.2rem; margin-bottom: 12px; color: var(--text-main); font-weight: 700; letter-spacing: -0.2px; }
        .value-card p { font-size: 0.95rem; color: var(--text-muted); line-height: 1.6; }

        /* Programs Card Specific */
        .program-card { border-left: 4px solid var(--primary-color); text-align: left; padding: 30px; }
        .program-card h3 { font-size: 1.3rem; margin-bottom: 15px; color: var(--text-main); font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .program-card ul { list-style: none; color: var(--text-muted); font-size: 0.95rem; line-height: 1.8; }
        .program-card ul li::before { content: "•"; color: var(--primary-color); font-weight: bold; display: inline-block; width: 1em; margin-left: -1em; }
        .program-card ul li { padding-left: 1em; margin-bottom: 8px; }

        /* Steps/Timeline Specific */
        .step-card { text-align: center; padding: 30px 20px; background: transparent; border: none; box-shadow: none; position: relative; }
        .step-card .step-number { width: 50px; height: 50px; border-radius: 50%; background: var(--primary-color); color: var(--primary-text); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 800; margin: 0 auto 20px auto; box-shadow: 0 4px 12px rgba(21, 128, 61, 0.3); }
        .step-card h4 { font-size: 1.1rem; color: var(--text-main); margin-bottom: 10px; font-weight: 700; }
        .step-card p { font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; }

        /* --- FOOTER --- */
        .footer { background: var(--panel-bg); border-top: 1px solid var(--panel-border); padding: 60px 20px 30px; margin-top: auto; }
        .footer-content { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 40px; max-width: 1200px; margin: 0 auto; text-align: left; }
        .footer-col h4 { color: var(--text-main); margin-bottom: 20px; font-weight: 700; font-size: 1.1rem; }
        .footer-col p { color: var(--text-muted); font-size: 0.95rem; line-height: 1.7; display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
        .footer-col a { color: var(--text-muted); font-size: 0.95rem; text-decoration: none; display: flex; align-items: center; gap: 10px; margin-bottom: 12px; transition: color 0.3s; }
        .footer-col a:hover { color: var(--primary-color); }
        .footer-bottom { text-align: center; padding-top: 30px; margin-top: 30px; border-top: 1px solid var(--panel-border); color: var(--text-muted); font-size: 0.85rem; }

        /* --- BUTTONS --- */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; border: 1px solid transparent; text-decoration: none; white-space: nowrap; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .btn-outline { background: transparent; color: var(--text-main); border: 1px solid var(--panel-border); }
        .btn-outline:hover { background: var(--panel-border); color: var(--text-main); transform: translateY(-1px); }
        .btn-primary { background: var(--primary-color); color: var(--primary-text); border: 1px solid var(--primary-color); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); box-shadow: 0 4px 14px rgba(21, 128, 61, 0.25); transform: translateY(-1px); color: var(--primary-text); }

        /* --- THEME TOGGLE BUTTON --- */
        .theme-toggle-btn { background: transparent; border: 1px solid var(--panel-border); color: var(--text-main); border-radius: 50%; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1.2rem; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); outline: none; }
        .theme-toggle-btn:hover { background: var(--panel-border); transform: rotate(15deg) scale(1.05); }
        .theme-toggle-btn i { transition: transform 0.4s ease, opacity 0.4s ease; }

        /* --- MOBILE RESPONSIVENESS (Refined for Phones & Tablets) --- */
        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; }
            .hero-section { padding: 60px 20px 80px; }
            .hero-section h1 { font-size: 2.5rem; letter-spacing: -1px; }
            .section-title { font-size: 1.8rem; }
            
            /* Stack buttons perfectly on mobile */
            .hero-section .btn { width: 100%; justify-content: center; margin-bottom: 10px; }
            .hero-section div[style*="display: flex"] { flex-direction: column; gap: 0; width: 100%; max-width: 320px; margin: 0 auto; }
            
            /* Tighten up paddings on mobile */
            section { padding: 60px 0 !important; }
            .card-panel { padding: 30px 20px; }
            
            /* Ensure grids stack nicely */
            .grid-3 { grid-template-columns: 1fr; }
            .grid-5 { grid-template-columns: 1fr; }
            
            /* Footer adjustments */
            .footer { padding: 40px 20px 20px; }
            .footer-content { grid-template-columns: 1fr; gap: 30px; text-align: center; }
            .footer-col p { justify-content: center; }
            .footer-col a { justify-content: center; }
        }
    </style>

    <!-- Pre-render Theme Check -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || 
           (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    </script>
</head>
<body>

    <!-- NAVIGATION BAR -->
    <nav class="navbar">
        <div style="display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 1.25rem; color: var(--text-main); letter-spacing: -0.5px;">
            <i class="bi bi-mortarboard-fill text-primary" style="font-size: 1.6rem;"></i>
            Pines NHS
        </div>
        
        <!-- Interactive Theme Toggle -->
        <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Theme" aria-label="Toggle Dark Mode">
            <i id="theme-icon" class="bi bi-moon-stars-fill"></i>
        </button>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="container">
            <span class="badge" style="background: var(--accent-bg); color: var(--primary-color); padding: 8px 16px; margin-bottom: 30px;">
                <i class="bi bi-calendar-check me-1"></i> Academic Year 2026-2027
            </span>

            <h1 style="font-size: 4.2rem; line-height: 1.1; font-weight: 800; margin-bottom: 24px; color: var(--text-main); letter-spacing: -1.5px;">
                Welcome to Pines <br>
                <span style="color: var(--primary-color);">National High School</span>
            </h1>
            
            <p style="color: var(--text-muted); max-width: 650px; margin: 0 auto 40px auto; font-size: 1.15rem; line-height: 1.7;">
                An institution dedicated to transforming potential into passion through holistic education. Manage your enrollment, track academic progress, and access official records securely.
            </p>

            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="application/apply.php" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Start Application</a>
                <a href="payment.php" class="btn btn-outline"><i class="bi bi-search"></i> Track Status & Pay</a>
                <a href="login.php" class="btn btn-outline"><i class="bi bi-person-circle"></i> Portal Login</a>
            </div>
        </div>
    </section>

    <!-- ACADEMIC PROGRAMS -->
    <section style="padding: 100px 0; background: var(--bg-color);">
        <div class="container">
            <h2 class="section-title">Academic Programs</h2>
            <p class="section-subtitle">Comprehensive educational tracks designed to prepare students for higher education, career readiness, and lifelong success.</p>
            
            <div style="max-width: 600px; margin: 0 auto 40px auto;">
                <div class="card-panel program-card">
                    <h3><i class="bi bi-book text-primary"></i> Junior High School</h3>
                    <p class="mb-4 text-muted">Grades 7 to 10 curriculum focused on building foundational knowledge and critical thinking skills.</p>
                    <ul>
                        <li>Science & Technology Curriculum</li>
                        <li>Mathematics Advancement</li>
                        <li>Languages & Communication</li>
                        <li>Values Education</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ENROLLMENT STEPS -->
    <section style="padding: 100px 0; background: var(--panel-bg); border-top: 1px solid var(--panel-border); border-bottom: 1px solid var(--panel-border);">
        <div class="container">
            <h2 class="section-title">How to Enroll</h2>
            <p class="section-subtitle">A streamlined, digital-first approach to student registration and assessment.</p>
            
            <div class="grid-5" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h4>Submit Application</h4>
                    <p>Complete the online registration form with accurate demographic and academic information.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h4>Save Credentials</h4>
                    <p>Secure the generated Enrollment Tracking Number. This serves as the portal access credential.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h4>Await Assessment</h4>
                    <p>The administrative office reviews the application and posts the official tuition assessment.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h4>Process Payment</h4>
                    <p>Submit tuition payments digitally via GCash or in-person cash transactions.</p>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="application/apply.php" class="btn btn-primary">Begin Registration Now <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- CORE VALUES (P.I.N.E.S.) -->
    <section style="padding: 100px 0; background: var(--bg-color);">
        <div class="container">
            <h2 class="section-title">Institutional Core Values</h2>
            <p class="section-subtitle">Guiding principles shaping the academic and moral development of the student body.</p>
            
            <div class="grid-5">
                <div class="card-panel value-card">
                    <div class="icon"><i class="bi bi-heart-fill"></i></div>
                    <h3>Passion</h3>
                    <p>Engage in the collection, analysis, and projection of knowledge to improve the educational management system.</p>
                </div>
                <div class="card-panel value-card">
                    <div class="icon"><i class="bi bi-shield-check"></i></div>
                    <h3>Integrity</h3>
                    <p>Embrace doing what is ethical, fair, and right in all academic and personal endeavors.</p>
                </div>
                <div class="card-panel value-card">
                    <div class="icon"><i class="bi bi-flower1"></i></div>
                    <h3>Nurturance</h3>
                    <p>Nurture honesty, openness, and respect, viewing stakeholders as partners for institutional growth.</p>
                </div>
                <div class="card-panel value-card">
                    <div class="icon"><i class="bi bi-star-fill"></i></div>
                    <h3>Excellence</h3>
                    <p>Strive for the highest personal and academic achievement in all aspects of life-long learning.</p>
                </div>
                <div class="card-panel value-card">
                    <div class="icon"><i class="bi bi-people-fill"></i></div>
                    <h3>Service</h3>
                    <p>Commit as catalysts of positive change, responsive to the evolving needs of the community.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-col">
                <div style="display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 1.25rem; color: var(--text-main); margin-bottom: 15px; letter-spacing: -0.5px;">
                    <i class="bi bi-mortarboard-fill text-primary"></i> Pines NHS
                </div>
                <p>Alma Mater of countless educators, executives, and competent professionals.</p>
            </div>
            <div class="footer-col">
                <h4>Contact Information</h4>
                <p><i class="bi bi-geo-alt-fill text-primary"></i> Magsaysay Ave., Baguio City, Philippines</p>
                <p><i class="bi bi-envelope-fill text-primary"></i> ask@pcc.edu.ph</p>
                <a href="https://fb.com/pccbaguio" target="_blank"><i class="bi bi-facebook text-primary"></i> fb.com/pccbaguio</a>
            </div>
            <div class="footer-col">
                <h4>System Portals</h4>
                <a href="login.php"><i class="bi bi-shield-lock text-primary"></i> Administrative Access</a>
                <a href="apply.php"><i class="bi bi-person-plus text-primary"></i> Student Admissions</a>
                <a href="payment.php"><i class="bi bi-search text-primary"></i> Application Tracking & Payment</a>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <span id="currentYear"></span> Pines City National High School. All rights reserved.
        </div>
    </footer>

    <!-- INTERACTIVE SCRIPTS -->
    <script>
        // Set dynamic year
        document.getElementById('currentYear').textContent = new Date().getFullYear();

        // Theme Icon Transition Logic
        function updateIcon(isDark) {
            const icon = document.getElementById('theme-icon');
            if (isDark) {
                icon.style.opacity = '0';
                setTimeout(() => {
                    icon.className = 'bi bi-sun-fill';
                    icon.style.opacity = '1';
                    icon.style.transform = 'rotate(360deg)';
                }, 200);
            } else {
                icon.style.opacity = '0';
                setTimeout(() => {
                    icon.className = 'bi bi-moon-stars-fill';
                    icon.style.opacity = '1';
                    icon.style.transform = 'rotate(0deg)';
                }, 200);
            }
        }

        // Theme Toggle Logic
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.hasAttribute('data-theme');
            
            if (isDark) {
                html.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                updateIcon(false);
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                updateIcon(true);
            }
        }
        
        // Sync icon on page load
        window.onload = () => {
            if (document.documentElement.hasAttribute('data-theme')) {
                const icon = document.getElementById('theme-icon');
                icon.className = 'bi bi-sun-fill';
            }
        };
    </script>
</body>
</html>