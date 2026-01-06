<?php
session_start();
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();
$cart_items = [];
$total_amount = 0;

// Handle cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add to cart
    if (isset($_POST['add_to_cart'])) {
        $product_id = $_POST['product_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;
        
        // Validate product exists and has stock
        $stmt = $conn->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product && $product['stock_quantity'] >= $quantity) {
            if (isLoggedIn()) {
                // Add to database cart
                $user_id = getCurrentUserId();
                $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
                
                if ($stmt->fetch()) {
                    // Update existing cart item
                    $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$quantity, $user_id, $product_id]);
                } else {
                    // Add new cart item
                    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $product_id, $quantity]);
                }
            } else {
                // Add to session cart
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
            }
            
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            $cart_count = isLoggedIn() ? getCartCount($conn, getCurrentUserId()) : getGuestCartCount();
            echo json_encode(['success' => true, 'cart_count' => $cart_count]);
            exit();
        }
    }
    
    // Update cart quantities
    if (isset($_POST['update_cart'])) {
        $quantities = $_POST['quantities'] ?? [];
        
        if (isLoggedIn()) {
            $user_id = getCurrentUserId();
            foreach ($quantities as $product_id => $quantity) {
                if ($quantity > 0) {
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$quantity, $user_id, $product_id]);
                }
            }
        } else {
            foreach ($quantities as $product_id => $quantity) {
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id] = $quantity;
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }
            }
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        $cart_count = isLoggedIn() ? getCartCount($conn, getCurrentUserId()) : getGuestCartCount();
        echo json_encode(['success' => true, 'cart_count' => $cart_count]);
        exit();
    }
    
    // Remove item from cart
    if (isset($_POST['remove_item'])) {
        $product_id = $_POST['remove_item'];
        
        if (isLoggedIn()) {
            $user_id = getCurrentUserId();
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
        
        // Return JSON response
        header('Content-Type: application/json');
        $cart_count = isLoggedIn() ? getCartCount($conn, getCurrentUserId()) : getGuestCartCount();
        echo json_encode(['success' => true, 'cart_count' => $cart_count]);
        exit();
    }
}

// Get cart items
if (isLoggedIn()) {
    $user_id = getCurrentUserId();
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.stock_quantity,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total
    foreach ($cart_items as $item) {
        $total_amount += $item['quantity'] * $item['price'];
    }
} else {
    // Get cart from session
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $product_ids = array_keys($_SESSION['cart']);
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $stmt = $conn->prepare("
            SELECT p.*, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
            FROM products p
            WHERE p.id IN ($placeholders)
        ");
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            $cart_items[] = [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'name' => $product['name'],
                'price' => $product['price'],
                'stock_quantity' => $product['stock_quantity'],
                'image_url' => $product['image_url']
            ];
            $total_amount += $quantity * $product['price'];
        }
    }
}
?>

<div class="container py-4">
    <h2 class="mb-4">Shopping Cart</h2>
    
    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart3" style="font-size: 4rem; color: #ccc;"></i>
            <h4 class="mt-3">Your cart is empty</h4>
            <p class="text-muted">Add some products to get started!</p>
            <a href="?page=products" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item row align-items-center mb-3">
                                <div class="col-md-2">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="img-fluid rounded">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 80px;">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <h6><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <p class="text-muted mb-0"><?php echo formatPrice($item['price']); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <input type="number" class="form-control quantity-selector" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['stock_quantity']; ?>"
                                               data-product-id="<?php echo $item['product_id']; ?>">
                                        <span class="input-group-text">qty</span>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <strong><?php echo formatPrice($item['quantity'] * $item['price']); ?></strong>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button class="btn btn-sm btn-outline-danger remove-from-cart" 
                                            data-product-id="<?php echo $item['product_id']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
                        
                        <a href="?page=checkout" class="btn btn-primary w-100 mb-2">Proceed to Checkout</a>
                        <a href="?page=products" class="btn btn-outline-secondary w-100">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
