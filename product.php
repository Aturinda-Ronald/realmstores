<?php
require_once 'config.php';
require_once 'includes/functions.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: ' . BASE_URL);
    exit;
}

$product = getProductById($pdo, $productId);

if (!$product) {
    header('Location: ' . BASE_URL);
    exit;
}

$variants = getVariants($product['variant_values']);
$images = array_filter([$product['image1'], $product['image2'], $product['image3']]);
$relatedProducts = getRelatedProducts($pdo, $productId, $product['category_id'], 4);
$hotDeals = getHotDeals($pdo, 4);
$recentlyViewed = getRecentlyViewed($pdo, 8, $productId);

// Track this product as viewed
addToRecentlyViewed($pdo, $productId);

$inWishlist = false;
if (isset($_SESSION['user_id'])) {
    $inWishlist = isInWishlist($pdo, $_SESSION['user_id'], $productId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($product['name']); ?> - Realm</title>
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
                        <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="Realm" class="logo-img logo-img-nav">
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

        </div>
    </header>

    <nav class="main-nav">
        <div class="container">
            <ul>
                <li><a href="<?php echo BASE_URL; ?>">HOME</a></li>
                <li><a href="<?php echo BASE_URL; ?>/products.php" class="active">ALL PRODUCTS</a></li>
                <li><a href="<?php echo BASE_URL; ?>/contact.php">CONTACT US</a></li>
                <li><a href="<?php echo BASE_URL; ?>/shipping.php">SHIPPING</a></li>
            </ul>
        </div>
    </nav>

    <div class="breadcrumb">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>">Home</a> /
            <span><?php echo escape($product['category_name']); ?></span> /
            <span><?php echo escape($product['name']); ?></span>
        </div>
    </div>

    <section class="product-detail">
        <div class="container">
            <div class="product-detail-grid">
                <div class="product-gallery">
                    <div class="main-image">
                        <?php if ($product['is_sponsored']): ?>
                            <span class="product-badge sponsored" style="position: absolute; top: 10px; left: 10px; z-index: 10;">SPONSORED</span>
                        <?php elseif ($product['is_hot']): ?>
                            <span class="product-badge hot" style="position: absolute; top: 10px; left: 10px; z-index: 10;">HOT</span>
                        <?php elseif (strtotime($product['created_at']) > strtotime('-7 days')): ?>
                            <span class="product-badge new" style="background: #28a745; position: absolute; top: 10px; left: 10px; z-index: 10;">NEW</span>
                        <?php elseif ($product['featured']): ?>
                            <span class="product-badge hot" style="position: absolute; top: 10px; left: 10px; z-index: 10;">FEATURED</span>
                        <?php endif; ?>
                        <img id="mainImage" src="<?php echo count($images) > 0 ? UPLOAD_URL . $images[0] : 'https://via.placeholder.com/600x600'; ?>" alt="<?php echo escape($product['name']); ?>">
                    </div>

                    <?php if (count($images) > 1): ?>
                    <div class="thumbnail-gallery">
                        <?php foreach ($images as $index => $image): ?>
                        <img src="<?php echo UPLOAD_URL . $image; ?>"
                             alt="View <?php echo $index + 1; ?>"
                             class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                             onclick="changeMainImage('<?php echo UPLOAD_URL . $image; ?>', this)">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="product-details">
                    <h1><?php echo escape($product['name']); ?></h1>

                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <?php echo renderStarRating($product['rating'], '20px'); ?>
                        <span style="color: #666; font-size: 14px;">(<?php echo number_format($product['rating'], 1); ?>)</span>
                    </div>

                    <div class="product-price-large">
                        <?php if ($product['compare_price'] > $product['price']): ?>
                        <span style="text-decoration: line-through; color: #999; font-size: 20px; margin-right: 10px;">Ush <?php echo number_format($product['compare_price']); ?></span>
                        <?php endif; ?>
                        <?php echo formatPrice($product['price']); ?>
                    </div>

                    <div class="product-category">
                        Category: <strong><?php echo escape($product['category_name']); ?></strong>
                    </div>

                    <?php if (count($variants) > 0): ?>
                    <div class="product-variants-selector">
                        <label><?php echo escape($product['variant_name'] ?: 'Options'); ?>:</label>
                        <select class="variant-select">
                            <option value="">Select <?php echo escape($product['variant_name'] ?: 'option'); ?></option>
                            <?php foreach ($variants as $variant): ?>
                            <option value="<?php echo escape($variant); ?>"><?php echo escape($variant); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="product-description">
                        <h3>Description</h3>
                        <p><?php echo nl2br(escape($product['description'])); ?></p>
                    </div>

                    <div class="product-actions">
                        <button class="btn-primary" id="addToCartBtn" onclick="addToCartWithVariant(<?php echo $product['id']; ?>)">Add to Cart</button>
                        <button class="btn-secondary" id="wishlistBtn" onclick="toggleWishlist(<?php echo $product['id']; ?>)">
                            <?php echo $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products -->
    <?php if (count($relatedProducts) > 0): ?>
    <div class="container">
        <section class="jumia-section">
            <div class="jumia-header" style="background: #d32f2f;">
                <h2>Related Products</h2>
                <a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo $product['category_id']; ?>" class="view-all">See All →</a>
            </div>

            <div class="products-grid">
                <?php foreach ($relatedProducts as $relatedProduct): 
                    $discount = 0;
                    if ($relatedProduct['compare_price'] > $relatedProduct['price']) {
                        $discount = round((($relatedProduct['compare_price'] - $relatedProduct['price']) / $relatedProduct['compare_price']) * 100);
                    }
                ?>
                <div class="product-card">
                    <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $relatedProduct['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                        <div class="product-image-wrapper">
                            <img src="<?php echo UPLOAD_URL . $relatedProduct['image1']; ?>" alt="<?php echo escape($relatedProduct['name']); ?>" class="main-img" loading="lazy">
                            <?php if (!empty($relatedProduct['image2'])): ?>
                                <img src="<?php echo UPLOAD_URL . $relatedProduct['image2']; ?>" alt="<?php echo escape($relatedProduct['name']); ?>" class="hover-img" loading="lazy">
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?php echo escape($relatedProduct['name']); ?></h3>
                            <div style="margin-bottom: 4px; font-size: 12px;">
                                <?php echo renderStarRating($relatedProduct['rating']); ?>
                            </div>
                            <div class="price-block">
                                <span class="current-price">Ush <?php echo number_format($relatedProduct['price']); ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="old-price">Ush <?php echo number_format($relatedProduct['compare_price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $relatedProduct['id']; ?>, event)">Add To Cart</button>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <?php endif; ?>

    <!-- Hot Deals -->
    <?php if (count($hotDeals) > 0): ?>
    <div class="container">
        <section class="jumia-section">
            <div class="jumia-header" style="background: #ffa000;">
                <h2>Hot Deals</h2>
                <a href="<?php echo BASE_URL; ?>/products.php?featured=1" class="view-all">See All →</a>
            </div>

            <div class="products-grid">
                <?php foreach ($hotDeals as $deal): 
                    $discount = 0;
                    if ($deal['compare_price'] > $deal['price']) {
                        $discount = round((($deal['compare_price'] - $deal['price']) / $deal['compare_price']) * 100);
                    }
                ?>
                <div class="product-card">
                    <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $deal['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                        <div class="product-image-wrapper">
                            <img src="<?php echo UPLOAD_URL . $deal['image1']; ?>" alt="<?php echo escape($deal['name']); ?>" class="main-img" loading="lazy">
                            <?php if (!empty($deal['image2'])): ?>
                                <img src="<?php echo UPLOAD_URL . $deal['image2']; ?>" alt="<?php echo escape($deal['name']); ?>" class="hover-img" loading="lazy">
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?php echo escape($deal['name']); ?></h3>
                            <div style="margin-bottom: 4px; font-size: 12px;">
                                <?php echo renderStarRating($deal['rating']); ?>
                            </div>
                            <div class="price-block">
                                <span class="current-price">Ush <?php echo number_format($deal['price']); ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="old-price">Ush <?php echo number_format($deal['compare_price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $deal['id']; ?>, event)">Add To Cart</button>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <?php endif; ?>

    <!-- Recently Viewed -->
    <?php if (count($recentlyViewed) > 0): ?>
    <div class="container">
        <section class="jumia-section">
            <div class="jumia-header" style="background: #616161;">
                <h2>Recently Viewed</h2>
            </div>

            <div class="products-grid">
                <?php foreach ($recentlyViewed as $recentProduct): 
                    $discount = 0;
                    if ($recentProduct['compare_price'] > $recentProduct['price']) {
                        $discount = round((($recentProduct['compare_price'] - $recentProduct['price']) / $recentProduct['compare_price']) * 100);
                    }
                ?>
                <div class="product-card">
                    <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $recentProduct['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                        <div class="product-image-wrapper">
                            <img src="<?php echo UPLOAD_URL . $recentProduct['image1']; ?>" alt="<?php echo escape($recentProduct['name']); ?>" class="main-img" loading="lazy">
                            <?php if (!empty($recentProduct['image2'])): ?>
                                <img src="<?php echo UPLOAD_URL . $recentProduct['image2']; ?>" alt="<?php echo escape($recentProduct['name']); ?>" class="hover-img" loading="lazy">
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?php echo escape($recentProduct['name']); ?></h3>
                            <div style="margin-bottom: 4px; font-size: 12px;">
                                <?php echo renderStarRating($recentProduct['rating']); ?>
                            </div>
                            <div class="price-block">
                                <span class="current-price">Ush <?php echo number_format($recentProduct['price']); ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="old-price">Ush <?php echo number_format($recentProduct['compare_price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $recentProduct['id']; ?>, event)">Add To Cart</button>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <?php endif; ?>

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
                    <h3>CATEGORIES</h3>
                    <ul>
                        <?php
                        $categories = getCategories($pdo);
                        foreach (array_slice($categories, 0, 6) as $cat):
                        ?>
                        <li><a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo $cat['id']; ?>"><?php echo escape($cat['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>MY ACCOUNT</h3>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/cart.php">Shopping Cart</a></li>
                        <li><a href="#">My Orders</a></li>
                        <li><a href="#">Wishlist</a></li>
                        <li><a href="#">Account Settings</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>Copyright &copy; 2025 Realm. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/js/script.js"></script>
    <script>
    function changeMainImage(src, element) {
        document.getElementById('mainImage').src = src;
        document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
        element.classList.add('active');
    }

    function addToCartWithVariant(productId) {
        const variantSelect = document.querySelector('.variant-select');
        const variant = variantSelect ? variantSelect.value : null;
        const btn = document.getElementById('addToCartBtn');
        const originalText = btn.textContent;

        // Check if variant is required but not selected
        <?php if (count($variants) > 0): ?>
        if (!variant) {
            alert('Please select a <?php echo escape($product['variant_name'] ?: 'variant'); ?>');
            return;
        }
        <?php endif; ?>

        btn.textContent = 'Adding...';
        btn.disabled = true;

        fetch('<?php echo BASE_URL; ?>/api/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1,
                variant: variant
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.textContent = 'Added to Cart!';
                // Update cart count in header
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge) {
                    cartBadge.textContent = data.cart_count;
                } else if (data.cart_count > 0) {
                    const cartIconWrapper = document.querySelector('.cart-icon-wrapper');
                    if (cartIconWrapper) {
                        const badge = document.createElement('span');
                        badge.className = 'cart-badge';
                        badge.textContent = data.cart_count;
                        cartIconWrapper.appendChild(badge);
                    }
                }
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                }, 1500);
            } else {
                btn.textContent = 'Error!';
                btn.disabled = false;
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 1500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.textContent = 'Error!';
            btn.disabled = false;
            setTimeout(() => {
                btn.textContent = originalText;
            }, 1500);
        });
    }

    // Add to Cart Function for related products
    function addToCart(productId, event) {
        event.preventDefault();
        event.stopPropagation();

        const btn = event.target;
        const originalText = btn.textContent;

        // First, check if product is already in cart
        const baseUrl = '<?php echo BASE_URL; ?>';
        fetch(baseUrl + '/api/check_cart_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId })
        })
        .then(response => response.json())
        .then(checkData => {
            if (checkData.success && checkData.in_cart) {
                // Product already in cart - replace button with quantity selector
                replaceWithQuantitySelector(btn, productId, checkData.cart_item_id, checkData.quantity);
                return;
            }
            
            // Product not in cart - proceed to add it
            btn.textContent = 'Adding...';
            btn.classList.add('adding');

            fetch(baseUrl + '/api/add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.textContent = 'Added!';
                    
                    // Update cart count and total
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                    } else if (data.cart_count > 0) {
                        const cartIconWrapper = document.querySelector('.cart-icon-wrapper');
                        if (cartIconWrapper) {
                            const badge = document.createElement('span');
                            badge.className = 'cart-badge';
                            badge.textContent = data.cart_count;
                            cartIconWrapper.appendChild(badge);
                        }
                    }
                    
                    const cartTotal = document.querySelector('.cart-total');
                    if (cartTotal && data.cart_total !== undefined) {
                        cartTotal.textContent = 'Ush ' + Number(data.cart_total).toLocaleString('en-US');
                    }
                    
                    // Replace with quantity selector
                    setTimeout(() => {
                        fetch(baseUrl + '/api/check_cart_status.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ product_id: productId })
                        })
                        .then(r => r.json())
                        .then(statusData => {
                            if (statusData.success && statusData.in_cart) {
                                replaceWithQuantitySelector(btn, productId, statusData.cart_item_id, statusData.quantity);
                            }
                        });
                    }, 800);
                } else {
                    btn.textContent = 'Error!';
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.classList.remove('adding');
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.textContent = 'Error!';
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('adding');
                }, 1500);
            });
        })
        .catch(error => {
            console.error('Cart check error:', error);
            btn.textContent = 'Error!';
            setTimeout(() => {
                btn.textContent = originalText;
            }, 1500);
        });
    }

    function replaceWithQuantitySelector(buttonElement, productId, cartItemId, quantity) {
        const selector = document.createElement('div');
        selector.className = 'quantity-selector';
        selector.dataset.cartItemId = cartItemId;
        selector.dataset.productId = productId;
        selector.dataset.quantity = quantity;
        
        selector.innerHTML = `
            <div class="qty-btn minus">-</div>
            <div class="qty-display">${quantity}</div>
            <div class="qty-btn plus">+</div>
        `;
        
        const minusBtn = selector.querySelector('.minus');
        const plusBtn = selector.querySelector('.plus');
        
        minusBtn.addEventListener('click', function() {
            updateQuantityInline(cartItemId, quantity - 1, selector, productId);
        });
        
        plusBtn.addEventListener('click', function() {
            updateQuantityInline(cartItemId, quantity + 1, selector, productId);
        });
        
        buttonElement.parentElement.replaceChild(selector, buttonElement);
    }

    function updateQuantityInline(cartItemId, newQuantity, selectorElement, productId) {
        if (newQuantity < 0) return;
        
        const baseUrl = '<?php echo BASE_URL; ?>';
        fetch(baseUrl + '/api/update_cart_quantity.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart_item_id: cartItemId, quantity: newQuantity })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (newQuantity === 0) {
                    const addBtn = document.createElement('button');
                    addBtn.className = 'add-to-cart-btn';
                    addBtn.textContent = 'Add To Cart';
                    addBtn.onclick = function(e) { addToCart(productId, e); };
                    selectorElement.parentElement.replaceChild(addBtn, selectorElement);
                } else {
                    selectorElement.dataset.quantity = newQuantity;
                    selectorElement.querySelector('.qty-display').textContent = newQuantity;
                    
                    const minusBtn = selectorElement.querySelector('.minus');
                    const plusBtn = selectorElement.querySelector('.plus');
                    
                    minusBtn.onclick = () => updateQuantityInline(cartItemId, newQuantity - 1, selectorElement, productId);
                    plusBtn.onclick = () => updateQuantityInline(cartItemId, newQuantity + 1, selectorElement, productId);
                }
                
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge) {
                    if (data.cart_count > 0) {
                        cartBadge.textContent = data.cart_count;
                    } else {
                        cartBadge.remove();
                    }
                } else if (data.cart_count > 0) {
                    const cartIconWrapper = document.querySelector('.cart-icon-wrapper');
                    if (cartIconWrapper) {
                        const badge = document.createElement('span');
                        badge.className = 'cart-badge';
                        badge.textContent = data.cart_count;
                        cartIconWrapper.appendChild(badge);
                    }
                }
                
                const cartTotal = document.querySelector('.cart-total');
                if (cartTotal) {
                    cartTotal.textContent = 'Ush ' + Number(data.cart_total).toLocaleString('en-US');
                }
            }
        })
        .catch(err => console.error('Update quantity failed:', err));
    }

    function toggleWishlist(productId) {
        const btn = document.getElementById('wishlistBtn');
        const originalText = btn.textContent;
        const isRemoving = btn.textContent.trim() === 'Remove from Wishlist';
        const action = isRemoving ? 'remove' : 'add';

        btn.disabled = true;
        btn.textContent = 'Processing...';

        fetch('<?php echo BASE_URL; ?>/api/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: action,
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                
                if (data.status === 'added') {
                    btn.textContent = 'Remove from Wishlist';
                } else {
                    btn.textContent = 'Add to Wishlist';
                }
            } else {
                alert(data.message || 'Error updating wishlist');
                btn.textContent = originalText;
            }
            btn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
            btn.textContent = originalText;
            btn.disabled = false;
        });
    }
    </script>
    <?php include 'includes/mobile_nav.php'; ?>
    <?php include 'includes/support_widget.php'; ?>
</body>
</html>
