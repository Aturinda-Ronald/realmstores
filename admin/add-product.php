<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        $errors[] = 'Invalid request';
    } else {
        $categoryId = (int)$_POST['category_id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (int)$_POST['price'];
        $variantName = trim($_POST['variant_name'] ?? '');
        $variantValues = trim($_POST['variant_values'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;
        $isSponsored = isset($_POST['is_sponsored']) ? 1 : 0;
        $rating = (float)($_POST['rating'] ?? 0);
        $isHot = isset($_POST['is_hot']) ? 1 : 0;
        $comparePrice = !empty($_POST['compare_price']) ? (int)$_POST['compare_price'] : null;

        // Validation
        if (empty($name)) $errors[] = 'Product name is required';
        if (empty($description)) $errors[] = 'Description is required';
        if ($price <= 0) $errors[] = 'Price must be greater than 0';
        if ($categoryId <= 0) $errors[] = 'Please select a category';

        if (empty($errors)) {
            try {
                // Process variant values
                $variantArray = [];
                if (!empty($variantValues)) {
                    $variantArray = array_map('trim', explode(',', $variantValues));
                    $variantArray = array_filter($variantArray);
                }
                $variantJson = !empty($variantArray) ? json_encode($variantArray) : null;

                // Upload images
                $image1 = null;
                $image2 = null;
                $image3 = null;

                try {
                    $image1 = uploadProductImage($_FILES, 'image1');
                    $image2 = uploadProductImage($_FILES, 'image2');
                    $image3 = uploadProductImage($_FILES, 'image3');
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }

                if (empty($errors)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            category_id, name, description, price, compare_price,
                            image1, image2, image3,
                            variant_name, variant_values, featured, is_sponsored,
                            rating, is_hot
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $categoryId, $name, $description, $price, $comparePrice,
                        $image1, $image2, $image3,
                        $variantName, $variantJson, $featured, $isSponsored,
                        $rating, $isHot
                    ]);

                    setMessage('Product added successfully');
                    redirect(ADMIN_URL . '/products.php');
                }
            } catch (Exception $e) {
                $errors[] = 'Error adding product: ' . $e->getMessage();
            }
        }
    }
}

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Add New Product</h2>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="margin-left: 20px;">
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description *</label>
            <textarea name="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Price (UGX) *</label>
                <input type="number" name="price" value="<?php echo isset($_POST['price']) ? (int)$_POST['price'] : ''; ?>" min="0" required>
            </div>

            <div class="form-group">
                <label>Compare Price (UGX) - Optional</label>
                <input type="number" name="compare_price" value="<?php echo isset($_POST['compare_price']) ? (int)$_POST['compare_price'] : ''; ?>" min="0" placeholder="Original price (for discount)">
            </div>

            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="featured" value="1" <?php echo (isset($_POST['featured'])) ? 'checked' : ''; ?>>
                    <span>Featured Product</span>
                </label>
                <label class="checkbox-group" style="margin-top: 10px;">
                    <input type="checkbox" name="is_sponsored" value="1" <?php echo (isset($_POST['is_sponsored'])) ? 'checked' : ''; ?>>
                    <span>Sponsored Product</span>
                </label>
                <label class="checkbox-group" style="margin-top: 10px;">
                    <input type="checkbox" name="is_hot" value="1" <?php echo (isset($_POST['is_hot'])) ? 'checked' : ''; ?>>
                    <span>Hot Product</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>Rating (0-5)</label>
            <input type="number" name="rating" value="<?php echo isset($_POST['rating']) ? (float)$_POST['rating'] : '0'; ?>" min="0" max="5" step="0.1">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Variant Type (e.g., Color, Size, Wattage)</label>
                <input type="text" name="variant_name" value="<?php echo isset($_POST['variant_name']) ? htmlspecialchars($_POST['variant_name']) : ''; ?>" placeholder="e.g., Color">
            </div>

            <div class="form-group">
                <label>Variant Values (comma-separated)</label>
                <input type="text" name="variant_values" value="<?php echo isset($_POST['variant_values']) ? htmlspecialchars($_POST['variant_values']) : ''; ?>" placeholder="e.g., White, Black, Gold">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Image 1 (Main) - Max 2MB, JPG/PNG/WEBP</label>
                <input type="file" name="image1" accept="image/jpeg,image/png,image/webp">
            </div>

            <div class="form-group">
                <label>Image 2 - Max 2MB, JPG/PNG/WEBP</label>
                <input type="file" name="image2" accept="image/jpeg,image/png,image/webp">
            </div>

            <div class="form-group">
                <label>Image 3 - Max 2MB, JPG/PNG/WEBP</label>
                <input type="file" name="image3" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn-primary">Add Product</button>
            <a href="<?php echo ADMIN_URL; ?>/products.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>



