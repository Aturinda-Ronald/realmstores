<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

// Handle toggle active status
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE categories SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    setMessage('Category status updated!', 'success');
    header('Location: categories.php');
    exit;
}

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        setMessage('Invalid request', 'error');
    } elseif (empty($name)) {
        setMessage('Category name is required', 'error');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, is_active) VALUES (?, 1)");
            $stmt->execute([$name]);
            setMessage('Category added successfully');
            redirect(ADMIN_URL . '/categories.php');
        } catch (Exception $e) {
            setMessage('Error adding category: ' . $e->getMessage(), 'error');
        }
    }
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        setMessage('Invalid request', 'error');
    } elseif (empty($name)) {
        setMessage('Category name is required', 'error');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            setMessage('Category updated successfully');
            redirect(ADMIN_URL . '/categories.php');
        } catch (Exception $e) {
            setMessage('Error updating category: ' . $e->getMessage(), 'error');
        }
    }
}

// Search functionality
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all categories
$sql = "
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
";

if (!empty($searchQuery)) {
    $sql .= " WHERE c.name LIKE :search";
}

$sql .= " GROUP BY c.id ORDER BY c.name ASC";

$stmt = $pdo->prepare($sql);

if (!empty($searchQuery)) {
    $stmt->execute(['search' => "%$searchQuery%"]);
} else {
    $stmt->execute();
}

$categories = $stmt->fetchAll();

// Get category for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCategory = $stmt->fetch();
}

include 'includes/header.php';
?>

<style>
.categories-container {
    padding: 20px;
}

.categories-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.categories-header h1 {
    font-size: 24px;
    color: #333;
    font-weight: 600;
    margin: 0;
}

.add-form-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    margin-bottom: 30px;
    max-width: 600px;
}

.add-form-card h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #333;
    font-weight: 600;
}

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
}

.search-input {
    flex: 1;
    max-width: 400px;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.category-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
    transition: transform 0.2s, box-shadow 0.2s;
}

.category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,.2);
}

.category-card-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
}

.category-name {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.category-status {
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

.category-info {
    margin-bottom: 15px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 14px;
    color: #666;
}

.info-row svg {
    flex-shrink: 0;
}

.category-actions {
    display: flex;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

@media (max-width: 768px) {
    .categories-container {
        padding: 10px;
    }
    
    .categories-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .categories-header h1 {
        font-size: 20px;
    }
    
    .search-section {
        padding: 15px;
    }
    
    .search-input {
        max-width: none;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .add-form-card {
        padding: 20px;
    }
}
</style>

<div class="categories-container">
    <div class="categories-header">
        <h1>Categories (<?php echo count($categories); ?>)</h1>
    </div>

    <!-- Add/Edit Form -->
    <div class="add-form-card">
        <h3><?php echo $editCategory ? 'Edit Category' : 'Add New Category'; ?></h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <?php if ($editCategory): ?>
            <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Category Name</label>
                <input type="text" name="name" value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>" required>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px;">
                <button type="submit" name="<?php echo $editCategory ? 'edit_category' : 'add_category'; ?>" class="btn-primary">
                    <?php echo $editCategory ? 'Update Category' : 'Add Category'; ?>
                </button>
                
                <?php if ($editCategory): ?>
                <a href="<?php echo ADMIN_URL; ?>/categories.php" class="btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Search -->
    <div class="search-section">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search categories..." value="<?php echo htmlspecialchars($searchQuery); ?>" class="search-input">
            <button type="submit" class="btn-primary">Search</button>
            <?php if (!empty($searchQuery)): ?>
            <a href="categories.php" class="btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Categories Grid -->
    <?php if (count($categories) > 0): ?>
    <div class="categories-grid">
        <?php foreach ($categories as $category): ?>
        <div class="category-card">
            <div class="category-card-header">
                <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                <span class="category-status <?php echo ($category['is_active'] ?? 1) ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo ($category['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                </span>
            </div>

            <div class="category-info">
                <div class="info-row">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 7h-3a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2H4"></path>
                        <path d="M10 11V7M14 11V7"></path>
                        <path d="M2 7h20v14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7z"></path>
                    </svg>
                    <span><strong><?php echo $category['product_count']; ?></strong> Products</span>
                </div>
                <div class="info-row">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>ID: #<?php echo $category['id']; ?></span>
                </div>
            </div>

            <div class="category-actions">
                <!-- Edit Button -->
                <a href="<?php echo ADMIN_URL; ?>/categories.php?edit=<?php echo $category['id']; ?>" class="icon-btn icon-btn-primary" title="Edit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </a>

                <!-- Toggle Active/Inactive -->
                <a href="?toggle_status=<?php echo $category['id']; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="icon-btn <?php echo ($category['is_active'] ?? 1) ? 'icon-btn-warning' : 'icon-btn-success'; ?>" 
                   title="<?php echo ($category['is_active'] ?? 1) ? 'Deactivate' : 'Activate'; ?>">
                    <?php if ($category['is_active'] ?? 1): ?>
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
                <a href="<?php echo ADMIN_URL; ?>/delete-category.php?id=<?php echo $category['id']; ?>" 
                   class="icon-btn icon-btn-danger" 
                   title="Delete"
                   onclick="return confirm('Are you sure you want to delete this category? This will also affect all products in this category.')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="no-results">
        <p style="font-size: 18px; margin-bottom: 10px;">No categories found</p>
        <?php if (!empty($searchQuery)): ?>
        <a href="categories.php" class="btn-secondary">Clear Search</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>



