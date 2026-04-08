<?php
require_once '../config.php';
require_once '../includes/mail_functions.php';

header('Content-Type: application/json');

// Disable display_errors to prevent JSON breakage, log them instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userType = $input['user_type'] ?? 'customer'; // 'customer' or 'admin'

if ($userType === 'admin') {
    $username = trim($input['username'] ?? '');
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your username']);
        exit;
    }
    
    // Resolve email from username
    $stmt = $pdo->prepare("SELECT id, email, username FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    $email = $admin['email'] ?? '';
    // Use username as the name for admin emails since first_name doesn't exist
    $admin['first_name'] = $admin['username'] ?? 'Admin'; 

    
    $email = $admin['email'] ?? '';
    // If admin not found or no email, we still proceed with empty email to trigger the security "fake success" logic later 
    // but first assign what we have to $user for downstream logic
    $user = $admin; 
    
} else {
    $email = trim($input['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }
    // Customer logic remains same, fetch user later
}

try {
    if ($userType === 'customer') {
        $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }
    
    // Don't reveal if user/email exists (security best practice)
    if (!$user || empty($email)) {
        // Still return success but don't send email
        echo json_encode([
            'success' => true,
            'message' => 'If this email exists, a verification code has been sent'
        ]);
        exit;
    }
    
    // Rate limiting: Check recent requests from this email
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM password_reset_tokens 
        WHERE email = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        AND user_type = ?
    ");
    $stmt->execute([$email, $userType]);
    $recentRequests = $stmt->fetchColumn();
    
    if ($recentRequests >= 3) {
        echo json_encode([
            'success' => false,
            'message' => 'Too many requests. Please try again in an hour'
        ]);
        exit;
    }
    
    // Generate 6-digit code
    $resetCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set expiration (15 minutes from now)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Store reset code
    $stmt = $pdo->prepare("
        INSERT INTO password_reset_tokens (user_id, email, reset_code, expires_at, user_type)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user['id'], $email, $resetCode, $expiresAt, $userType]);
    
    // Send email with code using existing mail functions
    $emailSent = sendPasswordResetCodeEmail($email, $user['first_name'], $resetCode);
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent to your email'
        ]);
    } else {
        // Log the error (it's already logged in mail_functions.php)
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send verification email. Please contact support.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Send reset code error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later'
    ]);
}
?>
