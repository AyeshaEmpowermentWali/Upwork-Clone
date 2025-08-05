<?php
require_once 'db.php';

// Get all categories with job counts
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(j.id) as job_count 
    FROM categories c 
    LEFT JOIN jobs j ON c.id = j.category_id AND j.status = 'open'
    GROUP BY c.id 
    ORDER BY c.name
");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - FreelanceHub</title>
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
            padding: 3rem 0;
            margin-bottom: 3rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .page-title {
            font-size: 3rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .page-subtitle {
            color: #666;
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .category-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        .category-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
            transition: transform 0.3s;
        }

        .category-card:hover .category-icon {
            transform: scale(1.1);
        }

        .category-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }

        .category-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .category-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        .explore-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }

        .explore-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Popular Categories Section */
        .popular-section {
            background: white;
            padding: 4rem 0;
            margin: 4rem 0;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 3rem;
        }

        .popular-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .popular-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .popular-card:hover {
            background: #667eea;
            color: white;
            transform: translateY(-5px);
        }

        .popular-card:hover .popular-icon {
            color: white;
        }

        .popular-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 1rem;
            transition: color 0.3s;
        }

        .popular-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .popular-jobs {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
            border-radius: 20px;
            margin: 4rem 0;
        }

        .cta-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cta-subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-btn {
            padding: 1rem 2rem;
            border: 2px solid white;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .cta-btn.primary {
            background: white;
            color: #667eea;
        }

        .cta-btn.primary:hover {
            background: transparent;
            color: white;
        }

        .cta-btn.secondary:hover {
            background: white;
            color: #667eea;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .page-title {
                font-size: 2rem;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }

            .category-stats {
                gap: 1rem;
            }

            .popular-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .cta-btn {
                width: 200px;
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

    <div class="page-header">
        <div class="container">
            <h1 class="page-title">Browse Categories</h1>
            <p class="page-subtitle">Explore different categories and find the perfect match for your skills or project needs</p>
        </div>
    </div>

    <div class="container">
        <div class="categories-grid">
            <?php if (empty($categories)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; color: #666;">
                    <i class="fas fa-folder-open" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <h3>No categories found</h3>
                    <p>Categories will appear here once they are added to the database.</p>
                </div>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                <div class="category-card" onclick="window.location.href='jobs.php?category=<?php echo $category['id']; ?>'">
                    <div class="category-icon">
                        <i class="fas fa-<?php echo $category['icon']; ?>"></i>
                    </div>
                    <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                    <div class="category-description"><?php echo htmlspecialchars($category['description']); ?></div>
                    <div class="category-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $category['job_count']; ?></div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                </div>
                <a href="jobs.php?category=<?php echo $category['id']; ?>" class="explore-btn">
                    <i class="fas fa-arrow-right"></i> Explore Jobs
                </a>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($categories)): ?>
    <div class="container">
        <div class="popular-section">
            <h2 class="section-title">Most Popular Categories</h2>
            <div class="popular-grid">
                <?php 
                $popular_categories = array_slice($categories, 0, 6);
                foreach ($popular_categories as $category): 
                ?>
                <div class="popular-card" onclick="window.location.href='jobs.php?category=<?php echo $category['id']; ?>'">
                    <div class="popular-icon">
                        <i class="fas fa-<?php echo $category['icon']; ?>"></i>
                    </div>
                    <div class="popular-name"><?php echo htmlspecialchars($category['name']); ?></div>
                    <div class="popular-jobs"><?php echo $category['job_count']; ?> jobs available</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="container">
        <div class="cta-section">
            <h2 class="cta-title">Ready to Get Started?</h2>
            <p class="cta-subtitle">Join thousands of freelancers and clients who trust FreelanceHub</p>
            <div class="cta-buttons">
                <?php if (isLoggedIn()): ?>
                    <?php if (getUserType() == 'freelancer'): ?>
                        <a href="jobs.php" class="cta-btn primary">
                            <i class="fas fa-search"></i> Find Jobs
                        </a>
                        <a href="profile.php" class="cta-btn secondary">
                            <i class="fas fa-user"></i> Update Profile
                        </a>
                    <?php else: ?>
                        <a href="post-job.php" class="cta-btn primary">
                            <i class="fas fa-plus"></i> Post a Job
                        </a>
                        <a href="freelancers.php" class="cta-btn secondary">
                            <i class="fas fa-users"></i> Find Freelancers
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="register.php" class="cta-btn primary">
                        <i class="fas fa-user-plus"></i> Sign Up Free
                    </a>
                    <a href="login.php" class="cta-btn secondary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add smooth scrolling and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate category cards on scroll
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

            // Initially hide cards and observe them
            document.querySelectorAll('.category-card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });
        });
    </script>
</body>
</html>
