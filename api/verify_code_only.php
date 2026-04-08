<?php
require_once '../config.php';

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

if ($userType === 'admin') {
    $username = trim($input['username'] ?? '');
    $resetCode = trim($input['reset_code'] ?? '');
    
    if (empty($username) || empty($resetCode)) {
        echo json_encode(['success' => false, 'message' => 'Username and code are required']);
        exit;
    }

    // Resolve email
    $stmt = $pdo->prepare("SELECT email FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    $email = $admin['email'] ?? '';

} else {
    $email = trim($input['email'] ?? '');
    $resetCode = trim($input['reset_code'] ?? '');
    
    if (empty($email) || empty($resetCode)) {
        echo json_encode(['success' => false, 'message' => 'Email and code are required']);
        exit;
    }
}

if (!preg_match('/^\d{6}$/', $resetCode)) {
    echo json_encode(['success' => false, 'message' => 'Invalid code format']);
    exit;
}

try {
    // Verify code exists and is valid
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
    
    // Code is valid!
    echo json_encode([
        'success' => true,
        'message' => 'Code verified successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Verify code error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again'
    ]);
}
?>
