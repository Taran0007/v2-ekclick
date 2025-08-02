<?php
require_once '../config.php';
$page_title = 'Order Management';
$current_page = 'orders';

// Check if user is admin
if (getUserRole() !== 'admin') {
    redirect('login.php');
}

// Handle order actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $id = (int)$_POST['id'];
                $status = sanitize($_POST['status']);
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$status, $id]);
                    $success = "Order status updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating order: " . $e->getMessage();
                }
                break;
                
            case 'update_payment':
                $id = (int)$_POST['id'];
                $payment_status = sanitize($_POST['payment_status']);
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$payment_status, $id]);
                    $success = "Payment status updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating payment status: " . $e->getMessage();
                }
                break;
                
            case 'assign_delivery':
                $id = (int)$_POST['id'];
                $delivery_id = (int)$_POST['delivery_id'];
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET delivery_person_id = ?, status = 'picked_up', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$delivery_id, $id]);
                    $success = "Delivery person assigned successfully!";
                } catch (PDOException $e) {
                    $error = "Error assigning delivery person: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$payment_filter = isset($_GET['payment']) ? sanitize($_GET['payment']) : '';
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ? OR v.shop_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($payment_filter) {
    $where_conditions[] = "o.payment_status = ?";
    $params[] = $payment_filter;
}

if ($date_filter) {
    $where_conditions[] = "DATE(o.created_at) = ?";
    $params[] = $date_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

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

// Get orders with related data
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
           v.shop_name, v.owner_name as vendor_name,
           d.full_name as delivery_person_name
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    LEFT JOIN users d ON o.delivery_person_id = d.id
    $where_clause 
    ORDER BY o.created_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
        SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
        SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_revenue
    FROM orders
");
$stats = $stats_stmt->fetch();

// Ensure all stats are not null
$stats['total'] = $stats['total'] ?? 0;
$stats['pending'] = $stats['pending'] ?? 0;
$stats['confirmed'] = $stats['confirmed'] ?? 0;
$stats['preparing'] = $stats['preparing'] ?? 0;
$stats['ready'] = $stats['ready'] ?? 0;
$stats['in_transit'] = $stats['in_transit'] ?? 0;
$stats['delivered'] = $stats['delivered'] ?? 0;
$stats['cancelled'] = $stats['cancelled'] ?? 0;
$stats['total_revenue'] = $stats['total_revenue'] ?? 0;
$stats['paid_revenue'] = $stats['paid_revenue'] ?? 0;

// Get delivery persons for assignment
$delivery_stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'delivery' ORDER BY full_name");
$delivery_persons = $delivery_stmt->fetchAll();

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
        <h4>Order Management</h4>
        <p class="text-muted">Manage all orders and deliveries</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                <p class="text-muted mb-0">Total Orders</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['pending']); ?></div>
                <p class="text-muted mb-0">Pending Orders</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['delivered']); ?></div>
                <p class="text-muted mb-0">Delivered</p>
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

<!-- Filters -->
<div class="dashboard-card">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <input type="text" class="form-control" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-2">
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
            <select class="form-select" name="payment">
                <option value="">All Payments</option>
                <option value="pending" <?php echo $payment_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="paid" <?php echo $payment_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="failed" <?php echo $payment_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                <option value="refunded" <?php echo $payment_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="orders.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="dashboard-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Vendor</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Delivery Person</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>
                        <strong><?php echo $order['order_number']; ?></strong>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($order['customer_name']); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($order['shop_name']); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($order['vendor_name']); ?></small>
                    </td>
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
                    <td>
                        <?php
                        $payment_colors = [
                            'pending' => 'warning',
                            'paid' => 'success',
                            'failed' => 'danger',
                            'refunded' => 'info'
                        ];
                        $payment_color = $payment_colors[$order['payment_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $payment_color; ?> badge-status">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($order['delivery_person_name']): ?>
                            <?php echo htmlspecialchars($order['delivery_person_name']); ?>
                        <?php else: ?>
                            <span class="text-muted">Not assigned</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-info" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if (!$order['delivery_person_id'] && in_array($order['status'], ['ready', 'confirmed'])): ?>
                            <button class="btn btn-sm btn-outline-success" onclick="assignDelivery(<?php echo $order['id']; ?>)">
                                <i class="fas fa-truck"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment=<?php echo urlencode($payment_filter); ?>&date=<?php echo urlencode($date_filter); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" id="status_order_id">
                    <div class="mb-3">
                        <label class="form-label">Order Status</label>
                        <select class="form-select" name="status" id="order_status" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="preparing">Preparing</option>
                            <option value="ready">Ready</option>
                            <option value="picked_up">Picked Up</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Delivery Modal -->
<div class="modal fade" id="assignDeliveryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Delivery Person</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_delivery">
                    <input type="hidden" name="id" id="delivery_order_id">
                    <div class="mb-3">
                        <label class="form-label">Delivery Person</label>
                        <select class="form-select" name="delivery_id" required>
                            <option value="">Select Delivery Person</option>
                            <?php foreach ($delivery_persons as $person): ?>
                            <option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetails">
                <!-- Order details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function updateOrderStatus(id, currentStatus) {
    document.getElementById('status_order_id').value = id;
    document.getElementById('order_status').value = currentStatus;
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

function assignDelivery(id) {
    document.getElementById('delivery_order_id').value = id;
    new bootstrap.Modal(document.getElementById('assignDeliveryModal')).show();
}

function viewOrder(id) {
    // You can implement AJAX call to fetch order details
    // For now, showing a placeholder
    document.getElementById('orderDetails').innerHTML = '<p>Loading order details...</p>';
    new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
    
    // Fetch order details via AJAX (implement as needed)
    fetch(`get-order-details.php?id=${id}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('orderDetails').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('orderDetails').innerHTML = '<p>Error loading order details.</p>';
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>
