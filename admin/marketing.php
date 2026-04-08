<?php
require_once '../config.php';
require_once 'includes/functions.php';
require_once '../includes/mail_functions.php';

// Calculate relative base path for frontend assets/API to avoid CORS/Mixed Content issues
// if BASE_URL mismatches current domain (e.g. localhost vs realmstores.com)
$urlParts = parse_url(BASE_URL);
$relativeBase = isset($urlParts['path']) ? rtrim($urlParts['path'], '/') : '';

// Ensure admin is logged in
if (!isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$success = '';
$error = '';

// Fetch all products for selection
$stmt = $pdo->query("SELECT id, name, price, image1 FROM products ORDER BY created_at DESC");
$allProducts = $stmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $selectedProductIds = $_POST['products'] ?? [];
    $selectionMode = $_POST['selection_mode'] ?? 'all'; // 'all', 'random', 'manual'
    $randomCount = (int)($_POST['random_count'] ?? 0);
    $selectedUserIds = $_POST['selected_users'] ?? [];

    if (empty($subject) || empty($message)) {
        $error = 'Please enter both a subject and a message.';
    } else {
        try {
            // Fetch details for selected products
            $selectedProducts = [];
            if (!empty($selectedProductIds)) {
                $placeholders = implode(',', array_fill(0, count($selectedProductIds), '?'));
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
                $stmt->execute($selectedProductIds);
                $selectedProducts = $stmt->fetchAll();
            }

            // Determine which users to send to based on selection mode
            $userIds = [];
            
            if ($selectionMode === 'all') {
                // Get all users
                $stmt = $pdo->query("SELECT id FROM users WHERE email IS NOT NULL AND email != ''");
                $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($selectionMode === 'random' && $randomCount > 0) {
                // Get random users
                $stmt = $pdo->query("SELECT id FROM users WHERE email IS NOT NULL AND email != '' ORDER BY RAND() LIMIT $randomCount");
                $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($selectionMode === 'manual') {
                // DEBUG: Log what we received  
                error_log("Manual mode - Selected User IDs type: " . gettype($selectedUserIds));
                error_log("Manual mode - Selected User IDs value: " . print_r($selectedUserIds, true));
                error_log("Manual mode - Is array: " . (is_array($selectedUserIds) ? 'YES' : 'NO'));
                error_log("Manual mode - Count: " . (is_array($selectedUserIds) ? count($selectedUserIds) : 'N/A'));
                
                if (!empty($selectedUserIds) && is_array($selectedUserIds)) {
                    // Use manually selected users
                    $userIds = $selectedUserIds;
                } else {
                    $error = 'Please select at least one user to send the email to.';
                    throw new Exception($error);
                }
            } else {
                throw new Exception($error);
            }
            
            $totalUsers = count($userIds);
            
            if ($totalUsers === 0) {
                $error = 'No users selected to send emails to.';
                throw new Exception($error);
            }
            
            // Fetch user details for sending
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id IN ($placeholders)");
            $stmt->execute($userIds);
            $users = $stmt->fetchAll();
            
            $sentCount = 0;
            $failedCount = 0;
            
            // Send emails immediately
            foreach ($users as $user) {
                try {
                    if (sendMarketingEmail($user['email'], $subject, $message, $selectedProducts)) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                    // Small delay to avoid overwhelming server
                    usleep(50000); // 0.05 second delay
                } catch (Exception $e) {
                    $failedCount++;
                    error_log("Failed to send to " . $user['email'] . ": " . $e->getMessage());
                }
            }
            
            // Set success message with actual results
            if ($sentCount > 0) {
                $success = "Marketing email sent successfully to " . number_format($sentCount) . " user" . ($sentCount > 1 ? 's' : '') . "!";
                if ($failedCount > 0) {
                    $success .= " (" . $failedCount . " failed)";
                }
            } else {
                $error = "Failed to send emails. Please check your SMTP configuration.";
            }
            
        } catch (Exception $e) {
            $error = "Error sending emails: " . $e->getMessage();
        }
    }
}

// Fetch all users for manual selection
$userLimit = 100; // Load only first 100 users initially for performance
$stmt = $pdo->query("SELECT id, first_name, last_name, email FROM users WHERE email IS NOT NULL AND email != '' ORDER BY first_name ASC LIMIT $userLimit");
$allUsers = $stmt->fetchAll();

// Get total user count
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE email IS NOT NULL AND email != ''");
$totalUserCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing - Admin Panel</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <!-- Quill CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        /* Reusing Admin Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; color: #333; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-left: 250px; padding: 30px; }
        
        /* Specific Styles */
        .marketing-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; }
        .btn-primary { background: #2c3d4f; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-weight: 600; width: 100%; font-size: 16px; }
        .btn-primary:hover { background: #5a6fd6; }
        
        /* Quill Editor Overrides */
        #editor-container { height: 200px; font-family: inherit; font-size: 16px; }
        .ql-toolbar { border-radius: 4px 4px 0 0; }
        .ql-container { border-radius: 0 0 4px 4px; }

        /* Product Selector Styles */
        .product-selector {
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 400px;
            overflow-y: auto;
            margin-top: 10px;
        }
        .product-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }
        .product-item:last-child { border-bottom: none; }
        .product-item:hover { background: #f9f9f9; }
        .product-item input[type="checkbox"] { margin-right: 15px; transform: scale(1.2); }
        .product-item img { width: 50px; height: 50px; object-fit: contain; margin-right: 15px; border-radius: 4px; border: 1px solid #eee; }
        .product-info { flex: 1; }
        .product-name { font-weight: 600; font-size: 14px; color: #333; }
        .product-price { font-size: 13px; color: #c53940; }
        
        .search-box {
            position: relative;
            margin-bottom: 10px;
        }
        .search-box input {
            padding-left: 35px !important;
        }
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        /* Preview Styles */
        .preview-section { background: #f4f4f4; padding: 20px; border-radius: 8px; border: 1px dashed #ccc; }
        .preview-header { text-align: center; margin-bottom: 20px; }
        .preview-message-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            text-align: left;
            font-size: 15px;
            color: #333;
            line-height: 1.6;
            margin-bottom: 25px;
            border: 1px solid #e6e6e6;
            box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
        }
        .preview-products { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .preview-product { background: white; padding: 10px; border-radius: 4px; text-align: center; font-size: 12px; border: 1px solid #eee; }
        .preview-product img { width: 100%; height: 120px; object-fit: contain; margin-bottom: 10px; }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding-top: 80px; }
            .marketing-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="marketing-grid">
        <!-- Compose Section -->
        <div class="card">
            <h2 style="margin-bottom: 20px; color: #333;">Compose Marketing Email</h2>
            
            <?php if ($success): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" id="marketingForm">
                <div class="form-group">
                    <label>Email Subject</label>
                    <input type="text" name="subject" id="subjectInput" placeholder="e.g. Huge Weekend Sale! Up to 50% Off" required>
                </div>
                
                <div class="form-group">
                    <label>Message Content</label>
                    <!-- Quill Editor Container -->
                    <div id="editor-container"></div>
                    <!-- Hidden input to store HTML content -->
                    <input type="hidden" name="message" id="messageInput">
                </div>


                <div class="form-group">
                    <label>Target Audience</label>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-top: 10px;">
                        
                        <!-- Option 1: All Users -->
                        <div style="margin-bottom: 20px;">
                            <div style="display: table; width: 100%;">
                                <div style="display: table-cell; width: 20px; vertical-align: top; padding-top: 2px;">
                                    <input type="radio" name="selection_mode" value="all" id="radioAll" checked style="cursor: pointer;">
                                </div>
                                <div style="display: table-cell; vertical-align: top; padding-left: 10px;">
                                    <span style="cursor: pointer; display: block;" onclick="document.getElementById('radioAll').click();">
                                        <strong style="display: block; margin-bottom: 3px; font-size: 14px;">Send to All Users</strong>
                                        <span style="color: #666; font-size: 13px;"><?php echo number_format($totalUserCount); ?> users</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Option 2: Random Users -->
                        <div style="margin-bottom: 20px;">
                            <div style="display: table; width: 100%;">
                                <div style="display: table-cell; width: 20px; vertical-align: top; padding-top: 2px;">
                                    <input type="radio" name="selection_mode" value="random" id="radioRandom" style="cursor: pointer;">
                                </div>
                                <div style="display: table-cell; vertical-align: top; padding-left: 10px;">
                                    <span style="cursor: pointer; display: block;" onclick="document.getElementById('radioRandom').click();">
                                        <strong style="display: block; margin-bottom: 3px; font-size: 14px;">Send to Random Users</strong>
                                    </span>
                                </div>
                            </div>
                            <div id="randomCountSection" style="display: none; margin-left: 30px; margin-top: 12px;">
                                <input type="number" name="random_count" id="randomCountInput" placeholder="e.g. 5000" min="1" max="<?php echo $totalUserCount; ?>" style="width: 100%; max-width: 250px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                                <small style="display: block; color: #666; margin-top: 6px;">Maximum: <?php echo number_format($totalUserCount); ?> users</small>
                            </div>
                        </div>
                        
                        <!-- Option 3: Manual Selection -->
                        <div>
                            <div style="display: table; width: 100%;">
                                <div style="display: table-cell; width: 20px; vertical-align: top; padding-top: 2px;">
                                    <input type="radio" name="selection_mode" value="manual" id="radioManual" style="cursor: pointer;">
                                </div>
                                <div style="display: table-cell; vertical-align: top; padding-left: 10px;">
                                    <span style="cursor: pointer; display: block;" onclick="document.getElementById('radioManual').click();">
                                        <strong style="display: block; margin-bottom: 3px; font-size: 14px;">Select Users Manually</strong>
                                        <span style="color: #666; font-size: 13px;">Search and select specific users</span>
                                    </span>
                                </div>
                            </div>
                            
                            <div id="manualSelectionSection" style="display: none; margin-left: 30px; margin-top: 12px;">
                                <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 15px; border-radius: 4px; margin-bottom: 15px;">
                                    <strong style="color: #856404;">Tip:</strong> 
                                    <span style="color: #856404;">Type at least 2 characters to search for users by name or email</span>
                                </div>
                                
                                <div class="search-box" style="margin-bottom: 10px;">
                                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input type="text" id="userSearch" placeholder="Search users..." autocomplete="off" style="padding-left: 35px;">
                                </div>
                                
                                <div id="userListContainer" style="background: white; border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow-y: auto;">
                                    <div style="padding: 50px 20px; text-align: center; color: #999;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 15px; opacity: 0.3; display: block;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                        <p style="margin: 0 0 8px 0; font-size: 15px;">Start typing to search</p>
                                        <small style="font-size: 12px;">Search by name or email address</small>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 10px; padding: 8px 0; border-top: 1px solid #e0e0e0;">
                                    <small style="color: #666;">
                                        Selected: <strong id="selectedCount" style="color: #2c3d4f;">0</strong> users
                                    </small>
                                </div>
                                
                                <div id="selectedUsersHidden"></div>
                            </div>
                        </div>
                        
                    </div>
                </div>

                <div class="form-group">
                    <label>Select Products to Feature</label>
                    <div class="search-box">
                        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" id="productSearch" placeholder="Search products...">
                    </div>
                    <div class="product-selector">
                        <?php foreach ($allProducts as $product): ?>
                        <label class="product-item" data-name="<?php echo strtolower(htmlspecialchars($product['name'])); ?>">
                            <input type="checkbox" name="products[]" value="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>" data-image="<?php echo $product['image1']; ?>">
                            <img src="<?php echo $relativeBase; ?>/uploads/products/<?php echo $product['image1']; ?>" alt="Product">
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-price">Ush <?php echo number_format($product['price']); ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <small style="color: #666; display: block; margin-top: 5px;">Selected products will appear in the "Top Deals" section.</small>
                </div>

                <button type="submit" class="btn-primary" id="submitBtn">Send Marketing Email</button>
            </form>
        </div>

        <!-- Preview Section -->
        <div class="card">
            <h2 style="margin-bottom: 20px; color: #333;">Email Preview</h2>
            <div class="preview-section">
                <div class="preview-header">
                    <img src="<?php echo $relativeBase; ?>/assets/logo.png" alt="Logo" style="height: 40px; margin-bottom: 10px;">
                    <div style="background: #c53940; color: white; padding: 15px; border-radius: 4px 4px 0 0; font-weight: bold; font-size: 18px;" id="previewSubject">
                        [Email Subject]
                    </div>
                </div>

                <div class="preview-message-card" id="previewMessage">
                    [Your message will appear here...]
                </div>

                <h3 style="text-align: center; color: #c53940; font-size: 16px; margin-bottom: 15px; text-transform: uppercase;">Top Deals For You</h3>
                
                <div class="preview-products" id="previewProductsGrid">
                    <!-- Selected products will appear here -->
                    <div style="grid-column: 1 / -1; text-align: center; color: #999; padding: 20px;">
                        Select products to see preview
                    </div>
                </div>
            </div>
        </div>
    </div>

    </main>
    </div>

    <!-- Quill JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Quill
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Write your message here...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['clean']
                ]
            }
        });

        const subjectInput = document.getElementById('subjectInput');
        const messageInput = document.getElementById('messageInput');
        const previewSubject = document.getElementById('previewSubject');
        const previewMessage = document.getElementById('previewMessage');
        const productSearch = document.getElementById('productSearch');
        const productItems = document.querySelectorAll('.product-item');
        const checkboxes = document.querySelectorAll('input[name="products[]"]');
        const previewGrid = document.getElementById('previewProductsGrid');
        const form = document.getElementById('marketingForm');

        // Live Preview: Subject
        subjectInput.addEventListener('input', function() {
            previewSubject.textContent = this.value || '[Email Subject]';
        });

        // Live Preview: Message (Quill)
        quill.on('text-change', function() {
            const html = quill.root.innerHTML;
            // Update hidden input
            messageInput.value = html;
            // Update preview
            previewMessage.innerHTML = (quill.getText().trim().length > 0) ? html : '[Your message will appear here...]';
        });


        // User Selection Mode Toggle
        const radioAll = document.querySelector('input[value="all"]');
        const radioRandom = document.getElementById('radioRandom');
        const radioManual = document.getElementById('radioManual');
        const randomCountSection = document.getElementById('randomCountSection');
        const manualSelectionSection = document.getElementById('manualSelectionSection');
        const randomCountInput = document.getElementById('randomCountInput');
        const userSearch = document.getElementById('userSearch');
        const selectedCount = document.getElementById('selectedCount');
        const submitBtn = document.getElementById('submitBtn');

        // Toggle sections based on radio selection
        radioAll.addEventListener('change', function() {
            if (this.checked) {
                randomCountSection.style.display = 'none';
                manualSelectionSection.style.display = 'none';
            }
        });

        radioRandom.addEventListener('change', function() {
            if (this.checked) {
                randomCountSection.style.display = 'block';
                manualSelectionSection.style.display = 'none';
            }
        });

        radioManual.addEventListener('change', function() {
            if (this.checked) {
                randomCountSection.style.display = 'none';
                manualSelectionSection.style.display = 'block';
            }
        });


        // AJAX User Search
        let selectedUsers = {}; // Store selected users as {id: {name, email}}
        let searchTimeout;
        
        const userListContainer = document.getElementById('userListContainer');
        const selectedUsersHidden = document.getElementById('selectedUsersHidden');
        
        userSearch.addEventListener('input', function() {
            const term = this.value.trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            if (term.length < 2) {
                userListContainer.innerHTML = `
                    <div style="padding: 40px 20px; text-align: center; color: #999;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px; opacity: 0.3;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <p>Start typing to search for users...</p>
                        <small style="display: block; margin-top: 8px; font-size: 12px;">Search by name or email address</small>
                    </div>
                `;
                return;
            }
            
            // Show loading
            userListContainer.innerHTML = `
                <div style="padding: 40px 20px; text-align: center; color: #999;">
                    <div style="border: 3px solid #f3f3f3; border-top: 3px solid #2c3d4f; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px;"></div>
                    <p>Searching users...</p>
                </div>
            `;
            
            // Debounce the search
            searchTimeout = setTimeout(() => {
                fetch('<?php echo $relativeBase; ?>/admin/api/search_users.php?search=' + encodeURIComponent(term))
                    .then(response => response.json())
                    .then(data => {
                        if (data.users && data.users.length > 0) {
                            let html = '';
                            data.users.forEach(user => {
                                const isSelected = selectedUsers[user.id] ? 'checked' : '';
                                // Escape strings for JavaScript
                                const safeName = `${user.first_name} ${user.last_name}`.replace(/'/g, "\\'").replace(/"/g, '\\"');
                                const safeEmail = user.email.replace(/'/g, "\\'").replace(/"/g, '\\"');
                                
                                html += `
                                    <div style="display: table; width: 100%; padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;" 
                                         onmouseover="this.style.background='#f9f9f9'" 
                                         onmouseout="this.style.background='white'"
                                         onclick="event.target.tagName !== 'INPUT' && document.getElementById('user_cb_${user.id}').click();">
                                        <div style="display: table-cell; width: 30px; vertical-align: middle;">
                                            <input type="checkbox" id="user_cb_${user.id}" ${isSelected} value="${user.id}" style="cursor: pointer; transform: scale(1.2);" onchange="toggleUserSelection(this, ${user.id}, '${safeName}', '${safeEmail}')" onclick="event.stopPropagation();">
                                        </div>
                                        <div style="display: table-cell; vertical-align: middle; padding-left: 10px;">
                                            <div style="font-weight: 600; font-size: 14px; color: #333; margin-bottom: 2px;">${user.first_name} ${user.last_name}</div>
                                            <div style="font-size: 12px; color: #666;">${user.email}</div>
                                        </div>
                                    </div>
                                `;
                            });
                            userListContainer.innerHTML = html;
                        } else {
                            userListContainer.innerHTML = `
                                <div style="padding: 40px 20px; text-align: center; color: #999;">
                                    <p>No users found matching "${term}"</p>
                                    <small style="display: block; margin-top: 8px; font-size: 12px;">Try a different search term</small>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        userListContainer.innerHTML = `
                            <div style="padding: 40px 20px; text-align: center; color: #dc3545;">
                                <p>Error loading users. Please try again.</p>
                            </div>
                        `;
                    });
            }, 300); // 300ms debounce
        });
        
        // Global function to toggle user selection
        window.toggleUserSelection = function(checkbox, userId, name, email) {
            if (checkbox.checked) {
                selectedUsers[userId] = {name, email};
                // Add hidden input
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_users[]';
                input.value = userId;
                input.id = 'user_' + userId;
                selectedUsersHidden.appendChild(input);
            } else {
                delete selectedUsers[userId];
                // Remove hidden input
                const input = document.getElementById('user_' + userId);
                if (input) input.remove();
            }
            updateSelectedCount();
        };
        
        function updateSelectedCount() {
            const count = Object.keys(selectedUsers).length;
            selectedCount.textContent = count;
        }



        // Form submission confirmation
        form.addEventListener('submit', function(e) {
            const mode = document.querySelector('input[name="selection_mode"]:checked').value;
            let count = 0;
            let message = '';

            if (mode === 'all') {
                count = <?php echo $totalUserCount; ?>;
                message = `Are you sure you want to send this email to ALL ${count.toLocaleString()} users?`;
            } else if (mode === 'random') {
                count = parseInt(randomCountInput.value) || 0;
                if (count === 0) {
                    alert('Please enter the number of random users to send to.');
                    e.preventDefault();
                    return false;
                }
                message = `Are you sure you want to send this email to ${count.toLocaleString()} random users?`;
            } else if (mode === 'manual') {
                count = Object.keys(selectedUsers).length;
                
                // Debug: Log selected users
                console.log('Selected users:', selectedUsers);
                console.log('Hidden inputs:', document.querySelectorAll('#selectedUsersHidden input'));
                
                if (count === 0) {
                    alert('Please select at least one user to send the email to.');
                    e.preventDefault();
                    return false;
                }
                message = `Are you sure you want to send this email to ${count.toLocaleString()} selected user${count > 1 ? 's' : ''}?`;
            }

            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
            
            // DEBUG: Log form data before submission
            const formData = new FormData(form);
            console.log('=== FORM SUBMISSION DEBUG ===');
            console.log('Selection mode:', mode);
            console.log('Selected users object:', selectedUsers);
            console.log('Hidden inputs count:', document.querySelectorAll('#selectedUsersHidden input').length);
            console.log('Form data entries:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            console.log('=== END DEBUG ===');
        });

        // Ensure hidden input is populated on submit (fallback)
        form.addEventListener('submit', function() {
            messageInput.value = quill.root.innerHTML;
        });

        // Product Search
        productSearch.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            productItems.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(term)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Live Preview: Products
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateProductPreview);
        });

        function updateProductPreview() {
            const selected = Array.from(checkboxes).filter(cb => cb.checked);
            
            if (selected.length === 0) {
                previewGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; color: #999; padding: 20px;">Select products to see preview</div>';
                return;
            }

            let html = '';
            selected.forEach(cb => {
                const name = cb.getAttribute('data-name');
                const price = parseInt(cb.getAttribute('data-price')).toLocaleString();
                const image = cb.getAttribute('data-image');
                const imageUrl = '<?php echo $relativeBase; ?>/uploads/products/' + image;

                html += `
                    <div class="preview-product">
                        <img src="${imageUrl}" alt="${name}">
                        <div style="font-weight: bold; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${name}</div>
                        <div style="color: #c53940; font-weight: bold;">Ush ${price}</div>
                    </div>
                `;
            });
            previewGrid.innerHTML = html;
        }
    });
    </script>
</body>
</html>



