<?php
// Admin helper functions

// Check if admin is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Require login for protected admin pages
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(ADMIN_URL . '/login.php');
    }
}

// Generate CSRF token for forms
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Handle product image upload
// $files is the $_FILES array, $fieldName is the input name (e.g. 'image1')
function uploadProductImage(array $files, string $fieldName) {
    if (!isset($files[$fieldName]) || $files[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        // No file uploaded for this field
        return null;
    }

    if ($files[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error for ' . $fieldName);
    }

    // Validate file size
    if ($files[$fieldName]['size'] > MAX_FILE_SIZE) {
        throw new Exception($fieldName . ' file size exceeds allowed limit');
    }

    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $files[$fieldName]['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_TYPES, true)) {
        throw new Exception($fieldName . ' must be an image (JPG, PNG, WEBP, GIF)');
    }

    // Make sure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0777, true) && !is_dir(UPLOAD_DIR)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    // Generate unique filename
    $extension = pathinfo($files[$fieldName]['name'], PATHINFO_EXTENSION);
    $extension = strtolower($extension ?: 'jpg');
    $filename  = uniqid('', true) . '_' . time() . '.' . $extension;
    $destination = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($files[$fieldName]['tmp_name'], $destination)) {
        throw new Exception('Failed to save ' . $fieldName);
    }

    return $filename;
}

// Delete a product image file by filename
function deleteProductImage(?string $filename): void {
    if (empty($filename)) {
        return;
    }

    $filepath = UPLOAD_DIR . $filename;
    if (file_exists($filepath)) {
        @unlink($filepath);
    }
}

// Simple redirect helper (THIS is the one that was missing)
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// Flash message helpers
function setMessage(string $message, string $type = 'success'): void {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function getMessage(): ?array {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type    = $_SESSION['message_type'] ?? 'success';
        unset($_SESSION['message'], $_SESSION['message_type']);

        return [
            'message' => $message,
            'type'    => $type,
        ];
    }
    return null;
}



