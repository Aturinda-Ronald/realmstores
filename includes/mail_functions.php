<?php

// Load SMTP configuration
require_once __DIR__ . '/../smtp_config.php';

/**
 * Send an email using SMTP (Gmail)
 */
function sendOrderEmail($to, $subject, $htmlBody) {
    try {
        $smtp = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
        
        if (!$smtp) {
            error_log("SMTP Connection Failed: $errstr ($errno)");
            return false;
        }
        
        stream_set_timeout($smtp, 30);
        
        // Read server greeting
        $response = fgets($smtp);
        if (strpos($response, '220') === false) {
            error_log("SMTP Greeting Failed: " . $response);
            fclose($smtp);
            return false;
        }
        
        // SMTP Handshake
        fputs($smtp, "EHLO localhost\r\n");
        while ($line = fgets($smtp)) {
            if ($line[3] == ' ') break;
        }
        
        // Start TLS
        fputs($smtp, "STARTTLS\r\n");
        $response = fgets($smtp);
        if (strpos($response, '220') === false) {
            error_log("STARTTLS Failed: " . $response);
            fclose($smtp);
            return false;
        }
        
        // Enable crypto
        $crypto_result = stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$crypto_result) {
            error_log("TLS encryption failed");
            fclose($smtp);
            return false;
        }
        
        // EHLO again after TLS
        fputs($smtp, "EHLO localhost\r\n");
        while ($line = fgets($smtp)) {
            if ($line[3] == ' ') break;
        }
        
        // Authenticate
        fputs($smtp, "AUTH LOGIN\r\n");
        fgets($smtp);
        fputs($smtp, base64_encode(SMTP_USERNAME) . "\r\n");
        fgets($smtp);
        fputs($smtp, base64_encode(SMTP_PASSWORD) . "\r\n");
        $auth_response = fgets($smtp);
        
        if (strpos($auth_response, '235') === false) {
            error_log("SMTP Auth Failed: " . $auth_response);
            fclose($smtp);
            return false;
        }
        
        // Set sender
        fputs($smtp, "MAIL FROM: <" . SMTP_FROM_EMAIL . ">\r\n");
        fgets($smtp);
        
        // Set recipient
        fputs($smtp, "RCPT TO: <" . $to . ">\r\n");
        $response = fgets($smtp);
        
        if (strpos($response, '250') === false && strpos($response, '251') === false) {
            error_log("RCPT TO failed: " . $response);
            fclose($smtp);
            return false;
        }
        
        // Start message
        fputs($smtp, "DATA\r\n");
        fgets($smtp);
        
        // Email headers and body
        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        $headers .= "To: " . $to . "\r\n";
        $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "\r\n";
        
        fputs($smtp, $headers . $htmlBody . "\r\n.\r\n");
        $response = fgets($smtp);
        
        // Quit
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        error_log("Email sent successfully to $to");
        return true;
        
    } catch (Exception $e) {
        error_log("Email Send Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Welcome Email
 */
function sendWelcomeEmail($to, $name, $password) {
    $subject = "Welcome to " . SITE_NAME . " - Your Account Details";
    $body = getWelcomeEmailTemplate($name, $to, $password);
    return sendOrderEmail($to, $subject, $body);
}

/**
 * Send Order Status Update Email
 */
function sendOrderStatusEmail($to, $name, $orderId, $status, $trackingNumber = null) {
    $subject = "Order #" . $orderId . " Update: " . ucfirst($status);
    $body = getOrderStatusEmailTemplate($name, $orderId, $status, $trackingNumber);
    return sendOrderEmail($to, $subject, $body);
}

/**
 * Common Email Header
 */
function getEmailHeader($title) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            
            /* Logo Section */
            .logo-container { background-color: #ffffff; padding: 20px; text-align: center; border-bottom: 1px solid #eee; }
            .logo-container img { max-height: 60px; }

            /* Title Header Section (Filled Color) */
            .header { background-color: #c53940; color: #ffffff; padding: 20px; text-align: center; }
            .header h2 { margin: 0; color: #ffffff; font-size: 24px; }
            
            .content { padding: 30px; }
            .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
            .btn { display: inline-block; padding: 12px 24px; background-color: #c53940; color: #ffffff !important; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th { text-align: left; background-color: #f8f9fa; padding: 12px; border-bottom: 2px solid #ddd; }
            td { padding: 12px; border-bottom: 1px solid #eee; }
            .highlight { color: #c53940; font-weight: bold; }
            
            /* Tracking Indicator Styles */
            .tracking-container { margin: 30px 0; }
            .tracking-bar { display: flex; justify-content: space-between; position: relative; margin-bottom: 20px; }
            .tracking-bar::before { content: ""; position: absolute; top: 15px; left: 0; right: 0; height: 4px; background-color: #e0e0e0; z-index: 1; }
            .step { position: relative; z-index: 2; text-align: center; width: 25%; }
            .step-circle { width: 30px; height: 30px; background-color: #e0e0e0; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px; border: 2px solid #fff; }
            .step.active .step-circle { background-color: #c53940; }
            .step.completed .step-circle { background-color: #c53940; }
            .step-label { font-size: 12px; color: #999; font-weight: bold; }
            .step.active .step-label, .step.completed .step-label { color: #333; }
            
            /* Progress Line Fill */
            .progress-fill { position: absolute; top: 15px; left: 0; height: 4px; background-color: #c53940; z-index: 1; transition: width 0.3s ease; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo-container">
                 <img src="' . EMAIL_IMAGE_BASE_URL . '/assets/logo.png" alt="' . SITE_NAME . '">
            </div>
            <div class="header">
                <h2>' . $title . '</h2>
            </div>
            <div class="content">';
}

/**
 * Common Email Footer
 */
function getEmailFooter() {
    return '
            </div>
            <div class="footer">
                <p>&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
                <p>' . SITE_TAGLINE . '</p>
                <p><a href="' . BASE_URL . '" style="color: #c53940; text-decoration: none;">Visit our Website</a></p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Generate Welcome Email Template
 */
function getWelcomeEmailTemplate($name, $email, $password) {
    return getEmailHeader('Welcome to ' . SITE_NAME . '!') . '
        <p>Dear ' . htmlspecialchars($name) . ',</p>
        <p>Welcome to <strong>' . SITE_NAME . '</strong>! We are thrilled to have you on board.</p>
        <p>Your account has been successfully created. You can now log in to track your orders, manage your wishlist, and enjoy a faster checkout experience.</p>
        
        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 4px; border-left: 4px solid #c53940; margin: 20px 0;">
            <p style="margin: 0;"><strong>Your Login Details:</strong></p>
            <p style="margin: 5px 0;">Email: ' . htmlspecialchars($email) . '</p>
            <p style="margin: 5px 0;">Password: ' . htmlspecialchars($password) . '</p>
            <p style="margin: 10px 0 0; font-size: 12px; color: #666;"><em>(Please keep this information safe or change your password after logging in)</em></p>
        </div>

        <p style="text-align: center;">
            <a href="' . BASE_URL . '/login.php" class="btn">Login to Your Account</a>
        </p>
        
        <p>If you have any questions, feel free to reply to this email.</p>
    ' . getEmailFooter();
}

/**
 * Generate Order Status Email Template with Tracking
 */
function getOrderStatusEmailTemplate($name, $orderId, $status, $trackingNumber = null) {
    // Determine progress width and active steps
    $progressWidth = '0%';
    $steps = ['pending' => false, 'processing' => false, 'shipped' => false, 'completed' => false];
    
    switch($status) {
        case 'pending':
            $progressWidth = '12%';
            $steps['pending'] = true;
            break;
        case 'processing':
            $progressWidth = '38%';
            $steps['pending'] = true;
            $steps['processing'] = true;
            break;
        case 'shipped':
            $progressWidth = '63%';
            $steps['pending'] = true;
            $steps['processing'] = true;
            $steps['shipped'] = true;
            break;
        case 'completed':
            $progressWidth = '100%';
            $steps['pending'] = true;
            $steps['processing'] = true;
            $steps['shipped'] = true;
            $steps['completed'] = true;
            break;
        case 'cancelled':
            $progressWidth = '0%';
            break;
    }

    $trackingHtml = '';
    if ($status !== 'cancelled') {
        $trackingHtml = '
        <div class="tracking-container">
            <div class="tracking-bar">
                <div class="progress-fill" style="width: ' . $progressWidth . ';"></div>
                
                <div class="step ' . ($steps['pending'] ? 'completed' : '') . '">
                    <div class="step-circle">&#10003;</div>
                    <div class="step-label">Pending</div>
                </div>
                <div class="step ' . ($steps['processing'] ? 'completed' : '') . '">
                    <div class="step-circle">' . ($steps['processing'] ? '&#10003;' : '2') . '</div>
                    <div class="step-label">Processing</div>
                </div>
                <div class="step ' . ($steps['shipped'] ? 'completed' : '') . '">
                    <div class="step-circle">' . ($steps['shipped'] ? '&#10003;' : '3') . '</div>
                    <div class="step-label">Shipped</div>
                </div>
                <div class="step ' . ($steps['completed'] ? 'completed' : '') . '">
                    <div class="step-circle">' . ($steps['completed'] ? '&#10003;' : '4') . '</div>
                    <div class="step-label">Delivered</div>
                </div>
            </div>
        </div>';
    }

    $message = '';
    if ($status == 'processing') {
        $message = 'Your order is currently being processed by our team. We are packing your items with care.';
    } elseif ($status == 'shipped') {
        $message = 'Great news! Your order has been shipped and is on its way to you.';
        if ($trackingNumber) {
            $message .= '<br><strong>Tracking Number:</strong> ' . htmlspecialchars($trackingNumber);
        }
    } elseif ($status == 'completed') {
        $message = 'Your order has been delivered. We hope you enjoy your purchase!';
    } elseif ($status == 'cancelled') {
        $message = 'Your order has been cancelled. If you did not request this, please contact us immediately.';
    } else {
        $message = 'Your order status has been updated.';
    }

    return getEmailHeader('Order Update: #' . $orderId) . '
        <p>Dear ' . htmlspecialchars($name) . ',</p>
        <p>' . $message . '</p>
        
        ' . $trackingHtml . '

        <p style="text-align: center;">
            <a href="' . BASE_URL . '/order-details.php?id=' . $orderId . '" class="btn">View Order Details</a>
        </p>
    ' . getEmailFooter();
}

/**
 * Generate HTML email template for Customer Invoice
 */
function getOrderEmailTemplate($orderId, $customerName, $items, $total, $shipping, $address, $city, $paymentMethod, $accessToken = null) {
    $itemsHtml = '';
    foreach ($items as $item) {
        $itemsHtml .= '
        <tr>
            <td>' . htmlspecialchars($item['name']) . '</td>
            <td>' . $item['quantity'] . '</td>
            <td>' . number_format($item['price']) . '</td>
            <td>' . number_format($item['price'] * $item['quantity']) . '</td>
        </tr>';
    }

    $paymentMethodLabel = ($paymentMethod === 'cod') ? 'Cash on Delivery' : 'Mobile Money';
    
    $orderLink = BASE_URL . '/order-details.php?id=' . $orderId;
    if ($accessToken) {
        $orderLink .= '&token=' . $accessToken;
    }

    return getEmailHeader('Order Confirmation') . '
        <p>Dear ' . htmlspecialchars($customerName) . ',</p>
        <p>Thank you for your order! We have received your request and are processing it.</p>
        
        <h3>Order Details (#' . $orderId . ')</h3>
        <p><strong>Date:</strong> ' . date('F j, Y, g:i a') . '</p>
        <p><strong>Payment Method:</strong> ' . $paymentMethodLabel . '</p>
        <p><strong>Delivery Address:</strong><br>' . nl2br(htmlspecialchars($address)) . ', ' . htmlspecialchars($city) . '</p>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                ' . $itemsHtml . '
            </tbody>
        </table>

        <div class="totals">
            <p>Subtotal: Ush ' . number_format($total - $shipping) . '</p>
            <p>Shipping: Ush ' . number_format($shipping) . '</p>
            <h3>Total: Ush ' . number_format($total) . '</h3>
        </div>

        <p>We will contact you shortly to confirm delivery.</p>
        
        <p style="text-align: center;">
            <a href="' . $orderLink . '" class="btn">View Order Details</a>
        </p>
    ' . getEmailFooter();
}

/**
 * Generate HTML email template for Admin
 */
function getAdminOrderEmailTemplate($orderId, $customerName, $total) {
    return getEmailHeader('New Order Received') . '
        <p><strong>Order ID:</strong> #' . $orderId . '</p>
        <p><strong>Customer:</strong> ' . htmlspecialchars($customerName) . '</p>
        <p><strong>Total Amount:</strong> Ush ' . number_format($total) . '</p>
        <p>A new order has been placed. Please log in to the admin panel to view details and process the order.</p>
        <p style="text-align: center; margin-top: 20px;">
            <a href="' . BASE_URL . '/admin/orders.php" class="btn">View Orders</a>
        </p>
    ' . getEmailFooter();
}
/**
 * Generate Marketing Email Template
 */
/**
 * Generate Marketing Email Template
 */
/**
 * Generate Marketing Email Template
 */
function getMarketingEmailTemplate($subject, $customMessage, $products) {
    $productsHtml = '';
    
    foreach ($products as $product) {
        $priceHtml = '';
        if ($product['compare_price'] > $product['price']) {
            $priceHtml = '
                <span style="text-decoration: line-through; color: #999; font-size: 14px;">Ush ' . number_format($product['compare_price']) . '</span>
                <br>
                <span style="color: #c53940; font-weight: bold; font-size: 18px;">Ush ' . number_format($product['price']) . '</span>
            ';
        } else {
            $priceHtml = '<span style="color: #333; font-weight: bold; font-size: 18px;">Ush ' . number_format($product['price']) . '</span>';
        }

        $imagePath = EMAIL_IMAGE_BASE_URL . '/uploads/products/' . $product['image1'];
        $productLink = BASE_URL . '/product.php?id=' . $product['id'];

        // Mobile-optimized card style
        $productsHtml .= '
        <div class="product-card" style="display: inline-block; width: 100%; max-width: 280px; vertical-align: top; margin: 0 5px 20px; text-align: center; padding: 0; box-sizing: border-box; background: #fff; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="height: 220px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #fff; border-bottom: 1px solid #f0f0f0;">
                <img src="' . $imagePath . '" alt="' . htmlspecialchars($product['name']) . '" style="width: 100%; height: 100%; object-fit: contain; padding: 10px;">
            </div>
            <div style="padding: 15px;">
                <h3 style="font-size: 16px; margin: 0 0 10px; height: 44px; overflow: hidden; color: #333; line-height: 1.4;">' . htmlspecialchars($product['name']) . '</h3>
                <div style="margin-bottom: 15px;">' . $priceHtml . '</div>
                <a href="' . $productLink . '" style="display: block; width: 100%; padding: 12px 0; background-color: #c53940; color: white; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: bold;">View Product</a>
            </div>
        </div>';
    }

    // Close the default content div to control padding manually
    return getEmailHeader($subject) . '
        </div> 
        
        <!-- Message Card Wrapper -->
        <div style="padding: 15px;">
            <div style="background: white; padding: 25px; border-radius: 8px; border: 1px solid #e6e6e6; box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26); text-align: left;">
                <div style="font-size: 16px; color: #333; line-height: 1.8;">
                    ' . $customMessage . '
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div style="background-color: #f8f9fa; padding: 30px 10px; margin-bottom: 20px;">
            <h3 style="text-align: center; color: #c53940; margin-top: 0; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 1px; font-size: 18px;">Top Deals For You</h3>
            <div style="text-align: center;">
                ' . $productsHtml . '
            </div>
        </div>

        <div style="padding: 0 20px 30px;">
            <p style="text-align: center; margin: 0;">
                <a href="' . BASE_URL . '/products.php" class="btn" style="padding: 15px 30px; font-size: 16px; display: inline-block; width: auto;">Shop All Deals</a>
            </p>
    ' . getEmailFooter();
}

/**
 * Send Marketing Email
 */
function sendMarketingEmail($to, $subject, $message, $products) {
    $body = getMarketingEmailTemplate($subject, $message, $products);
    return sendOrderEmail($to, $subject, $body);
}

/**
 * Generate Password Reset Code Email Template
 */
function getPasswordResetCodeEmailTemplate($name, $code) {
    return getEmailHeader('Password Reset Request') . '
        <p>Hello ' . htmlspecialchars($name) . ',</p>
        <p>You requested to reset your password. Here is your verification code:</p>
        
        <div style="background: #f8f9fa; border: 2px solid #c53940; border-radius: 8px; padding: 30px; text-align: center; margin: 30px 0;">
            <h1 style="margin: 0; font-size: 48px; letter-spacing: 12px; color: #c53940; font-weight: bold;">' . $code . '</h1>
        </div>
        
        <p><strong>This code will expire in 15 minutes.</strong></p>
        
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Security Notice:</strong> If you didn\'t request this code, please ignore this email. Never share this code with anyone.</p>
        </div>
        
        <p>Best regards,<br>' . SITE_NAME . ' Team<br>Kampala, Uganda</p>
    ' . getEmailFooter();
}

/**
 * Generate Password Changed Confirmation Email Template
 */
function getPasswordChangedEmailTemplate($name, $changeDate) {
    return getEmailHeader('Password Changed Successfully') . '
        <p>Hello ' . htmlspecialchars($name) . ',</p>
        <p>Your password has been changed successfully on <strong>' . $changeDate . '</strong>.</p>
        
        <div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #155724;"><strong>✓ Password Updated</strong><br>You can now use your new password to log in to your account.</p>
        </div>
        
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Security Notice:</strong> If you didn\'t make this change, please contact us immediately at ' . ADMIN_EMAIL . '</p>
        </div>
        
        <p>For your security, we recommend:</p>
        <ul style="line-height: 1.8;">
            <li>Using a unique password for your account</li>
            <li>Not sharing your password with anyone</li>
            <li>Changing your password regularly</li>
        </ul>
        
        <p>Best regards,<br>' . SITE_NAME . ' Team<br>Kampala, Uganda</p>
    ' . getEmailFooter();
}

/**
 * Send Password Reset Code Email
 */
function sendPasswordResetCodeEmail($email, $name, $code) {
    $subject = 'Password Reset Code - ' . SITE_NAME;
    $body = getPasswordResetCodeEmailTemplate($name, $code);
    return sendOrderEmail($email, $subject, $body);
}

/**
 * Send Password Changed Confirmation Email
 */
function sendPasswordChangedEmail($email, $name) {
    $changeDate = date('F j, Y \\a\\t g:i A');
    $subject = 'Password Changed Successfully - ' . SITE_NAME;
    $body = getPasswordChangedEmailTemplate($name, $changeDate);
    return sendOrderEmail($email, $subject, $body);
}

/**
 * Generic Send Email function (Wrapper for sendOrderEmail)
 * Matches signature used in edit-order.php
 */
function sendEmail($to, $name, $subject, $body) {
    // Wrap the body in the standard template
    $fullBody = getEmailHeader($subject) . $body . getEmailFooter();
    return sendOrderEmail($to, $subject, $fullBody);
}
