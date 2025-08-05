<?php
require_once 'db.php';

// Get freelancer ID from URL
$freelancer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$freelancer_id) {
    redirect('freelancers.php');
}

// Get freelancer details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'freelancer'");
$stmt->execute([$freelancer_id]);
$freelancer = $stmt->fetch();

if (!$freelancer) {
    redirect('freelancers.php');
}

// Get freelancer's completed jobs/reviews
$stmt = $pdo->prepare("
    SELECT r.*, j.title as job_title, u.first_name, u.last_name 
    FROM reviews r 
    JOIN contracts c ON r.contract_id = c.id 
    JOIN jobs j ON c.job_id = j.id 
    JOIN users u ON r.reviewer_id = u.id 
    WHERE r.reviewee_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$stmt->execute([$freelancer_id]);
$reviews = $stmt->fetchAll();

// Get freelancer's portfolio/recent work (using completed contracts as portfolio)
$stmt = $pdo->prepare("
    SELECT j.title, j.description, j.budget_type, c.contract_amount, c.created_at 
    FROM contracts c 
    JOIN jobs j ON c.job_id = j.id 
    WHERE c.freelancer_id = ? AND c.status = 'completed' 
    ORDER BY c.created_at DESC 
    LIMIT 6
");
$stmt->execute([$freelancer_id]);
$portfolio = $stmt->fetchAll();

// Calculate average rating
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE reviewee_id = ?");
$stmt->execute([$freelancer_id]);
$rating_data = $stmt->fetch();
$avg_rating = $rating_data['avg_rating'] ?: 0;
$total_reviews = $rating_data['total_reviews'] ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name']); ?> - FreelanceHub</title>
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

        /* Profile Header */
        .profile-header {
            background: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .profile-content {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 2rem;
            align-items: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .profile-info h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .profile-title {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .profile-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stars {
            display: flex;
            gap: 0.2rem;
        }

        .star {
            color: #ffc107;
        }

        .rating-text {
            color: #666;
            margin-left: 0.5rem;
        }

        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .hire-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }

        .hire-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .message-btn {
            background: #667eea;
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }

        .message-btn:hover {
            background: #5a6fd8;
        }

        .hourly-rate {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .content-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }

        .about-text {
            color: #666;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        /* Skills */
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

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
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
            font-size: 2rem;
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
        }

        .stat-card:hover .stat-label {
            color: white;
        }

        /* Portfolio */
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .portfolio-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }

        .portfolio-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .portfolio-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .portfolio-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .portfolio-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #666;
        }

        .portfolio-amount {
            font-weight: 600;
            color: #28a745;
        }

        /* Reviews */
        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .review-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            border-left: 4px solid #ffc107;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .reviewer-avatar {
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

        .reviewer-details h4 {
            color: #333;
            margin-bottom: 0.2rem;
        }

        .job-title {
            color: #666;
            font-size: 0.9rem;
        }

        .review-rating {
            display: flex;
            gap: 0.2rem;
        }

        .review-text {
            color: #666;
            line-height: 1.6;
        }

        .no-reviews {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .no-reviews i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .profile-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 1.5rem;
            }

            .profile-meta {
                justify-content: center;
                flex-wrap: wrap;
            }

            .main-content {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .portfolio-grid {
                grid-template-columns: 1fr;
            }

            .review-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 2rem;
            font-weight: 500;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #5a6fd8;
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
        <a href="freelancers.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Freelancers
        </a>

        <div class="profile-header">
            <div class="profile-content">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($freelancer['first_name'], 0, 1) . substr($freelancer['last_name'], 0, 1)); ?>
                </div>
                
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name']); ?></h1>
                    <div class="profile-title">
                        <?php echo htmlspecialchars($freelancer['bio'] ?: 'Professional Freelancer'); ?>
                    </div>
                    
                    <div class="profile-meta">
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($freelancer['location'] ?: 'Remote'); ?>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            Member since <?php echo date('M Y', strtotime($freelancer['created_at'])); ?>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-signal"></i>
                            <?php echo ucfirst($freelancer['experience_level']); ?> Level
                        </div>
                    </div>
                    
                    <div class="rating-display">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $avg_rating ? 'star' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text">
                            <?php echo number_format($avg_rating, 1); ?> (<?php echo $total_reviews; ?> reviews)
                        </span>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <div class="hourly-rate">
                        <?php echo formatCurrency($freelancer['hourly_rate']); ?>/hr
                    </div>
                    <?php if (isLoggedIn() && getUserType() == 'client'): ?>
                        <a href="hire-freelancer.php?id=<?php echo $freelancer['id']; ?>" class="hire-btn">
                            <i class="fas fa-handshake"></i> Hire Now
                        </a>
                        <a href="send-message.php?to=<?php echo $freelancer['id']; ?>" class="message-btn">
                            <i class="fas fa-envelope"></i> Send Message
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="hire-btn">
                            <i class="fas fa-sign-in-alt"></i> Login to Hire
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="left-column">
                <!-- About Section -->
                <div class="content-section">
                    <h2 class="section-title">About</h2>
                    <div class="about-text">
                        <?php if ($freelancer['bio']): ?>
                            <?php echo nl2br(htmlspecialchars($freelancer['bio'])); ?>
                        <?php else: ?>
                            <p>This freelancer hasn't added a detailed bio yet, but they're ready to work on your projects with their skills and experience.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Skills Section -->
                <?php if ($freelancer['skills']): ?>
                <div class="content-section">
                    <h2 class="section-title">Skills</h2>
                    <div class="skills-grid">
                        <?php 
                        $skills = explode(',', $freelancer['skills']);
                        foreach ($skills as $skill): 
                        ?>
                        <span class="skill-tag"><?php echo trim(htmlspecialchars($skill)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Portfolio Section -->
                <?php if (!empty($portfolio)): ?>
                <div class="content-section">
                    <h2 class="section-title">Recent Work</h2>
                    <div class="portfolio-grid">
                        <?php foreach ($portfolio as $item): ?>
                        <div class="portfolio-item">
                            <div class="portfolio-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="portfolio-description">
                                <?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?>
                            </div>
                            <div class="portfolio-meta">
                                <span><?php echo timeAgo($item['created_at']); ?></span>
                                <span class="portfolio-amount"><?php echo formatCurrency($item['contract_amount']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Reviews Section -->
                <div class="content-section">
                    <h2 class="section-title">Client Reviews</h2>
                    <?php if (empty($reviews)): ?>
                        <div class="no-reviews">
                            <i class="fas fa-star"></i>
                            <h3>No reviews yet</h3>
                            <p>This freelancer is new or hasn't received reviews yet. Be the first to work with them!</p>
                        </div>
                    <?php else: ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <div class="reviewer-avatar">
                                            <?php echo strtoupper(substr($review['first_name'], 0, 1) . substr($review['last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="reviewer-details">
                                            <h4><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h4>
                                            <div class="job-title"><?php echo htmlspecialchars($review['job_title']); ?></div>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'star' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-text">
                                    <?php echo htmlspecialchars($review['review_text'] ?: 'Great work!'); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right-column">
                <!-- Stats Section -->
                <div class="content-section">
                    <h2 class="section-title">Stats</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $freelancer['total_jobs_completed']; ?></div>
                            <div class="stat-label">Jobs Completed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo formatCurrency($freelancer['total_earnings']); ?></div>
                            <div class="stat-label">Total Earned</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($avg_rating, 1); ?></div>
                            <div class="stat-label">Average Rating</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_reviews; ?></div>
                            <div class="stat-label">Total Reviews</div>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="content-section">
                    <h2 class="section-title">Contact Info</h2>
                    <div style="space-y: 1rem;">
                        <?php if ($freelancer['email']): ?>
                        <div class="meta-item" style="margin-bottom: 1rem;">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($freelancer['email']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($freelancer['phone']): ?>
                        <div class="meta-item" style="margin-bottom: 1rem;">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($freelancer['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($freelancer['portfolio_url']): ?>
                        <div class="meta-item" style="margin-bottom: 1rem;">
                            <i class="fas fa-globe"></i>
                            <a href="<?php echo htmlspecialchars($freelancer['portfolio_url']); ?>" target="_blank" style="color: #667eea;">
                                Portfolio Website
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
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

        // Animate content sections
        document.querySelectorAll('.content-section').forEach((section, index) => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(30px)';
            section.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(section);
        });
    </script>
</body>
</html>
