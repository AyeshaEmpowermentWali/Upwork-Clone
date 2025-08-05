<?php
require_once 'db.php';

// Get job ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    redirect('jobs.php');
}

// Get job details with client info
$stmt = $pdo->prepare("
    SELECT j.*, u.first_name, u.last_name, u.company_name, u.bio as client_bio, 
           u.location, u.created_at as client_since, c.name as category_name,
           COUNT(p.id) as proposal_count
    FROM jobs j 
    JOIN users u ON j.client_id = u.id 
    JOIN categories c ON j.category_id = c.id 
    LEFT JOIN proposals p ON j.id = p.job_id
    WHERE j.id = ? 
    GROUP BY j.id
");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    redirect('jobs.php');
}

// Check if current user already applied
$already_applied = false;
if (isLoggedIn() && getUserType() == 'freelancer') {
    $stmt = $pdo->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
    $stmt->execute([$job_id, $_SESSION['user_id']]);
    $already_applied = $stmt->fetch() ? true : false;
}

// Get recent proposals (for client view)
$recent_proposals = [];
if (isLoggedIn() && getUserType() == 'client' && $_SESSION['user_id'] == $job['client_id']) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.first_name, u.last_name, u.hourly_rate, u.rating, u.total_jobs_completed
        FROM proposals p 
        JOIN users u ON p.freelancer_id = u.id 
        WHERE p.job_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$job_id]);
    $recent_proposals = $stmt->fetchAll();
}

// Get similar jobs
$stmt = $pdo->prepare("
    SELECT j.*, u.company_name, u.first_name, u.last_name 
    FROM jobs j 
    JOIN users u ON j.client_id = u.id 
    WHERE j.category_id = ? AND j.id != ? AND j.status = 'open' 
    ORDER BY j.created_at DESC 
    LIMIT 3
");
$stmt->execute([$job['category_id'], $job_id]);
$similar_jobs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - FreelanceHub</title>
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

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #667eea;
            text-decoration: none;
            margin: 2rem 0;
            font-weight: 500;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #5a6fd8;
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .job-content {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .job-sidebar {
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

        /* Job Header */
        .job-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #eee;
        }

        .job-title {
            font-size: 2.2rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 1rem;
            color: #666;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .job-budget {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 1rem;
        }

        .job-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-open {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-in-progress {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        /* Job Description */
        .job-description {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.4rem;
            color: #333;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }

        .description-text {
            color: #666;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        /* Skills */
        .skills-section {
            margin-bottom: 2rem;
        }

        .skills-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .skill-tag {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Apply Section */
        .apply-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .apply-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 1rem 3rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }

        .apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .apply-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .already-applied {
            background: #6c757d;
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Client Info */
        .client-info {
            text-align: center;
        }

        .client-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }

        .client-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .client-company {
            color: #667eea;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .client-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        /* Job Details */
        .job-details-list {
            list-style: none;
        }

        .job-details-list li {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }

        .job-details-list li:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #333;
        }

        .detail-value {
            color: #666;
        }

        /* Similar Jobs */
        .similar-jobs {
            margin-top: 2rem;
        }

        .similar-job-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .similar-job-item:hover {
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .similar-job-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .similar-job-title a {
            color: #333;
            text-decoration: none;
        }

        .similar-job-title a:hover {
            color: #667eea;
        }

        .similar-job-budget {
            color: #28a745;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Proposals Section */
        .proposals-section {
            margin-top: 2rem;
        }

        .proposal-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }

        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .freelancer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .freelancer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .proposal-bid {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }

        .proposal-text {
            color: #666;
            line-height: 1.6;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .main-content {
                grid-template-columns: 1fr;
            }

            .job-meta {
                flex-direction: column;
                gap: 1rem;
            }

            .client-stats {
                grid-template-columns: 1fr;
            }

            .job-title {
                font-size: 1.8rem;
            }
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
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="messages.php">Messages</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn btn-primary">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container">
        <a href="jobs.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Jobs
        </a>

        <div class="main-content">
            <div class="job-content">
                <div class="job-header">
                    <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                    
                    <div class="job-meta">
                        <div class="meta-item">
                            <i class="fas fa-tag"></i>
                            <span><?php echo htmlspecialchars($job['category_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>Posted <?php echo timeAgo($job['created_at']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo $job['proposal_count']; ?> proposals</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-signal"></i>
                            <span><?php echo ucfirst($job['experience_required']); ?> level</span>
                        </div>
                    </div>

                    <div class="job-budget">
                        <?php if ($job['budget_type'] == 'fixed'): ?>
                            <?php echo formatCurrency($job['budget_min']) . ' - ' . formatCurrency($job['budget_max']); ?>
                            <small style="color: #666; font-weight: normal;"> (Fixed Price)</small>
                        <?php else: ?>
                            <?php echo formatCurrency($job['hourly_rate']) . '/hr'; ?>
                            <small style="color: #666; font-weight: normal;"> (Hourly Rate)</small>
                        <?php endif; ?>
                    </div>

                    <span class="job-status status-<?php echo str_replace('_', '-', $job['status']); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
                    </span>
                </div>

                <div class="job-description">
                    <h2 class="section-title">Job Description</h2>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                    </div>
                </div>

                <?php if ($job['skills_required']): ?>
                <div class="skills-section">
                    <h2 class="section-title">Required Skills</h2>
                    <div class="skills-grid">
                        <?php 
                        $skills = explode(',', $job['skills_required']);
                        foreach ($skills as $skill): 
                        ?>
                        <span class="skill-tag"><?php echo trim(htmlspecialchars($skill)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isLoggedIn() && getUserType() == 'freelancer' && $job['status'] == 'open'): ?>
                <div class="apply-section">
                    <?php if ($already_applied): ?>
                        <div class="already-applied">
                            <i class="fas fa-check-circle"></i> You have already applied to this job
                        </div>
                    <?php else: ?>
                        <h3 style="margin-bottom: 1rem; color: #333;">Ready to apply?</h3>
                        <a href="apply-job.php?id=<?php echo $job['id']; ?>" class="apply-btn">
                            <i class="fas fa-paper-plane"></i> Submit Proposal
                        </a>
                    <?php endif; ?>
                </div>
                <?php elseif (!isLoggedIn()): ?>
                <div class="apply-section">
                    <h3 style="margin-bottom: 1rem; color: #333;">Interested in this job?</h3>
                    <a href="login.php" class="apply-btn">
                        <i class="fas fa-sign-in-alt"></i> Login to Apply
                    </a>
                </div>
                <?php endif; ?>

                <!-- Proposals Section (Only for job owner) -->
                <?php if (isLoggedIn() && getUserType() == 'client' && $_SESSION['user_id'] == $job['client_id'] && !empty($recent_proposals)): ?>
                <div class="proposals-section">
                    <h2 class="section-title">Recent Proposals</h2>
                    <?php foreach ($recent_proposals as $proposal): ?>
                    <div class="proposal-item">
                        <div class="proposal-header">
                            <div class="freelancer-info">
                                <div class="freelancer-avatar">
                                    <?php echo strtoupper(substr($proposal['first_name'], 0, 1) . substr($proposal['last_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #333;">
                                        <?php echo htmlspecialchars($proposal['first_name'] . ' ' . $proposal['last_name']); ?>
                                    </div>
                                    <div style="color: #666; font-size: 0.9rem;">
                                        <?php echo formatCurrency($proposal['hourly_rate']); ?>/hr • 
                                        <?php echo $proposal['total_jobs_completed']; ?> jobs completed
                                    </div>
                                </div>
                            </div>
                            <div class="proposal-bid">
                                <?php echo formatCurrency($proposal['bid_amount']); ?>
                            </div>
                        </div>
                        <div class="proposal-text">
                            <?php echo htmlspecialchars($proposal['cover_letter']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="job-sidebar">
                <!-- Client Information -->
                <div class="sidebar-card">
                    <h3 class="section-title">About the Client</h3>
                    <div class="client-info">
                        <div class="client-avatar">
                            <?php echo strtoupper(substr($job['first_name'], 0, 1) . substr($job['last_name'], 0, 1)); ?>
                        </div>
                        <div class="client-name">
                            <?php echo htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?>
                        </div>
                        <?php if ($job['company_name']): ?>
                        <div class="client-company">
                            <?php echo htmlspecialchars($job['company_name']); ?>
                        </div>
                        <?php endif; ?>
                        <div class="client-stats">
                            <div class="stat-item">
                                <div class="stat-number">
                                    <?php echo date('M Y', strtotime($job['client_since'])); ?>
                                </div>
                                <div class="stat-label">Member since</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">
                                    <?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?>
                                </div>
                                <div class="stat-label">Location</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Details -->
                <div class="sidebar-card">
                    <h3 class="section-title">Job Details</h3>
                    <ul class="job-details-list">
                        <li>
                            <span class="detail-label">Budget Type</span>
                            <span class="detail-value"><?php echo ucfirst($job['budget_type']); ?> Price</span>
                        </li>
                        <li>
                            <span class="detail-label">Experience Level</span>
                            <span class="detail-value"><?php echo ucfirst($job['experience_required']); ?></span>
                        </li>
                        <?php if ($job['deadline']): ?>
                        <li>
                            <span class="detail-label">Deadline</span>
                            <span class="detail-value"><?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if ($job['duration']): ?>
                        <li>
                            <span class="detail-label">Duration</span>
                            <span class="detail-value"><?php echo htmlspecialchars($job['duration']); ?></span>
                        </li>
                        <?php endif; ?>
                        <li>
                            <span class="detail-label">Proposals</span>
                            <span class="detail-value"><?php echo $job['proposal_count']; ?></span>
                        </li>
                    </ul>
                </div>

                <!-- Similar Jobs -->
                <?php if (!empty($similar_jobs)): ?>
                <div class="sidebar-card">
                    <h3 class="section-title">Similar Jobs</h3>
                    <div class="similar-jobs">
                        <?php foreach ($similar_jobs as $similar): ?>
                        <div class="similar-job-item">
                            <div class="similar-job-title">
                                <a href="job-details.php?id=<?php echo $similar['id']; ?>">
                                    <?php echo htmlspecialchars($similar['title']); ?>
                                </a>
                            </div>
                            <div class="similar-job-budget">
                                <?php if ($similar['budget_type'] == 'fixed'): ?>
                                    <?php echo formatCurrency($similar['budget_min']) . ' - ' . formatCurrency($similar['budget_max']); ?>
                                <?php else: ?>
                                    <?php echo formatCurrency($similar['hourly_rate']) . '/hr'; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Animate sidebar cards
        document.querySelectorAll('.sidebar-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });
    </script>
</body>
</html>
