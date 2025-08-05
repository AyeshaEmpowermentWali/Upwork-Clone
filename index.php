<?php
require_once 'db.php';

// Fetch featured jobs
$stmt = $pdo->prepare("
    SELECT j.*, u.first_name, u.last_name, u.company_name, c.name as category_name 
    FROM jobs j 
    JOIN users u ON j.client_id = u.id 
    JOIN categories c ON j.category_id = c.id 
    WHERE j.status = 'open' 
    ORDER BY j.featured DESC, j.created_at DESC 
    LIMIT 6
");
$stmt->execute();
$featured_jobs = $stmt->fetchAll();

// Fetch top freelancers
$stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE user_type = 'freelancer' 
    ORDER BY rating DESC, total_jobs_completed DESC 
    LIMIT 6
");
$stmt->execute();
$top_freelancers = $stmt->fetchAll();

// Fetch categories
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreelanceHub - Find Great Talent or Work</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
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

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .search-bar {
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            background: white;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .search-bar input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            font-size: 1rem;
            outline: none;
        }

        .search-bar button {
            padding: 1rem 2rem;
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .search-bar button:hover {
            background: #218838;
        }

        /* Stats Section */
        .stats {
            background: white;
            padding: 3rem 0;
            margin: 2rem 0;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item h3 {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-item p {
            color: #666;
            font-weight: 500;
        }

        /* Section Styles */
        .section {
            padding: 4rem 0;
        }

        .section h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
        }

        /* Job Cards */
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .job-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid #667eea;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .job-card h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .job-card h3 a {
            color: #333;
            text-decoration: none;
        }

        .job-card h3 a:hover {
            color: #667eea;
        }

        .job-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .job-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .job-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .skill-tag {
            background: #e9ecef;
            color: #495057;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .job-budget {
            font-weight: 600;
            color: #28a745;
            font-size: 1.1rem;
        }

        /* Freelancer Cards */
        .freelancers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .freelancer-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .freelancer-card:hover {
            transform: translateY(-5px);
        }

        .freelancer-avatar {
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

        .freelancer-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .freelancer-title {
            color: #666;
            margin-bottom: 1rem;
        }

        .rating {
            display: flex;
            justify-content: center;
            gap: 0.2rem;
            margin-bottom: 1rem;
        }

        .star {
            color: #ffc107;
        }

        /* Categories */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .category-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-5px);
        }

        .category-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .category-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        /* Footer */
        footer {
            background: #333;
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #667eea;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section ul li a:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #555;
            color: #ccc;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .search-bar {
                flex-direction: column;
                border-radius: 10px;
            }

            .search-bar button {
                border-radius: 0 0 10px 10px;
            }

            .jobs-grid,
            .freelancers-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .mobile-menu {
            display: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .mobile-menu {
                display: block;
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
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Find Great Talent or Work</h1>
            <p>Connect with skilled freelancers or find your next project</p>
            <div class="search-bar">
                <input type="text" placeholder="Search for jobs, skills, or freelancers..." id="searchInput">
                <button onclick="performSearch()">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="stats">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>10K+</h3>
                    <p>Active Freelancers</p>
                </div>
                <div class="stat-item">
                    <h3>5K+</h3>
                    <p>Jobs Posted</p>
                </div>
                <div class="stat-item">
                    <h3>98%</h3>
                    <p>Success Rate</p>
                </div>
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>Support</p>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="container">
            <h2>Featured Jobs</h2>
            <div class="jobs-grid">
                <?php foreach ($featured_jobs as $job): ?>
                <div class="job-card">
                    <h3><a href="job-details.php?id=<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a></h3>
                    <div class="job-meta">
                        <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company_name'] ?: $job['first_name'] . ' ' . $job['last_name']); ?></span>
                        <span><i class="fas fa-map-marker-alt"></i> Remote</span>
                        <span><i class="fas fa-clock"></i> <?php echo timeAgo($job['created_at']); ?></span>
                    </div>
                    <p class="job-description"><?php echo substr(htmlspecialchars($job['description']), 0, 150) . '...'; ?></p>
                    <?php if ($job['skills_required']): ?>
                    <div class="job-skills">
                        <?php 
                        $skills = explode(',', $job['skills_required']);
                        foreach (array_slice($skills, 0, 4) as $skill): 
                        ?>
                        <span class="skill-tag"><?php echo trim(htmlspecialchars($skill)); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="job-footer">
                        <div class="job-budget">
                            <?php if ($job['budget_type'] == 'fixed'): ?>
                                <?php echo formatCurrency($job['budget_min']) . ' - ' . formatCurrency($job['budget_max']); ?>
                            <?php else: ?>
                                <?php echo formatCurrency($job['hourly_rate']) . '/hr'; ?>
                            <?php endif; ?>
                        </div>
                        <span class="proposals-count"><?php echo $job['proposals_count']; ?> proposals</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center;">
                <a href="jobs.php" class="btn btn-primary">View All Jobs</a>
            </div>
        </div>
    </section>

    <section class="section" style="background: white;">
        <div class="container">
            <h2>Top Freelancers</h2>
            <div class="freelancers-grid">
                <?php foreach ($top_freelancers as $freelancer): ?>
                <div class="freelancer-card">
                    <div class="freelancer-avatar">
                        <?php echo strtoupper(substr($freelancer['first_name'], 0, 1) . substr($freelancer['last_name'], 0, 1)); ?>
                    </div>
                    <div class="freelancer-name"><?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name']); ?></div>
                    <div class="freelancer-title"><?php echo htmlspecialchars(substr($freelancer['bio'], 0, 50)) . '...'; ?></div>
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $freelancer['rating'] ? 'star' : ''; ?>"></i>
                        <?php endfor; ?>
                        <span>(<?php echo number_format($freelancer['rating'], 1); ?>)</span>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <strong><?php echo formatCurrency($freelancer['hourly_rate']); ?>/hr</strong>
                    </div>
                    <a href="freelancer-profile.php?id=<?php echo $freelancer['id']; ?>" class="btn btn-primary">View Profile</a>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center;">
                <a href="freelancers.php" class="btn btn-primary">View All Freelancers</a>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Browse Categories</h2>
            <div class="categories-grid">
                <?php foreach (array_slice($categories, 0, 8) as $category): ?>
                <div class="category-card" onclick="window.location.href='jobs.php?category=<?php echo $category['id']; ?>'">
                    <div class="category-icon">
                        <i class="fas fa-<?php echo $category['icon']; ?>"></i>
                    </div>
                    <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                    <p><?php echo htmlspecialchars($category['description']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>For Clients</h3>
                    <ul>
                        <li><a href="jobs.php">Post a Job</a></li>
                        <li><a href="freelancers.php">Find Freelancers</a></li>
                        <li><a href="#">How it Works</a></li>
                        <li><a href="#">Success Stories</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>For Freelancers</h3>
                    <ul>
                        <li><a href="jobs.php">Find Jobs</a></li>
                        <li><a href="register.php">Create Profile</a></li>
                        <li><a href="#">Tips & Resources</a></li>
                        <li><a href="#">Community</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Company</h3>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Connect</h3>
                    <ul>
                        <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                        <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                        <li><a href="#"><i class="fab fa-linkedin"></i> LinkedIn</a></li>
                        <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 FreelanceHub. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function performSearch() {
            const searchTerm = document.getElementById('searchInput').value;
            if (searchTerm.trim()) {
                window.location.href = `jobs.php?search=${encodeURIComponent(searchTerm)}`;
            }
        }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        // Mobile menu toggle
        document.querySelector('.mobile-menu').addEventListener('click', function() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
        });
    </script>
</body>
</html>
