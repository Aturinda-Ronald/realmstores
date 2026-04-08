<?php
define('NO_TRACKING', true);
require_once '../config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filters
$actionFilter = isset($_GET['action']) ? $_GET['action'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = [];
$params = [];

if ($actionFilter) {
    $where[] = "action_type = ?";
    $params[] = $actionFilter;
}

if ($dateFilter) {
    $where[] = "DATE(created_at) = ?";
    $params[] = $dateFilter;
}

if ($searchQuery) {
    $where[] = "(page_url LIKE ? OR ip_address LIKE ? OR city LIKE ? OR country LIKE ?)";
    $searchParam = "%{$searchQuery}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_activities $whereClause");
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Get activities
$stmt = $pdo->prepare("
    SELECT * 
    FROM user_activities 
    $whereClause
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique action types for filter
$actionTypes = $pdo->query("SELECT DISTINCT action_type FROM user_activities ORDER BY action_type")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Activity Logs';
include 'includes/header.php';
?>

<div class="activity-logs-page">
    <div class="page-header">
        <h1>Activity Logs</h1>
        <a href="analytics.php" class="btn-secondary">← Back to Analytics</a>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label>Action Type</label>
                <select name="action" class="form-select">
                    <option value="">All Actions</option>
                    <?php foreach ($actionTypes as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $actionFilter === $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Date</label>
                <input type="date" name="date" class="form-input" value="<?php echo escape($dateFilter); ?>">
            </div>

            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" class="form-input" placeholder="IP, Location, URL..." value="<?php echo escape($searchQuery); ?>">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filter</button>
                <a href="activity-logs.php" class="btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- Results Info -->
    <div class="results-info">
        <p>Showing <?php echo number_format(count($activities)); ?> of <?php echo number_format($totalRecords); ?> activities</p>
    </div>

    <!-- Activity Table -->
    <div class="table-card">
        <table class="activity-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Action</th>
                    <th>Page</th>
                    <th>Location</th>
                    <th>Device</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                <tr>
                    <td>
                        <div class="time-cell">
                            <strong><?php echo date('M d, Y', strtotime($activity['created_at'])); ?></strong>
                            <span><?php echo date('H:i:s', strtotime($activity['created_at'])); ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="action-badge action-<?php echo $activity['action_type']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $activity['action_type'])); ?>
                        </span>
                    </td>
                    <td class="page-cell">
                        <?php echo escape(substr($activity['page_url'], 0, 50)); ?>
                        <?php if (strlen($activity['page_url']) > 50): ?>...<?php endif; ?>
                    </td>
                    <td>
                        <div class="location-cell">
                            <strong><?php echo escape($activity['city']); ?></strong>
                            <span><?php echo escape($activity['country']); ?></span>
                        </div>
                    </td>
                    <td>
                        <div class="device-cell">
                            <span><?php echo escape($activity['device_type']); ?></span>
                            <small><?php echo escape($activity['browser']); ?></small>
                        </div>
                    </td>
                    <td><code><?php echo escape($activity['ip_address']); ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo $actionFilter ? '&action=' . urlencode($actionFilter) : ''; ?><?php echo $dateFilter ? '&date=' . urlencode($dateFilter) : ''; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" class="page-btn">← Previous</a>
        <?php endif; ?>
        
        <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo $actionFilter ? '&action=' . urlencode($actionFilter) : ''; ?><?php echo $dateFilter ? '&date=' . urlencode($dateFilter) : ''; ?><?php echo $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" class="page-btn">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.activity-logs-page {
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0;
    font-size: 24px;
    color: #333;
    font-weight: 600;
}

.filters-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    margin-bottom: 20px;
}

.filters-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    margin-bottom: 5px;
    font-size: 13px;
    font-weight: 600;
    color: #666;
}

.form-select, .form-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.results-info {
    margin-bottom: 15px;
}

.results-info p {
    color: #666;
    font-size: 14px;
}

.table-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    overflow: hidden;
}

.activity-table {
    width: 100%;
    border-collapse: collapse;
}

.activity-table thead {
    background: #f8f9fa;
}

.activity-table th {
    padding: 12px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    border-bottom: 2px solid #eee;
}

.activity-table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    color: #333;
}

.time-cell {
    display: flex;
    flex-direction: column;
}

.time-cell strong {
    font-size: 13px;
    color: #333;
}

.time-cell span {
    font-size: 12px;
    color: #999;
}

.action-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.action-page_view {
    background: #e3f2fd;
    color: #1976d2;
}

.action-add_to_cart {
    background: #fff3e0;
    color: #f57c00;
}

.action-purchase {
    background: #e8f5e9;
    color: #388e3c;
}

.action-login {
    background: #f3e5f5;
    color: #7b1fa2;
}

.page-cell {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.location-cell {
    display: flex;
    flex-direction: column;
}

.location-cell strong {
    font-size: 13px;
}

.location-cell span {
    font-size: 12px;
    color: #999;
}

.device-cell {
    display: flex;
    flex-direction: column;
}

.device-cell span {
    font-size: 13px;
}

.device-cell small {
    font-size: 12px;
    color: #999;
}

code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
    color: #666;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-top: 20px;
}

.page-btn {
    padding: 8px 16px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
}

.page-btn:hover {
    background: #f8f9fa;
}

.page-info {
    color: #666;
    font-size: 14px;
}

@media (max-width: 768px) {
    .filters-form {
        grid-template-columns: 1fr;
    }
    
    .activity-table {
        font-size: 12px;
    }
    
    .activity-table th,
    .activity-table td {
        padding: 8px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>



