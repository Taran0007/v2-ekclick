<?php
require_once '../config.php';
$page_title = 'Vendor Management';
$current_page = 'vendors';

// Check if user is admin
if (getUserRole() !== 'admin') {
    redirect('login.php');
}

// Handle vendor actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_vendor':
                // Sanitize user input
                $full_name = sanitize($_POST['full_name']);
                $username = sanitize($_POST['username']);
                $email = sanitize($_POST['email']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $phone = sanitize($_POST['phone']);
                $address = sanitize($_POST['address']);
                $city = sanitize($_POST['city']);
                
                // Sanitize vendor input
                $shop_name = sanitize($_POST['shop_name']);
                $owner_name = sanitize($_POST['owner_name']);
                $category = sanitize($_POST['category']);
                $cuisine_type = sanitize($_POST['cuisine_type']);
                $business_type = sanitize($_POST['business_type']);
                $shop_description = sanitize($_POST['shop_description']);
                $delivery_fee = floatval($_POST['delivery_fee']);
                $min_order_amount = floatval($_POST['min_order_amount']);
                $delivery_radius = floatval($_POST['delivery_radius']);
                $estimated_delivery_time = intval($_POST['estimated_delivery_time']);
                $accepts_custom_orders = isset($_POST['accepts_custom_orders']) ? 1 : 0;
                
                try {
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Create user first
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, phone, role, address, city) VALUES (?, ?, ?, ?, ?, 'vendor', ?, ?)");
                    $stmt->execute([$full_name, $username, $email, $password, $phone, $address, $city]);
                    $user_id = $pdo->lastInsertId();
                    
                    // Create vendor record
                    $stmt = $pdo->prepare("INSERT INTO vendors (user_id, shop_name, owner_name, email, phone, category, cuisine_type, business_type, shop_description, address, city, delivery_fee, min_order_amount, delivery_radius, estimated_delivery_time, accepts_custom_orders, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([$user_id, $shop_name, $owner_name, $email, $phone, $category, $cuisine_type, $business_type, $shop_description, $address, $city, $delivery_fee, $min_order_amount, $delivery_radius, $estimated_delivery_time, $accepts_custom_orders]);
                    
                    // Commit transaction
                    $pdo->commit();
                    $success = "Vendor created successfully! They can now login with username: $username";
                } catch (PDOException $e) {
                    // Rollback transaction on error
                    $pdo->rollback();
                    $error = "Error creating vendor: " . $e->getMessage();
                }
                break;
                
            case 'approve':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("UPDATE vendors SET status = 'active' WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Vendor approved successfully!";
                } catch (PDOException $e) {
                    $error = "Error approving vendor: " . $e->getMessage();
                }
                break;
                
            case 'reject':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("UPDATE vendors SET status = 'rejected' WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Vendor rejected successfully!";
                } catch (PDOException $e) {
                    $error = "Error rejecting vendor: " . $e->getMessage();
                }
                break;
                
            case 'suspend':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("UPDATE vendors SET status = 'suspended' WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Vendor suspended successfully!";
                } catch (PDOException $e) {
                    $error = "Error suspending vendor: " . $e->getMessage();
                }
                break;
                
            case 'activate':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("UPDATE vendors SET status = 'active' WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Vendor activated successfully!";
                } catch (PDOException $e) {
                    $error = "Error activating vendor: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Vendor deleted successfully!";
                } catch (PDOException $e) {
                    $error = "Error deleting vendor: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get vendors with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(v.shop_name LIKE ? OR v.owner_name LIKE ? OR v.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "v.status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM vendors v $where_clause");
$count_stmt->execute($params);
$total_vendors = $count_stmt->fetchColumn();
$total_pages = ceil($total_vendors / $limit);

// Get vendors with user info
$stmt = $pdo->prepare("
    SELECT v.*, u.full_name, u.email as user_email, u.phone 
    FROM vendors v 
    LEFT JOIN users u ON v.user_id = u.id 
    $where_clause 
    ORDER BY v.created_at DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$vendors = $stmt->fetchAll();

// Get statistics
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM vendors
");
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
        <h4>Vendor Management</h4>
        <p class="text-muted">Manage all vendors and their shops</p>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVendorModal">
            <i class="fas fa-plus"></i> Add New Vendor
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                <p class="text-muted mb-0">Total Vendors</p>
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
                <p class="text-muted mb-0">Pending Approval</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                <p class="text-muted mb-0">Active Vendors</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="dashboard-card">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats['suspended']); ?></div>
                <p class="text-muted mb-0">Suspended</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-card">
    <form method="GET" class="row g-3">
        <div class="col-md-4">
            <input type="text" class="form-control" name="search" placeholder="Search vendors..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
        <div class="col-md-3">
            <a href="vendors.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>
</div>

<!-- Vendors Table -->
<div class="dashboard-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Shop Name</th>
                    <th>Owner</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendors as $vendor): ?>
                <tr>
                    <td><?php echo $vendor['id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($vendor['shop_name']); ?></strong>
                        <br><small class="text-muted"><?php echo htmlspecialchars($vendor['cuisine_type'] ?? 'N/A'); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($vendor['owner_name'] ?? $vendor['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($vendor['email'] ?? $vendor['user_email']); ?></td>
                    <td><?php echo htmlspecialchars($vendor['phone'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($vendor['address'] ?? 'N/A'); ?></td>
                    <td>
                        <?php
                        $status_colors = [
                            'pending' => 'warning',
                            'active' => 'success',
                            'suspended' => 'danger',
                            'rejected' => 'secondary'
                        ];
                        $color = $status_colors[$vendor['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?> badge-status">
                            <?php echo ucfirst($vendor['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($vendor['created_at'])); ?></td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-info" onclick="viewVendor(<?php echo htmlspecialchars(json_encode($vendor)); ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            
                            <?php if ($vendor['status'] == 'pending'): ?>
                                <button class="btn btn-sm btn-outline-success" onclick="approveVendor(<?php echo $vendor['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="rejectVendor(<?php echo $vendor['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php elseif ($vendor['status'] == 'active'): ?>
                                <button class="btn btn-sm btn-outline-warning" onclick="suspendVendor(<?php echo $vendor['id']; ?>)">
                                    <i class="fas fa-ban"></i>
                                </button>
                            <?php elseif ($vendor['status'] == 'suspended'): ?>
                                <button class="btn btn-sm btn-outline-success" onclick="activateVendor(<?php echo $vendor['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteVendor(<?php echo $vendor['id']; ?>)">
                                <i class="fas fa-trash"></i>
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
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- View Vendor Modal -->
<div class="modal fade" id="viewVendorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vendor Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="vendorDetails">
                <!-- Vendor details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add New Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_vendor">
                    
                    <!-- User Account Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3"><i class="fas fa-user"></i> User Account Information</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" required>
                                <small class="text-muted">This will be used for login</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="phone" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="address" rows="2" required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Shop Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3"><i class="fas fa-store"></i> Shop Information</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Shop Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="shop_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Owner Name</label>
                                <input type="text" class="form-control" name="owner_name">
                                <small class="text-muted">Leave blank to use full name</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Food">Food & Restaurant</option>
                                    <option value="Grocery">Grocery</option>
                                    <option value="Books">Books & Stationery</option>
                                    <option value="Electronics">Electronics</option>
                                    <option value="Clothing">Clothing</option>
                                    <option value="Pharmacy">Pharmacy</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Cuisine Type</label>
                                <input type="text" class="form-control" name="cuisine_type" placeholder="e.g., Fast Food, Italian, Indian">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Business Type</label>
                                <select class="form-select" name="business_type">
                                    <option value="">Select Type</option>
                                    <option value="Restaurant">Restaurant</option>
                                    <option value="Grocery Store">Grocery Store</option>
                                    <option value="Book Store">Book Store</option>
                                    <option value="Electronics Store">Electronics Store</option>
                                    <option value="Clothing Store">Clothing Store</option>
                                    <option value="Pharmacy">Pharmacy</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Shop Description</label>
                                <textarea class="form-control" name="shop_description" rows="3" placeholder="Brief description of your shop and services"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Business Settings -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3"><i class="fas fa-cog"></i> Business Settings</h6>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Delivery Fee ($)</label>
                                <input type="number" class="form-control" name="delivery_fee" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Min Order Amount ($)</label>
                                <input type="number" class="form-control" name="min_order_amount" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Delivery Radius (miles)</label>
                                <input type="number" class="form-control" name="delivery_radius" step="0.1" min="0" value="5.0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Est. Delivery Time (min)</label>
                                <input type="number" class="form-control" name="estimated_delivery_time" min="1" value="30">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="accepts_custom_orders" id="accepts_custom_orders" checked>
                                <label class="form-check-label" for="accepts_custom_orders">
                                    Accept Custom Orders
                                </label>
                                <small class="text-muted d-block">Allow customers to place custom orders</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Action Confirmation Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="actionModalBody">
                <!-- Action confirmation message -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;" id="actionForm">
                    <input type="hidden" name="action" id="action_type">
                    <input type="hidden" name="id" id="action_id">
                    <button type="submit" class="btn" id="actionButton">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function viewVendor(vendor) {
    const details = `
        <div class="row">
            <div class="col-md-6">
                <h6>Shop Information</h6>
                <p><strong>Shop Name:</strong> ${vendor.shop_name}</p>
                <p><strong>Cuisine Type:</strong> ${vendor.cuisine_type || 'N/A'}</p>
                <p><strong>Description:</strong> ${vendor.description || 'N/A'}</p>
                <p><strong>Address:</strong> ${vendor.address || 'N/A'}</p>
            </div>
            <div class="col-md-6">
                <h6>Owner Information</h6>
                <p><strong>Owner Name:</strong> ${vendor.owner_name || vendor.full_name}</p>
                <p><strong>Email:</strong> ${vendor.email || vendor.user_email}</p>
                <p><strong>Phone:</strong> ${vendor.phone || 'N/A'}</p>
                <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(vendor.status)}">${vendor.status.charAt(0).toUpperCase() + vendor.status.slice(1)}</span></p>
                <p><strong>Created:</strong> ${new Date(vendor.created_at).toLocaleDateString()}</p>
            </div>
        </div>
    `;
    
    document.getElementById('vendorDetails').innerHTML = details;
    new bootstrap.Modal(document.getElementById('viewVendorModal')).show();
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'active': 'success',
        'suspended': 'danger',
        'rejected': 'secondary'
    };
    return colors[status] || 'secondary';
}

function approveVendor(id) {
    showActionModal('approve', id, 'Approve Vendor', 'Are you sure you want to approve this vendor?', 'btn-success');
}

function rejectVendor(id) {
    showActionModal('reject', id, 'Reject Vendor', 'Are you sure you want to reject this vendor?', 'btn-danger');
}

function suspendVendor(id) {
    showActionModal('suspend', id, 'Suspend Vendor', 'Are you sure you want to suspend this vendor?', 'btn-warning');
}

function activateVendor(id) {
    showActionModal('activate', id, 'Activate Vendor', 'Are you sure you want to activate this vendor?', 'btn-success');
}

function deleteVendor(id) {
    showActionModal('delete', id, 'Delete Vendor', 'Are you sure you want to delete this vendor? This action cannot be undone.', 'btn-danger');
}

function showActionModal(action, id, title, message, buttonClass) {
    document.getElementById('actionModalTitle').textContent = title;
    document.getElementById('actionModalBody').textContent = message;
    document.getElementById('action_type').value = action;
    document.getElementById('action_id').value = id;
    
    const button = document.getElementById('actionButton');
    button.className = 'btn ' + buttonClass;
    button.textContent = 'Confirm';
    
    new bootstrap.Modal(document.getElementById('actionModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
