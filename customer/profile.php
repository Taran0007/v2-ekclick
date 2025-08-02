<?php
require_once '../config.php';
$page_title = 'My Profile';
$current_page = 'profile';

// Check if user is customer
if (getUserRole() !== 'user') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle case where user is not found
if (!$user) {
    redirect('login.php');
}

// Handle profile updates
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $full_name = sanitize($_POST['full_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $date_of_birth = sanitize($_POST['date_of_birth']);
                
                try {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, date_of_birth = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $phone, $date_of_birth, $user_id]);
                    
                    // Update session
                    $_SESSION['full_name'] = $full_name;
                    
                    $success = "Profile updated successfully!";
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                } catch (PDOException $e) {
                    $error = "Error updating profile: " . $e->getMessage();
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if ($new_password !== $confirm_password) {
                    $error = "New passwords do not match!";
                } elseif (strlen($new_password) < 6) {
                    $error = "Password must be at least 6 characters long!";
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error = "Current password is incorrect!";
                } else {
                    try {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed_password, $user_id]);
                        $success = "Password changed successfully!";
                    } catch (PDOException $e) {
                        $error = "Error changing password: " . $e->getMessage();
                    }
                }
                break;
                
            case 'add_address':
                $address_type = sanitize($_POST['address_type']);
                $street_address = sanitize($_POST['street_address']);
                $city = sanitize($_POST['city']);
                $state = sanitize($_POST['state']);
                $zip_code = sanitize($_POST['zip_code']);
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                
                try {
                    // Create addresses table if it doesn't exist
                    $pdo->exec("CREATE TABLE IF NOT EXISTS user_addresses (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT,
                        address_type VARCHAR(50),
                        street_address TEXT,
                        city VARCHAR(100),
                        state VARCHAR(50),
                        zip_code VARCHAR(20),
                        is_default BOOLEAN DEFAULT FALSE,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id)
                    )");
                    
                    // If this is set as default, remove default from other addresses
                    if ($is_default) {
                        $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, address_type, street_address, city, state, zip_code, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $address_type, $street_address, $city, $state, $zip_code, $is_default]);
                    $success = "Address added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding address: " . $e->getMessage();
                }
                break;
                
            case 'delete_address':
                $address_id = (int)$_POST['address_id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
                    $stmt->execute([$address_id, $user_id]);
                    $success = "Address deleted successfully!";
                } catch (PDOException $e) {
                    $error = "Error deleting address: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get user addresses
try {
    $address_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $address_stmt->execute([$user_id]);
    $addresses = $address_stmt->fetchAll();
} catch (PDOException $e) {
    // If user_addresses table doesn't exist, create empty array
    $addresses = [];
}

// Get user statistics
try {
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_spent,
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE user_id = ?
    ");
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch();
    
    // Handle case where no stats are found
    if (!$stats) {
        $stats = [
            'total_orders' => 0,
            'total_spent' => 0,
            'completed_orders' => 0,
            'avg_order_value' => 0
        ];
    }
} catch (PDOException $e) {
    // If orders table doesn't exist or has issues, set default stats
    $stats = [
        'total_orders' => 0,
        'total_spent' => 0,
        'completed_orders' => 0,
        'avg_order_value' => 0
    ];
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
        <h4>My Profile</h4>
        <p class="text-muted">Manage your account settings and preferences</p>
    </div>
</div>

<!-- Profile Overview -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="dashboard-card text-center">
            <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2rem;">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <h5><?php echo htmlspecialchars($user['full_name'] ?? 'Unknown User'); ?></h5>
            <p class="text-muted"><?php echo htmlspecialchars($user['email'] ?? 'No email'); ?></p>
            <small class="text-muted">Member since <?php echo $user['created_at'] ? date('M Y', strtotime($user['created_at'])) : 'Unknown'; ?></small>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="dashboard-card">
            <h6 class="mb-3">Account Statistics</h6>
            <div class="row">
                <div class="col-6 col-md-3 text-center mb-3">
                    <div class="h4 text-primary"><?php echo number_format($stats['total_orders']); ?></div>
                    <small class="text-muted">Total Orders</small>
                </div>
                <div class="col-6 col-md-3 text-center mb-3">
                    <div class="h4 text-success">$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></div>
                    <small class="text-muted">Total Spent</small>
                </div>
                <div class="col-6 col-md-3 text-center mb-3">
                    <div class="h4 text-info"><?php echo number_format($stats['completed_orders'] ?? 0); ?></div>
                    <small class="text-muted">Completed</small>
                </div>
                <div class="col-6 col-md-3 text-center mb-3">
                    <div class="h4 text-warning">$<?php echo number_format($stats['avg_order_value'] ?? 0, 2); ?></div>
                    <small class="text-muted">Avg Order</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Tabs -->
<div class="dashboard-card">
    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                <i class="fas fa-user me-2"></i>Personal Info
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="addresses-tab" data-bs-toggle="tab" data-bs-target="#addresses" type="button" role="tab">
                <i class="fas fa-map-marker-alt me-2"></i>Addresses
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                <i class="fas fa-lock me-2"></i>Security
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="preferences-tab" data-bs-toggle="tab" data-bs-target="#preferences" type="button" role="tab">
                <i class="fas fa-cog me-2"></i>Preferences
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="profileTabContent">
        <!-- Personal Information -->
        <div class="tab-pane fade show active" id="personal" role="tabpanel">
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="update_profile">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-custom">Update Profile</button>
            </form>
        </div>
        
        <!-- Addresses -->
        <div class="tab-pane fade" id="addresses" role="tabpanel">
            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Saved Addresses</h6>
                    <button class="btn btn-primary-custom btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                        <i class="fas fa-plus me-2"></i>Add Address
                    </button>
                </div>
                
                <?php if (empty($addresses)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                    <h6>No addresses saved</h6>
                    <p class="text-muted">Add your delivery addresses for faster checkout</p>
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                        <i class="fas fa-plus me-2"></i>Add First Address
                    </button>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($addresses as $address): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-<?php echo $address['address_type'] == 'home' ? 'home' : ($address['address_type'] == 'work' ? 'briefcase' : 'map-marker-alt'); ?> me-2"></i>
                                        <?php echo ucfirst($address['address_type']); ?>
                                    </h6>
                                    <?php if ($address['is_default']): ?>
                                    <span class="badge bg-primary">Default</span>
                                    <?php endif; ?>
                                </div>
                                <p class="card-text small text-muted mb-2">
                                    <?php echo htmlspecialchars($address['street_address']); ?><br>
                                    <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['zip_code']); ?>
                                </p>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-primary" onclick="editAddress(<?php echo htmlspecialchars(json_encode($address)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteAddress(<?php echo $address['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Security -->
        <div class="tab-pane fade" id="security" role="tabpanel">
            <div class="mt-4">
                <h6 class="mb-3">Change Password</h6>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary-custom">Change Password</button>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6>Password Requirements:</h6>
                                <ul class="mb-0">
                                    <li>At least 6 characters long</li>
                                    <li>Mix of letters and numbers recommended</li>
                                    <li>Avoid using personal information</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <h6 class="mb-3">Account Security</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong>Two-Factor Authentication</strong>
                                <br><small class="text-muted">Add an extra layer of security</small>
                            </div>
                            <button class="btn btn-outline-primary btn-sm">Enable</button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong>Login Notifications</strong>
                                <br><small class="text-muted">Get notified of new logins</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" checked>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Preferences -->
        <div class="tab-pane fade" id="preferences" role="tabpanel">
            <div class="mt-4">
                <h6 class="mb-3">Notification Preferences</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                            <label class="form-check-label" for="emailNotifications">
                                <strong>Email Notifications</strong>
                                <br><small class="text-muted">Order updates, promotions, and news</small>
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="smsNotifications">
                            <label class="form-check-label" for="smsNotifications">
                                <strong>SMS Notifications</strong>
                                <br><small class="text-muted">Order status updates via text</small>
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="pushNotifications" checked>
                            <label class="form-check-label" for="pushNotifications">
                                <strong>Push Notifications</strong>
                                <br><small class="text-muted">Browser notifications for orders</small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="promotionalEmails" checked>
                            <label class="form-check-label" for="promotionalEmails">
                                <strong>Promotional Emails</strong>
                                <br><small class="text-muted">Deals, discounts, and special offers</small>
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="orderReminders" checked>
                            <label class="form-check-label" for="orderReminders">
                                <strong>Order Reminders</strong>
                                <br><small class="text-muted">Reminders about favorite restaurants</small>
                            </label>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h6 class="mb-3">Dietary Preferences</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="vegetarian">
                            <label class="form-check-label" for="vegetarian">Vegetarian</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="vegan">
                            <label class="form-check-label" for="vegan">Vegan</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="glutenFree">
                            <label class="form-check-label" for="glutenFree">Gluten-Free</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="dairyFree">
                            <label class="form-check-label" for="dairyFree">Dairy-Free</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="nutFree">
                            <label class="form-check-label" for="nutFree">Nut-Free</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="halal">
                            <label class="form-check-label" for="halal">Halal</label>
                        </div>
                    </div>
                </div>
                
                <button class="btn btn-primary-custom mt-3">Save Preferences</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_address">
                    <div class="mb-3">
                        <label class="form-label">Address Type</label>
                        <select class="form-select" name="address_type" required>
                            <option value="">Select Type</option>
                            <option value="home">Home</option>
                            <option value="work">Work</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Street Address</label>
                        <textarea class="form-control" name="street_address" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ZIP Code</label>
                        <input type="text" class="form-control" name="zip_code" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
                        <label class="form-check-label" for="is_default">
                            Set as default address
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Add Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Address Confirmation Modal -->
<div class="modal fade" id="deleteAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this address? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_address">
                    <input type="hidden" name="address_id" id="delete_address_id">
                    <button type="submit" class="btn btn-danger">Delete Address</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editAddress(address) {
    // Implement edit address functionality
    alert('Edit address functionality will be implemented soon!');
}

function deleteAddress(addressId) {
    document.getElementById('delete_address_id').value = addressId;
    new bootstrap.Modal(document.getElementById('deleteAddressModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
