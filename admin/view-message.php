<?php
require_once '../config.php';
require_once '../includes/mail_functions.php';
require_once 'includes/functions.php';

requireLogin();

// Get message ID
$messageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($messageId === 0) {
    header('Location: messages.php');
    exit;
}

// Get message
$stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
$stmt->execute([$messageId]);
$message = $stmt->fetch();

if (!$message) {
    setMessage('Message not found.', 'error');
    header('Location: messages.php');
    exit;
}

// Mark as read automatically when viewing
if (!$message['is_read']) {
    $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
    $stmt->execute([$messageId]);
    $message['is_read'] = 1;
}

// Handle mark as unread
if (isset($_GET['mark_unread'])) {
    $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 0 WHERE id = ?");
    $stmt->execute([$messageId]);
    setMessage('Message marked as unread.', 'success');
    header('Location: messages.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    setMessage('Message deleted successfully!', 'success');
    header('Location: messages.php');
    exit;
}
// Handle reply
if (isset($_POST['reply_message'])) {
    $replySubject = trim($_POST['reply_subject']);
    $replyMessage = trim($_POST['reply_message']);
    
    if (empty($replySubject) || empty($replyMessage)) {
        setMessage('Subject and message are required.', 'error');
    } else {
        // Send email to user
        $emailBody = "
            <p>Dear " . htmlspecialchars($message['name']) . ",</p>
            <p>Thank you for contacting us. In response to your message:</p>
            <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #2c3d4f; margin: 15px 0;'>
                " . nl2br(htmlspecialchars($replyMessage)) . "
            </div>
            <p>Best regards,<br>" . SITE_NAME . " Team</p>
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='color: #999; font-size: 12px;'>Original Message:<br>" . htmlspecialchars($message['message']) . "</p>
        ";
        
        if (sendEmail($message['email'], $message['name'], $replySubject, $emailBody)) {
            setMessage('Reply sent successfully!', 'success');
            // Mark as read if not already
            if (!$message['is_read']) {
                $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$messageId]);
            }
            header("Location: view-message.php?id=$messageId");
            exit;
        } else {
            setMessage('Failed to send email. Please check your mail configuration.', 'error');
        }
    }
}

include 'includes/header.php';
?>

<style>
.message-view-container {
    padding: 20px;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.message-header h1 {
    font-size: 24px;
    color: #333;
    font-weight: 600;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.message-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.message-card {
    background: white; padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
}

.card-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
    font-weight: 600;
}

.info-row {
    margin-bottom: 12px;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 4px;
    font-weight: 600;
}

.info-value {
    font-size: 14px;
    color: #333;
}

.message-content-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    margin-bottom: 30px;
}

.message-content-card h2 {
    margin: 0 0 20px 0;
    font-size: 18px;
    color: #333;
    font-weight: 600;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.message-text {
    color: #666;
    line-height: 1.8;
    white-space: pre-wrap;
    font-size: 15px;
}

.quick-actions-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
}

.quick-actions-card h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #333;
    font-weight: 600;
}

.action-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-read {
    background: #e8f5e9;
    color: #388e3c;
}

.status-unread {
    background: #e3f2fd;
    color: #1976d2;
}

/* Reply Form Styles */
.reply-form-container {
    display: none;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.reply-form-container.active {
    display: block;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

textarea.form-control {
    resize: vertical;
    min-height: 150px;
}
</style>

<div class="message-view-container">
    <div class="message-header">
        <h1>Message Details</h1>
        <span class="status-badge <?php echo $message['is_read'] ? 'status-read' : 'status-unread'; ?>">
            <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
        </span>
    </div>

    <a href="messages.php" class="btn-secondary" style="display: inline-block; margin-bottom: 20px;">← Back to Messages</a>

    <!-- Info Cards -->
    <div class="message-cards">
        <!-- Sender Information -->
        <div class="message-card">
            <div class="card-header">
                <div class="card-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h3>Sender Information</h3>
            </div>
            <div class="info-row">
                <div class="info-label">Name</div>
                <div class="info-value"><?php echo htmlspecialchars($message['name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">
                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" style="color: #2c3d4f;">
                        <?php echo htmlspecialchars($message['email']); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Message Info -->
        <div class="message-card">
            <div class="card-header">
                <div class="card-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h3>Message Info</h3>
            </div>
            <div class="info-row">
                <div class="info-label">Subject</div>
                <div class="info-value"><?php echo htmlspecialchars($message['subject']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Received</div>
                <div class="info-value"><?php echo date('M j, Y \a\t g:i A', strtotime($message['created_at'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Message ID</div>
                <div class="info-value">#<?php echo $message['id']; ?></div>
            </div>
        </div>
    </div>

    <!-- Message Content -->
    <div class="message-content-card">
        <h2>Message</h2>
        <div class="message-text">
            <?php echo htmlspecialchars($message['message']); ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-card">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <button type="button" class="btn-primary" onclick="toggleReplyForm()">
                Reply via Email
            </button>
            <a href="?id=<?php echo $messageId; ?>&mark_unread=1" class="btn-secondary">
                Mark as Unread
            </a>
            <a href="?id=<?php echo $messageId; ?>&delete=1" 
               class="btn-danger"
               onclick="return confirm('Are you sure you want to delete this message?')">
                Delete Message
            </a>
        </div>

        <!-- Reply Form -->
        <div id="replyForm" class="reply-form-container">
            <form method="POST">
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="reply_subject" class="form-control" value="Re: <?php echo htmlspecialchars($message['subject']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="reply_message" class="form-control" placeholder="Write your reply here..." required></textarea>
                </div>
                <button type="submit" class="btn-primary">Send Reply</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleReplyForm() {
    const form = document.getElementById('replyForm');
    if (form.style.display === 'block') {
        form.style.display = 'none';
    } else {
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>

<?php include 'includes/footer.php'; ?>



