<?php
require_once 'db.php';

// Get search parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$budget_type = isset($_GET['budget_type']) ? sanitize($_GET['budget_type']) : '';
$experience = isset($_GET['experience']) ? sanitize($_GET['experience']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';

// Build query
$where_conditions = ["j.status = 'open'"];
$params = [];

if ($search) {
    $where_conditions[] = "(j.title LIKE ? OR j.description LIKE ? OR j.skills_required LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($category) {
    $where_conditions[] = "j.category_id = ?";
    $params[] = $category;
}

if ($budget_type) {
    $where_conditions[] = "j.budget_type = ?";
    $params[] = $budget_type;
}

if ($experience) {
    $where_conditions[] = "j.experience_required = ?";
    $params[] = $experience;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$order_by = "j.created_at DESC";
switch ($sort) {
    case 'budget_high':
        $order_by = "j.budget_max DESC";
        break;
    case 'budget_low':
        $order_by = "j.budget_min ASC";
        break;
    case 'proposals':
        $order_by = "j.proposals_count ASC";
        break;
}

// Get jobs
$stmt = $pdo->prepare("
    SELECT j.*, u.first_name, u.last_name, u.company_name, c.name as category_name 
    FROM jobs j 
    JOIN users u ON j.client_id = u.id 
    JOIN categories c ON j.category_id = c.id 
    WHERE $where_clause 
    ORDER BY $order_by
");
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Jobs - FreelanceHub</title>
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

        .filter-group select {
            padding: 0.6rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .results-count {
            color: #666;
            font-size: 1.1rem;
        }

        .sort-dropdown {
            padding: 0.6rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            background: white;
        }

        /* Job Cards */
        .jobs-grid {
            display: grid;
            gap: 2rem;
        }

        .job-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid #667eea;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .job-title {
            font-size: 1.4rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .job-title a {
            color: #333;
            text-decoration: none;
        }

        .job-title a:hover {
            color: #667eea;
        }

        .job-budget {
            font-weight: 600;
            color: #28a745;
            font-size: 1.2rem;
        }

        .job-meta {
            display: flex;
            gap: 1.5rem;
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
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .job-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
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

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .proposals-count {
            color: #666;
            font-size: 0.9rem;
        }

        .apply-btn {
            background: #28a745;
            color: white;
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s;
        }

        .apply-btn:hover {
            background: #218838;
        }

        .no-jobs {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .no-jobs i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .no-jobs h3 {
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

            .results-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .job-header {
                flex-direction: column;
                gap: 1rem;
            }

            .job-meta {
                flex-wrap: wrap;
                gap: 1rem;
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
            <h1 class="page-title">Find Jobs</h1>
            <p class="page-subtitle">Discover amazing projects and start earning today</p>
        </div>
    </div>

    <div class="container">
        <div class="search-filters">
            <form method="GET" action="jobs.php">
                <div class="search-bar">
                    <input type="text" name="search" class="search-input" 
                           placeholder="Search for jobs, skills, or keywords..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>

                <div class="filters-row">
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select name="category" id="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="budget_type">Budget Type</label>
                        <select name="budget_type" id="budget_type">
                            <option value="">All Types</option>
                            <option value="fixed" <?php echo $budget_type == 'fixed' ? 'selected' : ''; ?>>Fixed Price</option>
                            <option value="hourly" <?php echo $budget_type == 'hourly' ? 'selected' : ''; ?>>Hourly Rate</option>
                        </select>
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
                        <label for="sort">Sort By</label>
                        <select name="sort" id="sort" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="budget_high" <?php echo $sort == 'budget_high' ? 'selected' : ''; ?>>Highest Budget</option>
                            <option value="budget_low" <?php echo $sort == 'budget_low' ? 'selected' : ''; ?>>Lowest Budget</option>
                            <option value="proposals" <?php echo $sort == 'proposals' ? 'selected' : ''; ?>>Fewest Proposals</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="results-header">
            <div class="results-count">
                <?php echo count($jobs); ?> jobs found
            </div>
        </div>

        <div class="jobs-grid">
            <?php if (empty($jobs)): ?>
                <div class="no-jobs">
                    <i class="fas fa-search"></i>
                    <h3>No jobs found</h3>
                    <p>Try adjusting your search criteria or check back later for new opportunities.</p>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <div class="job-header">
                        <div>
                            <h3 class="job-title">
                                <a href="job-details.php?id=<?php echo $job['id']; ?>">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            </h3>
                            <div class="job-meta">
                                <span>
                                    <i class="fas fa-building"></i> 
                                    <?php echo htmlspecialchars($job['company_name'] ?: $job['first_name'] . ' ' . $job['last_name']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-tag"></i> 
                                    <?php echo htmlspecialchars($job['category_name']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-clock"></i> 
                                    <?php echo timeAgo($job['created_at']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-signal"></i> 
                                    <?php echo ucfirst($job['experience_required']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="job-budget">
                            <?php if ($job['budget_type'] == 'fixed'): ?>
                                <?php echo formatCurrency($job['budget_min']) . ' - ' . formatCurrency($job['budget_max']); ?>
                            <?php else: ?>
                                <?php echo formatCurrency($job['hourly_rate']) . '/hr'; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p class="job-description">
                        <?php echo htmlspecialchars(substr($job['description'], 0, 200)) . '...'; ?>
                    </p>

                    <?php if ($job['skills_required']): ?>
                    <div class="job-skills">
                        <?php 
                        $skills = explode(',', $job['skills_required']);
                        foreach (array_slice($skills, 0, 5) as $skill): 
                        ?>
                        <span class="skill-tag"><?php echo trim(htmlspecialchars($skill)); ?></span>
                        <?php endforeach; ?>
                        <?php if (count($skills) > 5): ?>
                        <span class="skill-tag">+<?php echo count($skills) - 5; ?> more</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="job-footer">
                        <div class="proposals-count">
                            <i class="fas fa-users"></i> 
                            <?php echo $job['proposals_count']; ?> proposals
                        </div>
                        <?php if (isLoggedIn() && getUserType() == 'freelancer'): ?>
                            <a href="apply-job.php?id=<?php echo $job['id']; ?>" class="apply-btn">
                                <i class="fas fa-paper-plane"></i> Apply Now
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="apply-btn">
                                <i class="fas fa-sign-in-alt"></i> Login to Apply
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('select[name="category"], select[name="budget_type"], select[name="experience"]').forEach(select => {
            select.addEventListener('change', function() {
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
