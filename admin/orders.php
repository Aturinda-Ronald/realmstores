<?php
require_once '../config.php';
require_once '../includes/mail_functions.php';
require_once 'includes/functions.php';

requireLogin();

// Handle delete order
if (isset($_GET['delete'])) {
    $orderId = (int)$_GET['delete'];
    try {
        // Delete order items first
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        
        // Delete order
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        
        setMessage('Order deleted successfully!', 'success');
    } catch (PDOException $e) {
        setMessage('Error deleting order: ' . $e->getMessage(), 'error');
    }
    header('Location: orders.php');
    exit;
}

// Pagination and filters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$where = [];
$params = [];

if (!empty($searchQuery)) {
    $where[] = "(customer_name LIKE ? OR email LIKE ? OR id LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($statusFilter)) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Get stats
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$processingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
$completedOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();

// Get total revenue
$totalRevenue = $pdo->query("SELECT SUM(total) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?? 0;

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM orders $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$filteredCount = $stmt->fetchColumn();
$totalPages = ceil($filteredCount / $perPage);

// Get orders
$query = "SELECT * FROM orders $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

include 'includes/header.php';
?>

<style>
.orders-container {
    padding: 20px;
}

.orders-header {
    margin-bottom: 30px;
}

.orders-header h1 {
    font-size: 24px;
    color: #333;
    font-weight: 600;
    margin: 0 0 20px 0;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon.total,
.stat-icon.pending,
.stat-icon.processing,
.stat-icon.completed,
.stat-icon.revenue { background: #f5f5f5; color: #666; }

.stat-content h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.stat-content p {
    margin: 5px 0 0 0;
    font-size: 13px;
    color: #666;
    font-weight: 500;
}

/* Search Section */
.search-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    margin-bottom: 20px;
}

.search-form {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.search-input,
.status-select {
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.search-input {
    flex: 1;
    min-width: 250px;
}

.status-select {
    min-width: 150px;
}

/* Orders Grid */
.orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.order-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,.2);
}

.order-card-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-id {
    font-size: 16px;
    font-weight: 700;
    color: #333;
}

.order-status {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: #fff3e0; color: #f57c00; }
.status-processing { background: #e3f2fd; color: #1976d2; }
.status-shipped { background: #f3e5f5; color: #7b1fa2; }
.status-completed { background: #e8f5e9; color: #388e3c; }
.status-cancelled { background: #ffebee; color: #d32f2f; }

.order-card-body {
    padding: 20px;
}

.order-info-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 14px;
    color: #666;
}

.order-info-row svg {
    flex-shrink: 0;
    color: #999;
}

.order-info-row strong {
    color: #333;
}

.order-card-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-total {
    font-size: 18px;
    font-weight: 700;
    color: #333;
}

.order-actions {
    display: flex;
    gap: 8px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.page-btn, .page-num {
    padding: 8px 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 14px;
}

.page-btn:hover, .page-num:hover {
    background: #2c3d4f;
    color: white;
    border-color: #2c3d4f;
}

.page-num.active {
    background: #2c3d4f;
    color: white;
    border-color: #2c3d4f;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

@media (max-width: 768px) {
    .orders-container {
        padding: 10px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .search-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input,
    .status-select {
        width: 100%;
    }
    
    .orders-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .order-card-footer {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .order-actions {
        justify-content: center;
    }
}
</style>

<div class="orders-container">
    <div class="orders-header">
        <h1>Orders Management</h1>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 7h-3a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2H4"></path>
                    <path d="M10 11V7M14 11V7"></path>
                    <path d="M2 7h20v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7z"></path>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalOrders; ?></h3>
                <p>Total Orders</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon pending">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $pendingOrders; ?></h3>
                <p>Pending</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon processing">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $processingOrders; ?></h3>
                <p>Processing</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon completed">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $completedOrders; ?></h3>
                <p>Completed</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon revenue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="stat-content">
                <h3>Ush <?php echo number_format($totalRevenue / 1000); ?>K</h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="search-section">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by Order ID, Customer Name, or Email..." 
                   value="<?php echo htmlspecialchars($searchQuery); ?>" class="search-input">
            
            <select name="status" class="status-select">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>

            <button type="submit" class="btn-primary">Search</button>
            <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
            <a href="orders.php" class="btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders Grid -->
    <?php if (count($orders) > 0): ?>
    <div class="orders-grid">
        <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-card-header">
                <span class="order-id">#<?php echo $order['id']; ?></span>
                <span class="order-status status-<?php echo $order['status']; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>

            <div class="order-card-body">
                <div class="order-info-row">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></span>
                </div>

                <div class="order-info-row">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <span><?php echo htmlspecialchars($order['email']); ?></span>
                </div>

                <div class="order-info-row">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                </div>
            </div>

            <div class="order-card-footer">
                <span class="order-total">Ush <?php echo number_format($order['total']); ?></span>
                
                <div class="order-actions">
                    <!-- View Button -->
                    <a href="order-view.php?id=<?php echo $order['id']; ?>" class="icon-btn icon-btn-primary" title="View Order">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </a>

                    <!-- Edit Button -->
                    <a href="edit-order.php?id=<?php echo $order['id']; ?>" class="icon-btn icon-btn-success" title="Edit Order">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>

                    <!-- Delete Button -->
                    <a href="?delete=<?php echo $order['id']; ?>" 
                       class="icon-btn icon-btn-danger" 
                       title="Delete Order"
                       onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?>" class="page-btn">← Previous</a>
        <?php endif; ?>

        <div style="display: flex; gap: 5px; align-items: center;">
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            for ($i = $startPage; $i <= $endPage; $i++) {
                $activeClass = $i == $page ? 'active' : '';
                echo '<a href="?page=' . $i . (!empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '') . (!empty($statusFilter) ? '&status=' . $statusFilter : '') . '" class="page-num ' . $activeClass . '">' . $i . '</a>';
            }
            ?>
        </div>

        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . $statusFilter : ''; ?>" class="page-btn">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="no-results">
        <p style="font-size: 18px; margin-bottom: 10px;">No orders found</p>
        <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
        <a href="orders.php" class="btn-secondary">Clear Filters</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>



