<?php
require_once '../config.php';
require_once '../includes/mail_functions.php';

header('Content-Type: application/json');

// Disable display_errors to prevent JSON breakage
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userType = $input['user_type'] ?? 'customer';
$newPassword = $input['new_password'] ?? '';
$resetCode = trim($input['reset_code'] ?? '');

if ($userType === 'admin') {
    $username = trim($input['username'] ?? '');
    
    if (empty($username) || empty($resetCode) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    // Resolve email
    $stmt = $pdo->prepare("SELECT email, id FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    $email = $user['email'] ?? '';

} else {
    $email = trim($input['email'] ?? '');
    
    // Validation
    if (empty($email) || empty($resetCode) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    // Look up customer (for later usage)
    $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

if (!preg_match('/^\d{6}$/', $resetCode)) {
    echo json_encode(['success' => false, 'message' => 'Invalid code format']);
    exit;
}

try {
    // Verify code
    $stmt = $pdo->prepare("
        SELECT id, user_id, expires_at, used 
        FROM password_reset_tokens 
        WHERE email = ? 
        AND reset_code = ? 
        AND user_type = ?
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$email, $resetCode, $userType]);
    $token = $stmt->fetch();
    
    if (!$token) {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
        exit;
    }
    
    // Check if already used
    if ($token['used']) {
        echo json_encode(['success' => false, 'message' => 'This code has already been used']);
        exit;
    }
    
    // Check if expired
    if (strtotime($token['expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'This code has expired. Please request a new one']);
        exit;
    }
    
    // Get user info
    if ($userType === 'admin') {
        $stmt = $pdo->prepare("SELECT id, first_name FROM admin_users WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE id = ?");
    }
    $stmt->execute([$token['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    if ($userType === 'admin') {
        $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    }
    $stmt->execute([$hashedPassword, $user['id']]);
    
    // Mark token as used
    $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
    $stmt->execute([$token['id']]);
    
    // Send confirmation email using existing mail functions
    sendPasswordChangedEmail($email, $user['first_name']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully! Redirecting to login...'
    ]);
    
} catch (PDOException $e) {
    error_log("Verify reset code error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again'
    ]);
}
?>
