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

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // Check if category has products
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $product_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($product_count > 0) {
        $error_message = "Cannot delete category with $product_count products. Please move or delete products first.";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $success_message = "Category deleted successfully!";
    }
}

// Handle category addition
if (isset($_POST['add_category'])) {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        $success_message = "Category added successfully!";
    } else {
        $error_message = "Category name is required!";
    }
}

// Get all categories with product counts
$stmt = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Decathlon Admin</title>
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
                        <a class="nav-link active" href="categories.php">
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
                        <h2>Categories Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-circle me-2"></i>Add Category
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
                    
                    <!-- Categories Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Category Name</th>
                                            <th>Description</th>
                                            <th>Products Count</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($category['description'] ?? 'No description'); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $category['product_count']; ?></span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editCategory(<?php echo $category['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <a href="?delete=<?php echo $category['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this category?')">
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
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="category_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_description" class="form-label">Description</label>
                            <textarea class="form-control" id="category_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(categoryId) {
            // This would open an edit modal with category data
            alert('Edit functionality would be implemented here for category ID: ' + categoryId);
        }
    </script>
</body>
</html>
