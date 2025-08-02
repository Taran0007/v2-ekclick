<?php
require_once '../config.php';
$page_title = 'Delivery Earnings';
$current_page = 'earnings';

// Check if user is delivery personnel
if (getUserRole() !== 'delivery') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-d');

// Get earnings statistics
$earnings_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as successful_deliveries,
        SUM(CASE WHEN status = 'delivered' THEN 5.00 ELSE 0 END) as total_earnings,
        AVG(CASE WHEN status = 'delivered' AND actual_delivery_time IS NOT NULL 
                 THEN TIMESTAMPDIFF(MINUTE, created_at, actual_delivery_time) 
                 ELSE NULL END) as avg_delivery_time,
        SUM(CASE WHEN status = 'delivered' AND DATE(actual_delivery_time) = CURDATE() THEN 5.00 ELSE 0 END) as today_earnings,
        COUNT(CASE WHEN status = 'delivered' AND DATE(actual_delivery_time) = CURDATE() THEN 1 END) as today_deliveries
    FROM orders 
    WHERE delivery_person_id = ? AND DATE(created_at) BETWEEN ? AND ?
");
$earnings_stmt->execute([$user_id, $start_date, $end_date]);
$earnings = $earnings_stmt->fetch();

// Get daily earnings for chart
$daily_stmt = $pdo->prepare("
    SELECT 
        DATE(actual_delivery_time) as date,
        COUNT(*) as deliveries,
        SUM(5.00) as earnings,
        AVG(TIMESTAMPDIFF(MINUTE, created_at, actual_delivery_time)) as avg_time
    FROM orders 
    WHERE delivery_person_id = ? AND status = 'delivered' 
    AND DATE(actual_delivery_time) BETWEEN ? AND ?
    GROUP BY DATE(actual_delivery_time)
    ORDER BY DATE(actual_delivery_time)
");
$daily_stmt->execute([$user_id, $start_date, $end_date]);
$daily_earnings = $daily_stmt->fetchAll();

// Get weekly comparison
$current_week_start = date('Y-m-d', strtotime('monday this week'));
$last_week_start = date('Y-m-d', strtotime('monday last week'));
$last_week_end = date('Y-m-d', strtotime('sunday last week'));

$weekly_stmt = $pdo->prepare("
    SELECT 
        CASE 
            WHEN DATE(actual_delivery_time) >= ? THEN 'current'
            ELSE 'last'
        END as week_type,
        COUNT(*) as deliveries,
        SUM(5.00) as earnings
    FROM orders 
    WHERE delivery_person_id = ? AND status = 'delivered'
    AND DATE(actual_delivery_time) >= ?
    GROUP BY week_type
");
$weekly_stmt->execute([$current_week_start, $user_id, $last_week_start]);
$weekly_data = $weekly_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get hourly performance
$hourly_stmt = $pdo->prepare("
    SELECT 
        HOUR(actual_delivery_time) as hour,
        COUNT(*) as deliveries,
        SUM(5.00) as earnings
    FROM orders 
    WHERE delivery_person_id = ? AND status = 'delivered'
    AND DATE(actual_delivery_time) BETWEEN ? AND ?
    GROUP BY HOUR(actual_delivery_time)
    ORDER BY hour
");
$hourly_stmt->execute([$user_id, $start_date, $end_date]);
$hourly_data = $hourly_stmt->fetchAll();

// Get recent earnings transactions
$transactions_stmt = $pdo->prepare("
    SELECT 
        o.order_number,
        o.total_amount as order_value,
        5.00 as delivery_fee,
        o.actual_delivery_time,
        o.created_at,
        u.full_name as customer_name,
        v.shop_name,
        TIMESTAMPDIFF(MINUTE, o.created_at, o.actual_delivery_time) as duration
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN vendors v ON o.vendor_id = v.id
    WHERE o.delivery_person_id = ? AND o.status = 'delivered'
    AND DATE(o.actual_delivery_time) BETWEEN ? AND ?
    ORDER BY o.actual_delivery_time DESC
    LIMIT 20
");
$transactions_stmt->execute([$user_id, $start_date, $end_date]);
$transactions = $transactions_stmt->fetchAll();

// Calculate performance metrics
$current_week_earnings = $weekly_data['current']['earnings'] ?? 0;
$last_week_earnings = $weekly_data['last']['earnings'] ?? 0;
$week_change = $last_week_earnings > 0 ? (($current_week_earnings - $last_week_earnings) / $last_week_earnings) * 100 : 0;

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h4>Delivery Earnings</h4>
        <p class="text-muted">Track your delivery performance and earnings</p>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-outline-primary" onclick="exportEarnings()">
            <i class="fas fa-download"></i> Export Report
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
                <div class="stat-number">$<?php echo number_format($earnings['total_earnings'] ?? 0, 2); ?></div>
                <p class="text-muted mb-0">Total Earnings</p>
                <small class="text-success">Period total</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-number"><?php echo number_format($earnings['successful_deliveries'] ?? 0); ?></div>
                <p class="text-muted mb-0">Deliveries</p>
                <small class="text-info">Completed successfully</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($earnings['today_earnings'] ?? 0, 2); ?></div>
                <p class="text-muted mb-0">Today's Earnings</p>
                <small class="text-warning"><?php echo $earnings['today_deliveries'] ?? 0; ?> deliveries</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="stat-number">$<?php echo $earnings['successful_deliveries'] > 0 ? number_format($earnings['total_earnings'] / $earnings['successful_deliveries'], 2) : '0.00'; ?></div>
                <p class="text-muted mb-0">Avg Per Delivery</p>
                <small class="text-info">Per completed order</small>
            </div>
        </div>
    </div>
</div>

<!-- Performance Metrics -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="dashboard-card">
            <h6 class="mb-3">Weekly Comparison</h6>
            <div class="row">
                <div class="col-6">
                    <div class="text-center">
                        <div class="h4 text-primary">$<?php echo number_format($current_week_earnings, 2); ?></div>
                        <small class="text-muted">This Week</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="text-center">
                        <div class="h4 text-secondary">$<?php echo number_format($last_week_earnings, 2); ?></div>
                        <small class="text-muted">Last Week</small>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <span class="badge bg-<?php echo $week_change >= 0 ? 'success' : 'danger'; ?> fs-6">
                    <i class="fas fa-arrow-<?php echo $week_change >= 0 ? 'up' : 'down'; ?>"></i>
                    <?php echo abs(number_format($week_change, 1)); ?>%
                </span>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="dashboard-card">
            <h6 class="mb-3">Performance Metrics</h6>
            <div class="row">
                <div class="col-6">
                    <div class="text-center">
                        <div class="h4 text-success"><?php echo number_format($earnings['avg_delivery_time'] ?? 0); ?></div>
                        <small class="text-muted">Avg Time (min)</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="text-center">
                        <div class="h4 text-info">
                            <?php echo $earnings['total_deliveries'] > 0 ? number_format(($earnings['successful_deliveries'] / $earnings['total_deliveries']) * 100, 1) : 0; ?>%
                        </div>
                        <small class="text-muted">Success Rate</small>
                    </div>
                </div>
            </div>
            <div class="progress mt-3" style="height: 10px;">
                <div class="progress-bar bg-success" style="width: <?php echo $earnings['total_deliveries'] > 0 ? ($earnings['successful_deliveries'] / $earnings['total_deliveries']) * 100 : 0; ?>%"></div>
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
            <h6 class="mb-3">Peak Hours</h6>
            <canvas id="hourlyChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="dashboard-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Recent Deliveries</h6>
        <small class="text-muted"><?php echo count($transactions); ?> transactions</small>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Restaurant</th>
                    <th>Customer</th>
                    <th>Order Value</th>
                    <th>Your Fee</th>
                    <th>Duration</th>
                    <th>Completed</th>
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
                    <td><?php echo htmlspecialchars($transaction['shop_name']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                    <td>$<?php echo number_format($transaction['order_value'], 2); ?></td>
                    <td class="text-success fw-bold">$<?php echo number_format($transaction['delivery_fee'], 2); ?></td>
                    <td>
                        <?php if ($transaction['duration']): ?>
                            <?php echo $transaction['duration']; ?> min
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M d, H:i', strtotime($transaction['actual_delivery_time'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Earnings Goals -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="dashboard-card">
            <h6 class="mb-3">Earnings Goals</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="h5">$<?php echo number_format($earnings['today_earnings'] ?? 0, 2); ?> / $50</div>
                        <small class="text-muted">Daily Goal</small>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo min((($earnings['today_earnings'] ?? 0) / 50) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="h5">$<?php echo number_format($current_week_earnings, 2); ?> / $300</div>
                        <small class="text-muted">Weekly Goal</small>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?php echo min(($current_week_earnings / 300) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="h5"><?php echo $earnings['today_deliveries'] ?? 0; ?> / 10</div>
                        <small class="text-muted">Daily Deliveries</small>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: <?php echo min((($earnings['today_deliveries'] ?? 0) / 10) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="h5"><?php echo ($weekly_data['current']['deliveries'] ?? 0); ?> / 60</div>
                        <small class="text-muted">Weekly Deliveries</small>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: <?php echo min((($weekly_data['current']['deliveries'] ?? 0) / 60) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for daily earnings chart
const dailyData = <?php echo json_encode($daily_earnings); ?>;
const labels = dailyData.map(item => {
    const date = new Date(item.date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});
const earnings = dailyData.map(item => parseFloat(item.earnings) || 0);
const deliveries = dailyData.map(item => parseInt(item.deliveries) || 0);

// Create daily earnings chart
const ctx = document.getElementById('earningsChart').getContext('2d');
const earningsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Daily Earnings',
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
                        if (context.dataset.label === 'Daily Earnings') {
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

// Prepare data for hourly chart
const hourlyData = <?php echo json_encode($hourly_data); ?>;
const hourLabels = [];
const hourlyEarnings = [];

// Fill in all 24 hours
for (let i = 0; i < 24; i++) {
    hourLabels.push(i + ':00');
    const hourData = hourlyData.find(item => parseInt(item.hour) === i);
    hourlyEarnings.push(hourData ? parseFloat(hourData.earnings) : 0);
}

// Create hourly chart
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
const hourlyChart = new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: hourLabels,
        datasets: [{
            label: 'Hourly Earnings',
            data: hourlyEarnings,
            backgroundColor: 'rgba(155, 89, 182, 0.8)',
            borderColor: '#9b59b6',
            borderWidth: 1
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
                        return 'Earnings: $' + context.parsed.y.toFixed(2);
                    }
                }
            }
        }
    }
});

function exportEarnings() {
    const startDate = '<?php echo $start_date; ?>';
    const endDate = '<?php echo $end_date; ?>';
    window.open(`export-earnings.php?start_date=${startDate}&end_date=${endDate}`, '_blank');
}
</script>

<?php require_once '../includes/footer.php'; ?>
