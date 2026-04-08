<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($categoryId <= 0) {
    setMessage('Invalid category', 'error');
    redirect(ADMIN_URL . '/categories.php');
}

// Get all products in this category and delete their images
$stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ?");
$stmt->execute([$categoryId]);
$products = $stmt->fetchAll();

foreach ($products as $product) {
    deleteProductImage($product['image1']);
    deleteProductImage($product['image2']);
    deleteProductImage($product['image3']);
}

// Delete all products in category
$stmt = $pdo->prepare("DELETE FROM products WHERE category_id = ?");
$stmt->execute([$categoryId]);

// Delete category
$stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
$stmt->execute([$categoryId]);

setMessage('Category and all its products deleted successfully');
redirect(ADMIN_URL . '/categories.php');



