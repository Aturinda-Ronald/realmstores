<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
    <style>
        .account-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .account-tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid #eee;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
            font-size: 15px;
        }

        .tab-button:hover {
            color: #c53940;
        }

        .tab-button.active {
            color: #c53940;
            border-bottom-color: #c53940;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .account-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);
        }

        .account-card h2 {
            margin-bottom: 25px;
            color: #333;
            font-size: 24px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .success-message,
        .error-message {
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-save {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .btn-save:hover {
            background: #218838;
        }

        .btn-save:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
            transition: background 0.3s;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .password-strength {
            height: 4px;
            background: #eee;
            margin-top: 5px;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }

        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }

        @media (max-width: 768px) {
            .account-container {
                margin: 20px auto;
                padding: 0 15px;
            }

            .account-card {
                padding: 20px 15px;
            }

            .account-card h2 {
                font-size: 20px;
                margin-bottom: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .info-row {
                grid-template-columns: 1fr;
                gap: 5px;
                padding: 12px 0;
            }

            .info-label {
                font-size: 13px;
                margin-bottom: 3px;
            }

            .info-value {
                font-size: 15px;
                font-weight: 500;
            }

            .account-tabs {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                margin-bottom: 20px;
                gap: 0;
                border-bottom: 1px solid #eee;
            }

            .account-tabs::-webkit-scrollbar {
                display: none;
            }

            .tab-button {
                white-space: nowrap;
                font-size: 14px;
                padding: 10px 16px;
                flex-shrink: 0;
            }

            .btn-save,
            .btn-cancel {
                width: 100%;
                margin-left: 0;
                margin-top: 10px;
                padding: 14px 20px;
                font-size: 15px;
            }

            .btn-cancel {
                margin-top: 10px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-group label {
                font-size: 14px;
                margin-bottom: 6px;
            }

            .form-group input,
            .form-group textarea {
                font-size: 16px;
                padding: 12px;
            }

            h1 {
                font-size: 24px !important;
                margin-bottom: 20px !important;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="top-bar">
            <div class="container">
                <span><?php echo SITE_TAGLINE; ?></span>
            </div>
        </div>

        <div class="main-header">
            <div class="container">
                <div class="logo">
                    <a href="<?php echo BASE_URL; ?>">
                        <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-img logo-img-nav">
                    </a>
                </div>

                <div class="search-bar">
                    <form method="GET" action="<?php echo BASE_URL; ?>/products.php">
                        <input type="text" name="search" placeholder="Search products..." id="searchInput">
                        <button type="submit" class="search-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                    </form>
                </div>

                <div class="header-right">
                    <a href="<?php echo BASE_URL; ?>/account.php" class="account-section">
                        <div class="account-icon-circle">
                            <svg class="account-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="account-info">
                            <span class="greeting">Hello, <?php echo escape($user['first_name']); ?></span>
                            <a href="<?php echo BASE_URL; ?>/logout.php" class="auth-link">Logout</a>
                        </div>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/wishlist.php" class="account-section wishlist-link">
                        <div class="account-icon-circle">
                            <svg class="account-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </div>
                        <div class="account-info">
                            <span class="greeting">Wishlist</span>
                            <span class="auth-link">View Saved</span>
                        </div>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/cart.php" class="cart-link" id="cartLink">
                        <div class="cart-icon-circle">
                            <div class="cart-icon-wrapper">
                                <svg class="cart-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                <?php $cartCount = getCartCount($pdo); ?>
                                <?php if ($cartCount > 0): ?>
                                <span class="cart-badge"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="cart-info">
                            <span class="cart-label">Your cart:</span>
                            <span class="cart-total">Ush <?php
                                $cartItems = getCartItems($pdo);
                                $total = 0;
                                foreach ($cartItems as $item) {
                                    $total += $item['price'] * $item['quantity'];
                                }
                                echo number_format($total, 0, '.', ',');
                            ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <nav class="main-nav">
        <div class="container">
            <ul>
                <li><a href="<?php echo BASE_URL; ?>">HOME</a></li>
                <li><a href="<?php echo BASE_URL; ?>/products.php">ALL PRODUCTS</a></li>
                <li><a href="<?php echo BASE_URL; ?>/contact.php">CONTACT US</a></li>
                <li><a href="<?php echo BASE_URL; ?>/shipping.php">SHIPPING</a></li>
            </ul>
        </div>
    </nav>

    <div class="breadcrumb">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>">Home</a> / <span>My Account</span>
        </div>
    </div>

    <div class="account-container">
        <h1 style="margin-bottom: 30px;">My Account</h1>

        <div class="account-tabs">
            <button class="tab-button <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" onclick="switchTab('profile')">Profile</button>
            <button class="tab-button <?php echo $activeTab === 'edit' ? 'active' : ''; ?>" onclick="switchTab('edit')">Edit Profile</button>
            <button class="tab-button <?php echo $activeTab === 'password' ? 'active' : ''; ?>" onclick="switchTab('password')">Change Password</button>
        </div>

        <!-- Profile Tab -->
        <div id="profile-tab" class="tab-content <?php echo $activeTab === 'profile' ? 'active' : ''; ?>">
            <div class="account-card">
                <h2>Account Information</h2>
                <div class="info-row">
                    <div class="info-label">Full Name:</div>
                    <div class="info-value"><?php echo escape($user['first_name'] . ' ' . $user['last_name']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?php echo escape($user['email']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value"><?php echo escape($user['phone'] ?: 'Not provided'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Address:</div>
                    <div class="info-value"><?php echo escape($user['address'] ?: 'Not provided'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">City:</div>
                    <div class="info-value"><?php echo escape($user['city'] ?: 'Not provided'); ?></div>
                </div>
                <div class="info-row" style="border-bottom: none;">
                    <div class="info-label">Member Since:</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Tab -->
        <div id="edit-tab" class="tab-content <?php echo $activeTab === 'edit' ? 'active' : ''; ?>">
            <div class="account-card">
                <h2>Edit Profile</h2>
                <div id="edit-message"></div>
                <form id="editProfileForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo escape($user['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo escape($user['last_name']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo escape($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo escape($user['phone']); ?>" placeholder="e.g., 0771234567">
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo escape($user['city']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo escape($user['address']); ?></textarea>
                    </div>
                    <div>
                        <button type="submit" class="btn-save" id="saveProfileBtn">Save Changes</button>
                        <button type="button" class="btn-cancel" onclick="switchTab('profile')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Tab -->
        <div id="password-tab" class="tab-content <?php echo $activeTab === 'password' ? 'active' : ''; ?>">
            <div class="account-card">
                <h2>Change Password</h2>
                <div id="password-message"></div>
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label for="current_password">Current Password *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <small style="color: #666; margin-top: 5px; display: block;">Minimum 8 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    <div>
                        <button type="submit" class="btn-save" id="savePasswordBtn">Change Password</button>
                        <button type="button" class="btn-cancel" onclick="switchTab('profile')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="Realm" class="footer-logo-img logo-img-footer">
                    </div>
                    <div class="footer-contact">
                        <p><strong>Contact Us:</strong></p>
                        <p class="phone"><?= ADMIN_PHONE_1 ?></p>
                        <p class="phone"><?= ADMIN_PHONE_2 ?></p>
                        <p>Email: <?= ADMIN_EMAIL ?></p>
                        <p>Kampala, Uganda</p>
                    </div>
                </div>

                <div class="footer-col">
                    <h3>QUICK LINKS</h3>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/products.php">All Products</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/contact.php">Contact</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/shipping.php">Shipping</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>MY ACCOUNT</h3>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/cart.php">Shopping Cart</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/wishlist.php">Wishlist</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/account.php">Account Settings</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>Copyright &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/js/script.js"></script>
    <script>
        function switchTab(tabName) {
            // Update URL
            history.pushState(null, '', '<?php echo BASE_URL; ?>/account.php?tab=' + tabName);
            
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Edit Profile Form
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('saveProfileBtn');
            const messageDiv = document.getElementById('edit-message');
            
            btn.disabled = true;
            btn.textContent = 'Saving...';
            messageDiv.innerHTML = '';
            
            const formData = new FormData(this);
            
            fetch('<?php echo BASE_URL; ?>/api/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="success-message">' + data.message + '</div>';
                    setTimeout(() => {
                        window.location.href = '<?php echo BASE_URL; ?>/account.php?tab=profile';
                    }, 1500);
                } else {
                    messageDiv.innerHTML = '<div class="error-message">' + data.message + '</div>';
                    btn.disabled = false;
                    btn.textContent = 'Save Changes';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="error-message">An error occurred. Please try again.</div>';
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            });
        });

        // Change Password Form
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('savePasswordBtn');
            const messageDiv = document.getElementById('password-message');
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            messageDiv.innerHTML = '';
            
            if (newPassword !== confirmPassword) {
                messageDiv.innerHTML = '<div class="error-message">Passwords do not match!</div>';
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Changing...';
            
            const formData = new FormData(this);
            
            fetch('<?php echo BASE_URL; ?>/api/change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="success-message">' + data.message + '</div>';
                    this.reset();
                    setTimeout(() => {
                        window.location.href = '<?php echo BASE_URL; ?>/account.php?tab=profile';
                    }, 1500);
                } else {
                    messageDiv.innerHTML = '<div class="error-message">' + data.message + '</div>';
                    btn.disabled = false;
                    btn.textContent = 'Change Password';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="error-message">An error occurred. Please try again.</div>';
                btn.disabled = false;
                btn.textContent = 'Change Password';
            });
        });

        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('strengthBar');
            
            strengthBar.className = 'password-strength-bar';
            
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                return;
            }
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength === 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
    </script>
    <?php include 'includes/mobile_nav.php'; ?>
    <?php include 'includes/support_widget.php'; ?>
</body>
</html>
