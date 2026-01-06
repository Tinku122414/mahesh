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

// Get cart items
$stmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.stock_quantity,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if cart is empty
if (empty($cart_items)) {
    header('Location: ?page=cart');
    exit();
}

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate total
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['quantity'] * $item['price'];
}

// Process order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = $_POST['shipping_address'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($shipping_address) || empty($payment_method)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $conn->beginTransaction();
            
            // Create order
            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method) 
                VALUES (?, ?, 'pending', ?, ?)
            ");
            $stmt->execute([$user_id, $total_amount, $shipping_address, $payment_method]);
            $order_id = $conn->lastInsertId();
            
            // Add order items
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $stmt = $conn->prepare("
                    UPDATE products SET stock_quantity = stock_quantity - ? 
                    WHERE id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $conn->commit();
            
            // Redirect to order success page
            header('Location: ?page=order_success&order_id=' . $order_id);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error processing order. Please try again.';
        }
    }
}
?>

<div class="container py-4">
    <h2 class="mb-4">Checkout</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Shipping Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Shipping Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Shipping Address *</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method *</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="paypal">PayPal</option>
                                <option value="cash_on_delivery">Cash on Delivery</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Place Order</button>
                    </form>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="row align-items-center mb-3">
                            <div class="col-md-2">
                                <?php if ($item['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="img-fluid rounded">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 60px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                <p class="text-muted mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <strong><?php echo formatPrice($item['quantity'] * $item['price']); ?></strong>
                            </div>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card order-summary">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($total_amount); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>Free</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax:</span>
                        <span>$0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong><?php echo formatPrice($total_amount); ?></strong>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            By placing this order, you agree to our terms of service and privacy policy.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
