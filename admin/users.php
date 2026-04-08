<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

// Handle user deactivation/activation
if (isset($_GET['toggle_status'])) {
    $userId = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$userId]);
    setMessage('User status updated!', 'success');
    header('Location: users.php');
    exit;
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    setMessage('User deleted successfully!', 'success');
    header('Location: users.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search and Sort
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Allowed sort columns
$allowedSort = ['id', 'first_name', 'last_name', 'email', 'created_at'];
if (!in_array($sortBy, $allowedSort)) {
    $sortBy = 'created_at';
}

// Build query
$where = [];
$params = [];

if (!empty($searchQuery)) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countQuery = "SELECT COUNT(*) FROM users $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users
$query = "SELECT * FROM users $whereClause ORDER BY $sortBy $sortOrder LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

include 'includes/header.php';
?>

<style>
.users-container {
    padding: 20px;
}

.users-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.users-header h1 {
    font-size: 24px;
    color: #333;
    font-weight: 600;
    margin: 0;
}

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-size: 13px;
    font-weight: 600;
    color: #666;
}

.search-input, .sort-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.users-table-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    overflow: hidden;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table thead {
    background: #f8f9fa;
}

.users-table th {
    padding: 12px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    border-bottom: 2px solid #eee;
}

.users-table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    color: #333;
}

.users-table tbody tr:hover {
    background: #fafafa;
}

.user-name {
    text-transform: capitalize;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #e8f5e9;
    color: #388e3c;
}

.status-inactive {
    background: #ffebee;
    color: #d32f2f;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
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

@media (max-width: 768px) {
    .users-container {
        padding: 10px;
    }
    
    .users-header h1 {
        font-size: 20px;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .filter-group {
        width: 100%;
        max-width: none !important;
    }
    
    .filter-section {
        padding: 15px;
    }
    
    /* Make table card-style on mobile */
    .users-table-card {
        overflow-x: auto;
    }
    
    .users-table {
        display: block;
        font-size: 14px;
    }
    
    .users-table thead {
        display: none;
    }
    
    .users-table tbody {
        display: block;
    }
    
    .users-table tr {
        display: block;
        margin-bottom: 15px;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 15px;
        background: white;
    }
    
    .users-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f5f5f5;
    }
    
    .users-table td:last-child {
        border-bottom: none;
        padding-top: 12px;
        justify-content: flex-start;
    }
    
    .users-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #666;
        font-size: 12px;
        text-transform: uppercase;
    }
    
    .users-table td:last-child::before {
        display: none;
    }
    
    .pagination {
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .page-btn,
    .page-num {
        padding: 6px 12px;
        font-size: 13px;
    }
}
</style>

<div class="users-container">
    <div class="users-header">
        <h1>Users (<?php echo $totalUsers; ?>)</h1>
        <a href="add-user.php" class="btn-primary">Add New User</a>
    </div>

    <!-- Search and Filter -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>Search Users</label>
                <input type="text" name="search" placeholder="Search by name, email, phone, city..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>" class="search-input">
            </div>

            <div class="filter-group" style="max-width: 200px;">
                <label>Sort By</label>
                <select name="sort" class="sort-select">
                    <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Registration Date</option>
                    <option value="first_name" <?php echo $sortBy === 'first_name' ? 'selected' : ''; ?>>First Name</option>
                    <option value="last_name" <?php echo $sortBy === 'last_name' ? 'selected' : ''; ?>>Last Name</option>
                    <option value="email" <?php echo $sortBy === 'email' ? 'selected' : ''; ?>>Email</option>
                </select>
            </div>

            <div class="filter-group" style="max-width: 150px;">
                <label>Order</label>
                <select name="order" class="sort-select">
                    <option value="desc" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    <option value="asc" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                </select>
            </div>

            <div style="padding-top: 20px;">
                <button type="submit" class="btn-primary">Apply</button>
                <?php if (!empty($searchQuery) || $sortBy !== 'created_at'): ?>
                <a href="users.php" class="btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <?php if (count($users) > 0): ?>
    <div class="users-table-card">
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td data-label="ID"><?php echo $user['id']; ?></td>
                    <td data-label="Name" class="user-name">
                        <?php echo htmlspecialchars(ucwords(strtolower($user['first_name'] . ' ' . $user['last_name']))); ?>
                    </td>
                    <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td data-label="Phone"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
                    <td data-label="City" class="user-name"><?php echo htmlspecialchars($user['city'] ? ucwords(strtolower($user['city'])) : '-'); ?></td>
                    <td data-label="Status">
                        <span class="status-badge <?php echo ($user['is_active'] ?? 1) ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo ($user['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td data-label="Registered"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <!-- Edit Button -->
                            <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="icon-btn icon-btn-primary" title="Edit">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>

                            <!-- Toggle Active/Inactive -->
                            <a href="?toggle_status=<?php echo $user['id']; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $sortBy !== 'created_at' ? '&sort=' . $sortBy : ''; ?><?php echo $sortOrder !== 'DESC' ? '&order=' . strtolower($sortOrder) : ''; ?>&page=<?php echo $page; ?>" 
                               class="icon-btn <?php echo ($user['is_active'] ?? 1) ? 'icon-btn-warning' : 'icon-btn-success'; ?>" 
                               title="<?php echo ($user['is_active'] ?? 1) ? 'Deactivate' : 'Activate'; ?>">
                                <?php if ($user['is_active'] ?? 1): ?>
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

                            <!-- Delete Button -->
                            <a href="?delete=<?php echo $user['id']; ?>" 
                               class="icon-btn icon-btn-danger" 
                               title="Delete"
                               onclick="return confirm('Are you sure you want to delete this user?')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $sortBy !== 'created_at' ? '&sort=' . $sortBy : ''; ?><?php echo $sortOrder !== 'DESC' ? '&order=' . strtolower($sortOrder) : ''; ?>" class="page-btn">← Previous</a>
        <?php endif; ?>

        <div style="display: flex; gap: 5px; align-items: center;">
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            for ($i = $startPage; $i <= $endPage; $i++) {
                $activeClass = $i == $page ? 'active' : '';
                echo '<a href="?page=' . $i . (!empty($searchQuery) ? '&search=' . urlencode($searchQuery) : '') . ($sortBy !== 'created_at' ? '&sort=' . $sortBy : '') . ($sortOrder !== 'DESC' ? '&order=' . strtolower($sortOrder) : '') . '" class="page-num ' . $activeClass . '">' . $i . '</a>';
            }
            ?>
        </div>

        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $sortBy !== 'created_at' ? '&sort=' . $sortBy : ''; ?><?php echo $sortOrder !== 'DESC' ? '&order=' . strtolower($sortOrder) : ''; ?>" class="page-btn">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align: center; padding: 60px 20px; color: #666;">
        <p style="font-size: 18px; margin-bottom: 10px;">No users found</p>
        <?php if (!empty($searchQuery)): ?>
        <a href="users.php" class="btn-secondary">Clear Search</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>



