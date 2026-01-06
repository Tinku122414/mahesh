<?php
// Get featured products and categories
$db = new Database();
$conn = $db->getConnection();

$featured_products = getFeaturedProducts($conn, 8);
$categories = getCategories($conn);
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold mb-4">Sport for Everyone, Everywhere</h1>
                <p class="lead mb-4">High-quality sports equipment and apparel at unbeatable prices</p>
                <a href="?page=products" class="btn btn-light btn-lg">Shop Now</a>
            </div>
            <div class="col-md-6">
                <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600" alt="Sports Equipment" class="img-fluid rounded-3">
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Shop by Category</h2>
        <div class="row">
            <?php foreach ($categories as $category): ?>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card category-card h-100">
                    <div class="card-body text-center">
                        <div class="category-icon mb-3">
                            <i class="bi bi-tag"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                        <p class="card-text text-muted"><?php echo $category['product_count']; ?> products</p>
                        <a href="?page=products&category=<?php echo $category['id']; ?>" class="btn btn-primary">Shop Now</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Featured Products</h2>
        <div class="row">
            <?php foreach ($featured_products as $product): ?>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card product-card h-100">
                    <?php if ($product['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 250px;">
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price"><?php echo formatPrice($product['price']); ?></span>
                            <span class="badge bg-success">In Stock</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="btn-group w-100" role="group">
                            <a href="?page=product&id=<?php echo $product['id']; ?>" class="btn btn-outline-primary">View</a>
                            <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">Add to Cart</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="?page=products" class="btn btn-primary btn-lg">View All Products</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <div class="feature-icon mb-3">
                    <i class="bi bi-truck"></i>
                </div>
                <h4>Free Shipping</h4>
                <p class="text-muted">Free shipping on orders over $50</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <div class="feature-icon mb-3">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h4>Quality Guarantee</h4>
                <p class="text-muted">100% satisfaction guaranteed</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <div class="feature-icon mb-3">
                    <i class="bi bi-headset"></i>
                </div>
                <h4>24/7 Support</h4>
                <p class="text-muted">Customer support available 24/7</p>
            </div>
        </div>
    </div>
</section>
