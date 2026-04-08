<?php
session_start();
require_once '../../config.php';
require_once '../includes/functions.php';

// Ensure admin is logged in
header('Content-Type: application/json');
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$searchTerm = trim($_GET['search'] ?? '');

if (strlen($searchTerm) < 2) {
    echo json_encode(['users' => [], 'message' => 'Please enter at least 2 characters']);
    exit;
}

// Search users by name or email
$stmt = $pdo->prepare("
    SELECT id, first_name, last_name, email 
    FROM users 
    WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?) 
    AND email IS NOT NULL 
    AND email != '' 
    ORDER BY first_name ASC 
    LIMIT 50
");

$searchParam = "%$searchTerm%";
$stmt->execute([$searchParam, $searchParam, $searchParam]);
$users = $stmt->fetchAll();

echo json_encode(['users' => $users]);
?>



