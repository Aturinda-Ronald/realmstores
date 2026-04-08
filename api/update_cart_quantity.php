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
$cartItemId = isset($input['cart_item_id']) ? (int)$input['cart_item_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 0;

if ($cartItemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
    exit;
}

try {
    updateCartQuantity($pdo, $cartItemId, $quantity);
    $cartCount = getCartCount($pdo);
    $cartTotal = getCartTotal($pdo);

    echo json_encode([
        'success' => true,
        'message' => 'Cart updated',
        'cart_count' => $cartCount,
        'cart_total' => $cartTotal,
        'quantity' => $quantity
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}
