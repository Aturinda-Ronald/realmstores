<?php
require_once 'config.php';
require_once 'includes/functions.php';

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$cartCount = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
    <style>
        .forgot-password-section {
            padding: 60px 0;
            min-height: calc(100vh - 400px);
        }

        .forgot-password-card {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);
        }

        .forgot-password-card h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .forgot-password-card p {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #999;
        }

        .step.active {
            background: #c53940;
            color: white;
        }

        .step.completed {
            background: #28a745;
            color: white;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .code-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 10px;
            font-weight: 600;
        }

        .timer {
            text-align: center;
            color: #c53940;
            font-size: 14px;
            margin-top: 10px;
        }

        .resend-link {
            text-align: center;
            margin-top: 15px;
        }

        .resend-link a {
            color: #c53940;
            text-decoration: none;
            font-size: 14px;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }


        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .forgot-password-card {
                padding: 30px 20px;
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
    </header>

    <section class="forgot-password-section">
        <div class="container">
            <div class="forgot-password-card">
                <h1>Forgot Password</h1>
                <p>We'll send you a code to reset your password</p>

                <div class="step-indicator">
                    <div class="step active" id="step1-indicator">1</div>
                    <div class="step" id="step2-indicator">2</div>
                    <div class="step" id="step3-indicator">3</div>
                </div>

                <div id="message-container"></div>

                <!-- Step 1: Enter Email -->
                <div class="form-step active" id="step1">
                    <form id="emailForm">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required placeholder="Enter your email">
                        </div>
                        <button type="submit" class="btn-primary" id="sendCodeBtn" style="width: 100%;">Send Code</button>
                    </form>
                </div>

                <!-- Step 2: Verify Code -->
                <div class="form-step" id="step2">
                    <form id="verifyForm">
                        <input type="hidden" id="verify_email" name="email">
                        <div class="form-group">
                            <label for="verify_code">Verification Code</label>
                            <input type="text" id="verify_code" name="verify_code" class="code-input" maxlength="6" pattern="[0-9]{6}" required placeholder="000000">
                            <div class="timer" id="timer">Code expires in <span id="countdown">15:00</span></div>
                        </div>
                        <button type="submit" class="btn-primary" id="verifyBtn" style="width: 100%;">Verify Code</button>
                        <div class="resend-link">
                            <a href="#" id="resendLink">Didn't receive code? Resend</a>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Set New Password -->
                <div class="form-step" id="step3">
                    <form id="resetForm">
                        <input type="hidden" id="reset_email" name="email">
                        <input type="hidden" id="reset_code" name="reset_code">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="Minimum 8 characters">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-enter password">
                        </div>
                        <button type="submit" class="btn-primary" id="resetBtn" style="width: 100%;">Reset Password</button>
                    </form>
                </div>

                <div class="back-to-login">
                    <a href="<?php echo BASE_URL; ?>/login.php">← Back to Login</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Fixed: Removed includes/footer.php as it doesn't exist, used mobile_nav only -->
    <?php include 'includes/mobile_nav.php'; ?>

    <script src="<?php echo BASE_URL; ?>/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let countdownTimer;
            let timeRemaining = 900; // 15 minutes in seconds
            let currentEmail = '';
            let verifiedCode = '';

            const emailForm = document.getElementById('emailForm');
            const verifyForm = document.getElementById('verifyForm');
            const resetForm = document.getElementById('resetForm');
            const resendLink = document.getElementById('resendLink');

            console.log('Forgot Password Page Loaded');

            // Step 1: Send Code
            if (emailForm) {
                console.log('Email form found');
                emailForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Step 1: Form submitted');
                    
                    const btn = document.getElementById('sendCodeBtn');
                    const emailInput = document.getElementById('email');
                    
                    if (!btn || !emailInput) {
                        console.error('Critical elements missing: btn or email input');
                        return;
                    }

                    const email = emailInput.value;
                    currentEmail = email;
                    
                    const originalText = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Sending...';
                    clearMessage();
                    
                    console.log('Sending request to api/send_reset_code.php with email:', email);

                    fetch('<?php echo BASE_URL; ?>/api/send_reset_code.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: email, user_type: 'customer' })
                    })
                    .then(response => {
                        console.log('Response received:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Data received:', data);
                        if (data.success) {
                            showMessage(data.message, 'success');
                            const verifyEmailInput = document.getElementById('verify_email');
                            if (verifyEmailInput) verifyEmailInput.value = email;
                            
                            setTimeout(() => {
                                switchToStep2();
                                startCountdown();
                            }, 1500);
                        } else {
                            showMessage(data.message, 'error');
                            btn.disabled = false;
                            btn.textContent = originalText;
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        showMessage('An error occurred. Please try again.', 'error');
                        btn.disabled = false;
                        btn.textContent = originalText;
                    });
                });
            } else {
                console.error('Email form not found!');
            }

            // Step 2: Verify Code
            if (verifyForm) {
                verifyForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const btn = document.getElementById('verifyBtn');
                    const codeInput = document.getElementById('verify_code');
                    const emailInput = document.getElementById('verify_email');
                    
                    const code = codeInput.value;
                    const email = emailInput.value;
                    
                    const originalText = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Verifying...';
                    clearMessage();
                    
                    fetch('<?php echo BASE_URL; ?>/api/verify_code_only.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            email: email, 
                            reset_code: code, 
                            user_type: 'customer'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage('Code verified! Please set your new password.', 'success');
                            verifiedCode = code;
                            
                            const resetEmail = document.getElementById('reset_email');
                            const resetCode = document.getElementById('reset_code');
                            if (resetEmail) resetEmail.value = email;
                            if (resetCode) resetCode.value = code;
                            
                            setTimeout(() => {
                                switchToStep3();
                                clearInterval(countdownTimer);
                            }, 1000);
                        } else {
                            showMessage(data.message, 'error');
                            btn.disabled = false;
                            btn.textContent = originalText;
                        }
                    })
                    .catch(error => {
                        showMessage('An error occurred. Please try again.', 'error');
                        btn.disabled = false;
                        btn.textContent = originalText;
                    });
                });
            }

            // Step 3: Reset Password
            if (resetForm) {
                resetForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (newPassword !== confirmPassword) {
                        showMessage('Passwords do not match!', 'error');
                        return;
                    }
                    
                    const btn = document.getElementById('resetBtn');
                    const originalText = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Resetting...';
                    clearMessage();
                    
                    const formData = {
                        email: document.getElementById('reset_email').value,
                        reset_code: document.getElementById('reset_code').value,
                        new_password: newPassword,
                        user_type: 'customer'
                    };
                    
                    fetch('<?php echo BASE_URL; ?>/api/verify_reset_code.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage(data.message, 'success');
                            setTimeout(() => {
                                window.location.href = '<?php echo BASE_URL; ?>/login.php';
                            }, 2000);
                        } else {
                            showMessage(data.message, 'error');
                            btn.disabled = false;
                            btn.textContent = originalText;
                        }
                    })
                    .catch(error => {
                        showMessage('An error occurred. Please try again.', 'error');
                        btn.disabled = false;
                        btn.textContent = originalText;
                    });
                });
            }

            // Resend Code
            if (resendLink) {
                resendLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    const email = document.getElementById('verify_email').value;
                    
                    this.textContent = 'Sending...';
                    
                    fetch('<?php echo BASE_URL; ?>/api/send_reset_code.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: email, user_type: 'customer' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage('Code resent successfully!', 'success');
                            timeRemaining = 900;
                            startCountdown();
                        } else {
                            showMessage(data.message, 'error');
                        }
                        this.textContent = "Didn't receive code? Resend";
                    });
                });
            }

            function switchToStep2() {
                document.getElementById('step1').classList.remove('active');
                document.getElementById('step2').classList.add('active');
                document.getElementById('step1-indicator').classList.remove('active');
                document.getElementById('step1-indicator').classList.add('completed');
                document.getElementById('step2-indicator').classList.add('active');
            }

            function switchToStep3() {
                document.getElementById('step2').classList.remove('active');
                document.getElementById('step3').classList.add('active');
                document.getElementById('step2-indicator').classList.remove('active');
                document.getElementById('step2-indicator').classList.add('completed');
                document.getElementById('step3-indicator').classList.add('active');
            }

            function startCountdown() {
                clearInterval(countdownTimer);
                countdownTimer = setInterval(() => {
                    timeRemaining--;
                    const minutes = Math.floor(timeRemaining / 60);
                    const seconds = timeRemaining % 60;
                    const countdownEl = document.getElementById('countdown');
                    if (countdownEl) {
                        countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    }
                    
                    if (timeRemaining <= 0) {
                        clearInterval(countdownTimer);
                        showMessage('Code expired. Please request a new one.', 'error');
                    }
                }, 1000);
            }

            function showMessage(message, type) {
                const container = document.getElementById('message-container');
                if (container) {
                    const className = type === 'success' ? 'success-message' : 'error-message';
                    container.innerHTML = `<div class="${className}">${message}</div>`;
                }
            }

            function clearMessage() {
                const container = document.getElementById('message-container');
                if (container) {
                    container.innerHTML = '';
                }
            }
        });
    </script>
    
    <?php include 'includes/mobile_nav.php'; ?>
    <?php include 'includes/support_widget.php'; ?>
</body>
</html>
