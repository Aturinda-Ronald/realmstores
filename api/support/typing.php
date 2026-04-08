<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
$role = isset($_POST['role']) ? $_POST['role'] : '';

if (!$sessionId || !in_array($role, ['user', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $status = $_POST['status'] ?? 'typing';

    if ($role === 'user') {
        $val = ($status === 'stopped') ? 'NULL' : 'NOW()';
        // If NULL, we can't just use ? in execute for a function.
        // Better:
        if ($status === 'stopped') {
            $stmt = $pdo->prepare("UPDATE chat_sessions SET last_user_typing = NULL WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE chat_sessions SET last_user_typing = NOW() WHERE id = ?");
        }
    } else {
        if ($status === 'stopped') {
            $stmt = $pdo->prepare("UPDATE chat_sessions SET last_admin_typing = NULL WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE chat_sessions SET last_admin_typing = NOW() WHERE id = ?");
        }
    }
    
    $stmt->execute([$sessionId]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
