<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Don't allow deletion of admin users
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['role'] == 'admin') {
        $error_message = "Cannot delete admin users!";
    } else {
        $conn->beginTransaction();
        try {
            // Delete user's cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Delete user (orders will remain for record keeping)
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $conn->commit();
            $success_message = "User deleted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error deleting user: " . $e->getMessage();
        }
    }
}

// Get all users with order counts
$stmt = $conn->query("
    SELECT u.*, COUNT(o.id) as order_count
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Decathlon Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <div class="admin-sidebar">
                    <div class="p-3">
                        <h4 class="text-white">DECATHLON</h4>
                        <small class="text-white-50">Admin Panel</small>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-box me-2"></i> Products
                        </a>
                        <a class="nav-link" href="categories.php">
                            <i class="bi bi-tags me-2"></i> Categories
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart me-2"></i> Orders
                        </a>
                        <a class="nav-link active" href="users.php">
                            <i class="bi bi-people me-2"></i> Users
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10">
                <div class="admin-main">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Users Management</h2>
                    </div>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Role</th>
                                            <th>Orders</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                        <?php if ($user['address']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($user['address'], 0, 30)); ?>...</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '<span class="text-muted">Not provided</span>'; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $user['order_count']; ?></span>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewUserDetails(<?php echo $user['id']; ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <?php if ($user['role'] != 'admin'): ?>
                                                        <a href="?delete=<?php echo $user['id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this user?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Details Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetailsContent">
                    <!-- User details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewUserDetails(userId) {
            // Load user details via AJAX
            fetch('user_details.php?id=' + userId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('userDetailsContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
                })
                .catch(error => {
                    console.error('Error loading user details:', error);
                    alert('Error loading user details');
                });
        }
    </script>
</body>
</html>
