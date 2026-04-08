<?php
require_once '../config.php';
require_once '../includes/mail_functions.php';
require_once 'includes/functions.php';

// Check login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId === 0) {
    header('Location: orders.php');
    exit;
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);

        // Fetch customer details for email
        $stmtOrder = $pdo->prepare("SELECT customer_name, email FROM orders WHERE id = ?");
        $stmtOrder->execute([$orderId]);
        $orderInfo = $stmtOrder->fetch();

        if ($orderInfo) {
            sendOrderStatusEmail($orderInfo['email'], $orderInfo['customer_name'], $orderId, $newStatus);
        }

        setMessage('success', 'Order status updated successfully.');
        header("Location: order-view.php?id=$orderId");
        exit;
    } catch (PDOException $e) {
        setMessage('error', 'Failed to update status: ' . $e->getMessage());
    }
}

// Fetch Order Details
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        die("Order not found.");
    }

    // Fetch Order Items
    $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmtItems->execute([$orderId]);
    $items = $stmtItems->fetchAll();

} catch (PDOException $e) {
    die("Error fetching order details: " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<style>
.order-view-container {
    padding: 20px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.order-header h1 {
    font-size: 24px;
    color: #333;
    font-weight: 600;
    margin: 0;
}

.order-status-badge {
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fff3e0;
    color: #f57c00;
}

.status-processing {
    background: #e3f2fd;
    color: #1976d2;
}

.status-shipped {
    background: #f3e5f5;
    color: #7b1fa2;
}

.status-completed {
    background: #e8f5e9;
    color: #388e3c;
}

.status-cancelled {
    background: #ffebee;
    color: #d32f2f;
}

.info-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
}

.info-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
}

.info-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
}

.info-card-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
    font-weight: 600;
}

.info-row {
    margin-bottom: 12px;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 4px;
    font-weight: 600;
}

.info-value {
    font-size: 14px;
    color: #333;
}

.invoice-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    margin-bottom: 30px;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.invoice-title h2 {
    margin: 0 0 5px 0;
    font-size: 20px;
    color: #333;
}

.invoice-title p {
    margin: 0;
    font-size: 13px;
    color: #999;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.items-table thead {
    background: #f8f9fa;
}

.items-table th {
    padding: 12px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    border-bottom: 2px solid #eee;
}

.items-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    color: #333;
}

.items-table tbody tr:hover {
    background: #fafafa;
}

.invoice-totals {
    margin-left: auto;
    width: 300px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.total-row.final {
    border-top: 2px solid #333;
    border-bottom: none;
    margin-top: 10px;
    padding-top: 15px;
}

.total-label {
    font-size: 14px;
    color: #666;
}

.total-row.final .total-label {
    font-size: 16px;
    font-weight: 700;
    color: #333;
}

.total-value {
    font-size: 14px;
    color: #333;
}

.total-row.final .total-value {
    font-size: 18px;
    font-weight: 700;
    color: #c53940;
}

.status-update-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
}

.status-update-card h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #333;
    font-weight: 600;
}

.status-form {
    display: flex;
    gap: 12px;
    max-width: 500px;
}

.status-form select {
    flex: 1;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

@media (max-width: 768px) {
    .info-cards {
        grid-template-columns: 1fr;
    }
    
    .invoice-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .items-table {
        font-size: 12px;
    }
    
    .invoice-totals {
        width: 100%;
    }
}
</style>

<div class="order-view-container">
    <div class="order-header">
        <h1>Order #<?php echo $order['id']; ?></h1>
        <span class="order-status-badge status-<?php echo $order['status']; ?>">
            <?php echo ucfirst($order['status']); ?>
        </span>
    </div>

    <a href="orders.php" class="btn-secondary" style="display: inline-block; margin-bottom: 20px;">← Back to Orders</a>

    <!-- Info Cards -->
    <div class="info-cards">
        <!-- Customer Information -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h3>Customer Information</h3>
            </div>
            <div class="info-row">
                <div class="info-label">Name</div>
                <div class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($order['email']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone</div>
                <div class="info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
            </div>
        </div>

        <!-- Shipping Information -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <h3>Shipping Information</h3>
            </div>
            <div class="info-row">
                <div class="info-label">Address</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($order['address'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">City</div>
                <div class="info-value"><?php echo htmlspecialchars($order['city']); ?></div>
            </div>
            <?php if ($order['notes']): ?>
            <div class="info-row">
                <div class="info-label">Notes</div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order Info -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                </div>
                <h3>Order Info</h3>
            </div>
            <div class="info-row">
                <div class="info-label">Order Date</div>
                <div class="info-value"><?php echo date('M j, Y g:i a', strtotime($order['created_at'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Payment Method</div>
                <div class="info-value"><?php echo $order['payment_method'] == 'cod' ? 'Cash on Delivery' : 'Mobile Money'; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Order ID</div>
                <div class="info-value">#<?php echo $order['id']; ?></div>
            </div>
        </div>
    </div>

    <!-- Invoice -->
    <div class="invoice-card">
        <div class="invoice-header">
            <div class="invoice-title">
                <h2>Order Items</h2>
                <p>Invoice for Order #<?php echo $order['id']; ?></p>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="text-align: center;">Quantity</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                    <td style="text-align: right;">Ush <?php echo number_format($item['unit_price']); ?></td>
                    <td style="text-align: right;">Ush <?php echo number_format($item['line_total']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="invoice-totals">
            <div class="total-row">
                <span class="total-label">Subtotal</span>
                <span class="total-value">Ush <?php echo number_format($order['subtotal']); ?></span>
            </div>
            <div class="total-row">
                <span class="total-label">Shipping</span>
                <span class="total-value">Ush <?php echo number_format($order['shipping_amount']); ?></span>
            </div>
            <div class="total-row final">
                <span class="total-label">Total</span>
                <span class="total-value">Ush <?php echo number_format($order['total']); ?></span>
            </div>
        </div>
    </div>

    <!-- Status Update -->
    <div class="status-update-card">
        <h3>Update Order Status</h3>
        <form method="POST" class="status-form">
            <input type="hidden" name="update_status" value="1">
            <select name="status" required>
                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button type="submit" class="btn-primary">Update Status</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>



