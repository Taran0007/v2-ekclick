<?php
require_once '../config.php';
$page_title = 'Order Placed Successfully';
$current_page = 'orders';

// Check if user is customer
if (getUserRole() !== 'user') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get the most recent order(s) for this user
$stmt = $pdo->prepare("
    SELECT o.*, v.shop_name 
    FROM orders o 
    JOIN vendors v ON o.vendor_id = v.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Success Message -->
        <div class="dashboard-card text-center mb-4">
            <div class="success-icon mb-4">
                <i class="fas fa-check-circle fa-5x text-success"></i>
            </div>
            <h2 class="text-success mb-3">Order Placed Successfully! 🎉</h2>
            <p class="text-muted mb-4">Thank you for your order. We've received your request and our vendors are preparing your items.</p>
            
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="info-card p-3 rounded-3 bg-light">
                        <i class="fas fa-clock text-primary mb-2"></i>
                        <h6>Estimated Time</h6>
                        <p class="mb-0 text-muted">45-60 minutes</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card p-3 rounded-3 bg-light">
                        <i class="fas fa-bell text-warning mb-2"></i>
                        <h6>Order Updates</h6>
                        <p class="mb-0 text-muted">We'll notify you</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card p-3 rounded-3 bg-light">
                        <i class="fas fa-headset text-info mb-2"></i>
                        <h6>Need Help?</h6>
                        <p class="mb-0 text-muted">Contact support</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <?php if (!empty($recent_orders)): ?>
        <div class="dashboard-card">
            <h5 class="mb-4 d-flex align-items-center">
                <i class="fas fa-receipt me-2 text-primary"></i>Your Recent Orders
            </h5>
            
            <div class="order-list">
                <?php foreach ($recent_orders as $order): ?>
                <div class="order-item d-flex justify-content-between align-items-center p-3 mb-3 border rounded-3">
                    <div class="flex-grow-1">
                        <h6 class="mb-1"><?php echo htmlspecialchars($order['shop_name']); ?></h6>
                        <p class="mb-1 small text-muted">Order #<?php echo $order['order_number']; ?></p>
                        <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></small>
                    </div>
                    <div class="text-end">
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
                        <span class="badge bg-<?php echo $color; ?> mb-2">
                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                        <p class="mb-0 fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="orders.php" class="btn btn-primary-custom">
                    <i class="fas fa-list me-2"></i>View All Orders
                </a>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
                <a href="browse.php" class="btn btn-outline-secondary">
                    <i class="fas fa-utensils me-2"></i>Order More
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.success-icon {
    animation: bounce 1s ease-in-out;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

.info-card {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.info-card i {
    font-size: 1.5rem;
}

.order-item {
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.order-item:hover {
    background: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

<?php require_once '../includes/footer.php'; ?>
