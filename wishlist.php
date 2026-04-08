<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$wishlistItems = getWishlistItems($pdo, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
    <!-- Inline styles removed to use style.css -->
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
                    <div class="account-section">
                        <span class="greeting">Hello, <?php echo escape($_SESSION['user_name']); ?></span>
                        <a href="<?php echo BASE_URL; ?>/logout.php" class="auth-link">Logout</a>
                    </div>
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
                        <div class="cart-info">
                            <span class="cart-label">Your cart:</span>
                            <span class="cart-total">Ush <?php echo number_format(getCartTotal($pdo), 0, '.', ','); ?></span>
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

    <div class="breadcrumb">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>">Home</a> / <span>My Wishlist</span>
        </div>
    </div>

    <section class="wishlist-section" style="padding: 30px 0; min-height: 60vh;">
        <div class="container">
            <h1>My Wishlist</h1>

            <?php if (count($wishlistItems) > 0): ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlistItems as $item): ?>
                <div class="wishlist-card" id="wishlist-item-<?php echo $item['product_id']; ?>">
                    <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $item['product_id']; ?>" class="wishlist-image">
                        <img src="<?php echo UPLOAD_URL . $item['image1']; ?>" alt="<?php echo escape($item['name']); ?>" onerror="this.src='https://via.placeholder.com/300x300'">
                    </a>
                    <div class="wishlist-info">
                        <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $item['product_id']; ?>" class="wishlist-name">
                            <?php echo escape($item['name']); ?>
                        </a>
                        <div style="margin-bottom: 5px;">
                            <?php echo renderStarRating($item['rating']); ?>
                        </div>
                        <div class="wishlist-price">
                            <?php if ($item['compare_price'] > $item['price']): ?>
                            <span style="text-decoration: line-through; color: #999; font-size: 14px; margin-right: 5px;">Ush <?php echo number_format($item['compare_price']); ?></span>
                            <?php endif; ?>
                            <?php echo formatPrice($item['price']); ?>
                        </div>
                        
                        <div class="wishlist-actions">
                            <button class="add-to-cart-btn" style="flex: 1;" onclick="addToCart(<?php echo $item['product_id']; ?>, event)">Add to Cart</button>
                            <button class="btn-remove" onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)" title="Remove from Wishlist">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-wishlist">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1.5" style="margin-bottom: 20px;">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <h2>Your wishlist is empty</h2>
                <p style="color: #666; margin-bottom: 20px;">Save items you love to buy later.</p>
                <a href="<?php echo BASE_URL; ?>/products.php" class="btn-primary" style="display: inline-block; text-decoration: none;">Start Shopping</a>
            </div>
            <?php endif; ?>
        </div>
    </section>

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
                
                 <div class="footer-col">
                    <h3>MY ACCOUNT</h3>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/cart.php">Shopping Cart</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/wishlist.php">My Wishlist</a></li>
                        <li><a href="#">My Orders</a></li>
                        <li><a href="#">Account Settings</a></li>
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
    function removeFromWishlist(productId) {
        if (!confirm('Remove this item from your wishlist?')) return;

        fetch('<?php echo BASE_URL; ?>/api/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'remove',
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove element from DOM
                const item = document.getElementById('wishlist-item-' + productId);
                if (item) {
                    item.remove();
                    
                    // Check if empty
                    const grid = document.querySelector('.wishlist-grid');
                    if (grid && grid.children.length === 0) {
                        location.reload(); // Reload to show empty state
                    }
                }
            } else {
                alert(data.message || 'Error removing item');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
    </script>
    <?php include 'includes/mobile_nav.php'; ?>
    <?php include 'includes/support_widget.php'; ?>
</body>
</html>
