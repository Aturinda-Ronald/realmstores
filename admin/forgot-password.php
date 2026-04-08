<?php
require_once '../config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Forgot Password - REALM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #2c3e50;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-img {
            width: 100px;
            height: auto;
            margin-bottom: 10px;
        }

        .logo p {
            color: #666;
            margin-top: 5px;
            font-size: 16px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #2c3d4f;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #2c3d4f;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #1e2b3a;
        }
        
        .btn-primary:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        .login-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
        }
        
        /* Steps and Feedback */
        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #f5c6cb;
        }
        
        .timer {
            text-align: center;
            color: #2c3d4f;
            font-size: 13px;
            margin-top: 8px;
        }
        
        .resend-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .resend-link a {
            color: #2c3d4f;
            font-size: 13px;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            color: #2c3d4f;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="Realm" class="logo-img">
            <p>Reset Password</p>
        </div>

        <div id="message-container"></div>

        <!-- Step 1: Username -->
        <div class="form-step active" id="step1">
            <form id="emailForm">
                <div class="form-group">
                    <label for="username">Admin Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter username" autofocus>
                </div>
                <button type="submit" class="btn-primary" id="sendCodeBtn">Send Code</button>
            </form>
        </div>

        <!-- Step 2: Verify Code -->
        <div class="form-step" id="step2">
            <form id="verifyForm">
                <input type="hidden" id="verify_username" name="username">
                <div class="form-group">
                    <label for="verify_code">Verification Code</label>
                    <input type="text" id="verify_code" name="verify_code" style="text-align: center; letter-spacing: 5px; font-size: 20px;" maxlength="6" pattern="[0-9]{6}" required placeholder="000000">
                    <div class="timer" id="timer">Code expires in <span id="countdown">15:00</span></div>
                </div>
                <button type="submit" class="btn-primary" id="verifyBtn">Verify Code</button>
                <div class="resend-link">
                    <a href="#" id="resendLink">Didn't receive code? Resend</a>
                </div>
            </form>
        </div>

        <!-- Step 3: Set New Password -->
        <div class="form-step" id="step3">
            <form id="resetForm">
                <input type="hidden" id="reset_username" name="username">
                <input type="hidden" id="reset_code" name="reset_code">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="Min 8 chars">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-enter password">
                </div>
                <button type="submit" class="btn-primary" id="resetBtn">Reset Password</button>
            </form>
        </div>

        <a href="<?php echo BASE_URL; ?>/admin/login.php" class="back-link">← Back to Login</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let countdownTimer;
            let timeRemaining = 900;
            let currentUsername = '';

            const emailForm = document.getElementById('emailForm');
            const verifyForm = document.getElementById('verifyForm');
            const resetForm = document.getElementById('resetForm');
            const resendLink = document.getElementById('resendLink');

            console.log('Admin Forgot Password Loaded (Username Mode)');

            // Step 1: Send Code
            if (emailForm) {
                console.log('Email form found');
                emailForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const btn = document.getElementById('sendCodeBtn');
                    const usernameInput = document.getElementById('username');
                    
                    if (!btn || !usernameInput) return;

                    const username = usernameInput.value;
                    currentUsername = username;
                    const originalText = btn.textContent;
                    
                    btn.disabled = true;
                    btn.textContent = 'Sending...';
                    clearMessage();
                    
                    console.log('Sending admin reset code for username:', username);

                    fetch('<?php echo BASE_URL; ?>/api/send_reset_code.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username: username, user_type: 'admin' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Response:', data);
                        if (data.success) {
                            showMessage(data.message, 'success');
                            const verifyUserInput = document.getElementById('verify_username');
                            if (verifyUserInput) verifyUserInput.value = username;
                            
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
            }

            // Step 2: Verify Code
            if (verifyForm) {
                verifyForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const btn = document.getElementById('verifyBtn');
                    const codeInput = document.getElementById('verify_code');
                    const usernameInput = document.getElementById('verify_username');
                    
                    const code = codeInput.value;
                    const username = usernameInput.value;
                    const originalText = btn.textContent;
                    
                    btn.disabled = true;
                    btn.textContent = 'Verifying...';
                    clearMessage();
                    
                    fetch('<?php echo BASE_URL; ?>/api/verify_code_only.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            username: username, 
                            reset_code: code, 
                            user_type: 'admin'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage('Code verified! Please set your new password.', 'success');
                            const resetUserInput = document.getElementById('reset_username');
                            const resetCodeInput = document.getElementById('reset_code');
                            if (resetUserInput) resetUserInput.value = username;
                            if (resetCodeInput) resetCodeInput.value = code;

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
                        username: document.getElementById('reset_username').value,
                        reset_code: document.getElementById('reset_code').value,
                        new_password: newPassword,
                        user_type: 'admin'
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
                                window.location.href = '<?php echo BASE_URL; ?>/admin/login.php';
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
                    const username = document.getElementById('verify_username').value;
                    
                    this.textContent = 'Sending...';
                    
                    fetch('<?php echo BASE_URL; ?>/api/send_reset_code.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username: username, user_type: 'admin' })
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
            }

            function switchToStep3() {
                document.getElementById('step2').classList.remove('active');
                document.getElementById('step3').classList.add('active');
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
</body>
</html>
