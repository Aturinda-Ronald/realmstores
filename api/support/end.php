<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit;
}

try {
    // Close the session
    $stmt = $pdo->prepare("UPDATE chat_sessions SET status = 'closed', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$sessionId]);
    
    // Clear from session
    unset($_SESSION['chat_session_id']);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error']);
}
?>
