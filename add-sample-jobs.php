<?php
require_once 'db.php';

echo "<h1>Adding Sample Jobs</h1>";

try {
    // First, let's check if we have users and categories
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'client'");
    $stmt->execute();
    $client_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories");
    $stmt->execute();
    $category_count = $stmt->fetchColumn();
    
    echo "<p>Clients in database: $client_count</p>";
    echo "<p>Categories in database: $category_count</p>";
    
    // Add sample client if none exists
    if ($client_count == 0) {
        echo "<p>Adding sample clients...</p>";
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, user_type, first_name, last_name, company_name, bio) 
            VALUES (?, ?, ?, 'client', ?, ?, ?, ?)
        ");
        
        $clients = [
            ['techcorp', 'client1@example.com', password_hash('password123', PASSWORD_DEFAULT), 'John', 'Smith', 'TechCorp Solutions', 'Leading technology company looking for talented developers'],
            ['designstudio', 'client2@example.com', password_hash('password123', PASSWORD_DEFAULT), 'Sarah', 'Johnson', 'Creative Design Studio', 'Creative agency specializing in branding and digital design'],
            ['marketingpro', 'client3@example.com', password_hash('password123', PASSWORD_DEFAULT), 'Mike', 'Wilson', 'Marketing Pro Agency', 'Digital marketing agency helping businesses grow online'],
        ];
        
        foreach ($clients as $client) {
            $stmt->execute($client);
        }
        echo "<p>✅ Sample clients added!</p>";
    }
    
    // Add categories if none exist
    if ($category_count == 0) {
        echo "<p>Adding sample categories...</p>";
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)");
        
        $categories = [
            ['Web Development', 'Frontend and backend web development services', 'code'],
            ['Mobile Development', 'iOS and Android app development', 'mobile-alt'],
            ['Graphic Design', 'Logo design, branding, and visual content', 'palette'],
            ['Digital Marketing', 'SEO, social media, and online marketing', 'chart-line'],
            ['Writing & Translation', 'Content writing, copywriting, and translation services', 'pen'],
            ['Video & Animation', 'Video editing, motion graphics, and animation', 'video'],
            ['Data Entry', 'Data processing and administrative tasks', 'database'],
            ['Programming & Tech', 'Software development and technical services', 'laptop-code'],
        ];
        
        foreach ($categories as $cat) {
            $stmt->execute($cat);
        }
        echo "<p>✅ Sample categories added!</p>";
    }
    
    // Get client and category IDs
    $stmt = $pdo->prepare("SELECT id FROM users WHERE user_type = 'client' LIMIT 3");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $pdo->prepare("SELECT id FROM categories LIMIT 8");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check if jobs already exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs");
    $stmt->execute();
    $job_count = $stmt->fetchColumn();
    
    if ($job_count == 0) {
        echo "<p>Adding sample jobs...</p>";
        
        $stmt = $pdo->prepare("
            INSERT INTO jobs (client_id, category_id, title, description, budget_type, budget_min, budget_max, hourly_rate, experience_required, skills_required, deadline, featured) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $sample_jobs = [
            [
                $clients[0], $categories[0], 
                'E-commerce Website Development',
                'We need a modern e-commerce website built with PHP and MySQL. The site should have user registration, product catalog, shopping cart, payment integration, and admin panel. Must be responsive and SEO-friendly.',
                'fixed', 1500.00, 3000.00, null, 'intermediate',
                'PHP, MySQL, JavaScript, HTML, CSS, Bootstrap, Payment Integration',
                '2024-03-15', 1
            ],
            [
                $clients[1], $categories[2],
                'Logo Design for Tech Startup',
                'Looking for a creative and modern logo design for our new tech startup. We need a professional logo that represents innovation and technology. Should include multiple formats and color variations.',
                'fixed', 200.00, 500.00, null, 'beginner',
                'Logo Design, Photoshop, Illustrator, Branding, Creative Design',
                '2024-02-28', 1
            ],
            [
                $clients[0], $categories[1],
                'Mobile App Development - iOS & Android',
                'Need a mobile app for both iOS and Android platforms. The app is for food delivery service with features like user registration, restaurant listings, order placement, payment integration, and real-time tracking.',
                'fixed', 3000.00, 6000.00, null, 'expert',
                'React Native, Flutter, iOS, Android, API Integration, Firebase',
                '2024-04-20', 1
            ],
            [
                $clients[2], $categories[3],
                'SEO Content Writing for Blog',
                'We need 20 high-quality, SEO-optimized blog posts about digital marketing trends. Each article should be 1000-1500 words, well-researched, and engaging. Experience in digital marketing content required.',
                'fixed', 400.00, 800.00, null, 'intermediate',
                'Content Writing, SEO, Blog Writing, Digital Marketing, Research',
                '2024-03-10', 0
            ],
            [
                $clients[1], $categories[0],
                'WordPress Website Customization',
                'Need to customize an existing WordPress website. Tasks include theme customization, plugin integration, performance optimization, and adding new features. Must have experience with WordPress development.',
                'hourly', null, null, 25.00, 'intermediate',
                'WordPress, PHP, CSS, JavaScript, Plugin Development',
                '2024-03-05', 0
            ],
            [
                $clients[0], $categories[5],
                'Product Demo Video Creation',
                'Create a professional 2-3 minute product demo video for our software. Need video editing, motion graphics, voiceover, and background music. Should be engaging and explain key features clearly.',
                'fixed', 500.00, 1200.00, null, 'intermediate',
                'Video Editing, Motion Graphics, After Effects, Premiere Pro, Voiceover',
                '2024-03-12', 0
            ],
            [
                $clients[2], $categories[3],
                'Social Media Marketing Campaign',
                'Looking for a social media expert to manage our Facebook and Instagram accounts. Need content creation, posting schedule, engagement management, and monthly analytics reports.',
                'hourly', null, null, 15.00, 'beginner',
                'Social Media Marketing, Facebook, Instagram, Content Creation, Analytics',
                '2024-04-01', 0
            ],
            [
                $clients[1], $categories[7],
                'Python Data Analysis Script',
                'Need a Python script to analyze sales data from CSV files. Should generate charts, statistics, and automated reports. Must be well-documented and easy to use.',
                'fixed', 300.00, 600.00, null, 'intermediate',
                'Python, Data Analysis, Pandas, Matplotlib, CSV Processing',
                '2024-02-25', 0
            ],
            [
                $clients[0], $categories[6],
                'Data Entry for Product Catalog',
                'Need someone to enter 500+ products into our e-commerce database. Includes product names, descriptions, prices, categories, and images. Attention to detail required.',
                'fixed', 150.00, 300.00, null, 'beginner',
                'Data Entry, Excel, Attention to Detail, E-commerce',
                '2024-03-08', 0
            ],
            [
                $clients[2], $categories[0],
                'React.js Frontend Development',
                'Build a modern frontend for our web application using React.js. Need responsive design, API integration, state management, and clean code. UI/UX designs will be provided.',
                'hourly', null, null, 35.00, 'expert',
                'React.js, JavaScript, HTML, CSS, API Integration, Redux',
                '2024-03-20', 1
            ],
            [
                $clients[1], $categories[2],
                'Business Card Design',
                'Design professional business cards for our company. Need both front and back design, multiple color schemes, and print-ready files. Modern and clean design preferred.',
                'fixed', 50.00, 150.00, null, 'beginner',
                'Graphic Design, Business Cards, Print Design, Photoshop, Illustrator',
                '2024-02-20', 0
            ],
            [
                $clients[0], $categories[1],
                'Flutter Mobile App Bug Fixes',
                'Our Flutter app has several bugs that need fixing. Issues include login problems, UI glitches, and performance issues. Need experienced Flutter developer for quick fixes.',
                'hourly', null, null, 30.00, 'intermediate',
                'Flutter, Dart, Mobile Development, Bug Fixing, Performance Optimization',
                '2024-02-28', 0
            ]
        ];
        
        foreach ($sample_jobs as $job) {
            $stmt->execute($job);
        }
        
        echo "<p>✅ " . count($sample_jobs) . " sample jobs added!</p>";
    } else {
        echo "<p>Jobs already exist in database: $job_count jobs found</p>";
    }
    
    // Show final counts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE status = 'open'");
    $stmt->execute();
    $open_jobs = $stmt->fetchColumn();
    
    echo "<h2>Database Summary:</h2>";
    echo "<p>✅ Total open jobs: <strong>$open_jobs</strong></p>";
    echo "<p>✅ Total categories: <strong>$category_count</strong></p>";
    echo "<p>✅ Total clients: <strong>" . count($clients) . "</strong></p>";
    
    echo "<h2>Test Searches:</h2>";
    echo "<ul>";
    echo "<li><a href='jobs.php?search=website'>Search: 'website'</a></li>";
    echo "<li><a href='jobs.php?search=PHP'>Search: 'PHP'</a></li>";
    echo "<li><a href='jobs.php?search=design'>Search: 'design'</a></li>";
    echo "<li><a href='jobs.php?search=mobile'>Search: 'mobile'</a></li>";
    echo "<li><a href='jobs.php?search=React'>Search: 'React'</a></li>";
    echo "</ul>";
    
    echo "<p><a href='jobs.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Go to Jobs Page</a></p>";
    echo "<p><a href='index.php'>→ Back to Home</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
h1, h2 { color: #333; }
p { margin: 10px 0; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
a { color: #667eea; }
</style>
