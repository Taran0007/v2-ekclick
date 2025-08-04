<?php
require_once '../config.php';
$page_title = 'Admin Dashboard';
$current_page = 'dashboard';

// Check if user is admin
if (getUserRole() !== 'admin') {
    redirect(getBaseUrl() . '/login.php');
}

// Get statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $stmt->fetch()['count'];

// Total vendors
$stmt = $pdo->query("SELECT COUNT(*) as count FROM vendors");
$stats['total_vendors'] = $stmt->fetch()['count'];

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $stmt->fetch()['count'];

// Total revenue
$stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
$stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// Recent orders
$stmt = $pdo->query("
    SELECT o.*, u.full_name as customer_name, v.shop_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$recent_orders = $stmt->fetchAll();

// Recent users
$stmt = $pdo->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_users = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <!-- Statistics Cards -->
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 107, 107, 0.1); color: var(--primary-color);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                <p class="text-muted mb-0">Total Users</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(78, 205, 196, 0.1); color: var(--secondary-color);">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['total_vendors']); ?></div>
                <p class="text-muted mb-0">Total Vendors</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
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
</div>

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
                            <th>Vendor</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_number']; ?></td>
                            <td><?php echo $order['customer_name']; ?></td>
                            <td><?php echo $order['shop_name']; ?></td>
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
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Recent Users -->
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Recent Users</h5>
                <a href="users.php" class="btn btn-sm btn-primary-custom">View All</a>
            </div>
            
            <div class="list-group">
                <?php foreach ($recent_users as $user): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo $user['full_name']; ?></h6>
                            <small class="text-muted"><?php echo $user['email']; ?></small>
                        </div>
                        <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'vendor' ? 'primary' : ($user['role'] == 'delivery' ? 'info' : 'success')); ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
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
                    <a href="<?php echo getBaseUrl(); ?>/admin/users.php?action=add" class="btn btn-primary-custom w-100">
                        <i class="fas fa-user-plus"></i> Add New User
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?php echo getBaseUrl(); ?>/admin/vendors.php" class="btn btn-success w-100">
                        <i class="fas fa-store"></i> Manage Vendors
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?php echo getBaseUrl(); ?>/admin/orders.php" class="btn btn-info w-100">
                        <i class="fas fa-clipboard-list"></i> View Orders
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?php echo getBaseUrl(); ?>/admin/disputes.php" class="btn btn-warning w-100">
                        <i class="fas fa-exclamation-triangle"></i> Handle Disputes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


