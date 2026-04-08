<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

// Handle product activation/deactivation
if (isset($_GET['toggle_status'])) {
    $productId = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$productId]);
    setMessage('Product status updated!', 'success');
    header('Location: products.php' . ($_SERVER['QUERY_STRING'] ? '?' . http_build_query(array_diff_key($_GET, ['toggle_status' => ''])) : ''));
    exit;
}

// Pagination settings
$perPage = 12;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Category filter
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Search filter
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the query
$where = [];
$params = [];

if ($categoryFilter > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}

if (!empty($searchQuery)) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countQuery = "SELECT COUNT(*) FROM products p $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalProducts = $stmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products
$query = "
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    $whereClause
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get all categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>All Products (<?php echo $totalProducts; ?>)</h2>
        <a href="<?php echo ADMIN_URL; ?>/add-product.php" class="btn btn-primary">Add New Product</a>
    </div>

    <!-- Search and Filter -->
    <div class="products-filter">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="search-input">
            </div>
            <div class="filter-group">
                <select name="category" class="category-select">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($categoryFilter > 0 || !empty($searchQuery)): ?>
            <a href="<?php echo ADMIN_URL; ?>/products.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Products Grid -->
    <?php if (count($products) > 0): ?>
    <div class="admin-products-grid">
        <?php foreach ($products as $product): ?>
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
                
                <!-- Toggle Active/Inactive -->
                <a href="?toggle_status=<?php echo $product['id']; ?><?php echo $categoryFilter ? '&category=' . $categoryFilter : ''; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>&page=<?php echo $page; ?>" 
                   class="icon-btn <?php echo ($product['is_active'] ?? 1) ? 'icon-btn-warning' : 'icon-btn-success'; ?>" 
                   title="<?php echo ($product['is_active'] ?? 1) ? 'Deactivate' : 'Activate'; ?>">
                    <?php if ($product['is_active'] ?? 1): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    <?php endif; ?>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>/delete-product.php?id=<?php echo $product['id']; ?>"
                   class="icon-btn icon-btn-danger"
                   title="Delete"
                   onclick="return confirm('Are you sure you want to delete this product?')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?><?php echo $categoryFilter ? "&category=$categoryFilter" : ''; ?><?php echo !empty($searchQuery) ? "&search=" . urlencode($searchQuery) : ''; ?>" class="page-btn">← Previous</a>
        <?php endif; ?>

        <div class="page-numbers">
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            if ($startPage > 1) {
                echo '<a href="?page=1' . ($categoryFilter ? "&category=$categoryFilter" : '') . (!empty($searchQuery) ? "&search=" . urlencode($searchQuery) : '') . '" class="page-num">1</a>';
                if ($startPage > 2) echo '<span class="page-dots">...</span>';
            }

            for ($i = $startPage; $i <= $endPage; $i++) {
                $activeClass = $i == $page ? 'active' : '';
                echo '<a href="?page=' . $i . ($categoryFilter ? "&category=$categoryFilter" : '') . (!empty($searchQuery) ? "&search=" . urlencode($searchQuery) : '') . '" class="page-num ' . $activeClass . '">' . $i . '</a>';
            }

            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) echo '<span class="page-dots">...</span>';
                echo '<a href="?page=' . $totalPages . ($categoryFilter ? "&category=$categoryFilter" : '') . (!empty($searchQuery) ? "&search=" . urlencode($searchQuery) : '') . '" class="page-num">' . $totalPages . '</a>';
            }
            ?>
        </div>

        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo $categoryFilter ? "&category=$categoryFilter" : ''; ?><?php echo !empty($searchQuery) ? "&search=" . urlencode($searchQuery) : ''; ?>" class="page-btn">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="no-results">
        <p>No products found.</p>
        <?php if ($categoryFilter > 0 || !empty($searchQuery)): ?>
        <a href="<?php echo ADMIN_URL; ?>/products.php" class="btn btn-secondary">View All Products</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.products-filter {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.filter-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.search-input,
.category-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.page-btn,
.page-num {
    padding: 8px 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 14px;
}

.page-btn:hover,
.page-num:hover {
    background: #2c3d4f;
    color: white;
    border-color: #2c3d4f;
}

.page-num.active {
    background: #2c3d4f;
    color: white;
    border-color: #2c3d4f;
}

.page-dots {
    color: #999;
    padding: 0 5px;
}

.page-numbers {
    display: flex;
    gap: 5px;
    align-items: center;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-results p {
    font-size: 18px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
    }

    .filter-group {
        width: 100%;
    }

    .pagination {
        font-size: 13px;
    }

    .page-btn,
    .page-num {
        padding: 6px 10px;
        font-size: 13px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>



