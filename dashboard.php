<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

// Get database statistics
$db = new Database();
$conn = $db->getConnection();

// Total products
$stmt = $conn->query("SELECT COUNT(*) as total FROM products");
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Active products
$stmt = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
$active_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total orders
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending orders
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total customers
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total revenue
$stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Recent orders
$stmt = $conn->query("SELECT o.*, u.first_name, u.last_name, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent products
$stmt = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT 5");
$recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Decathlon Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .admin-sidebar .nav-link {
            color: white;
            padding: 15px 20px;
            border-radius: 0;
            transition: background-color 0.3s ease;
        }
        .admin-sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        .admin-sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
        }
        .admin-main {
            padding: 30px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #007bff;
        }
    </style>
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
                        <a class="nav-link active" href="dashboard.php">
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
                        <a class="nav-link" href="users.php">
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
                        <h2>Dashboard</h2>
                        <div class="text-muted">
                            Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="bi bi-box text-primary" style="font-size: 2rem;"></i>
                                    <div class="stat-number"><?php echo $total_products; ?></div>
                                    <p class="text-muted mb-0">Total Products</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="bi bi-cart text-success" style="font-size: 2rem;"></i>
                                    <div class="stat-number"><?php echo $total_orders; ?></div>
                                    <p class="text-muted mb-0">Total Orders</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="bi bi-people text-warning" style="font-size: 2rem;"></i>
                                    <div class="stat-number"><?php echo $total_customers; ?></div>
                                    <p class="text-muted mb-0">Customers</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <i class="bi bi-currency-dollar text-info" style="font-size: 2rem;"></i>
                                    <div class="stat-number"><?php echo formatPrice($total_revenue); ?></div>
                                    <p class="text-muted mb-0">Total Revenue</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Orders and Products -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Orders</h5>
                                    <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_orders)): ?>
                                        <p class="text-muted text-center">No orders yet</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Order #</th>
                                                        <th>Customer</th>
                                                        <th>Amount</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_orders as $order): ?>
                                                    <tr>
                                                        <td>#<?php echo $order['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
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
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Products</h5>
                                    <a href="products.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_products)): ?>
                                        <p class="text-muted text-center">No products yet</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Category</th>
                                                        <th>Price</th>
                                                        <th>Stock</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_products as $product): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars(substr($product['name'], 0, 20)); ?></td>
                                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo formatPrice($product['price']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                                                <?php echo $product['stock_quantity']; ?>
                                                            </span>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
