<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    setMessage('Invalid product', 'error');
    redirect(ADMIN_URL . '/products.php');
}

// Get product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setMessage('Product not found', 'error');
    redirect(ADMIN_URL . '/products.php');
}

// Delete images
deleteProductImage($product['image1']);
deleteProductImage($product['image2']);
deleteProductImage($product['image3']);

// Delete product
$stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$productId]);

setMessage('Product deleted successfully');
redirect(ADMIN_URL . '/products.php');



