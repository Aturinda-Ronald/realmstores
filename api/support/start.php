<?php
require_once '../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? ''); // Optional
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;

if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and Email are required']);
    exit;
}

try {
    // Create new session
    $stmt = $pdo->prepare("INSERT INTO chat_sessions (user_id, name, email, phone, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())");
    $stmt->execute([$userId, $name, $email, $phone]);
    
    $sessionId = $pdo->lastInsertId();
    
    $_SESSION['chat_session_id'] = $sessionId;
    
    echo json_encode(['success' => true, 'session_id' => $sessionId]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    error_log("Chat Start Error: " . $e->getMessage());
}
?>
