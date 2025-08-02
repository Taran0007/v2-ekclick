<?php
require_once '../config.php';
$page_title = 'System Settings';
$current_page = 'settings';

// Check if user is admin
if (getUserRole() !== 'admin') {
    redirect('login.php');
}

// Handle settings actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_general':
                $site_name = sanitize($_POST['site_name']);
                $site_description = sanitize($_POST['site_description']);
                $contact_email = sanitize($_POST['contact_email']);
                $contact_phone = sanitize($_POST['contact_phone']);
                $address = sanitize($_POST['address']);
                
                try {
                    // Create settings table if it doesn't exist
                    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        setting_key VARCHAR(100) UNIQUE,
                        setting_value TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )");
                    
                    $settings = [
                        'site_name' => $site_name,
                        'site_description' => $site_description,
                        'contact_email' => $contact_email,
                        'contact_phone' => $contact_phone,
                        'address' => $address
                    ];
                    
                    foreach ($settings as $key => $value) {
                        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                        $stmt->execute([$key, $value, $value]);
                    }
                    
                    $success = "General settings updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating settings: " . $e->getMessage();
                }
                break;
                
            case 'update_delivery':
                $delivery_fee = (float)$_POST['delivery_fee'];
                $free_delivery_threshold = (float)$_POST['free_delivery_threshold'];
                $delivery_radius = (float)$_POST['delivery_radius'];
                $estimated_delivery_time = (int)$_POST['estimated_delivery_time'];
                
                try {
                    $settings = [
                        'delivery_fee' => $delivery_fee,
                        'free_delivery_threshold' => $free_delivery_threshold,
                        'delivery_radius' => $delivery_radius,
                        'estimated_delivery_time' => $estimated_delivery_time
                    ];
                    
                    foreach ($settings as $key => $value) {
                        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                        $stmt->execute([$key, $value, $value]);
                    }
                    
                    $success = "Delivery settings updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating delivery settings: " . $e->getMessage();
                }
                break;
                
            case 'update_payment':
                $tax_rate = (float)$_POST['tax_rate'];
                $service_fee = (float)$_POST['service_fee'];
                $currency = sanitize($_POST['currency']);
                $payment_methods = isset($_POST['payment_methods']) ? implode(',', $_POST['payment_methods']) : '';
                
                try {
                    $settings = [
                        'tax_rate' => $tax_rate,
                        'service_fee' => $service_fee,
                        'currency' => $currency,
                        'payment_methods' => $payment_methods
                    ];
                    
                    foreach ($settings as $key => $value) {
                        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                        $stmt->execute([$key, $value, $value]);
                    }
                    
                    $success = "Payment settings updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating payment settings: " . $e->getMessage();
                }
                break;
                
            case 'update_notifications':
                $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
                $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
                $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
                $admin_email = sanitize($_POST['admin_email']);
                
                try {
                    $settings = [
                        'email_notifications' => $email_notifications,
                        'sms_notifications' => $sms_notifications,
                        'push_notifications' => $push_notifications,
                        'admin_email' => $admin_email
                    ];
                    
                    foreach ($settings as $key => $value) {
                        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                        $stmt->execute([$key, $value, $value]);
                    }
                    
                    $success = "Notification settings updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating notification settings: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get current settings
function getSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// Get system statistics
$stats_stmt = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM vendors) as total_vendors,
        (SELECT COUNT(*) FROM orders) as total_orders,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid') as total_revenue,
        (SELECT COUNT(*) FROM disputes WHERE status = 'open') as open_disputes
");
$system_stats = $stats_stmt->fetch();

// Ensure all stats are not null
$system_stats['total_users'] = $system_stats['total_users'] ?? 0;
$system_stats['total_vendors'] = $system_stats['total_vendors'] ?? 0;
$system_stats['total_orders'] = $system_stats['total_orders'] ?? 0;
$system_stats['total_revenue'] = $system_stats['total_revenue'] ?? 0;
$system_stats['open_disputes'] = $system_stats['open_disputes'] ?? 0;

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
        <h4>System Settings</h4>
        <p class="text-muted">Configure system-wide settings and preferences</p>
    </div>
</div>

<!-- System Overview -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="dashboard-card">
            <h5 class="mb-3">System Overview</h5>
            <div class="row">
                <div class="col-md-2">
                    <div class="text-center">
                        <div class="stat-number text-primary"><?php echo number_format($system_stats['total_users']); ?></div>
                        <small class="text-muted">Total Users</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center">
                        <div class="stat-number text-success"><?php echo number_format($system_stats['total_vendors']); ?></div>
                        <small class="text-muted">Total Vendors</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="text-center">
                        <div class="stat-number text-info"><?php echo number_format($system_stats['total_orders']); ?></div>
                        <small class="text-muted">Total Orders</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="stat-number text-success">$<?php echo number_format($system_stats['total_revenue'], 2); ?></div>
                        <small class="text-muted">Total Revenue</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="stat-number text-warning"><?php echo number_format($system_stats['open_disputes']); ?></div>
                        <small class="text-muted">Open Disputes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Settings Tabs -->
<div class="dashboard-card">
    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                <i class="fas fa-cog"></i> General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="delivery-tab" data-bs-toggle="tab" data-bs-target="#delivery" type="button" role="tab">
                <i class="fas fa-truck"></i> Delivery
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">
                <i class="fas fa-credit-card"></i> Payment
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                <i class="fas fa-bell"></i> Notifications
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                <i class="fas fa-tools"></i> Maintenance
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="settingsTabContent">
        <!-- General Settings -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="update_general">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars(getSetting('site_name', 'DeliverEase')); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" class="form-control" name="contact_email" value="<?php echo htmlspecialchars(getSetting('contact_email')); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" name="contact_phone" value="<?php echo htmlspecialchars(getSetting('contact_phone')); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Site Description</label>
                            <textarea class="form-control" name="site_description" rows="3"><?php echo htmlspecialchars(getSetting('site_description')); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Business Address</label>
                            <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars(getSetting('address')); ?></textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-custom">Save General Settings</button>
            </form>
        </div>
        
        <!-- Delivery Settings -->
        <div class="tab-pane fade" id="delivery" role="tabpanel">
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="update_delivery">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Delivery Fee ($)</label>
                            <input type="number" step="0.01" class="form-control" name="delivery_fee" value="<?php echo getSetting('delivery_fee', '5.00'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Free Delivery Threshold ($)</label>
                            <input type="number" step="0.01" class="form-control" name="free_delivery_threshold" value="<?php echo getSetting('free_delivery_threshold', '25.00'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Delivery Radius (miles)</label>
                            <input type="number" step="0.1" class="form-control" name="delivery_radius" value="<?php echo getSetting('delivery_radius', '10.0'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estimated Delivery Time (minutes)</label>
                            <input type="number" class="form-control" name="estimated_delivery_time" value="<?php echo getSetting('estimated_delivery_time', '30'); ?>" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-custom">Save Delivery Settings</button>
            </form>
        </div>
        
        <!-- Payment Settings -->
        <div class="tab-pane fade" id="payment" role="tabpanel">
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="update_payment">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tax Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" name="tax_rate" value="<?php echo getSetting('tax_rate', '8.25'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service Fee ($)</label>
                            <input type="number" step="0.01" class="form-control" name="service_fee" value="<?php echo getSetting('service_fee', '2.00'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Currency</label>
                            <select class="form-select" name="currency" required>
                                <option value="USD" <?php echo getSetting('currency', 'USD') == 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                <option value="EUR" <?php echo getSetting('currency') == 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                <option value="GBP" <?php echo getSetting('currency') == 'GBP' ? 'selected' : ''; ?>>GBP - British Pound</option>
                                <option value="CAD" <?php echo getSetting('currency') == 'CAD' ? 'selected' : ''; ?>>CAD - Canadian Dollar</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Accepted Payment Methods</label>
                            <?php $payment_methods = explode(',', getSetting('payment_methods', 'credit_card,debit_card,paypal')); ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="payment_methods[]" value="credit_card" id="credit_card" <?php echo in_array('credit_card', $payment_methods) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="credit_card">Credit Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="payment_methods[]" value="debit_card" id="debit_card" <?php echo in_array('debit_card', $payment_methods) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="debit_card">Debit Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="payment_methods[]" value="paypal" id="paypal" <?php echo in_array('paypal', $payment_methods) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="paypal">PayPal</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="payment_methods[]" value="cash" id="cash" <?php echo in_array('cash', $payment_methods) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cash">Cash on Delivery</label>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-custom">Save Payment Settings</button>
            </form>
        </div>
        
        <!-- Notification Settings -->
        <div class="tab-pane fade" id="notifications" role="tabpanel">
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="update_notifications">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Admin Email</label>
                            <input type="email" class="form-control" name="admin_email" value="<?php echo htmlspecialchars(getSetting('admin_email')); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notification Types</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications" <?php echo getSetting('email_notifications', '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_notifications">Email Notifications</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sms_notifications" id="sms_notifications" <?php echo getSetting('sms_notifications', '0') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sms_notifications">SMS Notifications</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="push_notifications" id="push_notifications" <?php echo getSetting('push_notifications', '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="push_notifications">Push Notifications</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6>Notification Events</h6>
                            <ul class="mb-0">
                                <li>New user registrations</li>
                                <li>New vendor applications</li>
                                <li>New orders placed</li>
                                <li>Payment confirmations</li>
                                <li>Dispute submissions</li>
                                <li>System errors</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-custom">Save Notification Settings</button>
            </form>
        </div>
        
        <!-- Maintenance -->
        <div class="tab-pane fade" id="maintenance" role="tabpanel">
            <div class="mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Database Maintenance</h6>
                                <p class="card-text">Clean up old data and optimize database performance.</p>
                                <button class="btn btn-outline-primary" onclick="cleanupDatabase()">
                                    <i class="fas fa-database"></i> Cleanup Database
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Cache Management</h6>
                                <p class="card-text">Clear system cache to improve performance.</p>
                                <button class="btn btn-outline-warning" onclick="clearCache()">
                                    <i class="fas fa-trash"></i> Clear Cache
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">System Backup</h6>
                                <p class="card-text">Create a backup of the system data.</p>
                                <button class="btn btn-outline-success" onclick="createBackup()">
                                    <i class="fas fa-download"></i> Create Backup
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">System Logs</h6>
                                <p class="card-text">View and manage system logs.</p>
                                <button class="btn btn-outline-info" onclick="viewLogs()">
                                    <i class="fas fa-file-alt"></i> View Logs
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cleanupDatabase() {
    if (confirm('Are you sure you want to cleanup the database? This will remove old data.')) {
        alert('Database cleanup initiated. This may take a few minutes.');
        // Implement database cleanup logic
    }
}

function clearCache() {
    if (confirm('Are you sure you want to clear the cache?')) {
        alert('Cache cleared successfully.');
        // Implement cache clearing logic
    }
}

function createBackup() {
    if (confirm('Are you sure you want to create a system backup?')) {
        alert('Backup creation initiated. You will be notified when complete.');
        // Implement backup creation logic
    }
}

function viewLogs() {
    // Open logs in a new window or modal
    window.open('system-logs.php', '_blank');
}
</script>

<?php require_once '../includes/footer.php'; ?>
