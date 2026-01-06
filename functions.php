<?php
// Helper functions for the e-commerce website

// Format price with currency
function formatPrice($price) {
    return "$" . number_format($price, 2);
}

// Get cart count for logged-in user
function getCartCount($conn, $user_id) {
    $query = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    $query->execute([$user_id]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result["count"] ?? 0;
}

// Get cart count for guest user
function getGuestCartCount() {
    if (isset($_SESSION["cart"]) && !empty($_SESSION["cart"])) {
        return array_sum($_SESSION["cart"]);
    }
    return 0;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION["user_id"] ?? null;
}

// Get current user name
function getCurrentUserName() {
    return $_SESSION["user_name"] ?? "";
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, "UTF-8");
}

// Get featured products
function getFeaturedProducts($conn, $limit = 8) {
    $query = $conn->prepare("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active' AND p.stock_quantity > 0
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $query->execute([$limit]);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

// Get categories
function getCategories($conn) {
    $query = $conn->prepare("
        SELECT c.*, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
        GROUP BY c.id
        ORDER BY c.name
    ");
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

// Get product by ID
function getProductById($conn, $product_id) {
    $query = $conn->prepare("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.status = 'active'
    ");
    $query->execute([$product_id]);
    return $query->fetch(PDO::FETCH_ASSOC);
}

// Get product images
function getProductImages($conn, $product_id) {
    $query = $conn->prepare("
        SELECT * FROM product_images 
        WHERE product_id = ? 
        ORDER BY is_primary DESC, id ASC
    ");
    $query->execute([$product_id]);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

// Get products by category
function getProductsByCategory($conn, $category_id, $limit = 20) {
    $query = $conn->prepare("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ? AND p.status = 'active' AND p.stock_quantity > 0
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $query->execute([$category_id, $limit]);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

// Search products
function searchProducts($conn, $search_term, $limit = 20) {
    $query = $conn->prepare("
        SELECT p.*, c.name as category_name,
               (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active' AND p.stock_quantity > 0 
        AND (p.name LIKE ? OR p.description LIKE ?)
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $query->execute(["%{$search_term}%", "%{$search_term}%", $limit]);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

// Get total cart amount
function getCartTotal($conn, $user_id) {
    $query = $conn->prepare("
        SELECT SUM(c.quantity * p.price) as total
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $query->execute([$user_id]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result["total"] ?? 0;
}

// Get guest cart total
function getGuestCartTotal($conn) {
    if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
        return 0;
    }
    
    $product_ids = array_keys($_SESSION["cart"]);
    if (empty($product_ids)) {
        return 0;
    }
    
    $placeholders = str_repeat("?,", count($product_ids) - 1) . "?";
    $query = $conn->prepare("
        SELECT id, price FROM products 
        WHERE id IN ($placeholders)
    ");
    $query->execute($product_ids);
    $products = $query->fetchAll(PDO::FETCH_ASSOC);
    
    $total = 0;
    foreach ($products as $product) {
        $quantity = $_SESSION["cart"][$product["id"]];
        $total += $product["price"] * $quantity;
    }
    
    return $total;
}
?>
