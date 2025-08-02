<?php
require_once '../config.php';
$page_title = 'Delivery Dashboard';
$current_page = 'dashboard';

// Check if user is delivery personnel
if (getUserRole() !== 'delivery') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get statistics
$stats = [];

// Today's deliveries
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE delivery_person_id = ? 
    AND DATE(created_at) = CURDATE()
");
$stmt->execute([$user_id]);
$stats['today_deliveries'] = $stmt->fetch()['count'];

// Active deliveries
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE delivery_person_id = ? 
    AND status IN ('picked_up', 'in_transit')
");
$stmt->execute([$user_id]);
$stats['active_deliveries'] = $stmt->fetch()['count'];

// Completed deliveries
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE delivery_person_id = ? 
    AND status = 'delivered'
");
$stmt->execute([$user_id]);
$stats['completed_deliveries'] = $stmt->fetch()['count'];

// Today's earnings (assuming $5 per delivery)
$delivery_fee = 5.00;
$stats['today_earnings'] = $stats['today_deliveries'] * $delivery_fee;

// Available orders (ready for pickup)
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, 
           v.shop_name, v.address as pickup_address
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    WHERE o.status = 'ready' 
    AND o.delivery_person_id IS NULL
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$available_orders = $stmt->fetchAll();

// Active deliveries
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, 
           v.shop_name, v.address as pickup_address
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    WHERE o.delivery_person_id = ?
    AND o.status IN ('picked_up', 'in_transit')
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$active_deliveries = $stmt->fetchAll();

// Recent deliveries
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name as customer_name, v.shop_name
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    WHERE o.delivery_person_id = ?
    AND o.status = 'delivered'
    ORDER BY o.actual_delivery_time DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_deliveries = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <!-- Statistics Cards -->
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 107, 107, 0.1); color: var(--primary-color);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['today_deliveries']); ?></div>
                <p class="text-muted mb-0">Today's Deliveries</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(78, 205, 196, 0.1); color: var(--secondary-color);">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['active_deliveries']); ?></div>
                <p class="text-muted mb-0">Active Deliveries</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['completed_deliveries']); ?></div>
                <p class="text-muted mb-0">Total Completed</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['today_earnings'], 2); ?></div>
                <p class="text-muted mb-0">Today's Earnings</p>
            </div>
        </div>
    </div>
</div>

<!-- Active Deliveries -->
<?php if (count($active_deliveries) > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card">
            <h5 class="mb-3">Active Deliveries</h5>
            <div class="row">
                <?php foreach ($active_deliveries as $delivery): ?>
                <div class="col-md-6 mb-3">
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">Order #<?php echo $delivery['order_number']; ?></h6>
                            <span class="badge bg-<?php echo $delivery['status'] == 'picked_up' ? 'info' : 'primary'; ?> badge-status">
                                <?php echo ucfirst(str_replace('_', ' ', $delivery['status'])); ?>
                            </span>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Pickup:</strong> <?php echo $delivery['shop_name']; ?><br>
                            <small class="text-muted"><?php echo $delivery['pickup_address']; ?></small>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Deliver to:</strong> <?php echo $delivery['customer_name']; ?><br>
                            <small class="text-muted"><?php echo $delivery['delivery_address']; ?></small><br>
                            <small><i class="fas fa-phone"></i> <?php echo $delivery['customer_phone']; ?></small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <?php if ($delivery['status'] == 'picked_up'): ?>
                                <button class="btn btn-sm btn-primary-custom" onclick="updateDeliveryStatus(<?php echo $delivery['id']; ?>, 'in_transit')">
                                    <i class="fas fa-truck"></i> Start Delivery
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success" onclick="updateDeliveryStatus(<?php echo $delivery['id']; ?>, 'delivered')">
                                    <i class="fas fa-check"></i> Mark Delivered
                                </button>
                            <?php endif; ?>
                            <a href="https://maps.google.com/?q=<?php echo urlencode($delivery['delivery_address']); ?>" target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-map-marker-alt"></i> Navigate
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Available Orders -->
<div class="row">
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Available Orders</h5>
                <a href="available-orders.php" class="btn btn-sm btn-primary-custom">View All</a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Pickup</th>
                            <th>Delivery</th>
                            <th>Distance</th>
                            <th>Fee</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_number']; ?></td>
                            <td>
                                <strong><?php echo $order['shop_name']; ?></strong><br>
                                <small class="text-muted"><?php echo substr($order['pickup_address'], 0, 30); ?>...</small>
                            </td>
                            <td>
                                <strong><?php echo $order['customer_name']; ?></strong><br>
                                <small class="text-muted"><?php echo substr($order['delivery_address'], 0, 30); ?>...</small>
                            </td>
                            <td>~<?php echo rand(2, 10); ?> km</td>
                            <td>$<?php echo number_format($delivery_fee, 2); ?></td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="acceptOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Recent Deliveries -->
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Recent Deliveries</h5>
                <a href="delivery-history.php" class="btn btn-sm btn-primary-custom">View All</a>
            </div>
            
            <div class="list-group">
                <?php foreach ($recent_deliveries as $delivery): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">#<?php echo $delivery['order_number']; ?></h6>
                            <small class="text-muted">
                                <?php echo $delivery['shop_name']; ?> → <?php echo $delivery['customer_name']; ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success">Delivered</span><br>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($delivery['actual_delivery_time'])); ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
function acceptOrder(orderId) {
    if (confirm("Are you sure you want to accept this order?")) {
        $.post("../api/accept-order.php", { order_id: orderId }, function(response) {
            location.reload();
        });
    }
}

function updateDeliveryStatus(orderId, status) {
    $.post("../api/update-delivery-status.php", { 
        order_id: orderId, 
        status: status 
    }, function(response) {
        location.reload();
    });
}
</script>
';
?>

<?php require_once '../includes/footer.php'; ?>
