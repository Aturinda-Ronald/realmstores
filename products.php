<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Get parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$featured = isset($_GET['featured']) ? 1 : null;

// Build query based on filters
$where = [];
$params = [];

if ($categoryId) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

if ($searchQuery) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

if ($featured) {
    $where[] = "p.featured = 1";
}

$whereSQL = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Sorting
$orderBy = "ORDER BY p.created_at DESC";
switch ($sortBy) {
    case 'price_low':
        $orderBy = "ORDER BY p.price ASC";
        break;
    case 'price_high':
        $orderBy = "ORDER BY p.price DESC";
        break;
    case 'name':
        $orderBy = "ORDER BY p.name ASC";
        break;
    case 'newest':
    default:
        $orderBy = "ORDER BY p.created_at DESC";
        break;
}

// Get total count
$countSQL = "SELECT COUNT(*) 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.is_active = 1 AND c.is_active = 1";

if (count($where) > 0) {
    $countSQL .= " AND " . implode(" AND ", $where);
}

$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products
$offset = ($page - 1) * $perPage;
$sql = "SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1 AND c.is_active = 1";

if (count($where) > 0) {
    $sql .= " AND " . implode(" AND ", $where);
}

$sql .= " {$orderBy} LIMIT {$perPage} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all categories for filter
$categories = getCategories($pdo);
$cartCount = getCartCount($pdo);

// Get current category name
$currentCategory = null;
if ($categoryId) {
    $currentCategory = getCategoryById($pdo, $categoryId);
}

// Prepare mobile view data (Grouped by Category)
$mobileCategories = [];
if (!$categoryId && !$searchQuery && !$featured) {
    // Only show grouped view if no specific filters are applied
    foreach ($categories as $cat) {
        $catProducts = getProductsByCategory($pdo, $cat['id'], 8);
        if (count($catProducts) > 0) {
            $mobileCategories[] = [
                'category' => $cat,
                'products' => $catProducts
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products - Realm</title>
    <meta name="description" content="Browse all products at Realm">
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
                    <a href="<?php echo BASE_URL; ?>">
                        <img src="<?php echo BASE_URL; ?>/assets/logo.png" alt="Realm" class="logo-img">
                    </a>
                </div>

                <div class="search-bar">
                    <form method="GET" action="<?php echo BASE_URL; ?>/products.php">
                        <input type="text" name="search" placeholder="Search products..." value="<?php echo escape($searchQuery); ?>" id="searchInput">
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

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>">Home</a> /
            <?php if ($currentCategory): ?>
                <span><?php echo escape($currentCategory['name']); ?></span>
            <?php elseif ($searchQuery): ?>
                <span>Search: "<?php echo escape($searchQuery); ?>"</span>
            <?php elseif ($featured): ?>
                <span>Hot Deals</span>
            <?php else: ?>
                <span>All Products</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Products Page -->
    <section class="products-page">
        <div class="container">
            <!-- Desktop View (Sidebar + Grid) -->
            <div class="products-page-grid desktop-view">
                <!-- Sidebar Filters -->
                <aside class="filters-sidebar">
                    <div class="filter-content" id="filterContent">
                        <div class="filter-section">
                            <h3>Categories</h3>
                            <ul class="category-filter-list">
                                <li>
                                    <a href="<?php echo BASE_URL; ?>/products.php" class="<?php echo !$categoryId ? 'active' : ''; ?>">
                                        All Products
                                    </a>
                                </li>
                                <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo $cat['id']; ?>"
                                       class="<?php echo $categoryId == $cat['id'] ? 'active' : ''; ?>">
                                        <?php echo escape($cat['name']); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="filter-section">
                            <h3>Quick Links</h3>
                            <ul class="category-filter-list">
                                <li>
                                    <a href="<?php echo BASE_URL; ?>/products.php?featured=1" class="<?php echo $featured ? 'active' : ''; ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#c53940" stroke-width="2">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                        </svg>
                                        Hot Deals
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </aside>

                <!-- Products Content -->
                <div class="products-content products-page-wrapper">
                    <!-- Toolbar -->
                    <div class="products-toolbar">
                        <div class="toolbar-info">
                            <h1>
                                <?php if ($currentCategory): ?>
                                    <?php echo escape($currentCategory['name']); ?>
                                <?php elseif ($searchQuery): ?>
                                    Search Results for "<?php echo escape($searchQuery); ?>"
                                <?php elseif ($featured): ?>
                                    Hot Deals
                                <?php else: ?>
                                    All Products
                                <?php endif; ?>
                            </h1>
                            <p class="products-count"><?php echo $totalProducts; ?> products found</p>
                        </div>

                        <div class="toolbar-sort">
                            <label>Sort by:</label>
                            <select id="sortSelect" onchange="window.location.href=this.value">
                                <?php
                                $baseUrl = BASE_URL . '/products.php?';
                                if ($categoryId) $baseUrl .= 'category=' . $categoryId . '&';
                                if ($searchQuery) $baseUrl .= 'search=' . urlencode($searchQuery) . '&';
                                if ($featured) $baseUrl .= 'featured=1&';
                                ?>
                                <option value="<?php echo $baseUrl; ?>sort=newest" <?php echo $sortBy == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="<?php echo $baseUrl; ?>sort=price_low" <?php echo $sortBy == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="<?php echo $baseUrl; ?>sort=price_high" <?php echo $sortBy == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="<?php echo $baseUrl; ?>sort=name" <?php echo $sortBy == 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                            </select>
                        </div>
                    </div>

                    <!-- Products Grid (Desktop) -->
                    <?php if (count($products) > 0): ?>
                    <section class="jumia-section">
                        <div class="jumia-header" style="background: #c53940;">
                            <h2>
                                <?php if ($currentCategory): ?>
                                    <?php echo escape($currentCategory['name']); ?>
                                <?php elseif ($searchQuery): ?>
                                    Results for "<?php echo escape($searchQuery); ?>"
                                <?php elseif ($featured): ?>
                                    Hot Deals
                                <?php else: ?>
                                    All Products
                                <?php endif; ?>
                            </h2>
                            <span class="view-all"><?php echo $totalProducts; ?> Products Found</span>
                        </div>

                        <div class="products-grid">
                            <?php foreach ($products as $product): 
                                $discount = 0;
                                if ($product['compare_price'] > $product['price']) {
                                    $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                                }
                            ?>
                            <div class="product-card">
                                <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                                    <div class="product-image-wrapper">
                                        <img src="<?php echo UPLOAD_URL . $product['image1']; ?>" alt="<?php echo escape($product['name']); ?>" class="main-img" loading="lazy">
                                        <?php if ($product['image2']): ?>
                                            <img src="<?php echo UPLOAD_URL . $product['image2']; ?>" alt="<?php echo escape($product['name']); ?>" class="hover-img" loading="lazy">
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
                    </section>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        $baseUrl = BASE_URL . '/products.php?';
                        if ($categoryId) $baseUrl .= 'category=' . $categoryId . '&';
                        if ($searchQuery) $baseUrl .= 'search=' . urlencode($searchQuery) . '&';
                        if ($featured) $baseUrl .= 'featured=1&';
                        if ($sortBy != 'newest') $baseUrl .= 'sort=' . $sortBy . '&';
                        ?>

                        <?php if ($page > 1): ?>
                            <a href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>" class="pagination-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                                Previous
                            </a>
                        <?php endif; ?>

                        <div class="pagination-numbers">
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            if ($startPage > 1) {
                                echo '<a href="' . $baseUrl . 'page=1" class="pagination-number">1</a>';
                                if ($startPage > 2) echo '<span class="pagination-dots">...</span>';
                            }

                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $activeClass = $i == $page ? 'active' : '';
                                echo '<a href="' . $baseUrl . 'page=' . $i . '" class="pagination-number ' . $activeClass . '">' . $i . '</a>';
                            }

                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) echo '<span class="pagination-dots">...</span>';
                                echo '<a href="' . $baseUrl . 'page=' . $totalPages . '" class="pagination-number">' . $totalPages . '</a>';
                            }
                            ?>
                        </div>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>" class="pagination-btn">
                                Next
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <!-- No Products Found -->
                    <div class="no-products">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <h2>No Products Found</h2>
                        <p>We couldn't find any products matching your criteria.</p>
                        <a href="<?php echo BASE_URL; ?>/products.php" class="btn-primary">Browse All Products</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

                </div>
            </div>

            <!-- Mobile View (Grouped by Category) -->
            <div class="mobile-view">
                <!-- Mobile Category Menu (Scrollable) -->
                <div class="mobile-category-menu">
                    <a href="<?php echo BASE_URL; ?>/products.php" class="mobile-cat-chip active">All</a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo $cat['id']; ?>" class="mobile-cat-chip">
                        <?php echo escape($cat['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Main Products Section (Paginated) -->
                <div class="container">
                    <section class="jumia-section">
                        <div class="jumia-header" style="background: #c53940;">
                            <h2>
                                <?php if ($currentCategory): ?>
                                    <?php echo escape($currentCategory['name']); ?>
                                <?php elseif ($searchQuery): ?>
                                    Results for "<?php echo escape($searchQuery); ?>"
                                <?php elseif ($featured): ?>
                                    Hot Deals
                                <?php else: ?>
                                    All Products
                                <?php endif; ?>
                            </h2>
                        </div>

                        <?php if (count($products) > 0): ?>
                        <div class="products-grid">
                            <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                                    <div class="product-image-wrapper">
                                        <img src="<?php echo UPLOAD_URL . $product['image1']; ?>" alt="<?php echo escape($product['name']); ?>" class="main-img" loading="lazy">
                                        <?php if ($product['image2']): ?>
                                            <img src="<?php echo UPLOAD_URL . $product['image2']; ?>" alt="<?php echo escape($product['name']); ?>" class="hover-img" loading="lazy">
                                        <?php endif; ?>
                                        <?php if ($product['compare_price'] > $product['price']): ?>
                                            <span class="discount-badge">-<?php echo round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100); ?>%</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="product-info">
                                        <h3 class="product-title"><?php echo escape($product['name']); ?></h3>
                                        <div style="margin-bottom: 4px; font-size: 12px;">
                                            <?php echo renderStarRating($product['rating']); ?>
                                        </div>
                                        <div class="price-block">
                                            <span class="current-price">Ush <?php echo number_format($product['price']); ?></span>
                                            <?php if ($product['compare_price'] > $product['price']): ?>
                                                <span class="old-price">Ush <?php echo number_format($product['compare_price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, event)">Add To Cart</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Mobile Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="pagination" style="padding: 10px;">
                             <?php
                            $baseUrl = BASE_URL . '/products.php?';
                            if ($categoryId) $baseUrl .= 'category=' . $categoryId . '&';
                            if ($searchQuery) $baseUrl .= 'search=' . urlencode($searchQuery) . '&';
                            if ($featured) $baseUrl .= 'featured=1&';
                            if ($sortBy != 'newest') $baseUrl .= 'sort=' . $sortBy . '&';
                            ?>
                            <?php if ($page > 1): ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>" class="pagination-btn">Prev</a>
                            <?php endif; ?>
                            <span class="pagination-number active"><?php echo $page; ?> / <?php echo $totalPages; ?></span>
                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>" class="pagination-btn">Next</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <p style="padding: 15px;">No products found.</p>
                        <?php endif; ?>
                    </section>
                </div>

                <!-- Category Sections (Only if no specific filters) -->
                <?php if (!empty($mobileCategories)): ?>
                    <?php foreach ($mobileCategories as $group): ?>
                    <div class="container">
                        <section class="jumia-section">
                            <div class="jumia-header" style="background: #c53940;">
                                <h2><?php echo escape($group['category']['name']); ?></h2>
                                <a href="<?php echo BASE_URL; ?>/products.php?category=<?php echo $group['category']['id']; ?>" class="view-all">See All →</a>
                            </div>

                            <div class="products-grid">
                                <?php foreach ($group['products'] as $product): ?>
                                <div class="product-card">
                                    <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit; display: block; flex: 1;">
                                        <div class="product-image-wrapper">
                                            <img src="<?php echo UPLOAD_URL . $product['image1']; ?>" alt="<?php echo escape($product['name']); ?>" class="main-img" loading="lazy">
                                            <?php if ($product['image2']): ?>
                                                <img src="<?php echo UPLOAD_URL . $product['image2']; ?>" alt="<?php echo escape($product['name']); ?>" class="hover-img" loading="lazy">
                                            <?php endif; ?>
                                            <?php if ($product['compare_price'] > $product['price']): ?>
                                                <span class="discount-badge">-<?php echo round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100); ?>%</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="product-info">
                                            <h3 class="product-title"><?php echo escape($product['name']); ?></h3>
                                            <div style="margin-bottom: 4px; font-size: 12px;">
                                                <?php echo renderStarRating($product['rating']); ?>
                                            </div>
                                            <div class="price-block">
                                                <span class="current-price">Ush <?php echo number_format($product['price']); ?></span>
                                                <?php if ($product['compare_price'] > $product['price']): ?>
                                                    <span class="old-price">Ush <?php echo number_format($product['compare_price']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, event)">Add To Cart</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <h2><?php echo SITE_NAME; ?></h2>
                    </div>
                    <div class="footer-contact">
                        <p><strong>Contact Us:</strong></p>
                        <p class="phone">(+256) 789 331 407</p>
                        <p class="phone">(+256) 701 613 195</p>
                        <p>Email: ronaldaturinda7@gmail.com</p>
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
                <p>Copyright &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All Rights Reserved.</p>
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
                headers: { 'Content-Type': 'application/json', },
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
                    
                    // Replace with quantity selector after adding
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

    // Toggle Filters on Mobile
    function toggleFilters() {
        const content = document.getElementById('filterContent');
        content.classList.toggle('active');
    }
    </script>
    <?php include 'includes/mobile_nav.php'; ?>
</body>
</html>
