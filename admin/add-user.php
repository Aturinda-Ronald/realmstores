<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($firstName) || empty($lastName) || empty($password)) {
        setMessage('Email, first name, last name, and password are required.', 'error');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setMessage('Invalid email address.', 'error');
    } elseif (strlen($password) < 6) {
        setMessage('Password must be at least 6 characters.', 'error');
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            setMessage('Email address is already in use.', 'error');
        } else {
            // Create user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, phone, address, city, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            
            if ($stmt->execute([$email, $hashed, $firstName, $lastName, $phone, $address, $city])) {
                setMessage('User created successfully!', 'success');
                header('Location: users.php');
                exit;
            } else {
                setMessage('Failed to create user. Please try again.', 'error');
            }
        }
    }
}

include 'includes/header.php';
?>

<style>
.edit-user-container {
    padding: 20px;
    max-width: 800px;
}

.edit-user-header {
    margin-bottom: 30px;
}

.edit-user-header h1 {
    font-size: 24px;
    color: #333;
    font-weight: 600;
    margin: 0 0 10px 0;
}

.form-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 1px 2px rgba(0,0,0,.26);
}

.form-section {
    margin-bottom: 25px;
}

.form-section h3 {
    font-size: 16px;
    color: #333;
    font-weight: 600;
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
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

.form-group small {
    display: block;
    margin-top: 5px;
    color: #999;
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
}
</style>

<div class="edit-user-container">
    <div class="edit-user-header">
        <h1>Add New User</h1>
        <a href="users.php" class="btn-secondary" style="display: inline-block; margin-top: 10px;">← Back to Users</a>
    </div>

    <div class="form-card">
        <form method="POST">
            <!-- Personal Information -->
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required value="<?php echo htmlspecialchars($firstName ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required value="<?php echo htmlspecialchars($lastName ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" placeholder="e.g., +256771331531">
                        <small>Include country code (e.g., +256)</small>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="form-section">
                <h3>Address Information</h3>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($city ?? ''); ?>">
                </div>
            </div>

            <!-- Security -->
            <div class="form-section">
                <h3>Security</h3>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                    <small>Minimum 6 characters.</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Create User</button>
                <a href="users.php" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
