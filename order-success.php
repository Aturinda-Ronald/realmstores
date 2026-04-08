<?php
require_once 'config.php';
require_once 'includes/functions.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId === 0) {
    header('Location: ' . BASE_URL);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
    <style>
        .success-page {
            padding: 80px 0;
            text-align: center;
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);
            max-width: 500px;
            width: 90%;
            margin: 0 auto;
        }
        .success-icon {
            color: #28a745;
            margin-bottom: 20px;
        }
        .order-id {
            font-size: 18px;
            color: #666;
            margin: 10px 0 30px;
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
            </div>
        </div>
    </header>

    <div class="success-page">
        <div class="container">
            <div class="success-card">
                <div class="success-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h1>Thank You!</h1>
                <p>Your order has been placed successfully.</p>
                <p class="order-id">Order ID: #<?php echo $orderId; ?></p>
                <p>We have sent a confirmation email to your inbox.</p>
                <div style="margin-top: 30px;">
                    <a href="<?php echo BASE_URL; ?>/products.php" class="btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>Copyright &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    <?php include 'includes/support_widget.php'; ?>
</body>
</html>
