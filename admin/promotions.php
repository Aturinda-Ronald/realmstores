<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $active = isset($_POST['active']) ? 1 : 0;

            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Please upload an image.';
            } else {
                // Handle file upload
                $uploadDir = '../uploads/promotions/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($fileExt, $allowedExts)) {
                    $error = 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP.';
                } else {
                    $fileName = uniqid('promo_') . '.' . $fileExt;
                    $uploadPath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                        $stmt = $pdo->prepare("INSERT INTO promotions (title, description, image, active) VALUES (?, ?, ?, ?)");
                        if ($stmt->execute([$title, $description, $fileName, $active])) {
                            setMessage('Promotion added successfully!', 'success');
                            header('Location: promotions.php');
                            exit;
                        } else {
                            $error = 'Failed to save promotion.';
                        }
                    } else {
                        $error = 'Failed to upload image.';
                    }
                }
            }
        } elseif ($_POST['action'] === 'toggle') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE promotions SET active = NOT active WHERE id = ?");
            if ($stmt->execute([$id])) {
                setMessage('Promotion status updated!', 'success');
                header('Location: promotions.php');
                exit;
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("SELECT image FROM promotions WHERE id = ?");
            $stmt->execute([$id]);
            $promo = $stmt->fetch();

            if ($promo) {
                // Delete image file
                $imagePath = '../uploads/promotions/' . $promo['image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }

                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = ?");
                if ($stmt->execute([$id])) {
                    setMessage('Promotion deleted!', 'success');
                    header('Location: promotions.php');
                    exit;
                }
            }
        }
    }
}

// Get all promotions
$stmt = $pdo->query("SELECT * FROM promotions ORDER BY created_at DESC");
$promotions = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Promotional Slider Management</h2>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <div class="form-row">
            <div class="form-group">
                <label>Promotion Title</label>
                <input type="text" name="title">
            </div>

            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description">
            </div>
        </div>

        <div class="form-group">
            <label>Promotion Image * (1200x400px recommended)</label>
            <input type="file" name="image" accept="image/*" required>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" name="active" id="active" checked>
                <label for="active">Active (Show on homepage)</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Add Promotion</button>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>All Promotions</h2>
    </div>

    <?php if (count($promotions) === 0): ?>
    <p style="text-align: center; padding: 40px; color: #999;">No promotions yet. Add your first promotion above!</p>
    <?php else: ?>
    
    <style>
    .promotions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .promotion-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
    }

    .promotion-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,.2);
    }

    .promotion-image-container {
        width: 100%;
        height: 160px;
        background: #f8f9fa;
        overflow: hidden;
        position: relative;
        border-bottom: 1px solid #eee;
    }

    .promotion-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .promotion-content {
        padding: 15px;
        flex: 1;
    }

    .promotion-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin: 0 0 8px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .promotion-desc {
        font-size: 13px;
        color: #666;
        margin: 0 0 15px 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.5;
        height: 39px; /* approx 2 lines */
    }

    .promotion-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        font-size: 12px;
        color: #999;
    }

    .promotion-footer {
        padding: 15px;
        background: #f8f9fa;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-active { background: #e8f5e9; color: #388e3c; }
    .status-inactive { background: #f5f5f5; color: #666; }

    .action-buttons {
        display: flex;
        gap: 8px;
    }
    </style>

    <div class="promotions-grid">
        <?php foreach ($promotions as $promo): ?>
        <div class="promotion-card">
            <div class="promotion-image-container">
                <img src="<?php echo BASE_URL; ?>/uploads/promotions/<?php echo $promo['image']; ?>" 
                     alt="<?php echo htmlspecialchars($promo['title']); ?>" 
                     class="promotion-image"
                     onerror="this.src='https://via.placeholder.com/300x160?text=No+Image'">
            </div>
            
            <div class="promotion-content">
                <h3 class="promotion-title" title="<?php echo htmlspecialchars($promo['title']); ?>">
                    <?php echo htmlspecialchars($promo['title']); ?>
                </h3>
                <p class="promotion-desc" title="<?php echo htmlspecialchars($promo['description']); ?>">
                    <?php echo htmlspecialchars($promo['description']); ?>
                </p>
                
                <div class="promotion-meta">
                    <span>Created: <?php echo date('M d, Y', strtotime($promo['created_at'])); ?></span>
                </div>
            </div>

            <div class="promotion-footer">
                <span class="status-badge <?php echo $promo['active'] ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $promo['active'] ? 'Active' : 'Inactive'; ?>
                </span>

                <div class="action-buttons">
                    <!-- Edit Button -->
                    <a href="edit-promotion.php?id=<?php echo $promo['id']; ?>" class="icon-btn icon-btn-primary" title="Edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>

                    <!-- Toggle Active/Inactive -->
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                        <button type="submit" class="icon-btn <?php echo $promo['active'] ? 'icon-btn-warning' : 'icon-btn-success'; ?>" title="<?php echo $promo['active'] ? 'Deactivate' : 'Activate'; ?>">
                            <?php if ($promo['active']): ?>
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
                        </button>
                    </form>

                    <!-- Delete Button -->
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this promotion?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                        <button type="submit" class="icon-btn icon-btn-danger" title="Delete">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>



