<?php
require_once 'db.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submission
if ($_POST) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $location = sanitize($_POST['location']);
    $bio = sanitize($_POST['bio']);
    $skills = sanitize($_POST['skills']);
    $hourly_rate = isset($_POST['hourly_rate']) ? (float)$_POST['hourly_rate'] : 0;
    $experience_level = sanitize($_POST['experience_level']);
    $portfolio_url = sanitize($_POST['portfolio_url']);
    $company_name = sanitize($_POST['company_name']);
    $company_description = sanitize($_POST['company_description']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = 'First name, last name, and email are required.';
    } else {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->fetch()) {
            $error = 'Email is already taken by another user.';
        } else {
            // Update user profile
            $stmt = $pdo->prepare("
                UPDATE users SET 
                first_name = ?, last_name = ?, email = ?, phone = ?, location = ?, 
                bio = ?, skills = ?, hourly_rate = ?, experience_level = ?, 
                portfolio_url = ?, company_name = ?, company_description = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([
                $first_name, $last_name, $email, $phone, $location, 
                $bio, $skills, $hourly_rate, $experience_level, 
                $portfolio_url, $company_name, $company_description, $user_id
            ])) {
                $success = 'Profile updated successfully!';
                // Update session data
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('login.php');
}

// Get user stats
if ($user['user_type'] == 'freelancer') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_proposals FROM proposals WHERE freelancer_id = ?");
    $stmt->execute([$user_id]);
    $total_proposals = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_contracts FROM contracts WHERE freelancer_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $active_contracts = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(contract_amount), 0) as total_earnings FROM contracts WHERE freelancer_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $total_earnings = $stmt->fetchColumn();
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_jobs FROM jobs WHERE client_id = ?");
    $stmt->execute([$user_id]);
    $total_jobs = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_jobs FROM jobs WHERE client_id = ? AND status = 'open'");
    $stmt->execute([$user_id]);
    $active_jobs = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_proposals FROM proposals p JOIN jobs j ON p.job_id = j.id WHERE j.client_id = ?");
    $stmt->execute([$user_id]);
    $total_proposals = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FreelanceHub</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
            font-weight: 500;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-primary {
            background: #28a745;
            color: white;
        }

        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-outline {
            border: 2px solid white;
            color: white;
            background: transparent;
        }

        .btn-outline:hover {
            background: white;
            color: #667eea;
        }

        /* Page Header */
        .page-header {
            background: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .profile-header-content {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .profile-info h1 {
            font-size: 2.2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .profile-type {
            color: #667eea;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .profile-meta {
            color: #666;
            display: flex;
            gap: 2rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-form {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .sidebar-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        input, select, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .save-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            background: #667eea;
            color: white;
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-card:hover .stat-number {
            color: white;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .stat-card:hover .stat-label {
            color: white;
        }

        /* Skills Display */
        .skills-display {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .skill-tag {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Profile Completion */
        .completion-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 10px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .completion-fill {
            background: linear-gradient(135deg, #28a745, #20c997);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s;
        }

        .completion-text {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .profile-header-content {
                flex-direction: column;
                text-align: center;
            }

            .profile-meta {
                justify-content: center;
                flex-wrap: wrap;
            }

            .main-content {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .freelancer-only, .client-only {
            display: none;
        }

        .freelancer-only.show, .client-only.show {
            display: block;
        }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <a href="index.php" class="logo">
                <i class="fas fa-briefcase"></i> FreelanceHub
            </a>
            <ul class="nav-links">
                <li><a href="jobs.php">Find Jobs</a></li>
                <li><a href="freelancers.php">Find Talent</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="page-header">
        <div class="container">
            <div class="profile-header-content">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    <div class="profile-type">
                        <i class="fas fa-<?php echo $user['user_type'] == 'freelancer' ? 'user-tie' : 'briefcase'; ?>"></i>
                        <?php echo ucfirst($user['user_type']); ?>
                    </div>
                    <div class="profile-meta">
                        <div class="meta-item">
                            <i class="fas fa-envelope"></i>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                        </div>
                        <?php if ($user['location']): ?>
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($user['location']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="profile-form">
                <h2 class="section-title">Edit Profile</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($user['location']); ?>" 
                                   placeholder="e.g. New York, USA">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bio">Bio / Description</label>
                        <textarea id="bio" name="bio" rows="4" 
                                  placeholder="Tell us about yourself, your experience, and what makes you unique..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>

                    <!-- Freelancer-specific fields -->
                    <div class="freelancer-only <?php echo $user['user_type'] == 'freelancer' ? 'show' : ''; ?>">
                        <div class="form-group">
                            <label for="skills">Skills (comma-separated)</label>
                            <input type="text" id="skills" name="skills" 
                                   value="<?php echo htmlspecialchars($user['skills']); ?>" 
                                   placeholder="e.g. PHP, JavaScript, React, Node.js, MySQL">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="hourly_rate">Hourly Rate ($)</label>
                                <input type="number" id="hourly_rate" name="hourly_rate" 
                                       value="<?php echo $user['hourly_rate']; ?>" 
                                       min="0" step="0.01" placeholder="25.00">
                            </div>
                            <div class="form-group">
                                <label for="experience_level">Experience Level</label>
                                <select id="experience_level" name="experience_level">
                                    <option value="beginner" <?php echo $user['experience_level'] == 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="intermediate" <?php echo $user['experience_level'] == 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="expert" <?php echo $user['experience_level'] == 'expert' ? 'selected' : ''; ?>>Expert</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="portfolio_url">Portfolio Website</label>
                            <input type="url" id="portfolio_url" name="portfolio_url" 
                                   value="<?php echo htmlspecialchars($user['portfolio_url']); ?>" 
                                   placeholder="https://yourportfolio.com">
                        </div>
                    </div>

                    <!-- Client-specific fields -->
                    <div class="client-only <?php echo $user['user_type'] == 'client' ? 'show' : ''; ?>">
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" id="company_name" name="company_name" 
                                   value="<?php echo htmlspecialchars($user['company_name']); ?>" 
                                   placeholder="Your Company Name">
                        </div>

                        <div class="form-group">
                            <label for="company_description">Company Description</label>
                            <textarea id="company_description" name="company_description" rows="3" 
                                      placeholder="Describe your company and what you do..."><?php echo htmlspecialchars($user['company_description']); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="save-btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>

            <div class="profile-sidebar">
                <!-- Profile Completion -->
                <div class="sidebar-card">
                    <h3 class="section-title">Profile Completion</h3>
                    <?php
                    $completion = 0;
                    $total_fields = $user['user_type'] == 'freelancer' ? 10 : 8;
                    
                    if ($user['first_name']) $completion++;
                    if ($user['last_name']) $completion++;
                    if ($user['email']) $completion++;
                    if ($user['phone']) $completion++;
                    if ($user['location']) $completion++;
                    if ($user['bio']) $completion++;
                    
                    if ($user['user_type'] == 'freelancer') {
                        if ($user['skills']) $completion++;
                        if ($user['hourly_rate'] > 0) $completion++;
                        if ($user['experience_level']) $completion++;
                        if ($user['portfolio_url']) $completion++;
                    } else {
                        if ($user['company_name']) $completion++;
                        if ($user['company_description']) $completion++;
                    }
                    
                    $percentage = round(($completion / $total_fields) * 100);
                    ?>
                    <div class="completion-bar">
                        <div class="completion-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <div class="completion-text"><?php echo $percentage; ?>% Complete</div>
                </div>

                <!-- Stats -->
                <div class="sidebar-card">
                    <h3 class="section-title">Your Stats</h3>
                    <div class="stats-grid">
                        <?php if ($user['user_type'] == 'freelancer'): ?>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_proposals; ?></div>
                                <div class="stat-label">Proposals Sent</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $active_contracts; ?></div>
                                <div class="stat-label">Active Projects</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo formatCurrency($total_earnings); ?></div>
                                <div class="stat-label">Total Earned</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo number_format($user['rating'], 1); ?></div>
                                <div class="stat-label">Rating</div>
                            </div>
                        <?php else: ?>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_jobs; ?></div>
                                <div class="stat-label">Jobs Posted</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $active_jobs; ?></div>
                                <div class="stat-label">Active Jobs</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $total_proposals; ?></div>
                                <div class="stat-label">Proposals Received</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Skills Preview (for freelancers) -->
                <?php if ($user['user_type'] == 'freelancer' && $user['skills']): ?>
                <div class="sidebar-card">
                    <h3 class="section-title">Your Skills</h3>
                    <div class="skills-display">
                        <?php 
                        $skills = explode(',', $user['skills']);
                        foreach ($skills as $skill): 
                        ?>
                        <span class="skill-tag"><?php echo trim(htmlspecialchars($skill)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <h3 class="section-title">Quick Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php if ($user['user_type'] == 'freelancer'): ?>
                            <a href="jobs.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Find Jobs
                            </a>
                            <a href="dashboard.php" class="btn btn-outline" style="color: #667eea; border-color: #667eea;">
                                <i class="fas fa-chart-bar"></i> View Dashboard
                            </a>
                        <?php else: ?>
                            <a href="post-job.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Post New Job
                            </a>
                            <a href="freelancers.php" class="btn btn-outline" style="color: #667eea; border-color: #667eea;">
                                <i class="fas fa-users"></i> Find Freelancers
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-save form data to localStorage
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, textarea, select');
        
        // Load saved data
        inputs.forEach(input => {
            const savedValue = localStorage.getItem('profile_' + input.name);
            if (savedValue && !input.value) {
                input.value = savedValue;
            }
        });

        // Save data on input
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                localStorage.setItem('profile_' + this.name, this.value);
            });
        });

        // Clear saved data on successful submit
        form.addEventListener('submit', function() {
            inputs.forEach(input => {
                localStorage.removeItem('profile_' + input.name);
            });
        });

        // Skills input helper
        const skillsInput = document.getElementById('skills');
        if (skillsInput) {
            skillsInput.addEventListener('blur', function() {
                // Clean up skills format
                const skills = this.value.split(',').map(skill => skill.trim()).filter(skill => skill);
                this.value = skills.join(', ');
            });
        }

        // Form validation
        form.addEventListener('submit', function(e) {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();

            if (!firstName || !lastName || !email) {
                e.preventDefault();
                alert('Please fill in all required fields (First Name, Last Name, Email).');
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });
    </script>
</body>
</html>
