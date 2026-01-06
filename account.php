<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: ?page=login');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$user_id = getCurrentUserId();

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if (isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    $stmt = $conn->prepare("
        UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$first_name, $last_name, $phone, $address, $user_id])) {
        $success_message = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update session
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    } else {
        $error_message = "Error updating profile. Please try again.";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "Please fill in all password fields";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $password_error = "Password must be at least 6 characters long";
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $user_id])) {
                $password_success = "Password changed successfully!";
            } else {
                $password_error = "Error changing password. Please try again.";
            }
        } else {
            $password_error = "Current password is incorrect";
        }
    }
}

// Get user's orders
$stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h2 class="mb-4">My Account</h2>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                    <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="badge bg-primary">Customer</span>
                </div>
            </div>
            
            <!-- Account Navigation -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="nav flex-column nav-pills">
                        <a class="nav-link active" data-bs-toggle="pill" href="#profile">Profile</a>
                        <a class="nav-link" data-bs-toggle="pill" href="#orders">My Orders</a>
                        <a class="nav-link" data-bs-toggle="pill" href="#security">Security</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-pane fade" id="orders">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">My Orders</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($orders)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-cart3" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="text-muted mt-3">You haven't placed any orders yet</p>
                                    <a href="?page=products" class="btn btn-primary">Start Shopping</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                    <td><?php echo $order['item_count']; ?> items</td>
                                                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $order['status'] == 'pending' ? 'warning' : 
                                                                 $order['status'] == 'processing' ? 'info' : 
                                                                 $order['status'] == 'shipped' ? 'primary' : 
                                                                 $order['status'] == 'delivered' ? 'success' : 'danger'; 
                                                        ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                            View
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Security Tab -->
                <div class="tab-pane fade" id="security">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($password_success)): ?>
                                <div class="alert alert-success"><?php echo htmlspecialchars($password_success); ?></div>
                            <?php endif; ?>
                            
                            <?php if (isset($password_error)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($password_error); ?></div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <small class="text-muted">Password must be at least 6 characters long</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewOrder(orderId) {
    // This would show order details in a modal
    alert('Order details for order #' + orderId + ' would be shown here');
}
</script>
