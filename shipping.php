<?php
require_once 'config.php';
require_once 'includes/functions.php';
$promotions = getActivePromotions($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Policy - Realm</title>
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
                    <a href="<?php echo BASE_URL; ?>/login.php" class="account-section">
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
    </header>

    <nav class="main-nav">
        <div class="container">
            <ul>
                <li><a href="<?php echo BASE_URL; ?>">HOME</a></li>
                <li><a href="<?php echo BASE_URL; ?>/products.php">ALL PRODUCTS</a></li>
                <li><a href="<?php echo BASE_URL; ?>/contact.php">CONTACT US</a></li>
                <li><a href="<?php echo BASE_URL; ?>/shipping.php" class="active">SHIPPING</a></li>
            </ul>
        </div>
    </nav>

    <!-- Promotional Slider -->
    <?php if (count($promotions) > 0): ?>
    <section class="promo-slider-section">
        <div class="container">
            <div class="promo-slider">
                <div class="slider-container" id="sliderContainer">
                    <?php foreach ($promotions as $promo): ?>
                    <div class="slide">
                        <img src="<?php echo BASE_URL; ?>/uploads/promotions/<?php echo $promo['image']; ?>" alt="<?php echo escape($promo['title']); ?>" onerror="this.src='https://via.placeholder.com/1200x400/667eea/ffffff?text=<?php echo urlencode($promo['title']); ?>'">
                        <div class="slide-content">
                            <h2><?php echo escape($promo['title']); ?></h2>
                            <p><?php echo escape($promo['description']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($promotions) > 1): ?>
                <button class="slider-nav prev" onclick="moveSlide(-1)">&#10094;</button>
                <button class="slider-nav next" onclick="moveSlide(1)">&#10095;</button>

                <div class="slider-dots">
                    <?php foreach ($promotions as $index => $promo): ?>
                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="currentSlide(<?php echo $index; ?>)"></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="page-content" style="padding: 40px 0;">
        <div class="container">
            <h1 style="font-size: 32px; color: #333; margin-bottom: 40px;">Shipping & Delivery Policy</h1>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 40px;">
                <!-- In-Store Collection -->
                <div style="background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);">
                    <div style="width: 50px; height: 50px; background: #f5f5f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <h2 style="font-size: 18px; color: #333; margin-bottom: 12px; font-weight: 600;">In-Store Collection</h2>
                    <p style="color: #666; line-height: 1.6; margin: 0; font-size: 14px;">We offer convenient in-store collection services. Simply place your order online and collect it from our store at your convenience. We'll notify you when your order is ready.</p>
                </div>

                <!-- Delivery Service -->
                <div style="background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);">
                    <div style="width: 50px; height: 50px; background: #f5f5f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                            <rect x="1" y="3" width="15" height="13"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                    </div>
                    <h2 style="font-size: 18px; color: #333; margin-bottom: 12px; font-weight: 600;">Delivery Service</h2>
                    <p style="color: #666; line-height: 1.6; margin: 0; font-size: 14px;">We provide reliable delivery services within Kampala and surrounding areas. Our delivery team ensures your products arrive safely and on time.</p>
                </div>

                <!-- Delivery Timeframes -->
                <div style="background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);">
                    <div style="width: 50px; height: 50px; background: #f5f5f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <h2 style="font-size: 18px; color: #333; margin-bottom: 16px; font-weight: 600;">Delivery Timeframes</h2>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="color: #666; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px;">Within Kampala: 1-2 business days</li>
                        <li style="color: #666; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px;">Outside Kampala: 3-5 business days</li>
                        <li style="color: #666; padding: 8px 0; font-size: 14px;">Remote areas: 5-7 business days</li>
                    </ul>
                </div>

                <!-- Shipping Costs -->
                <div style="background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);">
                    <div style="width: 50px; height: 50px; background: #f5f5f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <h2 style="font-size: 18px; color: #333; margin-bottom: 12px; font-weight: 600;">Shipping Costs</h2>
                    <p style="color: #666; line-height: 1.6; margin: 0; font-size: 14px;">Shipping costs are calculated based on the delivery location and order weight. You can view the estimated shipping cost at checkout before completing your purchase.</p>
                </div>

                <!-- Order Tracking -->
                <div style="background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);">
                    <div style="width: 50px; height: 50px; background: #f5f5f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <h2 style="font-size: 18px; color: #333; margin-bottom: 12px; font-weight: 600;">Order Tracking</h2>
                    <p style="color: #666; line-height: 1.6; margin: 0; font-size: 14px;">Once your order is dispatched, you will receive an email notification. You can also track the status of your order through your account dashboard or via the link in your confirmation email.</p>
                </div>

                <!-- Contact Us -->
                <div style="background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26);">
                    <div style="width: 50px; height: 50px; background: #f5f5f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h2 style="font-size: 18px; color: #333; margin-bottom: 16px; font-weight: 600;">Contact Us</h2>
                    <p style="color: #666; margin-bottom: 12px; font-size: 14px;">For more information about shipping and delivery, please contact us:</p>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="color: #666; padding: 6px 0; font-size: 14px;"><strong>Phone:</strong></li>
                        <li style="color: #666; padding: 6px 0; font-size: 14px;"><?= ADMIN_PHONE_1 ?></li>
                        <li style="color: #666; padding: 6px 0; font-size: 14px;"><?= ADMIN_PHONE_2 ?></li>
                        <li style="color: #666; padding: 6px 0; font-size: 14px; margin-top: 8px;"><strong>Email:</strong></li>
                        <li style="color: #666; padding: 6px 0; font-size: 14px;"><?= ADMIN_EMAIL ?></li>
                    </ul>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="<?php echo BASE_URL; ?>/products.php" style="display: inline-block; background: #c53940; color: white; padding: 14px 40px; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 14px; transition: background 0.3s;" onmouseover="this.style.background='#a82e35';" onmouseout="this.style.background='#c53940';">Continue Shopping</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
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
    // Promotional Slider
    document.addEventListener('DOMContentLoaded', function() {
        let slideIndex = 0;
        let autoSlideTimer;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const container = document.getElementById('sliderContainer');

        if (slides.length === 0 || !container) {
            return;
        }

        function showSlide(n) {
            if (n >= slides.length) slideIndex = 0;
            if (n < 0) slideIndex = slides.length - 1;

            container.style.transform = `translateX(-${slideIndex * 100}%)`;

            dots.forEach(dot => dot.classList.remove('active'));
            if (dots[slideIndex]) {
                dots[slideIndex].classList.add('active');
            }
        }

        window.moveSlide = function(n) {
            clearTimeout(autoSlideTimer);
            slideIndex += n;
            showSlide(slideIndex);
            startAutoSlide();
        }

        window.currentSlide = function(n) {
            clearTimeout(autoSlideTimer);
            slideIndex = n;
            showSlide(slideIndex);
            startAutoSlide();
        }

        function startAutoSlide() {
            clearTimeout(autoSlideTimer);
            autoSlideTimer = setTimeout(() => {
                slideIndex++;
                showSlide(slideIndex);
                startAutoSlide();
            }, 5000);
        }

        showSlide(slideIndex);
        if (slides.length > 1) {
            startAutoSlide();
        }
    });
    </script>
    <?php include 'includes/mobile_nav.php'; ?>
    <?php include 'includes/support_widget.php'; ?>
</body>
</html>
