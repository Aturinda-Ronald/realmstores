<?php
require_once '../config.php';
require_once '../includes/functions.php';
require_once '../includes/mail_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', '../debug_log.txt');
error_reporting(E_ALL);

function logError($msg) {
    error_log(date('[Y-m-d H:i:s] ') . $msg . "\n", 3, '../debug_log.txt');
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to place an order']);
    exit;
}

// Get and validate input
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$city = trim($_POST['city'] ?? '');
$address = trim($_POST['address'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');

$errors = [];

if (empty($name)) $errors[] = "Name is required";
if (empty($phone)) $errors[] = "Phone number is required";
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
if (empty($city)) $errors[] = "City is required";
if (empty($address)) $errors[] = "Address is required";
if (empty($payment_method)) $errors[] = "Payment method is required";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get cart items
    $cartItems = getCartItems($pdo);
    
    if (empty($cartItems)) {
        throw new Exception("Your cart is empty");
    }

    $subtotal = getCartTotal($pdo);
    $shipping_amount = 0.00; // Flat rate or calculated
    $total = $subtotal + $shipping_amount;

    // Generate Access Token
    $accessToken = bin2hex(random_bytes(32));

    // Insert Order
    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, email, phone, address, city, notes, payment_method, subtotal, shipping_amount, total, status, access_token, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())");
    $stmt->execute([$name, $email, $phone, $address, $city, $notes, $payment_method, $subtotal, $shipping_amount, $total, $accessToken]);
    $orderId = $pdo->lastInsertId();

    // Insert Order Items
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($cartItems as $item) {
        $lineTotal = $item['price'] * $item['quantity'];
        $stmtItem->execute([$orderId, $item['product_id'], $item['name'], $item['quantity'], $item['price'], $lineTotal]);
    }

    // Clear Cart
    $stmtClear = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmtClear->execute([$_SESSION['user_id']]);

    // Commit transaction
    $pdo->commit();

    // Send Emails
    // We do this after commit so if email fails, order is still saved
    try {
        // Send Customer Email
        $customerSubject = "Order Confirmation - Order #" . $orderId;
        $customerBody = getOrderEmailTemplate($orderId, $name, $cartItems, $total, $shipping_amount, $address, $city, $payment_method, $accessToken);
        sendOrderEmail($email, $customerSubject, $customerBody);

        // Send Admin Email
        $adminSubject = "New Order Received - Order #" . $orderId;
        $adminBody = getAdminOrderEmailTemplate($orderId, $name, $total);
        sendOrderEmail(ADMIN_EMAIL, $adminSubject, $adminBody);
    } catch (Exception $e) {
        // Log email error but don't fail the request
        error_log("Email sending failed: " . $e->getMessage());
    }

    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logError("Error processing order: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
