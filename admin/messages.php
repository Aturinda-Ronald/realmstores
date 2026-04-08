<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

// Handle message deletion
if (isset($_GET['delete'])) {
    $messageId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    setMessage('Message deleted successfully!', 'success');
    header('Location: messages.php');
    exit;
}

// Handle mark as read/unread
if (isset($_GET['toggle_read'])) {
    $messageId = (int)$_GET['toggle_read'];
    $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = NOT is_read WHERE id = ?");
    $stmt->execute([$messageId]);
    header('Location: messages.php');
    exit;
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Get all messages
$sql = "SELECT * FROM contact_messages";
if ($filter === 'unread') {
    $sql .= " WHERE is_read = 0";
} elseif ($filter === 'read') {
    $sql .= " WHERE is_read = 1";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->query($sql);
$messages = $stmt->fetchAll();

// Get counts
$unreadCount = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
$totalCount = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();

include 'includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Contact Messages</h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="background: #2c3d4f; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                <?php echo $unreadCount; ?> Unread
            </span>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs-wrapper" style="margin-bottom: 20px; border-bottom: 2px solid #f0f0f0;">
        <div style="display: flex; gap: 20px;">
            <a href="?filter=all" style="padding: 10px 20px; text-decoration: none; color: <?php echo $filter === 'all' ? '#2c3d4f' : '#666'; ?>; border-bottom: 3px solid <?php echo $filter === 'all' ? '#2c3d4f' : 'transparent'; ?>; font-weight: <?php echo $filter === 'all' ? '600' : '400'; ?>;">
                All (<?php echo $totalCount; ?>)
            </a>
            <a href="?filter=unread" style="padding: 10px 20px; text-decoration: none; color: <?php echo $filter === 'unread' ? '#2c3d4f' : '#666'; ?>; border-bottom: 3px solid <?php echo $filter === 'unread' ? '#2c3d4f' : 'transparent'; ?>; font-weight: <?php echo $filter === 'unread' ? '600' : '400'; ?>;">
                Unread (<?php echo $unreadCount; ?>)
            </a>
            <a href="?filter=read" style="padding: 10px 20px; text-decoration: none; color: <?php echo $filter === 'read' ? '#2c3d4f' : '#666'; ?>; border-bottom: 3px solid <?php echo $filter === 'read' ? '#2c3d4f' : 'transparent'; ?>; font-weight: <?php echo $filter === 'read' ? '600' : '400'; ?>;">
                Read (<?php echo $totalCount - $unreadCount; ?>)
            </a>
        </div>
    </div>

    <!-- Messages List -->
    <?php if (count($messages) > 0): ?>
    <div class="responsive-table">
        <table class="mobile-card-table">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $message): ?>
                <tr style="background: <?php echo $message['is_read'] ? '#ffffff' : '#f8f9ff'; ?>; <?php echo $message['is_read'] ? '' : 'font-weight: 600;'; ?>">
                    <td data-label="Status">
                        <?php if (!$message['is_read']): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#2c3d4f">
                            <circle cx="12" cy="12" r="8"/>
                        </svg>
                        <?php endif; ?>
                    </td>
                    <td data-label="Name"><?php echo htmlspecialchars($message['name']); ?></td>
                    <td data-label="Email"><?php echo htmlspecialchars($message['email']); ?></td>
                    <td data-label="Subject"><?php echo htmlspecialchars(substr($message['subject'], 0, 50)) . (strlen($message['subject']) > 50 ? '...' : ''); ?></td>
                    <td data-label="Date"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></td>
                    <td data-label="Actions">
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <!-- View Button -->
                            <a href="view-message.php?id=<?php echo $message['id']; ?>" class="icon-btn icon-btn-primary" title="View Message">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>

                            <!-- Toggle Read/Unread -->
                            <a href="?toggle_read=<?php echo $message['id']; ?>&filter=<?php echo $filter; ?>" 
                               class="icon-btn <?php echo $message['is_read'] ? 'icon-btn-warning' : 'icon-btn-success'; ?>" 
                               title="<?php echo $message['is_read'] ? 'Mark as Unread' : 'Mark as Read'; ?>">
                                <?php if ($message['is_read']): ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                <?php else: ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                <?php endif; ?>
                            </a>

                            <!-- Delete Button -->
                            <a href="?delete=<?php echo $message['id']; ?>" 
                               class="icon-btn icon-btn-danger" 
                               title="Delete"
                               onclick="return confirm('Are you sure you want to delete this message?')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 60px 20px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2" style="margin: 0 auto 20px;">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
        </svg>
        <p style="color: #666; font-size: 18px;">No messages found</p>
        <p style="color: #999; font-size: 14px; margin-top: 10px;">
            <?php if ($filter === 'unread'): ?>
                All messages have been read
            <?php elseif ($filter === 'read'): ?>
                No read messages yet
            <?php else: ?>
                No contact messages received yet
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>



