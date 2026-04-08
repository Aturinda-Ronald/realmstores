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
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;
$variant = isset($input['variant']) ? trim($input['variant']) : null;

if ($productId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit;
}

// Verify product exists
$product = getProductById($pdo, $productId);
if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

try {
    addToCart($pdo, $productId, $quantity, $variant);
    $cartCount = getCartCount($pdo);
    $cartTotal = getCartTotal($pdo);

    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart',
        'cart_count' => $cartCount,
        'cart_total' => $cartTotal
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
}
