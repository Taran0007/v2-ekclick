<?php
require_once '../config.php';
$page_title = 'Vendor Dashboard';
$current_page = 'dashboard';

// Check if user is vendor
if (getUserRole() !== 'vendor') {
    redirect('login.php');
}

$vendor_id = $_SESSION['user_id'];

// Get vendor information
$stmt = $pdo->prepare("SELECT * FROM vendors WHERE user_id = ?");
$stmt->execute([$vendor_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    // Create vendor record if it doesn't exist
    $stmt = $pdo->prepare("INSERT INTO vendors (user_id, shop_name, status) VALUES (?, 'My Shop', 'pending')");
    $stmt->execute([$vendor_id]);
    $vendor_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
    $stmt->execute([$vendor_id]);
    $vendor = $stmt->fetch();
} else {
    $vendor_id = $vendor['id'];
}

// Get statistics
$stats = [];

// Total products
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE vendor_id = ?");
$stmt->execute([$vendor_id]);
$stats['total_products'] = $stmt->fetch()['count'];

// Total orders
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE vendor_id = ?");
$stmt->execute([$vendor_id]);
$stats['total_orders'] = $stmt->fetch()['count'];

// Total revenue
$stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE vendor_id = ? AND payment_status = 'paid'");
$stmt->execute([$vendor_id]);
$stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// Pending orders
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE vendor_id = ? AND status IN ('pending', 'confirmed')");
$stmt->execute([$vendor_id]);
$stats['pending_orders'] = $stmt->fetch()['count'];

// Recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.vendor_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute([$vendor_id]);
$recent_orders = $stmt->fetchAll();

// Top products
$stmt = $pdo->prepare("
    SELECT p.name, COUNT(oi.id) as order_count, SUM(oi.quantity) as total_sold
    FROM products p 
    LEFT JOIN order_items oi ON p.id = oi.product_id 
    WHERE p.vendor_id = ? 
    GROUP BY p.id 
    ORDER BY total_sold DESC 
    LIMIT 5
");
$stmt->execute([$vendor_id]);
$top_products = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <!-- Statistics Cards -->
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 107, 107, 0.1); color: var(--primary-color);">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['total_products']); ?></div>
                <p class="text-muted mb-0">Total Products</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(78, 205, 196, 0.1); color: var(--secondary-color);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                <p class="text-muted mb-0">Total Orders</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                <p class="text-muted mb-0">Total Revenue</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['pending_orders']); ?></div>
                <p class="text-muted mb-0">Pending Orders</p>
            </div>
        </div>
    </div>
</div>

<?php if ($vendor['status'] == 'pending'): ?>
<div class="alert alert-warning" role="alert">
    <h5 class="alert-heading">Account Pending Approval</h5>
    <p>Your vendor account is currently pending approval. Please complete your shop setup and wait for admin approval to start selling.</p>
    <hr>
    <a href="shop-settings.php" class="btn btn-warning">Complete Shop Setup</a>
</div>
<?php elseif ($vendor['status'] == 'rejected'): ?>
<div class="alert alert-danger" role="alert">
    <h5 class="alert-heading">Account Rejected</h5>
    <p>Your vendor account has been rejected. Please contact support for more information.</p>
</div>
<?php elseif ($vendor['status'] == 'suspended'): ?>
<div class="alert alert-danger" role="alert">
    <h5 class="alert-heading">Account Suspended</h5>
    <p>Your vendor account has been suspended. Please contact support to resolve this issue.</p>
</div>
<?php endif; ?>

<div class="row">
    <!-- Recent Orders -->
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Recent Orders</h5>
                <a href="orders.php" class="btn btn-sm btn-primary-custom">View All</a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No orders yet</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_number']; ?></td>
                            <td><?php echo $order['customer_name']; ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
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
                                <span class="badge bg-<?php echo $color; ?> badge-status">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="orders.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Top Products -->
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Top Products</h5>
                <a href="products.php" class="btn btn-sm btn-primary-custom">Manage Products</a>
            </div>
            
            <div class="list-group">
                <?php if (empty($top_products)): ?>
                <div class="list-group-item text-center text-muted">
                    No products yet
                    <br><small><a href="products.php">Add your first product</a></small>
                </div>
                <?php else: ?>
                <?php foreach ($top_products as $product): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                            <small class="text-muted">Sold: <?php echo $product['total_sold'] ?? 0; ?> times</small>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?php echo $product['order_count'] ?? 0; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="dashboard-card">
            <h5 class="mb-3">Quick Actions</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <a href="products.php?action=add" class="btn btn-primary-custom w-100">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="orders.php" class="btn btn-success w-100">
                        <i class="fas fa-clipboard-list"></i> View Orders
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="earnings.php" class="btn btn-info w-100">
                        <i class="fas fa-chart-line"></i> View Earnings
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="shop-settings.php" class="btn btn-warning w-100">
                        <i class="fas fa-cog"></i> Shop Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Shop Status Toggle -->
<?php if ($vendor['status'] == 'active'): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Shop Status</h5>
                    <p class="text-muted mb-0">Toggle your shop online/offline status</p>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="shopStatus" <?php echo ($vendor['is_open'] ?? 1) ? 'checked' : ''; ?> onchange="toggleShopStatus()">
                    <label class="form-check-label" for="shopStatus">
                        <span id="statusText"><?php echo ($vendor['is_open'] ?? 1) ? 'Open' : 'Closed'; ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleShopStatus() {
    const checkbox = document.getElementById('shopStatus');
    const statusText = document.getElementById('statusText');
    const isOpen = checkbox.checked ? 1 : 0;
    
    fetch('../api/toggle-shop-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ is_open: isOpen })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusText.textContent = isOpen ? 'Open' : 'Closed';
        } else {
            checkbox.checked = !checkbox.checked;
            alert('Error updating shop status');
        }
    })
    .catch(error => {
        checkbox.checked = !checkbox.checked;
        alert('Error updating shop status');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
