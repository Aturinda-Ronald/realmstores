<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

$success = '';
$error = '';
$admin_success = '';
$admin_error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($current_password, $admin['password'])) {
            // Update password
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['admin_id']]);

            setMessage('Password updated successfully!', 'success');
            header('Location: settings.php');
            exit;
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

// Handle new admin creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $new_username = trim($_POST['new_username'] ?? '');
    $new_admin_password = $_POST['new_admin_password'] ?? '';
    $confirm_admin_password = $_POST['confirm_admin_password'] ?? '';

    if (empty($new_username) || empty($new_admin_password) || empty($confirm_admin_password)) {
        $admin_error = 'All fields are required.';
    } elseif (strlen($new_username) < 3) {
        $admin_error = 'Username must be at least 3 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
        $admin_error = 'Username can only contain letters, numbers, and underscores.';
    } elseif ($new_admin_password !== $confirm_admin_password) {
        $admin_error = 'Passwords do not match.';
    } elseif (strlen($new_admin_password) < 6) {
        $admin_error = 'Password must be at least 6 characters long.';
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->execute([$new_username]);
        
        if ($stmt->fetch()) {
            $admin_error = 'Username already exists. Please choose a different username.';
        } else {
            // Create new admin
            $hashed = password_hash($new_admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
            
            if ($stmt->execute([$new_username, $hashed])) {
                setMessage('New admin created successfully!', 'success');
                header('Location: settings.php');
                exit;
            } else {
                $admin_error = 'Failed to create admin user. Please try again.';
            }
        }
    }
}

// Fetch all admin users for display
$stmt = $pdo->query("SELECT id, username, created_at FROM admin_users ORDER BY created_at DESC");
$admin_users = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="panel">
    <div class="panel-header">
        <h2>Admin Settings</h2>
    </div>

    <!-- Change Password Section -->
    <div style="max-width: 600px; margin-bottom: 40px;">
        <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid #2c3d4f; padding-bottom: 10px;">Change Password</h3>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="change_password" value="1">
            
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="6">
                <small style="color: #666; display: block; margin-top: 5px;">Minimum 6 characters</small>
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="6">
            </div>

            <button type="submit" class="btn-primary">Update Password</button>
        </form>
    </div>

    <!-- Admin Management Section -->
    <div style="margin-top: 40px;">
        <h3 style="margin-bottom: 20px; color: #333; border-bottom: 2px solid #2c3d4f; padding-bottom: 10px;">Admin Management</h3>

        <!-- Create New Admin Form -->
        <div style="max-width: 600px; margin-bottom: 30px;">
            <h4 style="margin-bottom: 15px; color: #555;">Create New Admin</h4>

            <?php if ($admin_error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($admin_error); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="create_admin" value="1">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="new_username" required minlength="3" pattern="[a-zA-Z0-9_]+" 
                           placeholder="Enter username" value="<?php echo htmlspecialchars($_POST['new_username'] ?? ''); ?>">
                    <small style="color: #666; display: block; margin-top: 5px;">Letters, numbers, and underscores only. Minimum 3 characters.</small>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="new_admin_password" required minlength="6" placeholder="Enter password">
                    <small style="color: #666; display: block; margin-top: 5px;">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_admin_password" required minlength="6" placeholder="Confirm password">
                </div>

                <button type="submit" class="btn-primary">Create Admin</button>
            </form>
        </div>

        <!-- List of Existing Admins -->
        <div style="margin-top: 30px;">
            <h4 style="margin-bottom: 15px; color: #555;">Existing Admins</h4>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Created Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admin_users as $admin_user): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($admin_user['username']); ?></strong>
                            <?php if ($admin_user['id'] == $_SESSION['admin_id']): ?>
                                <span style="color: #28a745; font-size: 12px; margin-left: 8px;">(You)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y g:i A', strtotime($admin_user['created_at'])); ?></td>
                        <td>
                            <span style="color: #28a745; font-weight: 500;">Active</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
