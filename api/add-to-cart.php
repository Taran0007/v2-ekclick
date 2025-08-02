<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login to add items to cart']);
    exit;
}

// Check if user is a customer
if (getUserRole() !== 'user') {
    http_response_code(403);
    echo json_encode(['error' => 'Only customers can add items to cart']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$product_id = $_POST['product_id'] ?? null;
$quantity = $_POST['quantity'] ?? 1;
$user_id = $_SESSION['user_id'];

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

// Check stock
if ($product['stock'] < $quantity) {
    http_response_code(400);
    echo json_encode(['error' => 'Insufficient stock']);
    exit;
}

try {
    // Create cart table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        product_id INT,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id),
        UNIQUE KEY unique_user_product (user_id, product_id)
    )");

    // Check if product already in cart
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update quantity
        $new_quantity = $existing['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$new_quantity, $user_id, $product_id]);
    } else {
        // Insert new item
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }

    // Get cart count
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_count = $stmt->fetch()['total'] ?? 0;

    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart',
        'cart_count' => $cart_count
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
