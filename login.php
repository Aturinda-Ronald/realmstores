<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/mail_functions.php';

$error = '';
$success = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];
            $_SESSION['user_email'] = $user['email'];

            // Transfer cart items from session to user
            transferCartToUser($pdo, $user['id']);

            if (isset($_SESSION['redirect_url'])) {
                $redirectUrl = $_SESSION['redirect_url'];
                unset($_SESSION['redirect_url']);
                header('Location: ' . $redirectUrl);
            } else {
                header('Location: ' . BASE_URL . '/index.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered.';
        } else {
            // Create new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?)");

            if ($stmt->execute([$email, $hashedPassword, $firstName, $lastName, $phone])) {
                $userId = $pdo->lastInsertId();
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $firstName;
                $_SESSION['user_email'] = $email;

                // Transfer cart items
                transferCartToUser($pdo, $userId);

                // Send Welcome Email
                sendWelcomeEmail($email, $firstName, $password);

                $success = 'Account created successfully! Redirecting...';
                header('refresh:2;url=' . BASE_URL . '/index.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$cartCount = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body>
    <?php include 'includes/bg_decorations.php'; ?>
    
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
                        <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-img">
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
                    <a href="<?php echo BASE_URL; ?>/cart.php" class="cart-link">
                        <div class="cart-icon-circle">
                            <div class="cart-icon-wrapper">
                                <svg class="cart-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                <?php if ($cartCount > 0): ?>
                                <span class="cart-badge"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
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

    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <?php if ($error): ?>
                <div class="alert alert-error"><?php echo escape($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo escape($success); ?></div>
                <?php endif; ?>

                <div class="auth-tabs">
                    <button class="auth-tab active" onclick="switchTab('login')">Login</button>
                    <button class="auth-tab" onclick="switchTab('register')">Register</button>
                </div>

                <!-- Login Form -->
                <div id="loginForm" class="auth-form active">
                    <h2>Login to Your Account</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <button type="submit" name="login" class="btn-primary btn-full">Login</button>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="<?php echo BASE_URL; ?>/forgot-password.php" style="color: #c53940; text-decoration: none; font-size: 14px;">Forgot Password?</a>
                        </div>
                    </form>
                </div>

                <!-- Register Form -->
                <div id="registerForm" class="auth-form">
                    <h2>Create New Account</h2>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="reg_email">Email Address *</label>
                            <input type="email" id="reg_email" name="reg_email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="+256">
                        </div>
                        <div class="form-group">
                            <label for="reg_password">Password *</label>
                            <input type="password" id="reg_password" name="reg_password" required>
                            <small>Minimum 6 characters</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="register" class="btn-primary btn-full">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>Copyright &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/js/script.js"></script>
    <script>
    function switchTab(tab) {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const tabs = document.querySelectorAll('.auth-tab');

        if (tab === 'login') {
            loginForm.classList.add('active');
            registerForm.classList.remove('active');
            tabs[0].classList.add('active');
            tabs[1].classList.remove('active');
        } else {
            loginForm.classList.remove('active');
            registerForm.classList.add('active');
            tabs[0].classList.remove('active');
            tabs[1].classList.add('active');
        }
    }
    </script>
    <?php include 'includes/mobile_nav.php'; ?>
    <?php include 'includes/support_widget.php'; ?>
</body>
</html>
