<?php
session_start();
require_once '../config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ?page=home');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $query = "SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Transfer guest cart to user cart
                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $product_id => $quantity) {
                        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
                        $stmt->execute([$user['id'], $product_id]);
                        
                        if ($stmt->fetch()) {
                            // Update existing cart item
                            $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
                            $stmt->execute([$quantity, $user['id'], $product_id]);
                        } else {
                            // Add new cart item
                            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                            $stmt->execute([$user['id'], $product_id, $quantity]);
                        }
                    }
                    unset($_SESSION['cart']);
                }
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: ?page=home');
                }
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="text-primary">Welcome Back!</h2>
                        <p class="text-muted">Login to your Decathlon account</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                        
                        <div class="text-center">
                            <a href="?page=forgot-password" class="text-decoration-none">Forgot Password?</a>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? <a href="?page=register" class="text-decoration-none">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
