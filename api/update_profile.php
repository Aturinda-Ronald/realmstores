<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = $_SESSION['user_id'];
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$city = trim($_POST['city'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validation
if (empty($firstName) || empty($lastName) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch();
    
    if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Check if email is already taken by another user
    if ($email !== $currentUser['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email address is already in use']);
            exit;
        }
    }
    
    // Track changes for email notification
    $changes = [];
    if ($firstName !== $currentUser['first_name']) $changes[] = "First Name: {$currentUser['first_name']} → $firstName";
    if ($lastName !== $currentUser['last_name']) $changes[] = "Last Name: {$currentUser['last_name']} → $lastName";
    if ($email !== $currentUser['email']) $changes[] = "Email: {$currentUser['email']} → $email";
    if ($phone !== ($currentUser['phone'] ?? '')) $changes[] = "Phone: " . ($currentUser['phone'] ?: '(empty)') . " → " . ($phone ?: '(empty)');
    if ($city !== ($currentUser['city'] ?? '')) $changes[] = "City: " . ($currentUser['city'] ?: '(empty)') . " → " . ($city ?: '(empty)');
    if ($address !== ($currentUser['address'] ?? '')) $changes[] = "Address: " . ($currentUser['address'] ?: '(empty)') . " → " . ($address ?: '(empty)');
    
    // Update user data
    $stmt = $pdo->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, email = ?, phone = ?, city = ?, address = ?
        WHERE id = ?
    ");
    $stmt->execute([$firstName, $lastName, $email, $phone, $city, $address, $userId]);
    
    // Update session
    $_SESSION['user_name'] = $firstName;
    $_SESSION['user_email'] = $email;
    
    // Send email notification if there are changes
    if (count($changes) > 0) {
        sendProfileUpdateEmail($email, $firstName, $changes);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully!'
    ]);
    
} catch (PDOException $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}

function sendProfileUpdateEmail($email, $name, $changes) {
    require_once '../smtp_config.php';
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Profile Update Notification - ' . SITE_NAME;
        
        $changesHtml = '';
        foreach ($changes as $change) {
            $changesHtml .= "<li style='margin-bottom: 10px;'>$change</li>";
        }
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #c53940;'>Profile Updated</h2>
            <p>Hello $name,</p>
            <p>Your profile has been updated successfully. Here are the changes made:</p>
            <ul style='line-height: 1.8;'>
                $changesHtml
            </ul>
            <p style='margin-top: 20px;'>If you didn't make these changes, please contact us immediately at " . ADMIN_EMAIL . "</p>
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
            <p style='color: #666; font-size: 12px;'>
                Best regards,<br>
                " . SITE_NAME . " Team<br>
                Kampala, Uganda
            </p>
        </div>
        ";
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
    }
}
?>
