<?php
require_once 'config.php';
require_once 'includes/functions.php';

$promotions = getActivePromotions($pdo);
$newArrivals = getNewArrivals($pdo);
$bestSelling = getBestSelling($pdo);
$sponsoredProducts = getSponsoredProducts($pdo);
$recentlyViewed = getRecentlyViewed($pdo);
$cartCount = getCartCount($pdo);
$categories = getCategories($pdo); // Moved this line to maintain logical grouping or original position if not explicitly moved by instruction
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo SITE_TAGLINE; ?></title>
    <meta name="description" content="<?php echo SITE_TAGLINE; ?>. Shop electronics, lighting, tools, and more.">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/bg_decorations.php'; ?>
    
    <!-- Header -->
    <header class="header">
        <div class="top-bar">
            <div class="container">
                <span><?php echo SITE_TAGLINE; ?></span>
            </div>
        </div>

        <div class="main-header">
            <div class="container">
                <div class="logo">
                     <!-- Hamburger removed -->
                    <a href="<?php echo BASE_URL; ?>">
                        <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-img logo-img-nav">
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
            <ul class="nav-links">
                <li><a href="<?php echo BASE_URL; ?>" class="active">HOME</a></li>
                <li><a href="<?php echo BASE_URL; ?>/products.php">ALL PRODUCTS</a></li>
                <li><a href="<?php echo BASE_URL; ?>/contact.php">CONTACT US</a></li>
                <li><a href="<?php echo BASE_URL; ?>/shipping.php">SHIPPING</a></li>
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

    <!-- Promo Features -->
    <section class="promo-banners">
        <div class="container">
            <div class="promo-grid">
                <div class="promo-card">
                    <div class="promo-icon-wrapper">
                        <svg class="promo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                    </div>
                    <div class="promo-content">
                        <h3>In Store Collection</h3>
                        <p>Collect in store service</p>
                    </div>
                </div>
                <div class="promo-card">
                    <div class="promo-icon-wrapper">
                        <svg class="promo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="M9 12l2 2 4-4"></path>
                        </svg>
                    </div>
                    <div class="promo-content">
                        <h3>Shop With Confidence</h3>
                        <p>Minimum of One (1) Year Warranty</p>
                    </div>
                </div>
                <div class="promo-card">
                    <div class="promo-icon-wrapper">
                        <svg class="promo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="promo-content">
                        <h3>Call Center</h3>
                        <p>Online Support</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- New Arrivals -->
    <?php if (count($newArrivals) > 0): ?>
    <div class="container">
        <section class="jumia-section">
            <div class="jumia-header" style="background: #228B22;"> <!-- Forest Green for New Arrivals -->
                <h2>New Arrivals</h2>
                <a href="<?php echo BASE_URL; ?>/products.php?sort=newest" class="view-all">See All →</a>
            </div>

            <div class="products-grid" id="newArrivalsGrid" data-section="new-arrivals" data-products-per-page="12">
                <?php foreach ($newArrivals as $product):
                    $discount = 0;
                    if ($product['compare_price'] > $product['price']) {
                        $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                    }
                ?>
                <div class="product-card">
                    <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                        <div class="product-image-wrapper">
                            <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image1']; ?>" alt="<?php echo escape($product['name']); ?>" class="main-img" loading="lazy">
                            <?php if (!empty($product['image2'])): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image2']; ?>" alt="<?php echo escape($product['name']); ?>" class="hover-img" loading="lazy">
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                            <!-- Product Tags -->
                            <div style="position: absolute; top: 8px; left: 8px; display: flex; flex-direction: column; gap: 4px;">
                                <?php if (isset($product['is_sponsored']) && $product['is_sponsored']): ?>
                                    <span style="background: #ffc107; color: #000; font-size: 10px; padding: 2px 5px; border-radius: 2px; font-weight: bold;">SPONSORED</span>
                                <?php elseif (isset($product['is_hot']) && $product['is_hot']): ?>
                                    <span style="background: #dc3545; color: #fff; font-size: 10px; padding: 2px 5px; border-radius: 2px; font-weight: bold;">HOT</span>
                                <?php elseif (strtotime($product['created_at']) > strtotime('-7 days')): ?>
                                    <span style="background: #28a745; color: #fff; font-size: 10px; padding: 2px 5px; border-radius: 2px; font-weight: bold;">NEW</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?php echo escape($product['name']); ?></h3>
                            <div style="margin-bottom: 4px; font-size: 12px;">
                                <?php echo renderStarRating($product['rating']); ?>
                            </div>
                            <div class="price-block">
                                <span class="current-price">Ush <?php echo number_format($product['price']); ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="old-price">Ush <?php echo number_format($product['compare_price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, event)">Add To Cart</button>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($newArrivals) > 12): ?>
            <div class="pagination-dots" id="newArrivalsDots"></div>
            <?php endif; ?>
        </section>
    </div>
    <?php endif; ?>

    <!-- Best Selling -->
    <!-- Hot Deals / Best Selling -->
    <?php if (count($bestSelling) > 0): ?>
    <div class="container">
        <section class="jumia-section">
            <div class="jumia-header" style="background: #c53940;"> <!-- Red for Best Selling -->
                <h2>Hot Deals</h2>
                <a href="<?php echo BASE_URL; ?>/products.php?featured=1" class="view-all">See All →</a>
            </div>

            <div class="products-grid" id="hotDealsGrid" data-section="hot-deals" data-products-per-page="12">
                <?php foreach ($bestSelling as $product):
                    $discount = 0;
                    if ($product['compare_price'] > $product['price']) {
                        $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                    }
                ?>
                <div class="product-card">
                    <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                        <div class="product-image-wrapper">
                            <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image1']; ?>" alt="<?php echo escape($product['name']); ?>" class="main-img" loading="lazy">
                            <?php if (!empty($product['image2'])): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image2']; ?>" alt="<?php echo escape($product['name']); ?>" class="hover-img" loading="lazy">
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                            <!-- Product Tags -->
                            <div style="position: absolute; top: 8px; left: 8px; display: flex; flex-direction: column; gap: 4px;">
                                <?php if (isset($product['is_sponsored']) && $product['is_sponsored']): ?>
                                    <span style="background: #ffc107; color: #000; font-size: 10px; padding: 2px 5px; border-radius: 2px; font-weight: bold;">SPONSORED</span>
                                <?php elseif (isset($product['is_hot']) && $product['is_hot']): ?>
                                    <span style="background: #dc3545; color: #fff; font-size: 10px; padding: 2px 5px; border-radius: 2px; font-weight: bold;">HOT</span>
                                <?php elseif (isset($product['featured']) && $product['featured']): ?>
                                    <span style="background: #ff6b35; color: #fff; font-size: 10px; padding: 2px 5px; border-radius: 2px; font-weight: bold;">FEATURED</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?php echo escape($product['name']); ?></h3>
                            <div style="margin-bottom: 4px; font-size: 12px;">
                                <?php echo renderStarRating($product['rating']); ?>
                            </div>
                            <div class="price-block">
                                <span class="current-price">Ush <?php echo number_format($product['price']); ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="old-price">Ush <?php echo number_format($product['compare_price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, event)">Add To Cart</button>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($bestSelling) > 12): ?>
            <div class="pagination-dots" id="hotDealsDots"></div>
            <?php endif; ?>
        </section>
    </div>
    <?php endif; ?>


    <!-- Best Rated -->
    <?php if (count($newArrivals) > 0): ?>
    <div class="container">
        <section class="jumia-section">
            <div class="jumia-header" style="background: #d32f2f;"> <!-- Red for Best Rated (Hot Deals style) -->
                <h2>Best Rated</h2>
                <a href="<?php echo BASE_URL; ?>/products.php?sort=rating" class="view-all">See All →</a>
            </div>

            <div class="products-grid" id="bestRatedGrid" data-section="best-rated" data-products-per-page="12">
                <?php foreach ($newArrivals as $product):
                    $discount = 0;
                    if ($product['compare_price'] > $product['price']) {
                        $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                    }
                ?>
                <div class="product-card">
                    <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                        <div class="product-image-wrapper">
                            <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image1']; ?>" alt="<?php echo escape($product['name']); ?>" class="main-img" loading="lazy">
                            <?php if (!empty($product['image2'])): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image2']; ?>" alt="<?php echo escape($product['name']); ?>" class="hover-img" loading="lazy">
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?php echo escape($product['name']); ?></h3>
                            <div style="margin-bottom: 4px; font-size: 12px;">
                                <?php echo renderStarRating($product['rating']); ?>
                            </div>
                            <div class="price-block">
                                <span class="current-price">Ush <?php echo number_format($product['price']); ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="old-price">Ush <?php echo number_format($product['compare_price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, event)">Add To Cart</button>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($newArrivals) > 12): ?>
            <div class="pagination-dots" id="bestRatedDots"></div>
            <?php endif; ?>
        </section>
    </div>
    <?php endif; ?>

    <!-- Categories -->
    <div class="container">
        <section class="jumia-section">
            <div class="jumia-header" style="background: #d32f2f;"> <!-- Red for Categories -->
                <h2>Categories</h2>
                <a href="<?php echo BASE_URL; ?>/products.php" class="view-all">Browse All →</a>
            </div>

            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                <a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo urlencode($category['name']); ?>" class="category-card">
                    <div class="category-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </div>
                    <h3><?php echo escape($category['name']); ?></h3>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- Sponsored Products -->
    <?php if (count($sponsoredProducts) > 0): ?>
    <div class="container">
        <section class="jumia-section">
            <div class="jumia-header" style="background: #d19d02; color: #fff;"> <!-- Golden for Sponsored -->
                <h2>Sponsored</h2>
                <a href="<?php echo BASE_URL; ?>/products.php" class="view-all" style="color: #fff;">See All →</a>
            </div>

            <div class="products-grid" id="sponsoredGrid" data-section="sponsored" data-products-per-page="12">
                <?php foreach ($sponsoredProducts as $product):
                    $discount = 0;
                    if ($product['compare_price'] > $product['price']) {
                        $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                    }
                ?>
                <div class="product-card">
                    <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                        <div class="product-image-wrapper">
                            <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image1']; ?>" alt="<?php echo escape($product['name']); ?>" class="main-img" loading="lazy">
                            <?php if (!empty($product['image2'])): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image2']; ?>" alt="<?php echo escape($product['name']); ?>" class="hover-img" loading="lazy">
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                            <div style="position: absolute; top: 8px; left: 8px;">
                                <span style="background: #ffc107; color: #000; font-size: 10px; padding: 2px 5px; border-radius: 2px; font-weight: bold;">SPONSORED</span>
                            </div>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?php echo escape($product['name']); ?></h3>
                            <div style="margin-bottom: 4px; font-size: 12px;">
                                <?php echo renderStarRating($product['rating']); ?>
                            </div>
                            <div class="price-block">
                                <span class="current-price">Ush <?php echo number_format($product['price']); ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="old-price">Ush <?php echo number_format($product['compare_price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, event)">Add To Cart</button>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($sponsoredProducts) > 12): ?>
            <div class="pagination-dots" id="sponsoredDots"></div>
            <?php endif; ?>
        </section>
    </div>
    <?php endif; ?>

    <!-- Recently Viewed -->
    <?php if (count($recentlyViewed) > 0): ?>
    <div class="container">
        <section class="jumia-section">
            <div class="jumia-header" style="background: #333333;"> <!-- Dark Grey for Recently Viewed -->
                <h2>Recently Viewed</h2>
                <a href="<?php echo BASE_URL; ?>/products.php" class="view-all">See All →</a>
            </div>

            <div class="products-grid" id="recentlyViewedGrid" data-section="recently-viewed" data-products-per-page="12">
                <?php foreach ($recentlyViewed as $product):
                    $discount = 0;
                    if ($product['compare_price'] > $product['price']) {
                        $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                    }
                ?>
                <div class="product-card">
                    <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                        <div class="product-image-wrapper">
                            <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image1']; ?>" alt="<?php echo escape($product['name']); ?>" class="main-img" loading="lazy">
                            <?php if (!empty($product['image2'])): ?>
                                <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $product['image2']; ?>" alt="<?php echo escape($product['name']); ?>" class="hover-img" loading="lazy">
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title"><?php echo escape($product['name']); ?></h3>
                            <div style="margin-bottom: 4px; font-size: 12px;">
                                <?php echo renderStarRating($product['rating']); ?>
                            </div>
                            <div class="price-block">
                                <span class="current-price">Ush <?php echo number_format($product['price']); ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="old-price">Ush <?php echo number_format($product['compare_price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, event)">Add To Cart</button>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($recentlyViewed) > 12): ?>
            <div class="pagination-dots" id="recentlyViewedDots"></div>
            <?php endif; ?>
        </section>
    </div>
    <?php endif; ?>

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
                        <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
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

    <!-- Cart Preview Popup -->
    <div id="cartPreview" class="cart-preview">
        <div class="cart-preview-header">
            <h3>Shopping Cart (<span id="cartPreviewCount">0</span>)</h3>
        </div>
        <div class="cart-preview-items" id="cartPreviewItems">
            <div class="cart-preview-loading">Loading...</div>
        </div>
        <div class="cart-preview-footer">
            <div class="cart-preview-total">
                <span>Total:</span>
                <span id="cartPreviewTotal">Ush 0</span>
            </div>
            <a href="<?php echo BASE_URL; ?>/cart.php" class="view-cart-btn">View Cart</a>
        </div>
    </div>

    <?php include 'includes/support_widget.php'; ?>

    <script src="<?php echo BASE_URL; ?>/js/script.js"></script>
    <script>
    // Cart Preview on Hover
    document.addEventListener('DOMContentLoaded', function() {
        const cartLink = document.getElementById('cartLink');
        const cartPreview = document.getElementById('cartPreview');
        let hideTimeout;

        if (cartLink && cartPreview) {
            cartLink.addEventListener('mouseenter', function() {
                clearTimeout(hideTimeout);
                loadCartPreview();
                cartPreview.style.display = 'block';
            });

            cartLink.addEventListener('mouseleave', function() {
                hideTimeout = setTimeout(() => {
                    cartPreview.style.display = 'none';
                }, 300);
            });

            cartPreview.addEventListener('mouseenter', function() {
                clearTimeout(hideTimeout);
            });

            cartPreview.addEventListener('mouseleave', function() {
                cartPreview.style.display = 'none';
            });
        }

        function loadCartPreview() {
            fetch('<?php echo BASE_URL; ?>/cart.php?ajax=preview')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cartPreviewCount').textContent = data.count;
                    document.getElementById('cartPreviewTotal').textContent = 'Ush ' + data.total.toLocaleString();

                    const itemsContainer = document.getElementById('cartPreviewItems');
                    if (data.items.length === 0) {
                        itemsContainer.innerHTML = '<div class="cart-preview-empty">Your cart is empty</div>';
                    } else {
                        itemsContainer.innerHTML = data.items.map(item => `
                            <div class="cart-preview-item">
                                <img src="<?php echo UPLOAD_URL; ?>${item.image1}" alt="${item.name}" onerror="this.src='https://via.placeholder.com/50x50'">
                                <div class="cart-preview-item-details">
                                    <h4>${item.name}</h4>
                                    ${item.variant ? `<p class="variant">${item.variant}</p>` : ''}
                                    <p class="price">${item.quantity} × Ush ${item.price.toLocaleString()}</p>
                                </div>
                            </div>
                        `).join('');
                    }
                })
                .catch(error => {
                    console.error('Error loading cart preview:', error);
                });
        }
    });

    // Promotional Slider - Fixed version
    document.addEventListener('DOMContentLoaded', function() {
        let slideIndex = 0;
        let autoSlideTimer;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const container = document.getElementById('sliderContainer');

        // Only initialize slider if slides exist
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

        // Initialize slider
        showSlide(slideIndex);
        if (slides.length > 1) {
            startAutoSlide();
        }
    });

    // Add to Cart Function
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
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.textContent = 'Added!';
                    
                    // Update cart count
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
                    
                    // Update cart total
                    const cartTotal = document.querySelector('.cart-total');
                    if (cartTotal && data.cart_total !== undefined) {
                        cartTotal.textContent = 'Ush ' + Number(data.cart_total).toLocaleString('en-US');
                    }
                    
                    // Check cart status again to get cart_item_id and replace with quantity selector
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
                    console.error('API Error:', data);
                    btn.textContent = 'Error!';
                    alert('Failed to add to cart: ' + (data.message || 'Unknown error'));
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.classList.remove('adding');
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Network/Script Error:', error);
                btn.textContent = 'Error!';
                alert('System Error: ' + error.message);
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('adding');
                }, 1500);
            });
        })
        .catch(error => {
            console.error('Cart Status Check Error:', error);
            btn.textContent = 'Error!';
            alert('Cart Check Error: ' + error.message);
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
        
        // Add event listeners
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
                    // Remove selector, show add to cart button again
                    const addBtn = document.createElement('button');
                    addBtn.className = 'add-to-cart-btn';
                    addBtn.textContent = 'Add To Cart';
                    addBtn.onclick = function(e) { addToCart(productId, e); };
                    selectorElement.parentElement.replaceChild(addBtn, selectorElement);
                } else {
                    // Update display
                    const currentQty = parseInt(selectorElement.dataset.quantity);
                    selectorElement.dataset.quantity = newQuantity;
                    selectorElement.querySelector('.qty-display').textContent = newQuantity;
                    
                    // Update event listeners with new quantity
                    const minusBtn = selectorElement.querySelector('.minus');
                    const plusBtn = selectorElement.querySelector('.plus');
                    
                    minusBtn.onclick = () => updateQuantityInline(cartItemId, newQuantity - 1, selectorElement, productId);
                    plusBtn.onclick = () => updateQuantityInline(cartItemId, newQuantity + 1, selectorElement, productId);
                }
                
                // Update header cart
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

    // Product Pagination System
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize pagination for all product grids
        const productGrids = document.querySelectorAll('[data-section]');
        
        productGrids.forEach(grid => {
            initializePagination(grid);
        });

        function initializePagination(grid) {
            const section = grid.dataset.section;
            const productsPerPage = parseInt(grid.dataset.productsPerPage) || 12;
            const products = Array.from(grid.querySelectorAll('.product-card'));
            const totalProducts = products.length;
            
            // Only paginate if there are more products than the limit
            if (totalProducts <= productsPerPage) {
                return;
            }

            const totalPages = Math.ceil(totalProducts / productsPerPage);
            let currentPage = 0;

            // Get or create dots container
            const dotsContainer = grid.parentElement.querySelector('.pagination-dots');
            if (!dotsContainer) return;

            // Create dots
            for (let i = 0; i < totalPages; i++) {
                const dot = document.createElement('span');
                dot.className = 'dot' + (i === 0 ? ' active' : '');
                dot.onclick = () => goToPage(i);
                dotsContainer.appendChild(dot);
            }

            function showPage(pageIndex) {
                // Hide all products
                products.forEach(product => {
                    product.style.display = 'none';
                });

                // Show products for current page
                const start = pageIndex * productsPerPage;
                const end = start + productsPerPage;
                for (let i = start; i < end && i < totalProducts; i++) {
                    products[i].style.display = '';
                }

                // Update dots
                const dots = dotsContainer.querySelectorAll('.dot');
                dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === pageIndex);
                });

                currentPage = pageIndex;
            }

            function goToPage(pageIndex) {
                if (pageIndex >= 0 && pageIndex < totalPages) {
                    showPage(pageIndex);
                }
            }

            // Initialize first page
            showPage(0);
        }
    });
    </script>

    <?php include 'includes/mobile_nav.php'; ?>
</body>
</html>
