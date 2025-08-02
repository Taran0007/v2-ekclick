<?php
require_once '../config.php';
$page_title = 'Delivery History';
$current_page = 'delivery-history';

// Check if user is delivery personnel
if (getUserRole() !== 'delivery') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get delivery history with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$date_filter = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$where_conditions = ["o.delivery_person_id = ?"];
$params = [$user_id];

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ? OR v.shop_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($date_filter) {
    $where_conditions[] = "DATE(o.created_at) = ?";
    $params[] = $date_filter;
}

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
} else {
    // Default to completed deliveries
    $where_conditions[] = "o.status IN ('delivered', 'cancelled')";
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
$total_deliveries = $count_stmt->fetchColumn();
$total_pages = ceil($total_deliveries / $limit);

// Get delivery history
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.full_name as customer_name, u.phone as customer_phone,
           v.shop_name, v.address as pickup_address
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    $where_clause 
    ORDER BY o.actual_delivery_time DESC, o.updated_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$delivery_history = $stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as successful_deliveries,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_deliveries,
        SUM(CASE WHEN status = 'delivered' THEN 5.00 ELSE 0 END) as total_earnings,
        AVG(CASE WHEN status = 'delivered' AND actual_delivery_time IS NOT NULL 
                 THEN TIMESTAMPDIFF(MINUTE, created_at, actual_delivery_time) 
                 ELSE NULL END) as avg_delivery_time
    FROM orders 
    WHERE delivery_person_id = ? AND status IN ('delivered', 'cancelled')
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();

// Get monthly earnings
$monthly_stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(actual_delivery_time, '%Y-%m') as month,
        COUNT(*) as deliveries,
        SUM(5.00) as earnings
    FROM orders 
    WHERE delivery_person_id = ? AND status = 'delivered' 
    AND actual_delivery_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(actual_delivery_time, '%Y-%m')
    ORDER BY month DESC
");
$monthly_stmt->execute([$user_id]);
$monthly_earnings = $monthly_stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h4>Delivery History</h4>
        <p class="text-muted">View your completed deliveries and earnings</p>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-outline-primary" onclick="exportHistory()">
            <i class="fas fa-download"></i> Export History
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['total_deliveries']); ?></div>
                <p class="text-muted mb-0">Total Deliveries</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['successful_deliveries']); ?></div>
                <p class="text-muted mb-0">Successful</p>
                <small class="text-success">
                    <?php echo $stats['total_deliveries'] > 0 ? number_format(($stats['successful_deliveries'] / $stats['total_deliveries']) * 100, 1) : 0; ?>% success rate
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['total_earnings'], 2); ?></div>
                <p class="text-muted mb-0">Total Earnings</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['avg_delivery_time'] ?? 0); ?></div>
                <p class="text-muted mb-0">Avg Time (min)</p>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Earnings Chart -->
<?php if (!empty($monthly_earnings)): ?>
<div class="row mb-4">
    <div class="col-md-8">
        <div class="dashboard-card">
            <h6 class="mb-3">Monthly Earnings Trend</h6>
            <canvas id="earningsChart" height="100"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card">
            <h6 class="mb-3">Monthly Breakdown</h6>
            <div class="list-group">
                <?php foreach (array_slice($monthly_earnings, 0, 6) as $month_data): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?php echo date('M Y', strtotime($month_data['month'] . '-01')); ?></strong>
                        <br><small class="text-muted"><?php echo $month_data['deliveries']; ?> deliveries</small>
                    </div>
                    <span class="badge bg-success">$<?php echo number_format($month_data['earnings'], 2); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="dashboard-card">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <input type="text" class="form-control" name="search" placeholder="Search deliveries..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
        </div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
        <div class="col-md-3">
            <a href="delivery-history.php" class="btn btn-outline-secondary w-100">Clear Filters</a>
        </div>
    </form>
</div>

<!-- Delivery History Table -->
<div class="dashboard-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Restaurant</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Delivery Time</th>
                    <th>Duration</th>
                    <th>Earnings</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($delivery_history)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">No delivery history found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($delivery_history as $delivery): ?>
                <tr>
                    <td>
                        <strong><?php echo $delivery['order_number']; ?></strong>
                        <br><small class="text-muted"><?php echo date('M d, Y', strtotime($delivery['created_at'])); ?></small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($delivery['shop_name']); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($delivery['pickup_address'], 0, 30)); ?>...</small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($delivery['customer_name']); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($delivery['delivery_address'], 0, 30)); ?>...</small>
                    </td>
                    <td>
                        <?php
                        $status_colors = [
                            'delivered' => 'success',
                            'cancelled' => 'danger'
                        ];
                        $color = $status_colors[$delivery['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?> badge-status">
                            <?php echo ucfirst($delivery['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($delivery['actual_delivery_time']): ?>
                            <?php echo date('H:i', strtotime($delivery['actual_delivery_time'])); ?>
                            <br><small class="text-muted"><?php echo date('M d', strtotime($delivery['actual_delivery_time'])); ?></small>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($delivery['actual_delivery_time']): ?>
                            <?php 
                            $duration = floor((strtotime($delivery['actual_delivery_time']) - strtotime($delivery['created_at'])) / 60);
                            echo $duration . ' min';
                            ?>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($delivery['status'] == 'delivered'): ?>
                            <span class="text-success fw-bold">$5.00</span>
                        <?php else: ?>
                            <span class="text-muted">$0.00</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-info" onclick="viewDeliveryDetails(<?php echo htmlspecialchars(json_encode($delivery)); ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php if ($delivery['delivery_notes']): ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="viewNotes('<?php echo htmlspecialchars($delivery['delivery_notes']); ?>')">
                                <i class="fas fa-sticky-note"></i>
                            </button>
                            <?php endif; ?>
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
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date=<?php echo urlencode($date_filter); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Delivery Details Modal -->
<div class="modal fade" id="deliveryDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delivery Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="deliveryDetailsContent">
                <!-- Delivery details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Notes Modal -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delivery Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="notesContent">
                <!-- Notes will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for chart
<?php if (!empty($monthly_earnings)): ?>
const monthlyData = <?php echo json_encode(array_reverse($monthly_earnings)); ?>;
const labels = monthlyData.map(item => {
    const date = new Date(item.month + '-01');
    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
});
const earnings = monthlyData.map(item => parseFloat(item.earnings));
const deliveries = monthlyData.map(item => parseInt(item.deliveries));

// Create earnings chart
const ctx = document.getElementById('earningsChart').getContext('2d');
const earningsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Monthly Earnings',
            data: earnings,
            borderColor: '#2ecc71',
            backgroundColor: 'rgba(46, 204, 113, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Deliveries',
            data: deliveries,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4,
            fill: false,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                ticks: {
                    callback: function(value) {
                        return '$' + value.toFixed(0);
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false,
                },
                ticks: {
                    callback: function(value) {
                        return value + ' deliveries';
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.dataset.label === 'Monthly Earnings') {
                            return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                        } else {
                            return context.dataset.label + ': ' + context.parsed.y + ' deliveries';
                        }
                    }
                }
            }
        }
    }
});
<?php endif; ?>

function viewDeliveryDetails(delivery) {
    const details = `
        <div class="row">
            <div class="col-md-6">
                <h6>Pickup Information</h6>
                <p><strong>Restaurant:</strong> ${delivery.shop_name}</p>
                <p><strong>Address:</strong> ${delivery.pickup_address}</p>
                <p><strong>Order Time:</strong> ${new Date(delivery.created_at).toLocaleString()}</p>
            </div>
            <div class="col-md-6">
                <h6>Delivery Information</h6>
                <p><strong>Customer:</strong> ${delivery.customer_name}</p>
                <p><strong>Address:</strong> ${delivery.delivery_address}</p>
                <p><strong>Phone:</strong> ${delivery.customer_phone}</p>
                ${delivery.actual_delivery_time ? `<p><strong>Delivered At:</strong> ${new Date(delivery.actual_delivery_time).toLocaleString()}</p>` : ''}
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <h6>Order Details</h6>
                <p><strong>Order Number:</strong> ${delivery.order_number}</p>
                <p><strong>Order Value:</strong> $${parseFloat(delivery.total_amount).toFixed(2)}</p>
                <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(delivery.status)}">${delivery.status.charAt(0).toUpperCase() + delivery.status.slice(1)}</span></p>
            </div>
            <div class="col-md-6">
                <h6>Delivery Summary</h6>
                <p><strong>Your Earnings:</strong> ${delivery.status === 'delivered' ? '$5.00' : '$0.00'}</p>
                ${delivery.actual_delivery_time ? `<p><strong>Duration:</strong> ${Math.floor((new Date(delivery.actual_delivery_time) - new Date(delivery.created_at)) / (1000 * 60))} minutes</p>` : ''}
            </div>
        </div>
        ${delivery.special_instructions ? `<hr><h6>Special Instructions</h6><p>${delivery.special_instructions}</p>` : ''}
        ${delivery.delivery_notes ? `<hr><h6>Your Notes</h6><p>${delivery.delivery_notes}</p>` : ''}
    `;
    
    document.getElementById('deliveryDetailsContent').innerHTML = details;
    new bootstrap.Modal(document.getElementById('deliveryDetailsModal')).show();
}

function viewNotes(notes) {
    document.getElementById('notesContent').innerHTML = `<p>${notes}</p>`;
    new bootstrap.Modal(document.getElementById('notesModal')).show();
}

function getStatusColor(status) {
    const colors = {
        'delivered': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}

function exportHistory() {
    const search = '<?php echo $search; ?>';
    const date = '<?php echo $date_filter; ?>';
    const status = '<?php echo $status_filter; ?>';
    window.open(`export-delivery-history.php?search=${search}&date=${date}&status=${status}`, '_blank');
}
</script>

<?php require_once '../includes/footer.php'; ?>
