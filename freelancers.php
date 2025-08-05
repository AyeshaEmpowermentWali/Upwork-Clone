<?php
require_once 'db.php';

// Get search parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$skills = isset($_GET['skills']) ? sanitize($_GET['skills']) : '';
$experience = isset($_GET['experience']) ? sanitize($_GET['experience']) : '';
$hourly_min = isset($_GET['hourly_min']) ? (int)$_GET['hourly_min'] : 0;
$hourly_max = isset($_GET['hourly_max']) ? (int)$_GET['hourly_max'] : 1000;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'rating';

// Build query
$where_conditions = ["user_type = 'freelancer'"];
$params = [];

if ($search) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR bio LIKE ? OR skills LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($skills) {
    $where_conditions[] = "skills LIKE ?";
    $params[] = "%$skills%";
}

if ($experience) {
    $where_conditions[] = "experience_level = ?";
    $params[] = $experience;
}

if ($hourly_min > 0) {
    $where_conditions[] = "hourly_rate >= ?";
    $params[] = $hourly_min;
}

if ($hourly_max < 1000) {
    $where_conditions[] = "hourly_rate <= ?";
    $params[] = $hourly_max;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$order_by = "rating DESC, total_jobs_completed DESC";
switch ($sort) {
    case 'rate_low':
        $order_by = "hourly_rate ASC";
        break;
    case 'rate_high':
        $order_by = "hourly_rate DESC";
        break;
    case 'newest':
        $order_by = "created_at DESC";
        break;
}

// Get freelancers
$stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE $where_clause 
    ORDER BY $order_by
");
$stmt->execute($params);
$freelancers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Freelancers - FreelanceHub</title>
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

        .page-title {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #666;
            font-size: 1.1rem;
        }

        /* Search and Filters */
        .search-filters {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .search-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-input {
            flex: 1;
            padding: 0.8rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-btn {
            padding: 0.8rem 2rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .search-btn:hover {
            background: #5a6fd8;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.6rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .hourly-range {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .hourly-range input {
            width: 80px;
        }

        /* Freelancer Cards */
        .freelancers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .freelancer-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            text-align: center;
        }

        .freelancer-card:hover {
            transform: translateY(-5px);
        }

        .freelancer-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
        }

        .freelancer-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .freelancer-title {
            color: #666;
            margin-bottom: 1rem;
            font-size: 1rem;
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

        .rating-text {
            margin-left: 0.5rem;
            color: #666;
        }

        .freelancer-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .stat-item {
            padding: 0.5rem;
        }

        .stat-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        .freelancer-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .skill-tag {
            background: #e9ecef;
            color: #495057;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .hourly-rate {
            font-size: 1.3rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 1rem;
        }

        .view-profile-btn {
            background: #667eea;
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s;
            width: 100%;
        }

        .view-profile-btn:hover {
            background: #5a6fd8;
        }

        .no-freelancers {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
            grid-column: 1 / -1;
        }

        .no-freelancers i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .no-freelancers h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .search-bar {
                flex-direction: column;
            }

            .filters-row {
                grid-template-columns: 1fr;
            }

            .freelancers-grid {
                grid-template-columns: 1fr;
            }

            .freelancer-stats {
                grid-template-columns: repeat(3, 1fr);
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
            <h1 class="page-title">Find Freelancers</h1>
            <p class="page-subtitle">Discover talented professionals for your projects</p>
        </div>
    </div>

    <div class="container">
        <div class="search-filters">
            <form method="GET" action="freelancers.php">
                <div class="search-bar">
                    <input type="text" name="search" class="search-input" 
                           placeholder="Search freelancers by name, skills, or bio..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>

                <div class="filters-row">
                    <div class="filter-group">
                        <label for="skills">Skills</label>
                        <input type="text" name="skills" id="skills" 
                               placeholder="e.g. PHP, JavaScript, Design"
                               value="<?php echo htmlspecialchars($skills); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="experience">Experience Level</label>
                        <select name="experience" id="experience">
                            <option value="">All Levels</option>
                            <option value="beginner" <?php echo $experience == 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="intermediate" <?php echo $experience == 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="expert" <?php echo $experience == 'expert' ? 'selected' : ''; ?>>Expert</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Hourly Rate ($)</label>
                        <div class="hourly-range">
                            <input type="number" name="hourly_min" placeholder="Min" 
                                   value="<?php echo $hourly_min > 0 ? $hourly_min : ''; ?>">
                            <span>-</span>
                            <input type="number" name="hourly_max" placeholder="Max" 
                                   value="<?php echo $hourly_max < 1000 ? $hourly_max : ''; ?>">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label for="sort">Sort By</label>
                        <select name="sort" id="sort" onchange="this.form.submit()">
                            <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                            <option value="rate_low" <?php echo $sort == 'rate_low' ? 'selected' : ''; ?>>Lowest Rate</option>
                            <option value="rate_high" <?php echo $sort == 'rate_high' ? 'selected' : ''; ?>>Highest Rate</option>
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="freelancers-grid">
            <?php if (empty($freelancers)): ?>
                <div class="no-freelancers">
                    <i class="fas fa-users"></i>
                    <h3>No freelancers found</h3>
                    <p>Try adjusting your search criteria to find more freelancers.</p>
                </div>
            <?php else: ?>
                <?php foreach ($freelancers as $freelancer): ?>
                <div class="freelancer-card">
                    <div class="freelancer-avatar">
                        <?php echo strtoupper(substr($freelancer['first_name'], 0, 1) . substr($freelancer['last_name'], 0, 1)); ?>
                    </div>
                    
                    <div class="freelancer-name">
                        <?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name']); ?>
                    </div>
                    
                    <div class="freelancer-title">
                        <?php echo htmlspecialchars(substr($freelancer['bio'] ?: 'Professional Freelancer', 0, 60)) . '...'; ?>
                    </div>
                    
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $freelancer['rating'] ? 'star' : ''; ?>"></i>
                        <?php endfor; ?>
                        <span class="rating-text">(<?php echo number_format($freelancer['rating'], 1); ?>)</span>
                    </div>
                    
                    <div class="freelancer-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $freelancer['total_jobs_completed']; ?></div>
                            <div class="stat-label">Jobs Completed</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo ucfirst($freelancer['experience_level']); ?></div>
                            <div class="stat-label">Experience</div>
                        </div>
                    </div>
                    
                    <?php if ($freelancer['skills']): ?>
                    <div class="freelancer-skills">
                        <?php 
                        $skills = explode(',', $freelancer['skills']);
                        foreach (array_slice($skills, 0, 4) as $skill): 
                        ?>
                        <span class="skill-tag"><?php echo trim(htmlspecialchars($skill)); ?></span>
                        <?php endforeach; ?>
                        <?php if (count($skills) > 4): ?>
                        <span class="skill-tag">+<?php echo count($skills) - 4; ?> more</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="hourly-rate">
                        <?php echo formatCurrency($freelancer['hourly_rate']); ?>/hr
                    </div>
                    
                    <a href="freelancer-profile.php?id=<?php echo $freelancer['id']; ?>" class="view-profile-btn">
                        <i class="fas fa-eye"></i> View Profile
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('select, input[name="hourly_min"], input[name="hourly_max"]').forEach(element => {
            element.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Search on Enter key
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>
