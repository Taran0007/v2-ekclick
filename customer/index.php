<?php
require_once '../config.php';
$page_title = 'Customer Dashboard';
$current_page = 'dashboard';

// Check if user is customer
if (getUserRole() !== 'user') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get featured vendors
$stmt = $pdo->query("
    SELECT v.*, u.full_name 
    FROM vendors v 
    JOIN users u ON v.user_id = u.id 
    WHERE v.is_open = 1 
    ORDER BY v.rating DESC 
    LIMIT 6
");
$featured_vendors = $stmt->fetchAll();

// Get user's recent orders
$stmt = $pdo->prepare("
    SELECT o.*, v.shop_name 
    FROM orders o 
    JOIN vendors v ON o.vendor_id = v.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

// Get popular products
$stmt = $pdo->query("
    SELECT p.*, v.shop_name, COUNT(oi.id) as order_count
    FROM products p
    JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    WHERE p.is_active = 1 AND p.stock > 0
    GROUP BY p.id
    ORDER BY order_count DESC
    LIMIT 8
");
$popular_products = $stmt->fetchAll();

// Get user stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetch()['total_orders'];

require_once '../includes/header.php';
?>

<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
            <h4 class="mb-2">Welcome back, <?php echo $_SESSION['full_name']; ?>! 👋</h4>
            <p class="mb-0 opacity-75">What would you like to get delivered today?</p>
        </div>
    </div>
</div>

<!-- Quick Search -->
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card">
            <form action="browse.php" method="GET">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light border-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-0 shadow-sm" name="search" 
                           placeholder="Search for shops, products, or services..." 
                           style="font-size: 1rem;">
                    <button class="btn btn-primary-custom px-4" type="submit">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Categories -->
<div class="row mb-5">
    <div class="col-12">
        <div class="dashboard-card">
            <h5 class="mb-4">Browse by Category</h5>
            <div class="row g-3">
                <div class="col-6 col-md-2">
                    <a href="browse.php?category=Food" class="text-decoration-none">
                        <div class="category-card p-4 rounded-3 text-center h-100 shadow-sm">
                            <i class="fas fa-utensils fa-2x mb-3" style="color: var(--primary-color);"></i>
                            <p class="mb-0 fw-medium">Food</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-2">
                    <a href="browse.php?category=Grocery" class="text-decoration-none">
                        <div class="category-card p-4 rounded-3 text-center h-100 shadow-sm">
                            <i class="fas fa-shopping-basket fa-2x mb-3" style="color: var(--secondary-color);"></i>
                            <p class="mb-0 fw-medium">Grocery</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-2">
                    <a href="browse.php?category=Books" class="text-decoration-none">
                        <div class="category-card p-4 rounded-3 text-center h-100 shadow-sm">
                            <i class="fas fa-book fa-2x mb-3" style="color: #3498db;"></i>
                            <p class="mb-0 fw-medium">Books</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-2">
                    <a href="browse.php?category=Electronics" class="text-decoration-none">
                        <div class="category-card p-4 rounded-3 text-center h-100 shadow-sm">
                            <i class="fas fa-laptop fa-2x mb-3" style="color: #9b59b6;"></i>
                            <p class="mb-0 fw-medium">Electronics</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-2">
                    <a href="browse.php?category=Pharmacy" class="text-decoration-none">
                        <div class="category-card p-4 rounded-3 text-center h-100 shadow-sm">
                            <i class="fas fa-pills fa-2x mb-3" style="color: #e74c3c;"></i>
                            <p class="mb-0 fw-medium">Pharmacy</p>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-2">
                    <a href="browse.php" class="text-decoration-none">
                        <div class="category-card p-4 rounded-3 text-center h-100 shadow-sm">
                            <i class="fas fa-th fa-2x mb-3" style="color: #95a5a6;"></i>
                            <p class="mb-0 fw-medium">All Shops</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Featured Vendors -->
<div class="row mb-5">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Featured Local Shops</h5>
                <a href="browse.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-right me-2"></i>View All
                </a>
            </div>
            
            <div class="row g-4">
                <?php foreach ($featured_vendors as $vendor): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0 vendor-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="vendor-avatar me-3">
                                    <i class="fas fa-store"></i>
                                </div>
                                <div>
                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($vendor['shop_name']); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($vendor['category'] ?? 'Shop'); ?> • <?php echo htmlspecialchars($vendor['city'] ?? 'City'); ?></p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="rating">
                                    <i class="fas fa-star text-warning"></i>
                                    <span class="fw-medium"><?php echo number_format($vendor['rating'] ?? 0, 1); ?></span>
                                    <small class="text-muted">(<?php echo $vendor['total_reviews'] ?? 0; ?>)</small>
                                </div>
                                <a href="vendor-details.php?id=<?php echo $vendor['id']; ?>" class="btn btn-primary-custom btn-sm">
                                    <i class="fas fa-shopping-bag me-1"></i>View Shop
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Popular Products -->
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Popular Right Now</h5>
                <a href="browse.php?sort=popular" class="btn btn-outline-primary">
                    <i class="fas fa-fire me-2"></i>View More
                </a>
            </div>
            
            <div class="row g-3">
                <?php foreach ($popular_products as $product): ?>
                <div class="col-md-6">
                    <div class="product-card d-flex border-0 rounded-3 p-3 shadow-sm h-100">
                        <div class="product-image me-3">
                            <?php if ($product['image']): ?>
                            <img src="../uploads/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="rounded-3" style="width: 80px; height: 80px; object-fit: cover;">
                            <?php else: ?>
                            <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-utensils text-muted"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($product['shop_name']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary fs-6">$<?php echo number_format($product['price'], 2); ?></span>
                                <button class="btn btn-primary-custom btn-sm" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-plus me-1"></i>Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Recent Orders</h5>
                <a href="orders.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-history me-1"></i>View All
                </a>
            </div>
            
            <?php if (count($recent_orders) > 0): ?>
                <div class="order-list">
                    <?php foreach ($recent_orders as $order): ?>
                    <a href="order-tracking.php?id=<?php echo $order['id']; ?>" class="order-item d-block text-decoration-none border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-dark"><?php echo htmlspecialchars($order['shop_name']); ?></h6>
                                <p class="mb-1 small text-muted">#<?php echo $order['order_number']; ?></p>
                                <small class="text-muted"><?php echo date('M d, h:i A', strtotime($order['created_at'])); ?></small>
                            </div>
                            <div class="text-end">
                                <?php
                                $status_colors = [
                                    'pending' => 'warning',
                                    'confirmed' => 'info',
                                    'preparing' => 'primary',
                                    'ready' => 'secondary',
                                    'picked_up' => 'info',
                                    'in_transit' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $color = $status_colors[$order['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?> mb-2">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                                <p class="mb-0 fw-bold text-dark">$<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No orders yet. Start shopping!</p>
                    <a href="browse.php" class="btn btn-primary-custom">
                        <i class="fas fa-shopping-bag me-2"></i>Browse Shops
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.category-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.category-card:hover {
    background: white;
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

.vendor-card {
    transition: all 0.3s ease;
}

.vendor-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.vendor-avatar {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.product-card {
    transition: all 0.3s ease;
    background: white;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.order-item {
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.order-item:hover {
    background: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

<?php
$extra_js = '
<script>
function addToCart(productId) {
    const button = event.target.closest("button");
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = "<i class=\"fas fa-spinner fa-spin me-1\"></i>Adding...";
    button.disabled = true;
    
    $.post("../api/add-to-cart.php", { product_id: productId })
        .done(function(response) {
            // Show success state
            button.innerHTML = "<i class=\"fas fa-check me-1\"></i>Added!";
            button.classList.remove("btn-primary-custom");
            button.classList.add("btn-success");
            
            // Show success message
            showToast("Product added to cart successfully!", "success");
            
            // Reset button after 2 seconds
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove("btn-success");
                button.classList.add("btn-primary-custom");
                button.disabled = false;
            }, 2000);
        })
        .fail(function(xhr) {
            // Show error state
            button.innerHTML = "<i class=\"fas fa-times me-1\"></i>Error";
            button.classList.remove("btn-primary-custom");
            button.classList.add("btn-danger");
            
            const error = xhr.responseJSON ? xhr.responseJSON.error : "Failed to add to cart";
            showToast(error, "error");
            
            // Reset button after 2 seconds
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove("btn-danger");
                button.classList.add("btn-primary-custom");
                button.disabled = false;
            }, 2000);
        });
}

function showToast(message, type) {
    const toastContainer = document.getElementById("toast-container") || createToastContainer();
    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-white bg-${type === "success" ? "success" : "danger"} border-0`;
    toast.setAttribute("role", "alert");
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === "success" ? "check-circle" : "exclamation-circle"} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast element after it hides
    toast.addEventListener("hidden.bs.toast", () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement("div");
    container.id = "toast-container";
    container.className = "toast-container position-fixed top-0 end-0 p-3";
    container.style.zIndex = "1055";
    document.body.appendChild(container);
    return container;
}
</script>
';
?>


