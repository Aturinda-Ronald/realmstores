<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

// Get statistics
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$featuredProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE featured = 1")->fetchColumn();

// Get recent products
$recentProducts = $pdo->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 10
")->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon-wrapper">
            <svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
        </div>
        <div class="stat-content">
            <h3>Total Categories</h3>
            <p class="stat-number"><?php echo $totalCategories; ?></p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper">
            <svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
        </div>
        <div class="stat-content">
            <h3>Total Products</h3>
            <p class="stat-number"><?php echo $totalProducts; ?></p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper">
            <svg class="stat-icon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
        </div>
        <div class="stat-content">
            <h3>Featured Products</h3>
            <p class="stat-number"><?php echo $featuredProducts; ?></p>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Recent Products</h2>
        <a href="<?php echo ADMIN_URL; ?>/add-product.php" class="btn btn-primary">Add New Product</a>
    </div>

    <div class="admin-products-grid">
        <?php foreach ($recentProducts as $product): ?>
        <div class="admin-product-card">
            <div class="admin-product-image">
                <?php if ($product['image1']): ?>
                <img src="<?php echo UPLOAD_URL . $product['image1']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                <div class="no-image-placeholder">No image</div>
                <?php endif; ?>
                <div class="badges" style="position: absolute; top: 10px; right: 10px; display: flex; flex-direction: column; gap: 5px; align-items: flex-end;">
                    <?php if ($product['featured']): ?>
                    <span class="badge badge-warning" style="font-size: 11px;">Featured</span>
                    <?php endif; ?>
                    <?php if ($product['is_hot']): ?>
                    <span class="badge badge-danger" style="font-size: 11px;">HOT</span>
                    <?php endif; ?>
                    <?php if (strtotime($product['created_at']) > strtotime('-7 days')): ?>
                    <span class="badge badge-success" style="font-size: 11px;">NEW</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="admin-product-info">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                    <span style="color: #ffc107; font-size: 14px;">★</span>
                    <span style="font-size: 13px; color: #666;"><?php echo number_format($product['rating'], 1); ?></span>
                </div>
                <p class="product-price">
                    <?php if ($product['compare_price'] > $product['price']): ?>
                    <span style="text-decoration: line-through; color: #999; font-size: 14px; margin-right: 5px;">Ush <?php echo number_format($product['compare_price']); ?></span>
                    <?php endif; ?>
                    Ush <?php echo number_format($product['price']); ?>
                </p>
                <p class="product-date">Added: <?php echo date('M d, Y', strtotime($product['created_at'])); ?></p>
            </div>
            <div class="admin-product-actions">
                <a href="<?php echo ADMIN_URL; ?>/edit-product.php?id=<?php echo $product['id']; ?>" class="icon-btn icon-btn-primary" title="Edit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>



