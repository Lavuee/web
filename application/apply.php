<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Application | Pines NHS</title>
    
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
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-label { font-size: 0.85rem; font-weight: 600; color: var(--text-main); }
        
        .form-control {
            width: 100%; padding: 12px; border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--bg-color); color: var(--text-main);
            transition: all 0.3s ease;
        }
        .form-control:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.15); }
        
        .full-width { grid-column: 1 / -1; }
        
        /* File input styling */
        input[type="file"]::file-selector-button {
            background: var(--glass-bg); border: 1px solid var(--glass-border);
            padding: 6px 12px; border-radius: 6px; color: var(--text-main);
            cursor: pointer; transition: 0.2s; margin-right: 10px;
        }
        input[type="file"]::file-selector-button:hover { background: var(--primary-color); color: white; }

        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body style="display: flex; flex-direction: column; align-items: center; padding: 40px 20px; min-height: 100vh;">

    <div class="theme-switch-wrapper" style="position: absolute; top: 20px; right: 20px; margin-bottom: 0;" title="Toggle Theme">
        <label class="theme-switch">
            <input type="checkbox" id="theme-toggle-checkbox" onchange="toggleTheme()">
            <span class="slider"></span>
        </label>
    </div>

    <div style="max-width: 800px; width: 100%;">
        
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
            <a href="../index.php" class="btn btn-outline" style="padding: 8px 16px;">&larr; Home</a>
            <div>
                <h2 style="font-size: 1.8rem; margin-bottom: 5px; color: var(--text-main);">Enrollment Application</h2>
                <p class="text-muted" style="font-size: 0.95rem; margin: 0;">School Year <?= date('Y') ?>-<?= date('Y') + 1 ?></p>
            </div>
        </div>

        <form action="submit_application.php" method="POST" enctype="multipart/form-data">
            
            <div class="glass-panel form-section">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                   
                    <h3 style="font-size: 1.1rem; margin: 0; color: var(--text-main);">Personal Information</h3>
                </div>
                
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">First Name *</label><input type="text" class="form-control" name="first_name" required></div>
                    <div class="form-group"><label class="form-label">Middle Name</label><input type="text" class="form-control" name="middle_name"></div>
                    <div class="form-group"><label class="form-label">Last Name *</label><input type="text" class="form-control" name="last_name" required></div>
                    <div class="form-group"><label class="form-label">Suffix (Jr., III)</label><input type="text" class="form-control" name="suffix"></div>
                    <div class="form-group"><label class="form-label">Date of Birth *</label><input type="date" class="form-control" name="date_of_birth" required></div>
                    <div class="form-group">
                        <label class="form-label">Gender *</label>
                        <select name="gender" class="form-control" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="glass-panel form-section">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                   
                    <h3 style="font-size: 1.1rem; margin: 0; color: var(--text-main);">Contact & Guardian</h3>
                </div>
                
                <div class="form-grid">
                    <div class="form-group full-width"><label class="form-label">Active Email Address * (Used for login)</label><input type="email" class="form-control" name="student_email" required></div>
                    <div class="form-group full-width"><label class="form-label">Complete Home Address *</label><textarea name="address" class="form-control" rows="2" required></textarea></div>
                    <div class="form-group"><label class="form-label">Student Contact Number</label><input type="text" class="form-control" name="contact_number"></div>
                    <div class="form-group"><label class="form-label">Guardian / Parent Name *</label><input type="text" class="form-control" name="guardian_name" required></div>
                    <div class="form-group"><label class="form-label">Relationship to Student</label><input type="text" class="form-control" name="guardian_relationship" placeholder="Mother, Father, Aunt, etc."></div>
                    <div class="form-group"><label class="form-label">Guardian Contact Number *</label><input type="text" class="form-control" name="guardian_contact" required></div>
                </div>
            </div>

            <div class="glass-panel form-section">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                   
                    <h3 style="font-size: 1.1rem; margin: 0; color: var(--text-main);">Academic Details</h3>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Incoming Grade Level *</label>
                        <select name="grade_level" class="form-control" required>
                            <option value="">Select Grade</option>
                            <option value="Grade 7">Grade 7</option>
                            <option value="Grade 8">Grade 8</option>
                            <option value="Grade 9">Grade 9</option>
                            <option value="Grade 10">Grade 10</option>
                        </select>
                    </div>
                    <div class="form-group full-width"><label class="form-label">Learner Reference Number (LRN) *</label><input type="text" class="form-control" name="lrn" required></div>
                    <div class="form-group full-width"><label class="form-label">Last School Attended *</label><input type="text" class="form-control" name="previous_school" required></div>
                </div>
            </div>

            <div class="glass-panel form-section">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                  
                    <h3 style="font-size: 1.1rem; margin: 0; color: var(--text-main);">Required Documents</h3>
                </div>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Please upload clear photos or PDF scans of the following requirements.</p>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">PSA Birth Certificate *</label>
                        <input type="file" class="form-control" name="doc_birth_cert" accept=".pdf, .jpg, .jpeg, .png" required style="padding: 8px;">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Form 138 (Previous Report Card) *</label>
                        <input type="file" class="form-control" name="doc_report_card" accept=".pdf, .jpg, .jpeg, .png" required style="padding: 8px;">
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label">Certificate of Good Moral Character</label>
                        <input type="file" class="form-control" name="doc_good_moral" accept=".pdf, .jpg, .jpeg, .png" style="padding: 8px;">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 1.1rem; font-weight: 600; margin-top: 10px; box-shadow: 0 4px 15px rgba(22, 101, 52, 0.3);">
                Submit Application & Generate Tracking No. &rarr;
            </button>
            <p style="text-align: center; font-size: 0.8rem; color: var(--text-muted); margin-top: 15px;">By submitting this form, you agree to the school's data privacy policy.</p>
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