<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/mail_functions.php';

// Check login or token
$token = isset($_GET['token']) ? $_GET['token'] : '';
$isLoggedIn = isset($_SESSION['user_id']);

if (!$isLoggedIn && empty($token)) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $isLoggedIn ? $_SESSION['user_id'] : 0;

if ($orderId === 0) {
    header('Location: index.php'); 
    exit;
}

// Handle Order Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            die("Order not found.");
        }

        // Security check
        $authorized = false;
        if ($isLoggedIn && strtolower($order['email']) === strtolower($_SESSION['user_email'])) {
            $authorized = true;
        } elseif (!empty($token) && $token === $order['access_token']) {
            $authorized = true;
        }

        if (!$authorized) {
             die("Unauthorized access to this order.");
        }

        if ($order['status'] === 'pending') {
            $stmtUpdate = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $stmtUpdate->execute([$orderId]);
            
            // Send cancellation email to admin? Or just update status.
            
            $success = "Order has been cancelled successfully.";
            // Refresh order data
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
        } else {
            $error = "Order cannot be cancelled at this stage.";
        }

    } catch (PDOException $e) {
        $error = "Error updating order: " . $e->getMessage();
    }
} else {
    // Fetch Order Details
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            header('Location: index.php');
            exit;
        }

        // Security Check
        $authorized = false;
        if ($isLoggedIn && strtolower($order['email']) === strtolower($_SESSION['user_email'])) {
            $authorized = true;
        } elseif (!empty($token) && $token === $order['access_token']) {
            $authorized = true;
        }

        if (!$authorized) {
             // Redirect or show error
             if (!$isLoggedIn) {
                 $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
                 header('Location: login.php');
                 exit;
             }
             header('Location: index.php');
             exit;
        }

        // Fetch Order Items
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$orderId]);
        $items = $stmtItems->fetchAll();

    } catch (PDOException $e) {
        die("Error fetching order details: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderId; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
    <style>
        /* Header Styling */
        .header {
            background: white;
            box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
        }

        .order-view-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .order-header {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .order-header h1 {
            font-size: 24px;
            color: #333;
            font-weight: 600;
            margin: 0 0 5px 0;
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .order-status-badge {
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-processing {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-shipped {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .status-completed {
            background: #e8f5e9;
            color: #388e3c;
        }

        .status-cancelled {
            background: #ffebee;
            color: #d32f2f;
        }

        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }

        .info-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2c3d4f;
        }

        .info-card-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .info-row {
            margin-bottom: 12px;
            color: #666;
            line-height: 1.6;
        }

        .info-row strong {
            color: #333;
            font-weight: 600;
        }

        .items-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
            margin-bottom: 20px;
        }

        .items-card h3 {
            font-size: 18px;
            margin: 0 0 20px 0;
            color: #333;
            font-weight: 600;
        }

        .order-items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-items-table thead {
            background: #f8f9fa;
        }

        .order-items-table th {
            text-align: left;
            padding: 15px;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        .order-items-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }

        .order-items-table tr:last-child td {
            border-bottom: none;
        }

        .order-summary {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
            margin-bottom: 20px;
        }

        .order-summary h3 {
            font-size: 18px;
            margin: 0 0 20px 0;
            color: #333;
            font-weight: 600;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 8px 0;
            color: #666;
        }

        .summary-row.total {
            border-top: 2px solid #dee2e6;
            margin-top: 15px;
            padding-top: 15px;
            font-weight: 700;
            font-size: 18px;
            color: #c53940;
        }

        .cancel-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
            margin-bottom: 20px;
            text-align: center;
        }

        .return-link {
            display: inline-block;
            margin-top: 20px;
            color: #2c3d4f;
            text-decoration: none;
            font-weight: 600;
        }

        .return-link:hover {
            color: #1e2b3a;
        }

        @media (max-width: 768px) {
            .order-view-container {
                padding: 10px;
                margin: 20px auto;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 20px;
            }

            .order-header h1 {
                font-size: 20px;
            }

            .order-date {
                font-size: 13px;
            }

            .order-status-badge {
                padding: 8px 16px;
                font-size: 12px;
            }

            .info-cards {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .info-card {
                padding: 20px;
            }

            .info-card-header {
                margin-bottom: 15px;
            }

            .info-card-title {
                font-size: 15px;
            }

            .info-card-icon {
                width: 35px;
                height: 35px;
            }

            .items-card,
            .order-summary,
            .cancel-section {
                padding: 20px;
            }

            .items-card h3,
            .order-summary h3 {
                font-size: 16px;
            }

            /* Mobile-friendly table */
            .order-items-table {
                font-size: 14px;
            }

            .order-items-table thead {
                display: none;
            }

            .order-items-table tbody tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #f0f0f0;
                border-radius: 6px;
                padding: 15px;
                background: #f9f9f9;
            }

            .order-items-table td {
                display: block;
                text-align: left;
                padding: 8px 0;
                border: none;
            }

            .order-items-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #333;
                display: inline-block;
                width: 80px;
            }

            .summary-row {
                font-size: 14px;
            }

            .summary-row.total {
                font-size: 16px;
            }

            .cancel-section p {
                font-size: 14px;
            }

            .btn-primary {
                width: 100%;
                padding: 14px;
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
                <div class="header-right">
                    <a href="<?php echo BASE_URL; ?>/logout.php" class="auth-link">Logout</a>
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

    <div class="container">
        <div class="order-view-container">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Order Header Card -->
            <div class="order-header">
                <div>
                    <h1>Order #<?php echo $order['id']; ?></h1>
                    <p class="order-date">Placed on <?php echo date('F j, Y g:i a', strtotime($order['created_at'])); ?></p>
                </div>
                <span class="order-status-badge status-<?php echo $order['status']; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>

            <!-- Information Cards Grid -->
            <div class="info-cards">
                <!-- Shipping Details Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <h3 class="info-card-title">Shipping Details</h3>
                    </div>
                    <div class="info-row"><strong><?php echo escape($order['customer_name']); ?></strong></div>
                    <div class="info-row"><?php echo nl2br(escape($order['address'])); ?></div>
                    <div class="info-row"><?php echo escape($order['city']); ?></div>
                    <div class="info-row"><strong>Phone:</strong> <?php echo escape($order['phone']); ?></div>
                    <div class="info-row"><strong>Email:</strong> <?php echo escape($order['email']); ?></div>
                </div>

                <!-- Payment Details Card -->
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                        <h3 class="info-card-title">Payment Method</h3>
                    </div>
                    <div class="info-row">
                        <strong><?php echo $order['payment_method'] == 'cod' ? 'Cash on Delivery' : 'Mobile Money'; ?></strong>
                    </div>
                    <?php if ($order['notes']): ?>
                        <div style="margin-top: 20px;">
                            <div style="font-weight: 600; margin-bottom: 8px; color: #333;">Order Notes:</div>
                            <div class="info-row"><?php echo nl2br(escape($order['notes'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items Card -->
            <div class="items-card">
                <h3>Order Items</h3>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td data-label="Product"><?php echo escape($item['product_name']); ?></td>
                            <td data-label="Price">Ush <?php echo number_format($item['unit_price']); ?></td>
                            <td data-label="Qty"><?php echo $item['quantity']; ?></td>
                            <td data-label="Total">Ush <?php echo number_format($item['line_total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Order Summary Card -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>Ush <?php echo number_format($order['subtotal']); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>Ush <?php echo number_format($order['shipping_amount']); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>Ush <?php echo number_format($order['total']); ?></span>
                </div>
            </div>

            <?php if ($order['status'] === 'pending'): ?>
            <!-- Cancel Section Card -->
            <div class="cancel-section">
                <p style="margin-bottom: 15px; color: #666;">Need to change something? You can cancel this order while it is still pending.</p>
                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');" style="display: inline-block;">
                    <button type="submit" name="cancel_order" class="btn-primary" style="background-color: #dc3545; border-color: #dc3545;">Cancel Order</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div style="text-align: center;">
                 <a href="<?php echo BASE_URL; ?>" class="return-link">&larr; Return to Store</a>
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
