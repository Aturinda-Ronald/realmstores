<?php
require_once '../../config.php';
require_once '../../includes/mail_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
$message = trim($_POST['message'] ?? '');
$sender = $_POST['sender'] ?? 'user'; // 'user' or 'admin'

if ($sessionId === 0 || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    // Verify session exists
    $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }

    // Insert message
    $stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, sender_type, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$sessionId, $sender, $message]);

    // Update session timestamp
    $pdo->prepare("UPDATE chat_sessions SET updated_at = NOW() WHERE id = ?")->execute([$sessionId]);

    // Handle Notifications
    if ($sender === 'user') {
        // Notify Admin (Throttle: Only if no recent admin Activity or it's a new conversation start?)
        // Simple logic: Always notify for now, or check if last message was > 10 mins ago?
        // Let's just notify always for now as per "he can get a notification".
        
        $subject = "New Chat Message from " . $session['name'];
        $body = "
            <p><strong>" . htmlspecialchars($session['name']) . "</strong> (" . htmlspecialchars($session['email']) . ") sent a message:</p>
            <div style='background:#f5f5f5; padding:15px; border-radius:5px; margin:10px 0;'>
                " . nl2br(htmlspecialchars($message)) . "
            </div>
            <p><a href='" . ADMIN_URL . "/chat.php?session=" . $sessionId . "' style='background:#c53940; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;'>Reply in Admin Panel</a></p>
        ";
        // Send to ADMIN_EMAIL (defined in config/smtp)
        // We assume sendOrderEmail generic wrapper works or use sendEmail
        // Actually mail_functions has sendEmail($to, $name, $subject, $body)
        // We need an ADMIN_EMAIL constant? config.php usually has it.
        // Step 1087 summary says: ADMIN_EMAIL: realmstores2@gmail.com
        
        // We'll use a try-catch for mail to not block chat response
        try {
           sendEmail(ADMIN_EMAIL, 'Admin', $subject, $body);
        } catch (Exception $e) {
            error_log("Mail Notification Failed: " . $e->getMessage());
        }

    } elseif ($sender === 'admin') {
        // Notify User
        $subject = "New Reply from " . SITE_NAME . " Support";
        $body = "
            <p>Support replied to your chat:</p>
            <div style='background:#f5f5f5; padding:15px; border-radius:5px; margin:10px 0;'>
                " . nl2br(htmlspecialchars($message)) . "
            </div>
            <p>You can reply directly in the chat widget on our website.</p>
        ";
        try {
            sendEmail($session['email'], $session['name'], $subject, $body);
        } catch (Exception $e) {
            error_log("User Notification Failed: " . $e->getMessage());
        }
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    error_log("Chat Send Error: " . $e->getMessage());
}
?>
