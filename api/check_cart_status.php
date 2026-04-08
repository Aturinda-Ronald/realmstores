<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

$sessionId = getCartSessionId();
$userId = $_SESSION['user_id'] ?? null;

// Check if item is in cart
if ($userId) {
    $stmt = $pdo->prepare("SELECT c.id as cart_item_id, c.quantity FROM cart_items c WHERE c.user_id = ? AND c.product_id = ?");
    $stmt->execute([$userId, $productId]);
} else {
    $stmt = $pdo->prepare("SELECT c.id as cart_item_id, c.quantity FROM cart_items c WHERE c.session_id = ? AND c.product_id = ?");
    $stmt->execute([$sessionId, $productId]);
}

$cartItem = $stmt->fetch();

if ($cartItem) {
    echo json_encode([
        'success' => true,
        'in_cart' => true,
        'cart_item_id' => $cartItem['cart_item_id'],
        'quantity' => $cartItem['quantity']
    ]);
} else {
    echo json_encode([
        'success' => true,
        'in_cart' => false
    ]);
}
