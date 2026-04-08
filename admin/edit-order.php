<?php
require_once '../config.php';
require_once '../includes/mail_functions.php';
require_once 'includes/functions.php';

requireLogin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId === 0) {
    header('Location: orders.php');
    exit;
}

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setMessage('Order not found.', 'error');
    header('Location: orders.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $status = $_POST['status'] ?? '';
    
    if (empty($customerName) || empty($email)) {
        setMessage('Customer name and email are required.', 'error');
    } else {
        // Track changes for email notification
        $changes = [];
        
        if ($order['customer_name'] !== $customerName) $changes[] = "Customer Name: {$order['customer_name']} → {$customerName}";
        if ($order['email'] !== $email) $changes[] = "Email: {$order['email']} → {$email}";
        if ($order['phone'] !== $phone) $changes[] = "Phone: {$order['phone']} → {$phone}";
        if ($order['address'] !== $address) $changes[] = "Address Changed";
        if ($order['city'] !== $city) $changes[] = "City: {$order['city']} → {$city}";
        if ($order['status'] !== $status) $changes[] = "Status: " . ucfirst($order['status']) . " → " . ucfirst($status);
        
        // Update order
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET customer_name = ?, email = ?, phone = ?, address = ?, city = ?, status = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$customerName, $email, $phone, $address, $city, $status, $orderId]);
        
        // Send email notification if there were changes
        if (count($changes) > 0) {
            $changesText = implode("\n", array_map(function($change) {
                return "• " . $change;
            }, $changes));
            
            $subject = "Order #" . $orderId . " Updated";
            $message = "
                <h2 style='color: #333;'>Order Updated</h2>
                <p>Dear " . htmlspecialchars($customerName) . ",</p>
                <p>Your order <strong>#" . $orderId . "</strong> has been updated by our admin team.</p>
                
                <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin: 0 0 10px 0; color: #666; font-size: 14px;'>Changes Made:</h3>
                    " . nl2br(htmlspecialchars($changesText)) . "
                </div>
                
                <p><strong>Current Order Details:</strong></p>
                <ul>
                    <li><strong>Order ID:</strong> #" . $orderId . "</li>
                    <li><strong>Status:</strong> " . ucfirst($status) . "</li>
                    <li><strong>Total:</strong> Ush " . number_format($order['total']) . "</li>
                </ul>
                
                <p>If you have any questions about these changes, please contact us.</p>
                <p>Thank you for shopping with us!</p>
            ";
            
            sendEmail($email, $customerName, $subject, $message);
        }
        
        setMessage('Order updated successfully! Customer has been notified via email.', 'success');
        header('Location: orders.php');
        exit;
    }
}

include 'includes/header.php';
?>

<style>
.edit-order-container {
    padding: 20px;
    max-width: 1200px;
}

.edit-order-header {
    margin-bottom: 30px;
}

.edit-order-header h1 {
    font-size: 24px;
    color: #333;
    font-weight: 600;
    margin: 0 0 10px 0;
}

.back-link {
    display: inline-block;
    color: #2c3d4f;
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 20px;
}

.back-link:hover {
    text-decoration: underline;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #666;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
}

.alert-info {
    background: #e3f2fd;
    border-left: 4px solid #1976d2;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    color: #1565c0;
}

.edit-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.card-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    height: 100%;
}

.card-section h3 {
    font-size: 16px;
    color: #333;
    font-weight: 600;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.full-width {
    grid-column: 1 / -1;
}

.form-actions-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    position: sticky;
    bottom: 20px;
    z-index: 100;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th,
.items-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.items-table th {
    background: #f8f9fa;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
}

.btn-primary {
    background: #2c3d4f;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
}

.btn-primary:hover {
    background: #5a6fd6;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
}

.btn-secondary:hover {
    background: #5a6268;
}

@media (max-width: 768px) {
    .edit-order-container {
        padding: 10px;
    }
    
    .card-section,
    .form-actions-card {
        padding: 20px;
    }
}
</style>

<div class="edit-order-container">
    <div class="edit-order-header">
        <a href="orders.php" class="back-link">← Back to Orders</a>
        <h1>Edit Order #<?php echo $order['id']; ?></h1>
    </div>

    <div class="alert-info">
        <div style="display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            <div>
                <strong>Email Notification:</strong> The customer will be automatically notified via email about any changes you make to this order.
            </div>
        </div>
    </div>

    <form method="POST">
        <div class="edit-grid">
            <!-- Order Items (Full Width) -->
            <div class="card-section full-width">
                <h3>Order Items</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $item['quantity'] ?? 1; ?></td>
                            <td>Ush <?php echo number_format($item['unit_price'] ?? 0); ?></td>
                            <td>Ush <?php echo number_format(($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 600;">Total:</td>
                            <td style="font-weight: 700; font-size: 16px;">Ush <?php echo number_format($order['total']); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Customer Information -->
            <div class="card-section">
                <h3>Customer Information</h3>
                <div class="form-group">
                    <label>Customer Name *</label>
                    <input type="text" name="customer_name" required value="<?php echo htmlspecialchars($order['customer_name']); ?>">
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($order['email']); ?>">
                </div>

                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($order['phone']); ?>">
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="card-section">
                <h3>Shipping Information</h3>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"><?php echo htmlspecialchars($order['address']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($order['city']); ?>">
                </div>
            </div>

            <!-- Order Status -->
            <div class="card-section">
                <h3>Order Status</h3>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 13px; color: #666;">
                    <p style="margin: 0;"><strong>Note:</strong> Changing the status will trigger an email notification to the customer.</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="form-actions-card">
            <a href="orders.php" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Update Order & Notify Customer</button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>



