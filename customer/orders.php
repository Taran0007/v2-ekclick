<?php
require_once '../config.php';
$page_title = 'My Orders';
$current_page = 'orders';

// Check if user is customer
if (getUserRole() !== 'user') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';

$where_conditions = ["o.user_id = ?"];
$params = [$user_id];

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR v.shop_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(o.created_at) = ?";
    $params[] = $date_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders o 
    JOIN vendors v ON o.vendor_id = v.id 
    $where_clause
");
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Get orders
$stmt = $pdo->prepare("
    SELECT o.*, v.shop_name, v.logo, d.full_name as delivery_person_name, d.phone as delivery_phone
    FROM orders o 
    JOIN vendors v ON o.vendor_id = v.id 
    LEFT JOIN users d ON o.delivery_person_id = d.id
    $where_clause 
    ORDER BY o.created_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN status IN ('pending', 'confirmed', 'preparing', 'ready', 'picked_up', 'in_transit') THEN 1 ELSE 0 END) as active_orders,
        SUM(total_amount) as total_spent,
        AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE user_id = ?
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h4>My Orders</h4>
        <p class="text-muted">Track and manage your orders</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="browse.php" class="btn btn-primary-custom">
            <i class="fas fa-plus me-2"></i>Order Again
        </a>
    </div>
</div>

<!-- Order Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['total_orders'] ?? 0); ?></div>
                <p class="text-muted mb-0">Total Orders</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['delivered_orders'] ?? 0); ?></div>
                <p class="text-muted mb-0">Delivered</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['active_orders'] ?? 0); ?></div>
                <p class="text-muted mb-0">Active Orders</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></div>
                <p class="text-muted mb-0">Total Spent</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-card mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <input type="text" class="form-control" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="preparing" <?php echo $status_filter == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="ready" <?php echo $status_filter == 'ready' ? 'selected' : ''; ?>>Ready</option>
                <option value="picked_up" <?php echo $status_filter == 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                <option value="in_transit" <?php echo $status_filter == 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="orders.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>
</div>

<!-- Orders List -->
<?php if (empty($orders)): ?>
<div class="dashboard-card text-center">
    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
    <h5>No orders found</h5>
    <p class="text-muted">You haven't placed any orders yet or no orders match your search criteria.</p>
    <a href="browse.php" class="btn btn-primary-custom">
        <i class="fas fa-utensils me-2"></i>Start Ordering
    </a>
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($orders as $order): ?>
    <div class="col-12 mb-4">
        <div class="dashboard-card">
            <div class="row">
                <div class="col-md-2">
                    <?php if ($order['logo']): ?>
                    <img src="../uploads/<?php echo $order['logo']; ?>" alt="<?php echo htmlspecialchars($order['shop_name']); ?>" 
                         class="rounded" style="width: 80px; height: 80px; object-fit: cover;">
                    <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fas fa-store text-muted"></i>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <h6 class="mb-1"><?php echo htmlspecialchars($order['shop_name']); ?></h6>
                    <p class="text-muted mb-1">Order #<?php echo $order['order_number']; ?></p>
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo date('M d, Y \a\t H:i', strtotime($order['created_at'])); ?>
                    </small>
                    
                    <?php if ($order['delivery_person_name']): ?>
                    <div class="mt-2">
                        <small class="text-info">
                            <i class="fas fa-user me-1"></i>
                            Delivery: <?php echo htmlspecialchars($order['delivery_person_name']); ?>
                            <?php if ($order['delivery_phone']): ?>
                            <a href="tel:<?php echo $order['delivery_phone']; ?>" class="text-decoration-none ms-1">
                                <i class="fas fa-phone"></i>
                            </a>
                            <?php endif; ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-2 text-center">
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
                    <span class="badge bg-<?php echo $color; ?> badge-status mb-2">
                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                    </span>
                    <div class="fw-bold">$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></div>
                </div>
                
                <div class="col-md-2 text-end">
                    <div class="btn-group-vertical" role="group">
                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        
                        <?php if (in_array($order['status'], ['picked_up', 'in_transit'])): ?>
                        <a href="track-order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-map-marker-alt"></i> Track Order
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] == 'delivered'): ?>
                        <button class="btn btn-sm btn-outline-warning" onclick="rateOrder(<?php echo $order['id']; ?>)">
                            <i class="fas fa-star"></i> Rate
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="reorder(<?php echo $order['id']; ?>)">
                            <i class="fas fa-redo"></i> Reorder
                        </button>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['status'], ['pending', 'confirmed']) && strtotime($order['created_at']) > strtotime('-10 minutes')): ?>
                        <button class="btn btn-sm btn-outline-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Order Progress -->
            <?php if (in_array($order['status'], ['confirmed', 'preparing', 'ready', 'picked_up', 'in_transit'])): ?>
            <div class="mt-3">
                <div class="progress" style="height: 8px;">
                    <?php
                    $progress_steps = ['confirmed' => 20, 'preparing' => 40, 'ready' => 60, 'picked_up' => 80, 'in_transit' => 90, 'delivered' => 100];
                    $current_progress = $progress_steps[$order['status']] ?? 0;
                    ?>
                    <div class="progress-bar bg-<?php echo $color; ?>" style="width: <?php echo $current_progress; ?>%"></div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted">Order Confirmed</small>
                    <small class="text-muted">Preparing</small>
                    <small class="text-muted">Ready</small>
                    <small class="text-muted">Out for Delivery</small>
                    <small class="text-muted">Delivered</small>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Special Instructions -->
            <?php if ($order['special_instructions']): ?>
            <div class="mt-2">
                <small class="text-muted">
                    <strong>Special Instructions:</strong> <?php echo htmlspecialchars($order['special_instructions']); ?>
                </small>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date=<?php echo urlencode($date_filter); ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>

<!-- Rate Order Modal -->
<div class="modal fade" id="rateOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rate Your Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Rating</label>
                    <div class="rating-stars">
                        <i class="fas fa-star" data-rating="1"></i>
                        <i class="fas fa-star" data-rating="2"></i>
                        <i class="fas fa-star" data-rating="3"></i>
                        <i class="fas fa-star" data-rating="4"></i>
                        <i class="fas fa-star" data-rating="5"></i>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Review (Optional)</label>
                    <textarea class="form-control" rows="3" placeholder="Share your experience..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom">Submit Rating</button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this order?</p>
                <div class="mb-3">
                    <label class="form-label">Reason for cancellation</label>
                    <select class="form-select" id="cancelReason">
                        <option value="">Select a reason</option>
                        <option value="changed_mind">Changed my mind</option>
                        <option value="wrong_order">Ordered wrong items</option>
                        <option value="too_long">Taking too long</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Order</button>
                <button type="button" class="btn btn-danger" onclick="confirmCancel()">Cancel Order</button>
            </div>
        </div>
    </div>
</div>

<style>
.rating-stars {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
}

.rating-stars i:hover,
.rating-stars i.active {
    color: #ffc107;
}
</style>

<script>
let currentOrderId = null;
let selectedRating = 0;

function rateOrder(orderId) {
    currentOrderId = orderId;
    new bootstrap.Modal(document.getElementById('rateOrderModal')).show();
}

function cancelOrder(orderId) {
    currentOrderId = orderId;
    new bootstrap.Modal(document.getElementById('cancelOrderModal')).show();
}

function reorder(orderId) {
    if (confirm('Add all items from this order to your cart?')) {
        // Implement reorder functionality
        alert('Reorder functionality will be implemented soon!');
    }
}

function confirmCancel() {
    const reason = document.getElementById('cancelReason').value;
    if (!reason) {
        alert('Please select a reason for cancellation');
        return;
    }
    
    // Implement cancel order functionality
    alert('Order cancellation will be implemented soon!');
    bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal')).hide();
}

// Rating stars functionality
document.querySelectorAll('.rating-stars i').forEach(star => {
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.dataset.rating);
        updateStars();
    });
    
    star.addEventListener('mouseover', function() {
        const rating = parseInt(this.dataset.rating);
        highlightStars(rating);
    });
});

document.querySelector('.rating-stars').addEventListener('mouseleave', function() {
    updateStars();
});

function highlightStars(rating) {
    document.querySelectorAll('.rating-stars i').forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

function updateStars() {
    highlightStars(selectedRating);
}
</script>

<?php require_once '../includes/footer.php'; ?>
