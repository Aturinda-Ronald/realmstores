<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header('Location: promotions.php');
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;

    // Get current promotion data
    $stmt = $pdo->prepare("SELECT image FROM promotions WHERE id = ?");
    $stmt->execute([$id]);
    $currentPromo = $stmt->fetch();

    if (!$currentPromo) {
        $error = 'Promotion not found.';
    } else {
        $imageName = $currentPromo['image'];

        // Handle new image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/promotions/';
            $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($fileExt, $allowedExts)) {
                $error = 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP.';
            } else {
                $fileName = uniqid('promo_') . '.' . $fileExt;
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    // Delete old image
                    if (file_exists($uploadDir . $imageName)) {
                        unlink($uploadDir . $imageName);
                    }
                    $imageName = $fileName;
                }
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("UPDATE promotions SET title = ?, description = ?, image = ?, active = ? WHERE id = ?");
            if ($stmt->execute([$title, $description, $imageName, $active, $id])) {
                setMessage('Promotion updated successfully!', 'success');
                header('Location: promotions.php');
                exit;
            } else {
                $error = 'Failed to update promotion.';
            }
        }
    }
}

// Fetch promotion data
$stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
$stmt->execute([$id]);
$promo = $stmt->fetch();

if (!$promo) {
    die('Promotion not found.');
}

include 'includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Edit Promotion #<?php echo $promo['id']; ?></h2>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group">
                <label>Promotion Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($promo['title']); ?>">
            </div>

            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" value="<?php echo htmlspecialchars($promo['description']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Current Image</label>
            <div style="margin-bottom: 10px;">
                <img src="<?php echo BASE_URL; ?>/uploads/promotions/<?php echo $promo['image']; ?>" 
                     alt="Current promotion" style="max-width: 400px; height: auto; border-radius: 4px;">
            </div>
        </div>

        <div class="form-group">
            <label>New Image (optional - leave empty to keep current)</label>
            <input type="file" name="image" accept="image/*">
            <small style="color: #999; display: block; margin-top: 5px;">1200x400px recommended</small>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" name="active" id="active" <?php echo $promo['active'] ? 'checked' : ''; ?>>
                <label for="active">Active (Show on homepage)</label>
            </div>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Update Promotion</button>
            <a href="promotions.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>



