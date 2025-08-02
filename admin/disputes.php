<?php
require_once '../config.php';
$page_title = 'Dispute Management';
$current_page = 'disputes';

// Check if user is admin
if (getUserRole() !== 'admin') {
    redirect('login.php');
}

// Handle dispute actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $order_id = (int)$_POST['order_id'];
                $user_id = (int)$_POST['user_id'];
                $type = sanitize($_POST['type']);
                $description = sanitize($_POST['description']);
                $priority = sanitize($_POST['priority']);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO disputes (order_id, user_id, type, description, priority, status) VALUES (?, ?, ?, ?, ?, 'open')");
                    $stmt->execute([$order_id, $user_id, $type, $description, $priority]);
                    // Redirect to prevent form resubmission on refresh
                    header("Location: disputes.php?success=dispute_created");
                    exit();
                } catch (PDOException $e) {
                    $error = "Error creating dispute: " . $e->getMessage();
                }
                break;
                
            case 'update_status':
                $id = (int)$_POST['id'];
                $status = sanitize($_POST['status']);
                $admin_notes = sanitize($_POST['admin_notes']);
                
                try {
                    $stmt = $pdo->prepare("UPDATE disputes SET status = ?, admin_notes = ?, resolved_at = ?, resolved_by = ? WHERE id = ?");
                    $resolved_at = ($status == 'resolved') ? date('Y-m-d H:i:s') : null;
                    $resolved_by = ($status == 'resolved') ? $_SESSION['user_id'] : null;
                    $stmt->execute([$status, $admin_notes, $resolved_at, $resolved_by, $id]);
                    // Redirect to prevent form resubmission on refresh
                    header("Location: disputes.php?success=status_updated");
                    exit();
                } catch (PDOException $e) {
                    $error = "Error updating dispute: " . $e->getMessage();
                }
                break;
                
            case 'add_response':
                $dispute_id = (int)$_POST['dispute_id'];
                $response = sanitize($_POST['response']);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO dispute_responses (dispute_id, user_id, response, is_admin) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$dispute_id, $_SESSION['user_id'], $response]);
                    // Redirect to prevent form resubmission on refresh
                    header("Location: disputes.php?success=response_added");
                    exit();
                } catch (PDOException $e) {
                    $error = "Error adding response: " . $e->getMessage();
                }
                break;
        }
    }
}

// Create disputes table if it doesn't exist (without foreign key constraints to avoid issues)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS disputes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NULL,
        user_id INT NOT NULL,
        type ENUM('order_issue', 'delivery_issue', 'payment_issue', 'quality_issue', 'other') DEFAULT 'other',
        description TEXT,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
        admin_notes TEXT,
        resolved_at DATETIME NULL,
        resolved_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS dispute_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dispute_id INT NOT NULL,
        user_id INT NOT NULL,
        response TEXT NOT NULL,
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Tables might already exist, ignore error
}

// Get disputes with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$priority_filter = isset($_GET['priority']) ? sanitize($_GET['priority']) : '';
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(d.description LIKE ? OR u.full_name LIKE ? OR o.order_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "d.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter) {
    $where_conditions[] = "d.priority = ?";
    $params[] = $priority_filter;
}

if ($type_filter) {
    $where_conditions[] = "d.type = ?";
    $params[] = $type_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM disputes d 
    LEFT JOIN users u ON d.user_id = u.id 
    LEFT JOIN orders o ON d.order_id = o.id 
    $where_clause
");
$count_stmt->execute($params);
$total_disputes = $count_stmt->fetchColumn();
$total_pages = ceil($total_disputes / $limit);

// Get disputes with related data
$stmt = $pdo->prepare("
    SELECT d.*, 
           u.full_name as customer_name, u.email as customer_email,
           o.order_number, o.total_amount,
           v.shop_name,
           admin.full_name as resolved_by_name
    FROM disputes d 
    LEFT JOIN users u ON d.user_id = u.id 
    LEFT JOIN orders o ON d.order_id = o.id 
    LEFT JOIN vendors v ON o.vendor_id = v.id
    LEFT JOIN users admin ON d.resolved_by = admin.id
    $where_clause 
    ORDER BY 
        CASE d.priority 
            WHEN 'urgent' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            WHEN 'low' THEN 4 
        END,
        d.created_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$disputes = $stmt->fetchAll();

// Get statistics - simplified approach to avoid SQL issues
$stats = [
    'total' => 0,
    'open' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'urgent' => 0,
    'high_priority' => 0
];

try {
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM disputes");
    $stats['total'] = $total_stmt->fetchColumn() ?: 0;
    
    $open_stmt = $pdo->query("SELECT COUNT(*) FROM disputes WHERE status = 'open'");
    $stats['open'] = $open_stmt->fetchColumn() ?: 0;
    
    $progress_stmt = $pdo->query("SELECT COUNT(*) FROM disputes WHERE status = 'in_progress'");
    $stats['in_progress'] = $progress_stmt->fetchColumn() ?: 0;
    
    $resolved_stmt = $pdo->query("SELECT COUNT(*) FROM disputes WHERE status = 'resolved'");
    $stats['resolved'] = $resolved_stmt->fetchColumn() ?: 0;
    
    $urgent_stmt = $pdo->query("SELECT COUNT(*) FROM disputes WHERE priority = 'urgent'");
    $stats['urgent'] = $urgent_stmt->fetchColumn() ?: 0;
    
    $high_stmt = $pdo->query("SELECT COUNT(*) FROM disputes WHERE priority = 'high'");
    $stats['high_priority'] = $high_stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    // If table doesn't exist yet, keep default values
}

// Handle success messages from redirects
$success_messages = [
    'dispute_created' => 'Dispute created successfully!',
    'status_updated' => 'Dispute status updated successfully!',
    'response_added' => 'Response added successfully!'
];

$success = null;
if (isset($_GET['success']) && isset($success_messages[$_GET['success']])) {
    $success = $success_messages[$_GET['success']];
}

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
        <h4>Dispute Management</h4>
        <p class="text-muted">Handle customer complaints and disputes</p>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addDisputeModal">
            <i class="fas fa-plus"></i> Create Dispute
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                <p class="text-muted mb-0">Total Disputes</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['open']); ?></div>
                <p class="text-muted mb-0">Open</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['in_progress']); ?></div>
                <p class="text-muted mb-0">In Progress</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['resolved']); ?></div>
                <p class="text-muted mb-0">Resolved</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['urgent']); ?></div>
                <p class="text-muted mb-0">Urgent</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(230, 126, 34, 0.1); color: #e67e22;">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['high_priority']); ?></div>
                <p class="text-muted mb-0">High Priority</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-card">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <input type="text" class="form-control" name="search" placeholder="Search disputes..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="open" <?php echo $status_filter == 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                <option value="closed" <?php echo $status_filter == 'closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="priority">
                <option value="">All Priority</option>
                <option value="urgent" <?php echo $priority_filter == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>High</option>
                <option value="medium" <?php echo $priority_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>Low</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="type">
                <option value="">All Types</option>
                <option value="order_issue" <?php echo $type_filter == 'order_issue' ? 'selected' : ''; ?>>Order Issue</option>
                <option value="delivery_issue" <?php echo $type_filter == 'delivery_issue' ? 'selected' : ''; ?>>Delivery Issue</option>
                <option value="payment_issue" <?php echo $type_filter == 'payment_issue' ? 'selected' : ''; ?>>Payment Issue</option>
                <option value="quality_issue" <?php echo $type_filter == 'quality_issue' ? 'selected' : ''; ?>>Quality Issue</option>
                <option value="other" <?php echo $type_filter == 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="disputes.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>
</div>

<!-- Disputes Table -->
<div class="dashboard-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($disputes as $dispute): ?>
                <tr>
                    <td><?php echo $dispute['id']; ?></td>
                    <td>
                        <?php if ($dispute['order_number']): ?>
                            <strong><?php echo $dispute['order_number']; ?></strong>
                            <br><small class="text-muted">$<?php echo number_format($dispute['total_amount'], 2); ?></small>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($dispute['customer_name'] ?? 'Unknown'); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($dispute['customer_email'] ?? 'N/A'); ?></small>
                    </td>
                    <td>
                        <span class="badge bg-info">
                            <?php echo ucfirst(str_replace('_', ' ', $dispute['type'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $priority_colors = [
                            'urgent' => 'danger',
                            'high' => 'warning',
                            'medium' => 'primary',
                            'low' => 'secondary'
                        ];
                        $priority_color = $priority_colors[$dispute['priority']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $priority_color; ?> badge-status">
                            <?php echo ucfirst($dispute['priority']); ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $status_colors = [
                            'open' => 'danger',
                            'in_progress' => 'warning',
                            'resolved' => 'success',
                            'closed' => 'secondary'
                        ];
                        $status_color = $status_colors[$dispute['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $status_color; ?> badge-status">
                            <?php echo ucfirst(str_replace('_', ' ', $dispute['status'])); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y H:i', strtotime($dispute['created_at'])); ?></td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-info" onclick="viewDispute(<?php echo htmlspecialchars(json_encode($dispute)); ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="updateDisputeStatus(<?php echo $dispute['id']; ?>, '<?php echo $dispute['status']; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="addResponse(<?php echo $dispute['id']; ?>)">
                                <i class="fas fa-reply"></i>
                            </button>
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
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&priority=<?php echo urlencode($priority_filter); ?>&type=<?php echo urlencode($type_filter); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Add Dispute Modal -->
<div class="modal fade" id="addDisputeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Dispute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Order ID</label>
                        <input type="number" class="form-control" name="order_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Customer ID</label>
                        <input type="number" class="form-control" name="user_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" required>
                            <option value="">Select Type</option>
                            <option value="order_issue">Order Issue</option>
                            <option value="delivery_issue">Delivery Issue</option>
                            <option value="payment_issue">Payment Issue</option>
                            <option value="quality_issue">Quality Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority" required>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Create Dispute</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Dispute Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" id="status_dispute_id">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="dispute_status" required>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Notes</label>
                        <textarea class="form-control" name="admin_notes" rows="4" placeholder="Add notes about the resolution..."></textarea>
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

<!-- Add Response Modal -->
<div class="modal fade" id="addResponseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Response</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_response">
                    <input type="hidden" name="dispute_id" id="response_dispute_id">
                    <div class="mb-3">
                        <label class="form-label">Response</label>
                        <textarea class="form-control" name="response" rows="4" required placeholder="Enter your response to the customer..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Add Response</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Dispute Modal -->
<div class="modal fade" id="viewDisputeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dispute Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="disputeDetails">
                <!-- Dispute details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewDispute(dispute) {
    // Load dispute responses via AJAX
    fetch(`get_dispute_responses.php?dispute_id=${dispute.id}`)
        .then(response => response.json())
        .then(responses => {
            let responsesHtml = '';
            if (responses.length > 0) {
                responsesHtml = `
                    <hr>
                    <h6>Conversation History</h6>
                    <div class="chat-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; background-color: #f8f9fa;">
                `;
                
                responses.forEach(response => {
                    const isAdmin = response.is_admin == 1;
                    const alignClass = isAdmin ? 'text-end' : 'text-start';
                    const bgClass = isAdmin ? 'bg-primary text-white' : 'bg-light';
                    const roleLabel = isAdmin ? 'Admin' : 'User';
                    
                    responsesHtml += `
                        <div class="mb-3 ${alignClass}">
                            <div class="d-inline-block ${bgClass} p-2 rounded" style="max-width: 70%;">
                                <div class="fw-bold small">${response.user_name} (${roleLabel})</div>
                                <div>${response.response}</div>
                                <div class="small text-muted mt-1">${new Date(response.created_at).toLocaleString()}</div>
                            </div>
                        </div>
                    `;
                });
                
                responsesHtml += '</div>';
            }
            
            const details = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Dispute Information</h6>
                        <p><strong>ID:</strong> ${dispute.id}</p>
                        <p><strong>Type:</strong> <span class="badge bg-info">${dispute.type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></p>
                        <p><strong>Priority:</strong> <span class="badge bg-${getPriorityColor(dispute.priority)}">${dispute.priority.charAt(0).toUpperCase() + dispute.priority.slice(1)}</span></p>
                        <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(dispute.status)}">${dispute.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></p>
                        <p><strong>Created:</strong> ${new Date(dispute.created_at).toLocaleString()}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Customer & Order</h6>
                        <p><strong>Customer:</strong> ${dispute.customer_name || 'Unknown'}</p>
                        <p><strong>Email:</strong> ${dispute.customer_email || 'N/A'}</p>
                        <p><strong>Order:</strong> ${dispute.order_number || 'N/A'}</p>
                        <p><strong>Shop:</strong> ${dispute.shop_name || 'N/A'}</p>
                        ${dispute.resolved_by_name ? `<p><strong>Resolved By:</strong> ${dispute.resolved_by_name}</p>` : ''}
                    </div>
                </div>
                <hr>
                <h6>Description</h6>
                <p>${dispute.description}</p>
                ${dispute.admin_notes ? `<hr><h6>Admin Notes</h6><p>${dispute.admin_notes}</p>` : ''}
                ${responsesHtml}
            `;
            
            document.getElementById('disputeDetails').innerHTML = details;
            new bootstrap.Modal(document.getElementById('viewDisputeModal')).show();
        })
        .catch(error => {
            console.error('Error loading responses:', error);
            // Fallback to basic view without responses
            const details = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Dispute Information</h6>
                        <p><strong>ID:</strong> ${dispute.id}</p>
                        <p><strong>Type:</strong> <span class="badge bg-info">${dispute.type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></p>
                        <p><strong>Priority:</strong> <span class="badge bg-${getPriorityColor(dispute.priority)}">${dispute.priority.charAt(0).toUpperCase() + dispute.priority.slice(1)}</span></p>
                        <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(dispute.status)}">${dispute.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></p>
                        <p><strong>Created:</strong> ${new Date(dispute.created_at).toLocaleString()}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Customer & Order</h6>
                        <p><strong>Customer:</strong> ${dispute.customer_name || 'Unknown'}</p>
                        <p><strong>Email:</strong> ${dispute.customer_email || 'N/A'}</p>
                        <p><strong>Order:</strong> ${dispute.order_number || 'N/A'}</p>
                        <p><strong>Shop:</strong> ${dispute.shop_name || 'N/A'}</p>
                        ${dispute.resolved_by_name ? `<p><strong>Resolved By:</strong> ${dispute.resolved_by_name}</p>` : ''}
                    </div>
                </div>
                <hr>
                <h6>Description</h6>
                <p>${dispute.description}</p>
                ${dispute.admin_notes ? `<hr><h6>Admin Notes</h6><p>${dispute.admin_notes}</p>` : ''}
                <hr>
                <p class="text-muted">Unable to load conversation history.</p>
            `;
            
            document.getElementById('disputeDetails').innerHTML = details;
            new bootstrap.Modal(document.getElementById('viewDisputeModal')).show();
        });
}

function getPriorityColor(priority) {
    const colors = {
        'urgent': 'danger',
        'high': 'warning',
        'medium': 'primary',
        'low': 'secondary'
    };
    return colors[priority] || 'secondary';
}

function getStatusColor(status) {
    const colors = {
        'open': 'danger',
        'in_progress': 'warning',
        'resolved': 'success',
        'closed': 'secondary'
    };
    return colors[status] || 'secondary';
}

function updateDisputeStatus(id, currentStatus) {
    document.getElementById('status_dispute_id').value = id;
    document.getElementById('dispute_status').value = currentStatus;
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

function addResponse(id) {
    document.getElementById('response_dispute_id').value = id;
    new bootstrap.Modal(document.getElementById('addResponseModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
