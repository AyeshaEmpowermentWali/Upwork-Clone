<?php
require_once 'db.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get user stats
if ($user_type == 'freelancer') {
    // Freelancer stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_proposals FROM proposals WHERE freelancer_id = ?");
    $stmt->execute([$user_id]);
    $total_proposals = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_contracts FROM contracts WHERE freelancer_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    $active_contracts = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(contract_amount), 0) as total_earnings FROM contracts WHERE freelancer_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $total_earnings = $stmt->fetchColumn();
    
    // Recent job applications
    $stmt = $pdo->prepare("
        SELECT p.*, j.title, j.budget_min, j.budget_max, j.budget_type 
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        WHERE p.freelancer_id = ? 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_proposals = $stmt->fetchAll();
    
} else {
    // Client stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_jobs FROM jobs WHERE client_id = ?");
    $stmt->execute([$user_id]);
    $total_jobs = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_jobs FROM jobs WHERE client_id = ? AND status = 'open'");
    $stmt->execute([$user_id]);
    $active_jobs = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_proposals FROM proposals p JOIN jobs j ON p.job_id = j.id WHERE j.client_id = ?");
    $stmt->execute([$user_id]);
    $total_proposals = $stmt->fetchColumn();
    
    // Recent jobs
    $stmt = $pdo->prepare("
        SELECT j.*, COUNT(p.id) as proposal_count 
        FROM jobs j 
        LEFT JOIN proposals p ON j.id = p.job_id 
        WHERE j.client_id = ? 
        GROUP BY j.id 
        ORDER BY j.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_jobs = $stmt->fetchAll();
}

// Recent messages
$stmt = $pdo->prepare("
    SELECT m.*, u.first_name, u.last_name 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.receiver_id = ? 
    ORDER BY m.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FreelanceHub</title>
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

        .user-menu {
            position: relative;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
        }

        /* Dashboard Header */
        .dashboard-header {
            background: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            color: #666;
            font-size: 1.1rem;
        }

        .quick-actions {
            display: flex;
            gap: 1rem;
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
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .main-content {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .sidebar {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }

        /* Table Styles */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-accepted {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-open {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        /* Message List */
        .message-list {
            list-style: none;
        }

        .message-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }

        .message-item:hover {
            background: #f8f9fa;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-sender {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .message-preview {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .message-time {
            color: #999;
            font-size: 0.8rem;
        }

        .unread {
            background: #f0f8ff;
            border-left: 4px solid #667eea;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .welcome-section {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .quick-actions {
                flex-direction: column;
                width: 100%;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .table {
                font-size: 0.9rem;
            }

            .table th,
            .table td {
                padding: 0.5rem;
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <?php if ($user_type == 'freelancer'): ?>
                    <li><a href="jobs.php">Find Jobs</a></li>
                    <li><a href="my-proposals.php">My Proposals</a></li>
                <?php else: ?>
                    <li><a href="post-job.php">Post Job</a></li>
                    <li><a href="my-jobs.php">My Jobs</a></li>
                <?php endif; ?>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li class="user-menu">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?>
                    </div>
                </li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="dashboard-header">
        <div class="container">
            <div class="welcome-section">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
                    <p><?php echo $user_type == 'freelancer' ? 'Ready to find your next project?' : 'Ready to find great talent?'; ?></p>
                </div>
                <div class="quick-actions">
                    <?php if ($user_type == 'freelancer'): ?>
                        <a href="jobs.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Find Jobs
                        </a>
                        <a href="profile.php" class="btn btn-outline">
                            <i class="fas fa-user"></i> Update Profile
                        </a>
                    <?php else: ?>
                        <a href="post-job.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Post a Job
                        </a>
                        <a href="freelancers.php" class="btn btn-outline">
                            <i class="fas fa-users"></i> Find Freelancers
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Stats Section -->
        <div class="stats-grid">
            <?php if ($user_type == 'freelancer'): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_proposals; ?></div>
                    <div class="stat-label">Total Proposals</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-number"><?php echo $active_contracts; ?></div>
                    <div class="stat-label">Active Contracts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-number"><?php echo formatCurrency($total_earnings); ?></div>
                    <div class="stat-label">Total Earnings</div>
                </div>
            <?php else: ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_jobs; ?></div>
                    <div class="stat-label">Total Jobs Posted</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo $active_jobs; ?></div>
                    <div class="stat-label">Active Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_proposals; ?></div>
                    <div class="stat-label">Total Proposals Received</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="content-grid">
            <div class="main-content">
                <?php if ($user_type == 'freelancer'): ?>
                    <h2 class="section-title">Recent Proposals</h2>
                    <?php if (empty($recent_proposals)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <h3>No proposals yet</h3>
                            <p>Start applying to jobs to see your proposals here</p>
                            <a href="jobs.php" class="btn btn-primary">Find Jobs</a>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Bid Amount</th>
                                    <th>Status</th>
                                    <th>Applied</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_proposals as $proposal): ?>
                                <tr>
                                    <td>
                                        <a href="job-details.php?id=<?php echo $proposal['job_id']; ?>">
                                            <?php echo htmlspecialchars($proposal['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo formatCurrency($proposal['bid_amount']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $proposal['status']; ?>">
                                            <?php echo ucfirst($proposal['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo timeAgo($proposal['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php else: ?>
                    <h2 class="section-title">Recent Jobs</h2>
                    <?php if (empty($recent_jobs)): ?>
                        <div class="empty-state">
                            <i class="fas fa-briefcase"></i>
                            <h3>No jobs posted yet</h3>
                            <p>Post your first job to start finding great talent</p>
                            <a href="post-job.php" class="btn btn-primary">Post a Job</a>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Budget</th>
                                    <th>Proposals</th>
                                    <th>Status</th>
                                    <th>Posted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_jobs as $job): ?>
                                <tr>
                                    <td>
                                        <a href="job-details.php?id=<?php echo $job['id']; ?>">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($job['budget_type'] == 'fixed'): ?>
                                            <?php echo formatCurrency($job['budget_min']) . ' - ' . formatCurrency($job['budget_max']); ?>
                                        <?php else: ?>
                                            <?php echo formatCurrency($job['hourly_rate']) . '/hr'; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $job['proposal_count']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $job['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo timeAgo($job['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="sidebar">
                <h3 class="section-title">Recent Messages</h3>
                <?php if (empty($recent_messages)): ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope"></i>
                        <p>No messages yet</p>
                    </div>
                <?php else: ?>
                    <ul class="message-list">
                        <?php foreach ($recent_messages as $message): ?>
                        <li class="message-item <?php echo !$message['is_read'] ? 'unread' : ''; ?>">
                            <div class="message-sender">
                                <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?>
                            </div>
                            <div class="message-preview">
                                <?php echo htmlspecialchars(substr($message['message'], 0, 50)) . '...'; ?>
                            </div>
                            <div class="message-time">
                                <?php echo timeAgo($message['created_at']); ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="messages.php" class="btn btn-outline">View All Messages</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 300000);

        // Add click handlers for quick navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Stat cards click handlers
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.style.cursor = 'pointer';
                card.addEventListener('click', function() {
                    const label = this.querySelector('.stat-label').textContent;
                    if (label.includes('Proposals')) {
                        window.location.href = '<?php echo $user_type == "freelancer" ? "my-proposals.php" : "my-jobs.php"; ?>';
                    } else if (label.includes('Jobs')) {
                        window.location.href = '<?php echo $user_type == "freelancer" ? "jobs.php" : "my-jobs.php"; ?>';
                    } else if (label.includes('Contracts')) {
                        window.location.href = 'contracts.php';
                    }
                });
            });
        });
    </script>
</body>
</html>

I've created a comprehensive Upwork clone with all the features you requested. Here's what I've built:
