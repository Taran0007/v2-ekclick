<?php
require_once '../config.php';
$page_title = 'Active Deliveries';
$current_page = 'active-deliveries';

// Check if user is delivery personnel
if (getUserRole() !== 'delivery') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle delivery status updates
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_status':
            $order_id = (int)$_POST['order_id'];
            $status = sanitize($_POST['status']);
            
            try {
                $update_fields = "status = ?, updated_at = NOW()";
                $params = [$status, $order_id, $user_id];
                
                // If marking as delivered, set delivery time
                if ($status == 'delivered') {
                    $update_fields .= ", actual_delivery_time = NOW()";
                }
                
                $stmt = $pdo->prepare("UPDATE orders SET $update_fields WHERE id = ? AND delivery_person_id = ?");
                $stmt->execute($params);
                
                $success = "Delivery status updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating status: " . $e->getMessage();
            }
            break;
            
        case 'add_note':
            $order_id = (int)$_POST['order_id'];
            $note = sanitize($_POST['note']);
            
            try {
                $stmt = $pdo->prepare("UPDATE orders SET delivery_notes = ? WHERE id = ? AND delivery_person_id = ?");
                $stmt->execute([$note, $order_id, $user_id]);
                $success = "Delivery note added successfully!";
            } catch (PDOException $e) {
                $error = "Error adding note: " . $e->getMessage();
            }
            break;
    }
}

// Get active deliveries
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email,
           v.shop_name, v.address as pickup_address, v.phone as vendor_phone,
           v.latitude as pickup_lat, v.longitude as pickup_lng
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    WHERE o.delivery_person_id = ? 
    AND o.status IN ('picked_up', 'in_transit')
    ORDER BY o.created_at ASC
");
$stmt->execute([$user_id]);
$active_deliveries = $stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_active,
        SUM(CASE WHEN status = 'picked_up' THEN 1 ELSE 0 END) as picked_up,
        SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
        AVG(TIMESTAMPDIFF(MINUTE, created_at, NOW())) as avg_time
    FROM orders 
    WHERE delivery_person_id = ? AND status IN ('picked_up', 'in_transit')
");
$stats_stmt->execute([$user_id]);
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
        <h4>Active Deliveries</h4>
        <p class="text-muted">Manage your current delivery assignments</p>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-outline-primary" onclick="refreshDeliveries()">
            <i class="fas fa-sync-alt"></i> Refresh
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
                <div class="stat-number"><?php echo number_format($stats['total_active']); ?></div>
                <p class="text-muted mb-0">Total Active</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['picked_up']); ?></div>
                <p class="text-muted mb-0">Picked Up</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="fas fa-route"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['in_transit']); ?></div>
                <p class="text-muted mb-0">In Transit</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['avg_time'] ?? 0); ?></div>
                <p class="text-muted mb-0">Avg Time (min)</p>
            </div>
        </div>
    </div>
</div>

<!-- Active Deliveries -->
<?php if (empty($active_deliveries)): ?>
<div class="dashboard-card text-center">
    <i class="fas fa-truck fa-3x text-muted mb-3"></i>
    <h5>No Active Deliveries</h5>
    <p class="text-muted">You don't have any active deliveries at the moment.</p>
    <a href="available-orders.php" class="btn btn-primary-custom">
        <i class="fas fa-search"></i> Find Available Orders
    </a>
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($active_deliveries as $delivery): ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="dashboard-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="mb-0">Order #<?php echo $delivery['order_number']; ?></h6>
                <span class="badge bg-<?php echo $delivery['status'] == 'picked_up' ? 'info' : 'primary'; ?> badge-status">
                    <?php echo ucfirst(str_replace('_', ' ', $delivery['status'])); ?>
                </span>
            </div>
            
            <!-- Pickup Information -->
            <div class="mb-3">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-store text-primary me-2"></i>
                    <div class="flex-grow-1">
                        <strong><?php echo htmlspecialchars($delivery['shop_name']); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($delivery['pickup_address']); ?></small>
                    </div>
                    <?php if ($delivery['status'] == 'picked_up'): ?>
                    <span class="badge bg-success">✓</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Delivery Information -->
            <div class="mb-3">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-home text-success me-2"></i>
                    <div class="flex-grow-1">
                        <strong><?php echo htmlspecialchars($delivery['customer_name']); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($delivery['delivery_address']); ?></small>
                    </div>
                </div>
                
                <div class="d-flex align-items-center">
                    <i class="fas fa-phone text-info me-2"></i>
                    <a href="tel:<?php echo $delivery['customer_phone']; ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($delivery['customer_phone']); ?>
                    </a>
                </div>
            </div>
            
            <!-- Order Details -->
            <div class="row mb-3">
                <div class="col-6">
                    <small class="text-muted">Order Value</small>
                    <div class="fw-bold">$<?php echo number_format($delivery['total_amount'], 2); ?></div>
                </div>
                <div class="col-6">
                    <small class="text-muted">Your Fee</small>
                    <div class="fw-bold text-success">$5.00</div>
                </div>
            </div>
            
            <!-- Time Information -->
            <div class="mb-3">
                <small class="text-muted">
                    Order Time: <?php echo date('H:i', strtotime($delivery['created_at'])); ?>
                    <br>Duration: <?php echo floor((time() - strtotime($delivery['created_at'])) / 60); ?> minutes
                </small>
            </div>
            
            <!-- Special Instructions -->
            <?php if ($delivery['special_instructions']): ?>
            <div class="mb-3">
                <small class="text-muted">Special Instructions:</small>
                <div class="small bg-light p-2 rounded"><?php echo htmlspecialchars($delivery['special_instructions']); ?></div>
            </div>
            <?php endif; ?>
            
            <!-- Delivery Notes -->
            <?php if ($delivery['delivery_notes']): ?>
            <div class="mb-3">
                <small class="text-muted">Your Notes:</small>
                <div class="small bg-info bg-opacity-10 p-2 rounded"><?php echo htmlspecialchars($delivery['delivery_notes']); ?></div>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="mt-auto">
                <div class="d-grid gap-2">
                    <?php if ($delivery['status'] == 'picked_up'): ?>
                        <button class="btn btn-primary-custom" onclick="updateStatus(<?php echo $delivery['id']; ?>, 'in_transit')">
                            <i class="fas fa-truck"></i> Start Delivery
                        </button>
                    <?php else: ?>
                        <button class="btn btn-success" onclick="updateStatus(<?php echo $delivery['id']; ?>, 'delivered')">
                            <i class="fas fa-check"></i> Mark as Delivered
                        </button>
                    <?php endif; ?>
                    
                    <div class="btn-group" role="group">
                        <button class="btn btn-outline-secondary btn-sm" onclick="addNote(<?php echo $delivery['id']; ?>, '<?php echo htmlspecialchars($delivery['delivery_notes'] ?? ''); ?>')">
                            <i class="fas fa-sticky-note"></i> Note
                        </button>
                        <a href="tel:<?php echo $delivery['customer_phone']; ?>" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-phone"></i> Call
                        </a>
                        <a href="https://maps.google.com/?q=<?php echo urlencode($delivery['delivery_address']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-map-marker-alt"></i> Navigate
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Delivery Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="status_order_id">
                    <input type="hidden" name="status" id="new_status">
                    <div id="status_message"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom" id="confirm_status">Confirm</button>
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
                <h5 class="modal-title">Add Delivery Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_note">
                    <input type="hidden" name="order_id" id="note_order_id">
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" name="note" id="delivery_note" rows="4" placeholder="Add notes about this delivery..."></textarea>
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

<script>
function updateStatus(orderId, status) {
    document.getElementById('status_order_id').value = orderId;
    document.getElementById('new_status').value = status;
    
    let message = '';
    let buttonText = 'Confirm';
    let buttonClass = 'btn-primary-custom';
    
    if (status === 'in_transit') {
        message = '<p>Are you sure you want to mark this order as <strong>In Transit</strong>?</p><div class="alert alert-info"><small>This means you have picked up the order and are on your way to deliver it.</small></div>';
        buttonText = 'Start Delivery';
    } else if (status === 'delivered') {
        message = '<p>Are you sure you want to mark this order as <strong>Delivered</strong>?</p><div class="alert alert-success"><small>This will complete the delivery and you will receive your delivery fee.</small></div>';
        buttonText = 'Mark Delivered';
        buttonClass = 'btn-success';
    }
    
    document.getElementById('status_message').innerHTML = message;
    document.getElementById('confirm_status').textContent = buttonText;
    document.getElementById('confirm_status').className = 'btn ' + buttonClass;
    
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

function addNote(orderId, currentNote) {
    document.getElementById('note_order_id').value = orderId;
    document.getElementById('delivery_note').value = currentNote;
    new bootstrap.Modal(document.getElementById('addNoteModal')).show();
}

function refreshDeliveries() {
    location.reload();
}

// Auto-refresh every 60 seconds
setInterval(function() {
    // Only refresh if no modals are open
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 60000);

// Update time display every minute
setInterval(function() {
    const timeElements = document.querySelectorAll('[data-order-time]');
    timeElements.forEach(function(element) {
        const orderTime = new Date(element.dataset.orderTime);
        const now = new Date();
        const diffMinutes = Math.floor((now - orderTime) / (1000 * 60));
        element.textContent = `Duration: ${diffMinutes} minutes`;
    });
}, 60000);
</script>

<?php require_once '../includes/footer.php'; ?>
