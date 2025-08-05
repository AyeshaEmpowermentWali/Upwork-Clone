<?php
require_once 'db.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle sending new message
if ($_POST && isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : null;
    
    if (empty($receiver_id) || empty($message)) {
        $error = 'Please select a recipient and enter a message.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, job_id, subject, message) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$user_id, $receiver_id, $job_id, $subject, $message])) {
            $success = 'Message sent successfully!';
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    }
}

// Handle marking message as read
if (isset($_GET['read']) && $_GET['read']) {
    $message_id = (int)$_GET['read'];
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$message_id, $user_id]);
}

// Get conversation partner if specified
$conversation_with = isset($_GET['with']) ? (int)$_GET['with'] : 0;

// Get all conversations (unique users)
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as user_id,
        u.first_name, u.last_name, u.company_name, u.user_type,
        (SELECT message FROM messages m2 
         WHERE (m2.sender_id = ? AND m2.receiver_id = user_id) 
            OR (m2.receiver_id = ? AND m2.sender_id = user_id)
         ORDER BY m2.created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages m2 
         WHERE (m2.sender_id = ? AND m2.receiver_id = user_id) 
            OR (m2.receiver_id = ? AND m2.sender_id = user_id)
         ORDER BY m2.created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages m2 
         WHERE m2.sender_id = user_id AND m2.receiver_id = ? AND m2.is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON u.id = CASE 
        WHEN m.sender_id = ? THEN m.receiver_id 
        ELSE m.sender_id 
    END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY last_message_time DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

// Get messages for selected conversation
$messages = [];
if ($conversation_with) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.first_name, u.last_name, j.title as job_title
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        LEFT JOIN jobs j ON m.job_id = j.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user_id, $conversation_with, $conversation_with, $user_id]);
    $messages = $stmt->fetchAll();
    
    // Mark messages as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([$conversation_with, $user_id]);
}

// Get conversation partner info
$partner_info = null;
if ($conversation_with) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$conversation_with]);
    $partner_info = $stmt->fetch();
}

// Get potential message recipients (users who have interacted with current user)
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.company_name, u.user_type
    FROM users u
    WHERE u.id != ? AND u.user_type != ?
    ORDER BY u.first_name, u.last_name
    LIMIT 20
");
$stmt->execute([$user_id, getUserType()]);
$potential_recipients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - FreelanceHub</title>
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

        /* Messages Layout */
        .messages-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            height: 600px;
        }

        /* Conversations Sidebar */
        .conversations-sidebar {
            border-right: 1px solid #eee;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }

        .sidebar-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
        }

        .new-message-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.8rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .new-message-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }

        .conversation-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.3s;
            position: relative;
        }

        .conversation-item:hover {
            background: #f8f9fa;
        }

        .conversation-item.active {
            background: #e3f2fd;
            border-right: 3px solid #667eea;
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .conversation-name {
            font-weight: 600;
            color: #333;
        }

        .conversation-time {
            font-size: 0.8rem;
            color: #999;
        }

        .conversation-preview {
            color: #666;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .unread-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Chat Area */
        .chat-area {
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }

        .chat-partner-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .partner-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .partner-details h3 {
            color: #333;
            margin-bottom: 0.2rem;
        }

        .partner-type {
            color: #666;
            font-size: 0.9rem;
        }

        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background: #fafafa;
        }

        .message-item {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
        }

        .message-item.own {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .message-item.own .message-avatar {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .message-content {
            max-width: 70%;
        }

        .message-bubble {
            background: white;
            padding: 0.8rem 1rem;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }

        .message-item.own .message-bubble {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .message-text {
            line-height: 1.5;
        }

        .message-meta {
            font-size: 0.7rem;
            color: #999;
            margin-top: 0.3rem;
            text-align: right;
        }

        .message-item.own .message-meta {
            color: rgba(255,255,255,0.8);
            text-align: left;
        }

        .job-reference {
            background: rgba(255,255,255,0.1);
            padding: 0.5rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
        }

        /* Message Input */
        .message-input-area {
            padding: 1rem;
            border-top: 1px solid #eee;
            background: white;
        }

        .message-form {
            display: flex;
            gap: 0.8rem;
            align-items: flex-end;
        }

        .message-input {
            flex: 1;
            padding: 0.8rem;
            border: 2px solid #e1e5e9;
            border-radius: 20px;
            resize: none;
            max-height: 100px;
            font-family: inherit;
        }

        .message-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .send-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .send-btn:hover {
            transform: scale(1.1);
        }

        /* Empty States */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
            text-align: center;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        /* New Message Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            color: #333;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group select:focus,
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .messages-container {
                grid-template-columns: 1fr;
                height: auto;
                min-height: 500px;
            }

            .conversations-sidebar {
                display: none;
            }

            .conversations-sidebar.show {
                display: flex;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 999;
                background: white;
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php" class="btn btn-outline">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title">Messages</h1>
            <p class="page-subtitle">Communicate with clients and freelancers</p>
        </div>
    </div>

    <div class="container">
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

        <div class="messages-container">
            <!-- Conversations Sidebar -->
            <div class="conversations-sidebar">
                <div class="sidebar-header">
                    <h3 class="sidebar-title">Conversations</h3>
                    <button class="new-message-btn" onclick="openNewMessageModal()">
                        <i class="fas fa-plus"></i> New Message
                    </button>
                </div>
                
                <div class="conversations-list">
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <h3>No conversations yet</h3>
                            <p>Start a conversation by sending a message</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                        <div class="conversation-item <?php echo $conversation_with == $conv['user_id'] ? 'active' : ''; ?>" 
                             onclick="window.location.href='messages.php?with=<?php echo $conv['user_id']; ?>'">
                            <div class="conversation-header">
                                <div class="conversation-name">
                                    <?php echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']); ?>
                                    <?php if ($conv['company_name']): ?>
                                        <small>(<?php echo htmlspecialchars($conv['company_name']); ?>)</small>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-time">
                                    <?php echo timeAgo($conv['last_message_time']); ?>
                                </div>
                            </div>
                            <div class="conversation-preview">
                                <?php echo htmlspecialchars(substr($conv['last_message'], 0, 50)) . '...'; ?>
                            </div>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <div class="unread-badge"><?php echo $conv['unread_count']; ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="chat-area">
                <?php if ($conversation_with && $partner_info): ?>
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="chat-partner-info">
                            <div class="partner-avatar">
                                <?php echo strtoupper(substr($partner_info['first_name'], 0, 1) . substr($partner_info['last_name'], 0, 1)); ?>
                            </div>
                            <div class="partner-details">
                                <h3><?php echo htmlspecialchars($partner_info['first_name'] . ' ' . $partner_info['last_name']); ?></h3>
                                <div class="partner-type">
                                    <?php echo ucfirst($partner_info['user_type']); ?>
                                    <?php if ($partner_info['company_name']): ?>
                                        • <?php echo htmlspecialchars($partner_info['company_name']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div class="messages-area">
                        <?php if (empty($messages)): ?>
                            <div class="empty-state">
                                <i class="fas fa-comment"></i>
                                <h3>No messages yet</h3>
                                <p>Start the conversation by sending a message below</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                            <div class="message-item <?php echo $msg['sender_id'] == $user_id ? 'own' : ''; ?>">
                                <div class="message-avatar">
                                    <?php echo strtoupper(substr($msg['first_name'], 0, 1) . substr($msg['last_name'], 0, 1)); ?>
                                </div>
                                <div class="message-content">
                                    <div class="message-bubble">
                                        <?php if ($msg['job_title']): ?>
                                            <div class="job-reference">
                                                <i class="fas fa-briefcase"></i> Re: <?php echo htmlspecialchars($msg['job_title']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($msg['subject']): ?>
                                            <div style="font-weight: 600; margin-bottom: 0.5rem;">
                                                <?php echo htmlspecialchars($msg['subject']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="message-text">
                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        </div>
                                    </div>
                                    <div class="message-meta">
                                        <?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Message Input -->
                    <div class="message-input-area">
                        <form class="message-form" method="POST">
                            <input type="hidden" name="receiver_id" value="<?php echo $conversation_with; ?>">
                            <input type="hidden" name="send_message" value="1">
                            <textarea class="message-input" name="message" placeholder="Type your message..." required></textarea>
                            <button type="submit" class="send-btn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope"></i>
                        <h3>Select a conversation</h3>
                        <p>Choose a conversation from the sidebar to start messaging</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- New Message Modal -->
    <div class="modal" id="newMessageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">New Message</h3>
                <button class="close-btn" onclick="closeNewMessageModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="send_message" value="1">
                <div class="form-group">
                    <label for="recipient">To:</label>
                    <select name="receiver_id" id="recipient" required>
                        <option value="">Select recipient...</option>
                        <?php foreach ($potential_recipients as $recipient): ?>
                            <option value="<?php echo $recipient['id']; ?>">
                                <?php echo htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']); ?>
                                <?php if ($recipient['company_name']): ?>
                                    (<?php echo htmlspecialchars($recipient['company_name']); ?>)
                                <?php endif; ?>
                                - <?php echo ucfirst($recipient['user_type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" name="subject" id="subject" placeholder="Message subject (optional)">
                </div>
                <div class="form-group">
                    <label for="new_message">Message:</label>
                    <textarea name="message" id="new_message" rows="5" placeholder="Type your message..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </div>

    <script>
        function openNewMessageModal() {
            document.getElementById('newMessageModal').classList.add('show');
        }

        function closeNewMessageModal() {
            document.getElementById('newMessageModal').classList.remove('show');
        }

        // Close modal when clicking outside
        document.getElementById('newMessageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeNewMessageModal();
            }
        });

        // Auto-scroll to bottom of messages
        const messagesArea = document.querySelector('.messages-area');
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Auto-resize message input
        const messageInput = document.querySelector('.message-input');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });

            // Send message on Ctrl+Enter
            messageInput.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    this.form.submit();
                }
            });
        }

        // Refresh messages every 30 seconds
        setInterval(function() {
            if (window.location.search.includes('with=')) {
                // Only refresh if we're in a conversation
                window.location.reload();
            }
        }, 30000);

        // Mark messages as read when conversation is opened
        <?php if ($conversation_with): ?>
        // Auto-mark as read after 2 seconds
        setTimeout(function() {
            fetch('messages.php?read=1&with=<?php echo $conversation_with; ?>')
                .catch(err => console.log('Error marking as read:', err));
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>
