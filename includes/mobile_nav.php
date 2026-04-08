<?php
// Mobile Bottom Navigation - Only shown on mobile devices
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="mobile-bottom-nav">
    <div class="mobile-bottom-nav-inner">
        <!-- Home -->
        <a href="<?php echo BASE_URL; ?>" class="mobile-nav-item <?php echo ($current_page == 'index.php' || $current_page == '') ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span class="mobile-nav-label">Home</span>
        </a>

        <!-- Shop/Products -->
        <a href="<?php echo BASE_URL; ?>/products.php" class="mobile-nav-item <?php echo ($current_page == 'products.php' || $current_page == 'product.php') ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span class="mobile-nav-label">Shop</span>
        </a>

        <!-- Cart -->
        <a href="<?php echo BASE_URL; ?>/cart.php" class="mobile-nav-item mobile-nav-cart <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <?php
            $cartCount = isset($pdo) ? getCartCount($pdo) : 0;
            if ($cartCount > 0):
            ?>
            <span class="mobile-nav-badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
            <span class="mobile-nav-label">Cart</span>
        </a>

        <!-- Wishlist -->
        <a href="<?php echo BASE_URL; ?>/wishlist.php" class="mobile-nav-item <?php echo $current_page == 'wishlist.php' ? 'active' : ''; ?>">
            <svg class="mobile-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span class="mobile-nav-label">Wishlist</span>
        </a>

        <!-- Account -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?php echo BASE_URL; ?>/account.php" class="mobile-nav-item <?php echo $current_page == 'account.php' ? 'active' : ''; ?>">
        <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/login.php" class="mobile-nav-item <?php echo ($current_page == 'login.php' || $current_page == 'register.php') ? 'active' : ''; ?>">
        <?php endif; ?>
            <svg class="mobile-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span class="mobile-nav-label">Account</span>
        </a>
    </div>
</nav>
