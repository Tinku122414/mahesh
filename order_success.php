<?php
session_start();
require_once '../config/database.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: ?page=login');
    exit();
}

$order_id = $_GET['order_id'] ?? 0;
$db = new Database();
$conn = $db->getConnection();

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, u.first_name, u.last_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, getCurrentUserId()]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: ?page=home');
    exit();
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="text-success mb-3">Order Placed Successfully!</h2>
                    <p class="text-muted mb-4">Thank you for your order. We'll send you an email confirmation shortly.</p>
                    
                    <div class="alert alert-info">
                        <strong>Order Number:</strong> #<?php echo $order['id']; ?><br>
                        <strong>Status:</strong> <span class="badge bg-warning">Pending</span><br>
                        <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?>
                    </div>
                    
                    <div class="text-start">
                        <h5 class="mb-3">Order Details</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatPrice($item['price']); ?></td>
                                        <td><?php echo formatPrice($item['quantity'] * $item['price']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <h5 class="mb-3 mt-4">Shipping Information</h5>
                        <p class="text-muted"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <a href="?page=home" class="btn btn-primary">Continue Shopping</a>
                        <a href="?page=account" class="btn btn-outline-secondary">View Account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
