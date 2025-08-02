<?php
require_once '../config.php';
$page_title = 'Order Management';
$current_page = 'orders';

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
    redirect('index.php');
}

$vendor_id = $vendor['id'];

// Handle order actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $id = (int)$_POST['id'];
                $status = sanitize($_POST['status']);
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND vendor_id = ?");
                    $stmt->execute([$status, $id, $vendor_id]);
                    $success = "Order status updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating order: " . $e->getMessage();
                }
                break;
                
            case 'add_note':
                $id = (int)$_POST['id'];
                $note = sanitize($_POST['note']);
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET vendor_notes = ? WHERE id = ? AND vendor_id = ?");
                    $stmt->execute([$note, $id, $vendor_id]);
                    $success = "Note added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding note: " . $e->getMessage();
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
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';

$where_conditions = ["o.vendor_id = ?"];
$params = [$vendor_id];

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ?)";
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
    JOIN users u ON o.user_id = u.id 
    $where_clause
");
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Get orders with related data
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
           d.full_name as delivery_person_name
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN users d ON o.delivery_person_id = d.id
    $where_clause 
    ORDER BY o.created_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
        SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(total_amount) as total_revenue
    FROM orders WHERE vendor_id = ?
");
$stats_stmt->execute([$vendor_id]);
$stats = $stats_stmt->fetch();

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
        <p class="text-muted">Manage your restaurant orders</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-2">
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
    
    <div class="col-md-2">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['pending']); ?></div>
                <p class="text-muted mb-0">Pending</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['preparing']); ?></div>
                <p class="text-muted mb-0">Preparing</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['ready']); ?></div>
                <p class="text-muted mb-0">Ready</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
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
    
    <div class="col-md-2">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                <p class="text-muted mb-0">Revenue</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-card">
    <form method="GET" class="row g-3">
        <div class="col-md-4">
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
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">No orders found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>
                        <strong><?php echo $order['order_number']; ?></strong>
                        <?php if ($order['vendor_notes']): ?>
                        <br><small class="text-info"><i class="fas fa-sticky-note"></i> Has note</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($order['customer_name']); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($order['customer_phone'] ?? $order['customer_email']); ?></small>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" onclick="viewOrderItems(<?php echo $order['id']; ?>)">
                            <i class="fas fa-list"></i> View Items
                        </button>
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
                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                        <br><small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-info" onclick="viewOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if (in_array($order['status'], ['pending', 'confirmed', 'preparing', 'ready'])): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="addNote(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['vendor_notes'] ?? ''); ?>')">
                                <i class="fas fa-sticky-note"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
                            <option value="ready">Ready for Pickup</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <strong>Status Guide:</strong><br>
                            • <strong>Confirmed:</strong> Order accepted and being processed<br>
                            • <strong>Preparing:</strong> Food is being prepared<br>
                            • <strong>Ready:</strong> Order is ready for pickup<br>
                            • <strong>Cancelled:</strong> Order cancelled (refund will be processed)
                        </small>
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

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Order Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_note">
                    <input type="hidden" name="id" id="note_order_id">
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" name="note" id="order_note" rows="4" placeholder="Add special instructions or notes for this order..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Save Note</button>
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

<!-- View Order Items Modal -->
<div class="modal fade" id="viewOrderItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderItemsDetails">
                <!-- Order items will be loaded here -->
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

function addNote(id, currentNote) {
    document.getElementById('note_order_id').value = id;
    document.getElementById('order_note').value = currentNote;
    new bootstrap.Modal(document.getElementById('addNoteModal')).show();
}

function viewOrder(order) {
    const details = `
        <div class="row">
            <div class="col-md-6">
                <h6>Order Information</h6>
                <p><strong>Order Number:</strong> ${order.order_number}</p>
                <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(order.status)}">${order.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></p>
                <p><strong>Payment Status:</strong> <span class="badge bg-${getPaymentColor(order.payment_status)}">${order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1)}</span></p>
                <p><strong>Total Amount:</strong> $${parseFloat(order.total_amount).toFixed(2)}</p>
                <p><strong>Created:</strong> ${new Date(order.created_at).toLocaleString()}</p>
            </div>
            <div class="col-md-6">
                <h6>Customer Information</h6>
                <p><strong>Name:</strong> ${order.customer_name}</p>
                <p><strong>Email:</strong> ${order.customer_email}</p>
                <p><strong>Phone:</strong> ${order.customer_phone || 'N/A'}</p>
                ${order.delivery_person_name ? `<p><strong>Delivery Person:</strong> ${order.delivery_person_name}</p>` : ''}
            </div>
        </div>
        ${order.delivery_address ? `<hr><h6>Delivery Address</h6><p>${order.delivery_address}</p>` : ''}
        ${order.vendor_notes ? `<hr><h6>Vendor Notes</h6><p>${order.vendor_notes}</p>` : ''}
        ${order.special_instructions ? `<hr><h6>Special Instructions</h6><p>${order.special_instructions}</p>` : ''}
    `;
    
    document.getElementById('orderDetails').innerHTML = details;
    new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
}

function viewOrderItems(orderId) {
    document.getElementById('orderItemsDetails').innerHTML = '<p>Loading order items...</p>';
    new bootstrap.Modal(document.getElementById('viewOrderItemsModal')).show();
    
    // Fetch order items via AJAX (implement as needed)
    fetch(`get-order-items.php?id=${orderId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('orderItemsDetails').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('orderItemsDetails').innerHTML = '<p>Error loading order items.</p>';
        });
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'confirmed': 'info',
        'preparing': 'primary',
        'ready': 'secondary',
        'picked_up': 'info',
        'in_transit': 'primary',
        'delivered': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
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
</script>

<?php require_once '../includes/footer.php'; ?>
