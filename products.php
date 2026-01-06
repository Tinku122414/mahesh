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

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Delete product and related images
    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        $stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        $stmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        
        $conn->commit();
        $success_message = "Product deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error deleting product: " . $e->getMessage();
    }
}

// Get all products with category names
$stmt = $conn->query("
    SELECT p.*, c.name as category_name,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Decathlon Admin</title>
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
                        <a class="nav-link active" href="products.php">
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
                        <h2>Products Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="bi bi-plus-circle me-2"></i>Add Product
                        </button>
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
                    
                    <!-- Products Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <?php if ($product['image_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</small>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo formatPrice($product['price']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                                    <?php echo $product['stock_quantity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editProduct(<?php echo $product['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <a href="?delete=<?php echo $product['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this product?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
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
    
    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="product_actions.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Product Name</label>
                                    <input type="text" class="form-control" id="product_name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_category" class="form-label">Category</label>
                                    <select class="form-select" id="product_category" name="category_id">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="product_description" class="form-label">Description</label>
                            <textarea class="form-control" id="product_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="product_price" class="form-label">Price</label>
                                    <input type="number" class="form-control" id="product_price" name="price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="product_stock" class="form-label">Stock Quantity</label>
                                    <input type="number" class="form-control" id="product_stock" name="stock_quantity" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="product_status" class="form-label">Status</label>
                                    <select class="form-select" id="product_status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="product_image" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="product_image" name="image_url" placeholder="https://example.com/image.jpg">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(productId) {
            // This would open an edit modal with product data
            alert('Edit functionality would be implemented here for product ID: ' + productId);
        }
    </script>
</body>
</html>
