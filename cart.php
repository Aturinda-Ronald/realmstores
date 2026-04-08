<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $cartItemId = (int)$_POST['cart_item_id'];
                $quantity = (int)$_POST['quantity'];
                updateCartQuantity($pdo, $cartItemId, $quantity);
                header('Location: ' . BASE_URL . '/cart.php');
                exit;
                break;

            case 'remove':
                $cartItemId = (int)$_POST['cart_item_id'];
                removeFromCart($pdo, $cartItemId);
                header('Location: ' . BASE_URL . '/cart.php');
                exit;
                break;
        }
    }
}

// Handle AJAX requests for cart preview
if (isset($_GET['ajax']) && $_GET['ajax'] === 'preview') {
    header('Content-Type: application/json');
    $cartItems = getCartItems($pdo);
    $total = getCartTotal($pdo);
    echo json_encode([
        'items' => $cartItems,
        'total' => $total,
        'count' => getCartCount($pdo)
    ]);
    exit;
}

$cartItems = getCartItems($pdo);
$cartCount = getCartCount($pdo);
$total = getCartTotal($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="page-cart">
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo BASE_URL; ?>/account.php" class="account-section">
                    <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="account-section">
                    <?php endif; ?>
                        <div class="account-icon-circle">
                            <svg class="account-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="account-info">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <span class="greeting">Hello, <?php echo escape($_SESSION['user_name']); ?></span>
                                <a href="<?php echo BASE_URL; ?>/logout.php" class="auth-link">Logout</a>
                            <?php else: ?>
                                <span class="greeting">Hello, Sign in</span>
                                <span class="auth-link">Login / Register</span>
                            <?php endif; ?>
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
                    <a href="<?php echo BASE_URL; ?>/cart.php" class="cart-link active" id="cartLink">
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
                        <div class="cart-info">
                            <span class="cart-label">Your cart:</span>
                            <span class="cart-total">Ush <?php echo number_format($total, 0, '.', ','); ?></span>
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

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>">Home</a> / <span>Shopping Cart</span>
        </div>
    </div>

    <!-- Cart Page -->
    <section class="cart-page">
        <div class="container">
            <h1>Shopping Cart</h1>

            <?php if (count($cartItems) > 0): ?>
            <div class="cart-container">
                <div class="cart-items-section">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td class="product-cell" data-label="Product">
                                    <div class="cart-product">
                                        <img src="<?php echo UPLOAD_URL . $item['image1']; ?>"
                                             alt="<?php echo escape($item['name']); ?>"
                                             onerror="this.src='https://via.placeholder.com/80x80'">
                                        <div class="product-details">
                                            <h3><?php echo escape($item['name']); ?></h3>
                                            <?php if ($item['variant']): ?>
                                            <p class="variant-text"><?php echo escape($item['variant']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="price-cell" data-label="Price">
                                    <?php echo formatPrice($item['price']); ?>
                                </td>
                                <td class="quantity-cell" data-label="Quantity">
                                    <form method="POST" class="quantity-form">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                        <div class="quantity-control">
                                            <button type="button" class="qty-btn" onclick="decrementQty(this)">-</button>
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="99" class="qty-input">
                                            <button type="button" class="qty-btn" onclick="incrementQty(this)">+</button>
                                        </div>
                                        <button type="submit" class="update-btn">Update</button>
                                    </form>
                                </td>
                                <td class="total-cell" data-label="Total">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </td>
                                <td class="action-cell" data-label="Action">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="remove-btn" onclick="return confirm('Remove this item from cart?')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="cart-actions">
                        <a href="<?php echo BASE_URL; ?>/products.php" class="continue-shopping">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            Continue Shopping
                        </a>
                    </div>
                </div>

                <div class="cart-summary">
                    <h2>Cart Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($total); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span class="shipping-note">Calculated at checkout</span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row total-row">
                        <span>Total:</span>
                        <span class="total-amount"><?php echo formatPrice($total); ?></span>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <button type="button" class="checkout-btn" id="checkoutBtn">Proceed to Checkout</button>
                    <?php else: ?>
                    <p class="checkout-notice">Please <a href="<?php echo BASE_URL; ?>/login.php">login</a> to proceed to checkout</p>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="checkout-btn">Login to Checkout</a>
                    <?php endif; ?>

                    <div class="secure-checkout">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Secure Checkout</span>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <h2>Your Cart is Empty</h2>
                <p>Start adding products to your cart!</p>
                <a href="<?php echo BASE_URL; ?>/products.php" class="btn-primary">Browse Products</a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h2>Checkout</h2>
            </div>
            <form id="checkoutForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required value="<?php echo isset($_SESSION['user_name']) ? escape($_SESSION['user_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required placeholder="e.g., 0771234567">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_SESSION['user_email']) ? escape($_SESSION['user_email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="city">City / Town *</label>
                    <input type="text" id="city" name="city" required>
                </div>

                <div class="form-group">
                    <label for="address">Delivery Address *</label>
                    <textarea id="address" name="address" rows="3" required placeholder="Street name, building, etc."></textarea>
                </div>

                <div class="form-group">
                    <label for="notes">Additional Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="2" placeholder="Special delivery instructions..."></textarea>
                </div>

                <div class="payment-methods">
                    <label>Payment Method *</label>
                    <label class="payment-option disabled">
                        <input type="radio" name="payment_method" value="mobile_money" disabled>
                        Mobile Money (Coming Soon)
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cod" checked required>
                        Cash on Delivery
                    </label>
                </div>

                <div id="checkoutError" class="error-message"></div>
                <button type="submit" class="checkout-submit-btn" id="placeOrderBtn">Place Order</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="Realm" class="footer-logo-img">
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
            </div>

            <div class="footer-bottom">
                <p>Copyright &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/js/script.js"></script>
    <script>
    // Quantity update logic
    function incrementQty(btn) {
        const input = btn.parentElement.querySelector('.qty-input');
        input.value = Math.min(99, parseInt(input.value) + 1);
    }

    function decrementQty(btn) {
        const input = btn.parentElement.querySelector('.qty-input');
        input.value = Math.max(1, parseInt(input.value) - 1);
    }

    // Checkout Modal Logic
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('checkoutModal');
        const btn = document.getElementById('checkoutBtn');
        const span = document.getElementsByClassName('close-modal')[0];
        const form = document.getElementById('checkoutForm');
        const errorDiv = document.getElementById('checkoutError');
        const placeOrderBtn = document.getElementById('placeOrderBtn');

        if (btn) {
            btn.onclick = function() {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
        }

        if (span) {
            span.onclick = function() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        if (form) {
            form.onsubmit = function(e) {
                e.preventDefault();
                errorDiv.style.display = 'none';
                placeOrderBtn.disabled = true;
                placeOrderBtn.textContent = 'Processing...';

                const formData = new FormData(form);

                fetch('<?php echo BASE_URL; ?>/api/place_order.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '<?php echo BASE_URL; ?>/order-success.php?id=' + data.order_id;
                    } else {
                        errorDiv.textContent = data.message || 'An error occurred. Please try again.';
                        errorDiv.style.display = 'block';
                        placeOrderBtn.disabled = false;
                        placeOrderBtn.textContent = 'Place Order';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorDiv.textContent = 'An error occurred. Please check your connection.';
                    errorDiv.style.display = 'block';
                    placeOrderBtn.disabled = false;
                    placeOrderBtn.textContent = 'Place Order';
                });
            }
        }
    });

    </script>
    <?php include 'includes/mobile_nav.php'; ?>
    <?php include 'includes/support_widget.php'; ?>
</body>
</html>
