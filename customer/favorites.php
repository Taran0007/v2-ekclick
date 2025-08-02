<?php
require_once '../config.php';
$page_title = 'My Favorites';
$current_page = 'favorites';

// Check if user is customer
if (getUserRole() !== 'user') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Create favorites table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        vendor_id INT NULL,
        product_id INT NULL,
        type ENUM('vendor', 'product') DEFAULT 'vendor',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (vendor_id) REFERENCES vendors(id),
        FOREIGN KEY (product_id) REFERENCES products(id),
        UNIQUE KEY unique_user_vendor (user_id, vendor_id),
        UNIQUE KEY unique_user_product (user_id, product_id)
    )");
} catch (PDOException $e) {
    // Table might already exist
}

// Handle favorite actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'remove_vendor':
                $vendor_id = (int)$_POST['vendor_id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND vendor_id = ? AND type = 'vendor'");
                    $stmt->execute([$user_id, $vendor_id]);
                    $success = "Restaurant removed from favorites!";
                } catch (PDOException $e) {
                    $error = "Error removing favorite: " . $e->getMessage();
                }
                break;
                
            case 'remove_product':
                $product_id = (int)$_POST['product_id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ? AND type = 'product'");
                    $stmt->execute([$user_id, $product_id]);
                    $success = "Item removed from favorites!";
                } catch (PDOException $e) {
                    $error = "Error removing favorite: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get favorite vendors
try {
    $vendor_stmt = $pdo->prepare("
        SELECT f.*, v.shop_name, v.cuisine_type, v.rating, v.total_reviews, 
               v.delivery_fee, v.estimated_delivery_time, v.logo, v.is_open
        FROM favorites f
        JOIN vendors v ON f.vendor_id = v.id
        WHERE f.user_id = ? AND f.type = 'vendor'
        ORDER BY f.created_at DESC
    ");
    $vendor_stmt->execute([$user_id]);
    $favorite_vendors = $vendor_stmt->fetchAll();
} catch (PDOException $e) {
    // If cuisine_type column doesn't exist, use category instead
    $vendor_stmt = $pdo->prepare("
        SELECT f.*, v.shop_name, v.category as cuisine_type, v.rating, v.total_reviews, 
               v.delivery_fee, v.estimated_delivery_time, v.logo, v.is_open
        FROM favorites f
        JOIN vendors v ON f.vendor_id = v.id
        WHERE f.user_id = ? AND f.type = 'vendor'
        ORDER BY f.created_at DESC
    ");
    $vendor_stmt->execute([$user_id]);
    $favorite_vendors = $vendor_stmt->fetchAll();
}

// Get favorite products
$product_stmt = $pdo->prepare("
    SELECT f.*, p.name, p.price, p.image, p.description, p.is_available,
           v.shop_name, v.id as vendor_id
    FROM favorites f
    JOIN products p ON f.product_id = p.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE f.user_id = ? AND f.type = 'product'
    ORDER BY f.created_at DESC
");
$product_stmt->execute([$user_id]);
$favorite_products = $product_stmt->fetchAll();

require_once '../includes/header.php';
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <h4>My Favorites</h4>
        <p class="text-muted">Your saved restaurants and dishes</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="browse.php" class="btn btn-primary-custom">
            <i class="fas fa-search me-2"></i>Discover More
        </a>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="favoritesTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="restaurants-tab" data-bs-toggle="tab" data-bs-target="#restaurants" type="button" role="tab">
            <i class="fas fa-store me-2"></i>Restaurants (<?php echo count($favorite_vendors); ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="dishes-tab" data-bs-toggle="tab" data-bs-target="#dishes" type="button" role="tab">
            <i class="fas fa-utensils me-2"></i>Dishes (<?php echo count($favorite_products); ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="favoritesTabContent">
    <!-- Favorite Restaurants -->
    <div class="tab-pane fade show active" id="restaurants" role="tabpanel">
        <?php if (empty($favorite_vendors)): ?>
        <div class="dashboard-card text-center">
            <i class="fas fa-heart fa-3x text-muted mb-3"></i>
            <h5>No favorite restaurants yet</h5>
            <p class="text-muted">Start exploring and save your favorite restaurants for quick access!</p>
            <a href="browse.php" class="btn btn-primary-custom">
                <i class="fas fa-search me-2"></i>Browse Restaurants
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($favorite_vendors as $vendor): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="dashboard-card h-100">
                    <div class="position-relative">
                        <?php if ($vendor['logo']): ?>
                        <img src="../uploads/<?php echo $vendor['logo']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($vendor['shop_name']); ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-store fa-3x text-muted"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="position-absolute top-0 end-0 m-2">
                            <button class="btn btn-sm btn-danger" onclick="removeFavoriteVendor(<?php echo $vendor['vendor_id']; ?>)">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        
                        <div class="position-absolute top-0 start-0 m-2">
                            <span class="badge bg-<?php echo $vendor['is_open'] ? 'success' : 'secondary'; ?>">
                                <?php echo $vendor['is_open'] ? 'Open' : 'Closed'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title"><?php echo htmlspecialchars($vendor['shop_name']); ?></h6>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($vendor['cuisine_type'] ?? 'Shop'); ?></p>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-star text-warning me-1"></i>
                                    <span class="fw-bold"><?php echo number_format($vendor['rating'] ?? 0, 1); ?></span>
                                    <small class="text-muted ms-1">(<?php echo $vendor['total_reviews'] ?? 0; ?>)</small>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo $vendor['estimated_delivery_time'] ?? 30; ?> min
                                </small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-truck me-1"></i>
                                Delivery: <?php echo $vendor['delivery_fee'] == 0 ? 'Free' : '$' . number_format($vendor['delivery_fee'], 2); ?>
                            </small>
                        </div>
                        
                        <div class="mt-auto">
                            <div class="d-grid gap-2">
                                <a href="vendor-menu.php?id=<?php echo $vendor['vendor_id']; ?>" class="btn btn-primary-custom">
                                    <i class="fas fa-utensils me-2"></i>View Menu
                                </a>
                                <small class="text-muted text-center">
                                    Added <?php echo date('M d, Y', strtotime($vendor['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Favorite Dishes -->
    <div class="tab-pane fade" id="dishes" role="tabpanel">
        <?php if (empty($favorite_products)): ?>
        <div class="dashboard-card text-center">
            <i class="fas fa-heart fa-3x text-muted mb-3"></i>
            <h5>No favorite dishes yet</h5>
            <p class="text-muted">Save your favorite dishes for easy reordering!</p>
            <a href="browse.php" class="btn btn-primary-custom">
                <i class="fas fa-search me-2"></i>Browse Dishes
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($favorite_products as $product): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="dashboard-card h-100">
                    <div class="position-relative">
                        <?php if ($product['image']): ?>
                        <img src="../uploads/<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-utensils fa-3x text-muted"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="position-absolute top-0 end-0 m-2">
                            <button class="btn btn-sm btn-danger" onclick="removeFavoriteProduct(<?php echo $product['product_id']; ?>)">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        
                        <?php if (!$product['is_available']): ?>
                        <div class="position-absolute top-0 start-0 m-2">
                            <span class="badge bg-secondary">Unavailable</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-store me-1"></i><?php echo htmlspecialchars($product['shop_name']); ?>
                        </p>
                        
                        <?php if ($product['description']): ?>
                        <p class="card-text small text-muted mb-3">
                            <?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>
                            <?php if (strlen($product['description']) > 100): ?>...<?php endif; ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="h5 mb-0 text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                        </div>
                        
                        <div class="mt-auto">
                            <div class="d-grid gap-2">
                                <?php if ($product['is_available']): ?>
                                <button class="btn btn-primary-custom" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </button>
                                <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-times me-2"></i>Currently Unavailable
                                </button>
                                <?php endif; ?>
                                <small class="text-muted text-center">
                                    Added <?php echo date('M d, Y', strtotime($product['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<?php if (!empty($favorite_vendors) || !empty($favorite_products)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="dashboard-card">
            <h6 class="mb-3">Quick Actions</h6>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <button class="btn btn-outline-primary w-100" onclick="orderFromFavorites()">
                        <i class="fas fa-shopping-cart"></i> Order from Favorites
                    </button>
                </div>
                <div class="col-md-3 mb-3">
                    <button class="btn btn-outline-success w-100" onclick="shareList()">
                        <i class="fas fa-share"></i> Share List
                    </button>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="browse.php?favorites=true" class="btn btn-outline-info w-100">
                        <i class="fas fa-search"></i> Find Similar
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <button class="btn btn-outline-warning w-100" onclick="exportFavorites()">
                        <i class="fas fa-download"></i> Export List
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function removeFavoriteVendor(vendorId) {
    if (confirm('Remove this restaurant from your favorites?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="remove_vendor">
            <input type="hidden" name="vendor_id" value="${vendorId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function removeFavoriteProduct(productId) {
    if (confirm('Remove this dish from your favorites?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="remove_product">
            <input type="hidden" name="product_id" value="${productId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function addToCart(productId) {
    fetch('../api/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Item added to cart!');
            // Update cart count in header if needed
        } else {
            alert('Error adding item to cart');
        }
    })
    .catch(error => {
        alert('Error adding item to cart');
    });
}

function orderFromFavorites() {
    alert('Quick order from favorites feature will be implemented soon!');
}

function shareList() {
    if (navigator.share) {
        navigator.share({
            title: 'My Favorite Restaurants',
            text: 'Check out my favorite restaurants on DeliverEase!',
            url: window.location.href
        });
    } else {
        // Fallback for browsers that don't support Web Share API
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}

function exportFavorites() {
    alert('Export favorites feature will be implemented soon!');
}
</script>

<?php require_once '../includes/footer.php'; ?>
