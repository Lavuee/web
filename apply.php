<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Application | Pines NHS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .form-section { margin-bottom: 30px; padding: 20px; background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 12px; }
        .form-section h3 { margin-bottom: 15px; color: var(--primary-color); border-bottom: 1px solid var(--glass-border); padding-bottom: 5px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-color); color: var(--text-main); }
        .btn-submit { padding: 12px 24px; font-size: 1.1rem; width: 100%; margin-top: 20px; }
    </style>
</head>
<body>
    <div style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
        <a href="index.php" class="btn btn-outline" style="margin-bottom: 20px;">&larr; Back to Home</a>
        
        <h1 style="font-size: 2rem; margin-bottom: 10px;">Junior High School Application</h1>
        <p class="text-muted" style="margin-bottom: 30px;">Please fill out all required fields to submit your application for review.</p>

        <form action="actions/submit_application.php" method="POST" enctype="multipart/form-data">
            
            <!-- Student Information -->
            <div class="form-section">
                <h3>1. Personal Information</h3>
                <div class="form-group">
                    <label class="form-label">Learner Reference Number (LRN) *</label>
                    <input type="text" name="lrn" class="form-control" required placeholder="12-digit LRN">
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">Suffix (e.g., Jr, III)</label>
                        <input type="text" name="suffix" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Birthdate *</label>
                        <input type="date" name="birthdate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sex *</label>
                        <select name="sex" class="form-control" required>
                            <option value="">Select...</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="text" name="phone" class="form-control" required placeholder="09XXXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Complete Address *</label>
                        <input type="text" name="address" class="form-control" required>
                    </div>
                </div>
            </div>

            <!-- Guardian Information -->
            <div class="form-section">
                <h3>2. Guardian Information</h3>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">Guardian's Full Name *</label>
                        <input type="text" name="guardian_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Number *</label>
                        <input type="text" name="guardian_contact" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Relationship *</label>
                        <select name="guardian_relationship" class="form-control" required>
                            <option value="Mother">Mother</option>
                            <option value="Father">Father</option>
                            <option value="Relative">Relative</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="form-section">
                <h3>3. Academic Details</h3>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Previous School Attended *</label>
                        <input type="text" name="previous_school" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grade Level Applying For *</label>
                        <select name="grade_applying_for" class="form-control" required>
                            <option value="7">Grade 7</option>
                            <option value="8">Grade 8</option>
                            <option value="9">Grade 9</option>
                            <option value="10">Grade 10</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="form-section">
                <h3>4. Required Documents (PDF, JPG, PNG)</h3>
                <div class="form-group">
                    <label class="form-label">PSA Birth Certificate *</label>
                    <input type="file" name="doc_psa" class="form-control" accept=".pdf, .jpg, .jpeg, .png" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Form 138 (Report Card) *</label>
                    <input type="file" name="doc_f138" class="form-control" accept=".pdf, .jpg, .jpeg, .png" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Certificate of Good Moral *</label>
                    <input type="file" name="doc_good_moral" class="form-control" accept=".pdf, .jpg, .jpeg, .png" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-submit">Submit Application</button>
        </form>
    </div>
</body>
</html>