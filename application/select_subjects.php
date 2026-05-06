<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Subjects & Section | Pines NHS</title>
    
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
        .form-section { padding: 30px; margin-bottom: 20px; text-align: left; }
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .form-label { font-size: 0.85rem; font-weight: 600; color: var(--text-main); }
        
        .form-control {
            width: 100%; padding: 12px; border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--bg-color); color: var(--text-main);
            transition: all 0.3s ease;
        }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.15); }
        
        .subject-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 15px;
        }
        
        /* Modern Checkbox Styling */
        .subject-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: rgba(100, 116, 139, 0.05);
        }
        .subject-item:hover {
            border-color: var(--primary-color);
            background: rgba(22, 101, 52, 0.05);
        }
        .subject-item input[type="checkbox"] {
            width: 18px; 
            height: 18px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }
        .subject-name {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-main);
        }

        @media (max-width: 600px) { .subject-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body style="display: flex; flex-direction: column; align-items: center; padding: 40px 20px; min-height: 100vh;">

    <div class="theme-switch-wrapper" style="position: absolute; top: 20px; right: 20px; margin-bottom: 0;" title="Toggle Theme">
        <label class="theme-switch">
            <input type="checkbox" id="theme-toggle-checkbox" onchange="toggleTheme()">
            <span class="slider"></span>
        </label>
    </div>

    <div style="max-width: 650px; width: 100%;">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="font-size: 1.8rem; margin-bottom: 5px; color: var(--text-main);">Select Subjects & Section</h2>
            <p style="font-size: 0.95rem; color: var(--text-muted);">
                Tracking ID: <strong style="font-family: monospace; color: var(--primary-color); font-size: 1.1rem;"><?php echo htmlspecialchars($_GET['enrollment_id'] ?? 'Not Provided'); ?></strong>
            </p>
        </div>

        <form action="save_subject_enroll.php" method="POST">
            <input type="hidden" name="enrollment_id" value="<?php echo htmlspecialchars($_GET['enrollment_id'] ?? ''); ?>">

            <div class="glass-panel form-section">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                    
                    <h3 style="font-size: 1.1rem; margin: 0; color: var(--text-main);">Class Section</h3>
                </div>
                <div class="form-group">
                    <label class="form-label">Choose Section *</label>
                    <select name="section" class="form-control" required>
                        <option value="">Select a section...</option>
                        <option value="Section A (Rizal)">Section A (Rizal)</option>
                        <option value="Section B (Bonifacio)">Section B (Bonifacio)</option>
                        <option value="Section C (Mabini)">Section C (Mabini)</option>
                        <option value="Section D (Luna)">Section D (Luna)</option>
                    </select>
                </div>
            </div>

            <div class="glass-panel form-section">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                   
                    <h3 style="font-size: 1.1rem; margin: 0; color: var(--text-main);">Select Subjects</h3>
                </div>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">Core subjects are pre-selected. Uncheck any electives you do not wish to take.</p>

                <div class="subject-grid">
                    <label class="subject-item">
                        <input type="checkbox" name="subjects[]" value="Mathematics" checked> 
                        <span class="subject-name">Mathematics</span>
                    </label>
                    <label class="subject-item">
                        <input type="checkbox" name="subjects[]" value="Science" checked> 
                        <span class="subject-name">Science</span>
                    </label>
                    <label class="subject-item">
                        <input type="checkbox" name="subjects[]" value="English" checked> 
                        <span class="subject-name">English</span>
                    </label>
                    <label class="subject-item">
                        <input type="checkbox" name="subjects[]" value="Filipino" checked> 
                        <span class="subject-name">Filipino</span>
                    </label>
                    <label class="subject-item">
                        <input type="checkbox" name="subjects[]" value="Araling Panlipunan" checked> 
                        <span class="subject-name">Araling Panlipunan</span>
                    </label>
                    <label class="subject-item">
                        <input type="checkbox" name="subjects[]" value="MAPEH" checked> 
                        <span class="subject-name">MAPEH</span>
                    </label>
                    <label class="subject-item">
                        <input type="checkbox" name="subjects[]" value="TLE"> 
                        <span class="subject-name">TLE / ICT</span>
                    </label>
                    <label class="subject-item">
                        <input type="checkbox" name="subjects[]" value="Values Education" checked> 
                        <span class="subject-name">Values Education</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 1.1rem; font-weight: 600; box-shadow: 0 4px 15px rgba(22, 101, 52, 0.3);">
                Save Subjects & View Tracking &rarr;
            </button>
        </form>
    </div>

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
</body>
</html>