<?php
require_once '../config.php';
$page_title = 'Earnings & Analytics';
$current_page = 'earnings';

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

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');

// Get earnings statistics
$earnings_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount * 0.85 ELSE 0 END) as net_earnings,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount * 0.15 ELSE 0 END) as platform_fee,
        AVG(CASE WHEN payment_status = 'paid' THEN total_amount ELSE NULL END) as avg_order_value,
        SUM(CASE WHEN status = 'delivered' AND payment_status = 'paid' THEN 1 ELSE 0 END) as completed_orders
    FROM orders 
    WHERE vendor_id = ? AND DATE(created_at) BETWEEN ? AND ?
");
$earnings_stmt->execute([$vendor_id, $start_date, $end_date]);
$earnings = $earnings_stmt->fetch();

// Get daily earnings for chart
$daily_stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount * 0.85 ELSE 0 END) as earnings
    FROM orders 
    WHERE vendor_id = ? AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at)
");
$daily_stmt->execute([$vendor_id, $start_date, $end_date]);
$daily_earnings = $daily_stmt->fetchAll();

// Get top selling products
$products_stmt = $pdo->prepare("
    SELECT 
        p.name,
        p.price,
        COUNT(oi.id) as order_count,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as revenue
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE p.vendor_id = ? AND o.payment_status = 'paid' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 10
");
$products_stmt->execute([$vendor_id, $start_date, $end_date]);
$top_products = $products_stmt->fetchAll();

// Get recent transactions
$transactions_stmt = $pdo->prepare("
    SELECT 
        o.order_number,
        o.total_amount,
        o.total_amount * 0.85 as net_amount,
        o.total_amount * 0.15 as fee,
        o.payment_status,
        o.created_at,
        u.full_name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.vendor_id = ? AND DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.created_at DESC
    LIMIT 20
");
$transactions_stmt->execute([$vendor_id, $start_date, $end_date]);
$transactions = $transactions_stmt->fetchAll();

// Get monthly comparison
$current_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));

$monthly_stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as orders,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount * 0.85 ELSE 0 END) as earnings
    FROM orders 
    WHERE vendor_id = ? AND DATE_FORMAT(created_at, '%Y-%m') IN (?, ?)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
");
$monthly_stmt->execute([$vendor_id, $current_month, $last_month]);
$monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h4>Earnings & Analytics</h4>
        <p class="text-muted">Track your restaurant's financial performance</p>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-outline-primary" onclick="exportData()">
            <i class="fas fa-download"></i> Export Data
        </button>
    </div>
</div>

<!-- Date Filter -->
<div class="dashboard-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary-custom w-100">Apply Filter</button>
        </div>
        <div class="col-md-2">
            <a href="earnings.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
        <div class="col-md-2">
            <small class="text-muted">
                Showing data from <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?>
            </small>
        </div>
    </form>
</div>

<!-- Earnings Overview -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($earnings['net_earnings'] ?? 0, 2); ?></div>
                <p class="text-muted mb-0">Net Earnings</p>
                <small class="text-success">After platform fees</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($earnings['total_revenue'] ?? 0, 2); ?></div>
                <p class="text-muted mb-0">Total Revenue</p>
                <small class="text-info">Gross sales</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo number_format($earnings['total_orders'] ?? 0); ?></div>
                <p class="text-muted mb-0">Total Orders</p>
                <small class="text-warning"><?php echo number_format($earnings['completed_orders'] ?? 0); ?> completed</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($earnings['avg_order_value'] ?? 0, 2); ?></div>
                <p class="text-muted mb-0">Avg Order Value</p>
                <small class="text-info">Per order</small>
            </div>
        </div>
    </div>
</div>

<!-- Platform Fees -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="dashboard-card">
            <h6 class="mb-3">Fee Breakdown</h6>
            <div class="row">
                <div class="col-6">
                    <div class="text-center">
                        <div class="h4 text-success">$<?php echo number_format($earnings['net_earnings'] ?? 0, 2); ?></div>
                        <small class="text-muted">Your Earnings (85%)</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="text-center">
                        <div class="h4 text-warning">$<?php echo number_format($earnings['platform_fee'] ?? 0, 2); ?></div>
                        <small class="text-muted">Platform Fee (15%)</small>
                    </div>
                </div>
            </div>
            <div class="progress mt-3" style="height: 10px;">
                <div class="progress-bar bg-success" style="width: 85%"></div>
                <div class="progress-bar bg-warning" style="width: 15%"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="dashboard-card">
            <h6 class="mb-3">Monthly Comparison</h6>
            <?php
            $current_earnings = 0;
            $last_earnings = 0;
            foreach ($monthly_data as $month => $data) {
                if ($month == $current_month) {
                    $current_earnings = $data['earnings'] ?? 0;
                } elseif ($month == $last_month) {
                    $last_earnings = $data['earnings'] ?? 0;
                }
            }
            $change = $last_earnings > 0 ? (($current_earnings - $last_earnings) / $last_earnings) * 100 : 0;
            ?>
            <div class="row">
                <div class="col-6">
                    <div class="text-center">
                        <div class="h4">$<?php echo number_format($current_earnings, 2); ?></div>
                        <small class="text-muted">This Month</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="text-center">
                        <div class="h4">$<?php echo number_format($last_earnings, 2); ?></div>
                        <small class="text-muted">Last Month</small>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <span class="badge bg-<?php echo $change >= 0 ? 'success' : 'danger'; ?> fs-6">
                    <i class="fas fa-arrow-<?php echo $change >= 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo abs(number_format($change, 1)); ?>%
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="dashboard-card">
            <h6 class="mb-3">Daily Earnings Trend</h6>
            <canvas id="earningsChart" height="100"></canvas>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="dashboard-card">
            <h6 class="mb-3">Top Selling Products</h6>
            <div class="list-group">
                <?php if (empty($top_products)): ?>
                <div class="list-group-item text-center text-muted">
                    No sales data available
                </div>
                <?php else: ?>
                <?php foreach (array_slice($top_products, 0, 5) as $product): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                            <small class="text-muted">Sold: <?php echo $product['total_sold']; ?> | Orders: <?php echo $product['order_count']; ?></small>
                        </div>
                        <span class="badge bg-success">$<?php echo number_format($product['revenue'], 0); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="dashboard-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Recent Transactions</h6>
        <small class="text-muted"><?php echo count($transactions); ?> transactions</small>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Gross Amount</th>
                    <th>Platform Fee</th>
                    <th>Net Earnings</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">No transactions found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo $transaction['order_number']; ?></td>
                    <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                    <td>$<?php echo number_format($transaction['total_amount'], 2); ?></td>
                    <td class="text-warning">-$<?php echo number_format($transaction['fee'], 2); ?></td>
                    <td class="text-success">$<?php echo number_format($transaction['net_amount'], 2); ?></td>
                    <td>
                        <?php
                        $payment_colors = [
                            'pending' => 'warning',
                            'paid' => 'success',
                            'failed' => 'danger',
                            'refunded' => 'info'
                        ];
                        $color = $payment_colors[$transaction['payment_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?>">
                            <?php echo ucfirst($transaction['payment_status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for chart
const dailyData = <?php echo json_encode($daily_earnings); ?>;
const labels = dailyData.map(item => {
    const date = new Date(item.date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});
const earnings = dailyData.map(item => parseFloat(item.earnings) || 0);
const revenue = dailyData.map(item => parseFloat(item.revenue) || 0);

// Create earnings chart
const ctx = document.getElementById('earningsChart').getContext('2d');
const earningsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Net Earnings',
            data: earnings,
            borderColor: '#2ecc71',
            backgroundColor: 'rgba(46, 204, 113, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Gross Revenue',
            data: revenue,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4,
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toFixed(0);
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                    }
                }
            }
        }
    }
});

function exportData() {
    const startDate = '<?php echo $start_date; ?>';
    const endDate = '<?php echo $end_date; ?>';
    window.open(`export-earnings.php?start_date=${startDate}&end_date=${endDate}`, '_blank');
}
</script>

<?php require_once '../includes/footer.php'; ?>
