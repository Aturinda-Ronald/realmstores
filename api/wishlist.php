<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to use wishlist', 'redirect' => BASE_URL . '/login.php']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action']) || !isset($input['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $input['action'];
$productId = (int)$input['product_id'];

try {
    if ($action === 'add') {
        // Check if already in wishlist
        if (isInWishlist($pdo, $userId, $productId)) {
            echo json_encode(['success' => true, 'message' => 'Product already in wishlist', 'status' => 'added']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        echo json_encode(['success' => true, 'message' => 'Added to wishlist', 'status' => 'added']);

    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        echo json_encode(['success' => true, 'message' => 'Removed from wishlist', 'status' => 'removed']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Wishlist error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
