<?php
require_once '../config.php';
$page_title = 'Shop Details';

// Check if vendor ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(getBaseUrl() . '/customer/browse.php');
}

$vendor_id = (int)$_GET['id'];

// Get vendor information
$stmt = $pdo->prepare("SELECT v.*, u.full_name FROM vendors v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
$stmt->execute([$vendor_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    // Vendor not found
    redirect(getBaseUrl() . '/customer/browse.php');
}

// Get vendor's products
$stmt = $pdo->prepare("SELECT * FROM products WHERE vendor_id = ? AND is_active = 1");
$stmt->execute([$vendor_id]);
$products = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="card-title"><?php echo htmlspecialchars($vendor['shop_name']); ?></h1>
                    <p class="text-muted">Owned by: <?php echo htmlspecialchars($vendor['full_name']); ?></p>
                    <p><?php echo htmlspecialchars($vendor['description']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h2 class="mb-3">Products</h2>
            <div class="row g-4">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4">
                            <div class="card h-100 shadow-sm">
                                <?php if ($product['image']): ?>
                                    <img src="../uploads/<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <p class="card-text"><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
                                    <button class="btn btn-primary-custom" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-muted">This shop has no products yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
function addToCart(productId) {
    $.post("../api/add-to-cart.php", { product_id: productId }, function(response) {
        if(response.success) {
            alert("Product added to cart!");
        } else {
            alert("Failed to add product to cart: " + response.error);
        }
    });
}
</script>
';
?>
