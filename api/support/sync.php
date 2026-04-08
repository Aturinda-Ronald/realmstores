<?php
require_once '../../config.php';

header('Content-Type: application/json');

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
// Optional: Last message ID to fetch only new ones
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if ($sessionId === 0) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    // Mark admin messages as read if user is calling this?
    // We need to know who is calling. 
    // Usually the widget calls this. If widget calls, mark 'admin' messages as read.
    // If admin panel calls, mark 'user' messages as read.
    // We'll verify this via a 'context' param or just infer.
    // For simplicity: The Widget (User) polls this to read Admin messages. 
    // The Admin Panel polls a different endpoint? OR this one with context=admin.
    
    $context = $_GET['context'] ?? 'user'; // 'user' or 'admin'

    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? AND id > ? ORDER BY created_at ASC");
    $stmt->execute([$sessionId, $lastId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark as read
    if ($context === 'user') {
        // User reading admin messages
        $update = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'admin' AND is_read = 0");
        $update->execute([$sessionId]);
    } elseif ($context === 'admin') {
        // Admin reading user messages
        $update = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'user' AND is_read = 0");
        $update->execute([$sessionId]);
    }

    // Check for typing status
    $typing = false;
    $otherRole = ($context === 'user') ? 'admin' : 'user';
    $typingCol = ($context === 'user') ? 'last_admin_typing' : 'last_user_typing';

    // Check if other party typed in last 5 seconds
    $stmtType = $pdo->prepare("SELECT $typingCol FROM chat_sessions WHERE id = ?");
    $stmtType->execute([$sessionId]);
    $result = $stmtType->fetch(PDO::FETCH_ASSOC);

    if ($result && !empty($result[$typingCol])) {
        $lastTyping = strtotime($result[$typingCol]);
        if (time() - $lastTyping < 5) {
            $typing = true;
        }
    }

    echo json_encode(['success' => true, 'messages' => $messages, 'typing' => $typing]);

} catch (PDOException $e) {
    echo json_encode(['success' => false]);
}
?>
