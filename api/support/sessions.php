<?php
require_once '../../config.php';

header('Content-Type: application/json');

// Ensure admin is logged in (simplified check for API)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Fetch active sessions with unread count
    $sql = "
        SELECT cs.*, 
        (SELECT COUNT(*) FROM chat_messages cm WHERE cm.session_id = cs.id AND cm.sender_type = 'user' AND cm.is_read = 0) as unread_count,
        (SELECT message FROM chat_messages cm WHERE cm.session_id = cs.id ORDER BY id DESC LIMIT 1) as last_message
        FROM chat_sessions cs 
        ORDER BY cs.updated_at DESC
    ";
    
    $stmt = $pdo->query($sql);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'sessions' => $sessions]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error']);
}
?>
