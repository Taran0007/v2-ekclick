<?php
require_once '../config.php';
$page_title = 'Available Orders';
$current_page = 'available-orders';

// Check if user is delivery personnel
if (getUserRole() !== 'delivery') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle order acceptance
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'accept_order') {
    $order_id = (int)$_POST['order_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET delivery_person_id = ?, status = 'picked_up', updated_at = NOW() WHERE id = ? AND status = 'ready' AND delivery_person_id IS NULL");
        $stmt->execute([$user_id, $order_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Order accepted successfully!";
        } else {
            $error = "Order is no longer available.";
        }
    } catch (PDOException $e) {
        $error = "Error accepting order: " . $e->getMessage();
    }
}

// Get available orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$distance_filter = isset($_GET['distance']) ? (int)$_GET['distance'] : 0;

$where_conditions = ["o.status = 'ready'", "o.delivery_person_id IS NULL"];
$params = [];

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ? OR v.shop_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    $where_clause
");
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Get available orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email,
           v.shop_name, v.address as pickup_address, v.phone as vendor_phone,
           v.latitude as pickup_lat, v.longitude as pickup_lng
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    $where_clause 
    ORDER BY o.created_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$available_orders = $stmt->fetchAll();

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
        <h4>Available Orders</h4>
        <p class="text-muted">Orders ready for pickup and delivery</p>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-outline-primary" onclick="refreshOrders()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-card">
    <form method="GET" class="row g-3">
        <div class="col-md-4">
            <input type="text" class="form-control" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="distance">
                <option value="0">All Distances</option>
                <option value="5" <?php echo $distance_filter == 5 ? 'selected' : ''; ?>>Within 5 km</option>
                <option value="10" <?php echo $distance_filter == 10 ? 'selected' : ''; ?>>Within 10 km</option>
                <option value="15" <?php echo $distance_filter == 15 ? 'selected' : ''; ?>>Within 15 km</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
        <div class="col-md-3">
            <a href="available-orders.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>
</div>

<!-- Available Orders -->
<div class="row">
    <?php if (empty($available_orders)): ?>
    <div class="col-12">
        <div class="dashboard-card text-center">
            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
            <h5>No Available Orders</h5>
            <p class="text-muted">Check back later for new delivery opportunities.</p>
            <button class="btn btn-primary-custom" onclick="refreshOrders()">
                <i class="fas fa-sync-alt"></i> Refresh Orders
            </button>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($available_orders as $order): ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="dashboard-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="mb-0">Order #<?php echo $order['order_number']; ?></h6>
                <span class="badge bg-warning">Ready</span>
            </div>
            
            <!-- Order Details -->
            <div class="mb-3">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-store text-primary me-2"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($order['shop_name']); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($order['pickup_address']); ?></small>
                    </div>
                </div>
                
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-user text-success me-2"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($order['delivery_address']); ?></small>
                    </div>
                </div>
                
                <div class="d-flex align-items-center">
                    <i class="fas fa-phone text-info me-2"></i>
                    <small><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                </div>
            </div>
            
            <!-- Order Info -->
            <div class="row mb-3">
                <div class="col-6">
                    <small class="text-muted">Order Value</small>
                    <div class="fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></div>
                </div>
                <div class="col-6">
                    <small class="text-muted">Delivery Fee</small>
                    <div class="fw-bold text-success">$5.00</div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-6">
                    <small class="text-muted">Distance</small>
                    <div class="fw-bold">~<?php echo rand(2, 15); ?> km</div>
                </div>
                <div class="col-6">
                    <small class="text-muted">Est. Time</small>
                    <div class="fw-bold"><?php echo rand(15, 45); ?> min</div>
                </div>
            </div>
            
            <!-- Order Time -->
            <div class="mb-3">
                <small class="text-muted">Order Time: <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></small>
            </div>
            
            <!-- Special Instructions -->
            <?php if ($order['special_instructions']): ?>
            <div class="mb-3">
                <small class="text-muted">Special Instructions:</small>
                <div class="small bg-light p-2 rounded"><?php echo htmlspecialchars($order['special_instructions']); ?></div>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="mt-auto">
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="acceptOrder(<?php echo $order['id']; ?>)">
                        <i class="fas fa-check"></i> Accept Order
                    </button>
                    <div class="btn-group" role="group">
                        <button class="btn btn-outline-info btn-sm" onclick="viewOrderDetails(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                            <i class="fas fa-eye"></i> Details
                        </button>
                        <a href="https://maps.google.com/?q=<?php echo urlencode($order['pickup_address']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-map-marker-alt"></i> Pickup
                        </a>
                        <a href="https://maps.google.com/?q=<?php echo urlencode($order['delivery_address']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-home"></i> Delivery
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&distance=<?php echo $distance_filter; ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="acceptFromModal">Accept Order</button>
            </div>
        </div>
    </div>
</div>

<!-- Accept Order Confirmation Modal -->
<div class="modal fade" id="acceptOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Accept Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to accept this order? You will be responsible for picking up and delivering this order.</p>
                <div class="alert alert-info">
                    <small>
                        <strong>Note:</strong> Once accepted, you should proceed to the pickup location immediately.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="accept_order">
                    <input type="hidden" name="order_id" id="accept_order_id">
                    <button type="submit" class="btn btn-success">Accept Order</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentOrderId = null;

function acceptOrder(orderId) {
    currentOrderId = orderId;
    document.getElementById('accept_order_id').value = orderId;
    new bootstrap.Modal(document.getElementById('acceptOrderModal')).show();
}

function viewOrderDetails(order) {
    const details = `
        <div class="row">
            <div class="col-md-6">
                <h6>Pickup Information</h6>
                <p><strong>Restaurant:</strong> ${order.shop_name}</p>
                <p><strong>Address:</strong> ${order.pickup_address}</p>
                <p><strong>Phone:</strong> ${order.vendor_phone || 'N/A'}</p>
            </div>
            <div class="col-md-6">
                <h6>Delivery Information</h6>
                <p><strong>Customer:</strong> ${order.customer_name}</p>
                <p><strong>Address:</strong> ${order.delivery_address}</p>
                <p><strong>Phone:</strong> ${order.customer_phone}</p>
                <p><strong>Email:</strong> ${order.customer_email}</p>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <h6>Order Information</h6>
                <p><strong>Order Number:</strong> ${order.order_number}</p>
                <p><strong>Order Value:</strong> $${parseFloat(order.total_amount).toFixed(2)}</p>
                <p><strong>Payment Status:</strong> <span class="badge bg-${getPaymentColor(order.payment_status)}">${order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1)}</span></p>
                <p><strong>Order Time:</strong> ${new Date(order.created_at).toLocaleString()}</p>
            </div>
            <div class="col-md-6">
                <h6>Delivery Details</h6>
                <p><strong>Delivery Fee:</strong> $5.00</p>
                <p><strong>Estimated Distance:</strong> ~${Math.floor(Math.random() * 13) + 2} km</p>
                <p><strong>Estimated Time:</strong> ${Math.floor(Math.random() * 30) + 15} minutes</p>
            </div>
        </div>
        ${order.special_instructions ? `<hr><h6>Special Instructions</h6><p>${order.special_instructions}</p>` : ''}
    `;
    
    document.getElementById('orderDetailsContent').innerHTML = details;
    document.getElementById('acceptFromModal').onclick = () => acceptOrder(order.id);
    new bootstrap.Modal(document.getElementById('orderDetailsModal')).show();
}

function getPaymentColor(status) {
    const colors = {
        'pending': 'warning',
        'paid': 'success',
        'failed': 'danger',
        'refunded': 'info'
    };
    return colors[status] || 'secondary';
}

function refreshOrders() {
    location.reload();
}

// Auto-refresh every 30 seconds
setInterval(function() {
    // Only refresh if no modals are open
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 30000);
</script>

<?php require_once '../includes/footer.php'; ?>
