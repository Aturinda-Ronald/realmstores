<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    setMessage('Invalid product', 'error');
    redirect(ADMIN_URL . '/products.php');
}

// Get product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setMessage('Product not found', 'error');
    redirect(ADMIN_URL . '/products.php');
}

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

                // Handle image deletions
                if (isset($_POST['delete_image1']) && $product['image1']) {
                    deleteProductImage($product['image1']);
                    $product['image1'] = null;
                }
                if (isset($_POST['delete_image2']) && $product['image2']) {
                    deleteProductImage($product['image2']);
                    $product['image2'] = null;
                }
                if (isset($_POST['delete_image3']) && $product['image3']) {
                    deleteProductImage($product['image3']);
                    $product['image3'] = null;
                }

                // Upload new images
                $image1 = $product['image1'];
                $image2 = $product['image2'];
                $image3 = $product['image3'];

                try {
                    $newImage1 = uploadProductImage($_FILES, 'image1');
                    if ($newImage1) {
                        if ($image1) deleteProductImage($image1);
                        $image1 = $newImage1;
                    }

                    $newImage2 = uploadProductImage($_FILES, 'image2');
                    if ($newImage2) {
                        if ($image2) deleteProductImage($image2);
                        $image2 = $newImage2;
                    }

                    $newImage3 = uploadProductImage($_FILES, 'image3');
                    if ($newImage3) {
                        if ($image3) deleteProductImage($image3);
                        $image3 = $newImage3;
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }

                if (empty($errors)) {
                    $stmt = $pdo->prepare("
                        UPDATE products SET
                            category_id = ?, name = ?, description = ?, price = ?, compare_price = ?,
                            image1 = ?, image2 = ?, image3 = ?,
                            variant_name = ?, variant_values = ?, featured = ?, is_sponsored = ?,
                            rating = ?, is_hot = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $categoryId, $name, $description, $price, $comparePrice,
                        $image1, $image2, $image3,
                        $variantName, $variantJson, $featured, $isSponsored,
                        $rating, $isHot,
                        $productId
                    ]);

                    setMessage('Product updated successfully');
                    redirect(ADMIN_URL . '/products.php');
                }
            } catch (Exception $e) {
                $errors[] = 'Error updating product: ' . $e->getMessage();
            }
        }
    }
}

// Get categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Decode variants
$variantValuesStr = '';
if ($product['variant_values']) {
    $decoded = json_decode($product['variant_values'], true);
    if (is_array($decoded)) {
        $variantValuesStr = implode(', ', $decoded);
    }
}

include 'includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Edit Product</h2>
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
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description *</label>
            <textarea name="description" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Price (UGX) *</label>
                <input type="number" name="price" value="<?php echo $product['price']; ?>" min="0" required>
            </div>

            <div class="form-group">
                <label>Compare Price (UGX) - Optional</label>
                <input type="number" name="compare_price" value="<?php echo isset($product['compare_price']) ? $product['compare_price'] : ''; ?>" min="0" placeholder="Original price (for discount)">
            </div>

            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="featured" value="1" <?php echo $product['featured'] ? 'checked' : ''; ?>>
                    <span>Featured Product</span>
                </label>
                <label class="checkbox-group" style="margin-top: 10px;">
                    <input type="checkbox" name="is_sponsored" value="1" <?php echo (isset($product['is_sponsored']) && $product['is_sponsored']) ? 'checked' : ''; ?>>
                    <span>Sponsored Product</span>
                </label>
                <label class="checkbox-group" style="margin-top: 10px;">
                    <input type="checkbox" name="is_hot" value="1" <?php echo (isset($product['is_hot']) && $product['is_hot']) ? 'checked' : ''; ?>>
                    <span>Hot Product</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label>Rating (0-5)</label>
            <input type="number" name="rating" value="<?php echo isset($product['rating']) ? (float)$product['rating'] : '0'; ?>" min="0" max="5" step="0.1">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Variant Type (e.g., Color, Size, Wattage)</label>
                <input type="text" name="variant_name" value="<?php echo htmlspecialchars($product['variant_name']); ?>" placeholder="e.g., Color">
            </div>

            <div class="form-group">
                <label>Variant Values (comma-separated)</label>
                <input type="text" name="variant_values" value="<?php echo htmlspecialchars($variantValuesStr); ?>" placeholder="e.g., White, Black, Gold">
            </div>
        </div>

        <!-- Image 1 -->
        <div class="form-group">
            <label>Image 1 (Main) - Max 2MB, JPG/PNG/WEBP</label>
            <?php if ($product['image1']): ?>
            <div class="image-preview">
                <img src="<?php echo UPLOAD_URL . $product['image1']; ?>" alt="Image 1">
            </div>
            <label class="checkbox-group" style="margin-top: 10px;">
                <input type="checkbox" name="delete_image1" value="1">
                <span>Delete current image</span>
            </label>
            <?php endif; ?>
            <input type="file" name="image1" accept="image/jpeg,image/png,image/webp" style="margin-top: 10px;">
        </div>

        <!-- Image 2 -->
        <div class="form-group">
            <label>Image 2 - Max 2MB, JPG/PNG/WEBP</label>
            <?php if ($product['image2']): ?>
            <div class="image-preview">
                <img src="<?php echo UPLOAD_URL . $product['image2']; ?>" alt="Image 2">
            </div>
            <label class="checkbox-group" style="margin-top: 10px;">
                <input type="checkbox" name="delete_image2" value="1">
                <span>Delete current image</span>
            </label>
            <?php endif; ?>
            <input type="file" name="image2" accept="image/jpeg,image/png,image/webp" style="margin-top: 10px;">
        </div>

        <!-- Image 3 -->
        <div class="form-group">
            <label>Image 3 - Max 2MB, JPG/PNG/WEBP</label>
            <?php if ($product['image3']): ?>
            <div class="image-preview">
                <img src="<?php echo UPLOAD_URL . $product['image3']; ?>" alt="Image 3">
            </div>
            <label class="checkbox-group" style="margin-top: 10px;">
                <input type="checkbox" name="delete_image3" value="1">
                <span>Delete current image</span>
            </label>
            <?php endif; ?>
            <input type="file" name="image3" accept="image/jpeg,image/png,image/webp" style="margin-top: 10px;">
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn-primary">Update Product</button>
            <a href="<?php echo ADMIN_URL; ?>/products.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>



