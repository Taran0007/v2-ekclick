<?php
require_once '../config.php';
$page_title = 'Shop Settings';
$current_page = 'shop-settings';

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
    // Create vendor record if it doesn't exist
    $stmt = $pdo->prepare("INSERT INTO vendors (user_id, shop_name, status) VALUES (?, 'My Shop', 'pending')");
    $stmt->execute([$vendor_id]);
    $vendor_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
    $stmt->execute([$vendor_id]);
    $vendor = $stmt->fetch();
} else {
    $vendor_id = $vendor['id'];
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_basic':
                $shop_name = sanitize($_POST['shop_name']);
                $owner_name = sanitize($_POST['owner_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $description = sanitize($_POST['description']);
                $cuisine_type = sanitize($_POST['cuisine_type']);
                
                $logo = $vendor['logo'];
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                    $logo = uploadFile($_FILES['logo'], 'logos');
                }
                
                try {
                    $stmt = $pdo->prepare("UPDATE vendors SET shop_name = ?, owner_name = ?, email = ?, phone = ?, description = ?, cuisine_type = ?, logo = ? WHERE id = ?");
                    $stmt->execute([$shop_name, $owner_name, $email, $phone, $description, $cuisine_type, $logo, $vendor_id]);
                    $success = "Basic information updated successfully!";
                    
                    // Refresh vendor data
                    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
                    $stmt->execute([$vendor_id]);
                    $vendor = $stmt->fetch();
                } catch (PDOException $e) {
                    $error = "Error updating information: " . $e->getMessage();
                }
                break;
                
            case 'update_address':
                $address = sanitize($_POST['address']);
                $city = sanitize($_POST['city']);
                $state = sanitize($_POST['state']);
                $zip_code = sanitize($_POST['zip_code']);
                $latitude = (float)$_POST['latitude'];
                $longitude = (float)$_POST['longitude'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE vendors SET address = ?, city = ?, state = ?, zip_code = ?, latitude = ?, longitude = ? WHERE id = ?");
                    $stmt->execute([$address, $city, $state, $zip_code, $latitude, $longitude, $vendor_id]);
                    $success = "Address updated successfully!";
                    
                    // Refresh vendor data
                    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
                    $stmt->execute([$vendor_id]);
                    $vendor = $stmt->fetch();
                } catch (PDOException $e) {
                    $error = "Error updating address: " . $e->getMessage();
                }
                break;
                
            case 'update_hours':
                $operating_hours = [];
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                
                foreach ($days as $day) {
                    $is_open = isset($_POST[$day . '_open']);
                    $open_time = $is_open ? sanitize($_POST[$day . '_open_time']) : null;
                    $close_time = $is_open ? sanitize($_POST[$day . '_close_time']) : null;
                    
                    $operating_hours[$day] = [
                        'is_open' => $is_open,
                        'open_time' => $open_time,
                        'close_time' => $close_time
                    ];
                }
                
                try {
                    $stmt = $pdo->prepare("UPDATE vendors SET operating_hours = ? WHERE id = ?");
                    $stmt->execute([json_encode($operating_hours), $vendor_id]);
                    $success = "Operating hours updated successfully!";
                    
                    // Refresh vendor data
                    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
                    $stmt->execute([$vendor_id]);
                    $vendor = $stmt->fetch();
                } catch (PDOException $e) {
                    $error = "Error updating hours: " . $e->getMessage();
                }
                break;
                
            case 'update_delivery':
                $delivery_fee = (float)$_POST['delivery_fee'];
                $min_order_amount = (float)$_POST['min_order_amount'];
                $delivery_radius = (float)$_POST['delivery_radius'];
                $estimated_delivery_time = (int)$_POST['estimated_delivery_time'];
                $free_delivery_threshold = (float)$_POST['free_delivery_threshold'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE vendors SET delivery_fee = ?, min_order_amount = ?, delivery_radius = ?, estimated_delivery_time = ?, free_delivery_threshold = ? WHERE id = ?");
                    $stmt->execute([$delivery_fee, $min_order_amount, $delivery_radius, $estimated_delivery_time, $free_delivery_threshold, $vendor_id]);
                    $success = "Delivery settings updated successfully!";
                    
                    // Refresh vendor data
                    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
                    $stmt->execute([$vendor_id]);
                    $vendor = $stmt->fetch();
                } catch (PDOException $e) {
                    $error = "Error updating delivery settings: " . $e->getMessage();
                }
                break;
                
            case 'update_payment':
                $payment_methods = isset($_POST['payment_methods']) ? implode(',', $_POST['payment_methods']) : '';
                $tax_rate = (float)$_POST['tax_rate'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE vendors SET payment_methods = ?, tax_rate = ? WHERE id = ?");
                    $stmt->execute([$payment_methods, $tax_rate, $vendor_id]);
                    $success = "Payment settings updated successfully!";
                    
                    // Refresh vendor data
                    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
                    $stmt->execute([$vendor_id]);
                    $vendor = $stmt->fetch();
                } catch (PDOException $e) {
                    $error = "Error updating payment settings: " . $e->getMessage();
                }
                break;
        }
    }
}

// Parse operating hours
$operating_hours = json_decode($vendor['operating_hours'] ?? '{}', true);
$payment_methods = explode(',', $vendor['payment_methods'] ?? '');

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
        <h4>Shop Settings</h4>
        <p class="text-muted">Configure your restaurant settings and preferences</p>
    </div>
    <div class="col-md-6 text-end">
        <?php if ($vendor['status'] == 'pending'): ?>
        <span class="badge bg-warning fs-6">Pending Approval</span>
        <?php elseif ($vendor['status'] == 'active'): ?>
        <span class="badge bg-success fs-6">Active</span>
        <?php elseif ($vendor['status'] == 'suspended'): ?>
        <span class="badge bg-danger fs-6">Suspended</span>
        <?php endif; ?>
    </div>
</div>

<!-- Settings Tabs -->
<div class="dashboard-card">
    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                <i class="fas fa-store"></i> Basic Info
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="address-tab" data-bs-toggle="tab" data-bs-target="#address" type="button" role="tab">
                <i class="fas fa-map-marker-alt"></i> Address
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="hours-tab" data-bs-toggle="tab" data-bs-target="#hours" type="button" role="tab">
                <i class="fas fa-clock"></i> Hours
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
    </ul>
    
    <div class="tab-content" id="settingsTabContent">
        <!-- Basic Information -->
        <div class="tab-pane fade show active" id="basic" role="tabpanel">
            <form method="POST" enctype="multipart/form-data" class="mt-4">
                <input type="hidden" name="action" value="update_basic">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Shop Name *</label>
                            <input type="text" class="form-control" name="shop_name" value="<?php echo htmlspecialchars($vendor['shop_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Owner Name</label>
                            <input type="text" class="form-control" name="owner_name" value="<?php echo htmlspecialchars($vendor['owner_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($vendor['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($vendor['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Cuisine Type</label>
                            <select class="form-select" name="cuisine_type">
                                <option value="">Select Cuisine Type</option>
                                <option value="American" <?php echo $vendor['cuisine_type'] == 'American' ? 'selected' : ''; ?>>American</option>
                                <option value="Italian" <?php echo $vendor['cuisine_type'] == 'Italian' ? 'selected' : ''; ?>>Italian</option>
                                <option value="Chinese" <?php echo $vendor['cuisine_type'] == 'Chinese' ? 'selected' : ''; ?>>Chinese</option>
                                <option value="Mexican" <?php echo $vendor['cuisine_type'] == 'Mexican' ? 'selected' : ''; ?>>Mexican</option>
                                <option value="Indian" <?php echo $vendor['cuisine_type'] == 'Indian' ? 'selected' : ''; ?>>Indian</option>
                                <option value="Japanese" <?php echo $vendor['cuisine_type'] == 'Japanese' ? 'selected' : ''; ?>>Japanese</option>
                                <option value="Thai" <?php echo $vendor['cuisine_type'] == 'Thai' ? 'selected' : ''; ?>>Thai</option>
                                <option value="Mediterranean" <?php echo $vendor['cuisine_type'] == 'Mediterranean' ? 'selected' : ''; ?>>Mediterranean</option>
                                <option value="Fast Food" <?php echo $vendor['cuisine_type'] == 'Fast Food' ? 'selected' : ''; ?>>Fast Food</option>
                                <option value="Other" <?php echo $vendor['cuisine_type'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Shop Logo</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
                            <?php if ($vendor['logo']): ?>
                            <div class="mt-2">
                                <img src="../uploads/<?php echo $vendor['logo']; ?>" alt="Current Logo" style="max-width: 100px; max-height: 100px;">
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4" placeholder="Tell customers about your restaurant..."><?php echo htmlspecialchars($vendor['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-custom">Save Basic Information</button>
            </form>
        </div>
        
        <!-- Address -->
        <div class="tab-pane fade" id="address" role="tabpanel">
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="update_address">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Street Address *</label>
                            <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($vendor['address'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City *</label>
                            <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($vendor['city'] ?? ''); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">State *</label>
                                    <input type="text" class="form-control" name="state" value="<?php echo htmlspecialchars($vendor['state'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ZIP Code *</label>
                                    <input type="text" class="form-control" name="zip_code" value="<?php echo htmlspecialchars($vendor['zip_code'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Latitude</label>
                            <input type="number" step="any" class="form-control" name="latitude" value="<?php echo $vendor['latitude'] ?? ''; ?>" placeholder="e.g., 40.7128">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Longitude</label>
                            <input type="number" step="any" class="form-control" name="longitude" value="<?php echo $vendor['longitude'] ?? ''; ?>" placeholder="e.g., -74.0060">
                        </div>
                        <div class="alert alert-info">
                            <small>
                                <strong>Tip:</strong> You can find your coordinates using Google Maps. Right-click on your location and select "What's here?" to get the coordinates.
                            </small>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-custom">Save Address</button>
            </form>
        </div>
        
        <!-- Operating Hours -->
        <div class="tab-pane fade" id="hours" role="tabpanel">
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="update_hours">
                <div class="row">
                    <?php
                    $days = [
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday'
                    ];
                    ?>
                    <?php foreach ($days as $day => $label): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="<?php echo $day; ?>_open" id="<?php echo $day; ?>_open" 
                                           <?php echo ($operating_hours[$day]['is_open'] ?? false) ? 'checked' : ''; ?>
                                           onchange="toggleDayHours('<?php echo $day; ?>')">
                                    <label class="form-check-label fw-bold" for="<?php echo $day; ?>_open">
                                        <?php echo $label; ?>
                                    </label>
                                </div>
                                <div id="<?php echo $day; ?>_hours" style="display: <?php echo ($operating_hours[$day]['is_open'] ?? false) ? 'block' : 'none'; ?>;">
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label small">Open</label>
                                            <input type="time" class="form-control form-control-sm" name="<?php echo $day; ?>_open_time" 
                                                   value="<?php echo $operating_hours[$day]['open_time'] ?? '09:00'; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Close</label>
                                            <input type="time" class="form-control form-control-sm" name="<?php echo $day; ?>_close_time" 
                                                   value="<?php echo $operating_hours[$day]['close_time'] ?? '22:00'; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary-custom">Save Operating Hours</button>
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
                            <input type="number" step="0.01" class="form-control" name="delivery_fee" value="<?php echo $vendor['delivery_fee'] ?? '3.99'; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Minimum Order Amount ($)</label>
                            <input type="number" step="0.01" class="form-control" name="min_order_amount" value="<?php echo $vendor['min_order_amount'] ?? '15.00'; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Free Delivery Threshold ($)</label>
                            <input type="number" step="0.01" class="form-control" name="free_delivery_threshold" value="<?php echo $vendor['free_delivery_threshold'] ?? '25.00'; ?>">
                            <small class="text-muted">Orders above this amount get free delivery</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Delivery Radius (miles)</label>
                            <input type="number" step="0.1" class="form-control" name="delivery_radius" value="<?php echo $vendor['delivery_radius'] ?? '5.0'; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estimated Delivery Time (minutes)</label>
                            <input type="number" class="form-control" name="estimated_delivery_time" value="<?php echo $vendor['estimated_delivery_time'] ?? '30'; ?>">
                        </div>
                        <div class="alert alert-info">
                            <small>
                                <strong>Note:</strong> These settings help customers understand your delivery terms and calculate accurate delivery costs.
                            </small>
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
                            <input type="number" step="0.01" class="form-control" name="tax_rate" value="<?php echo $vendor['tax_rate'] ?? '8.25'; ?>">
                            <small class="text-muted">Local tax rate for your area</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Accepted Payment Methods</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="payment_methods[]" value="credit_card" id="credit_card" 
                                       <?php echo in_array('credit_card', $payment_methods) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="credit_card">Credit Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="payment_methods[]" value="debit_card" id="debit_card" 
                                       <?php echo in_array('debit_card', $payment_methods) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="debit_card">Debit Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="payment_methods[]" value="paypal" id="paypal" 
                                       <?php echo in_array('paypal', $payment_methods) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="paypal">PayPal</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="payment_methods[]" value="cash" id="cash" 
                                       <?php echo in_array('cash', $payment_methods) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cash">Cash on Delivery</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6>Payment Processing</h6>
                            <ul class="mb-0">
                                <li>Platform fee: 15% of each order</li>
                                <li>Payments are processed securely</li>
                                <li>Earnings are transferred weekly</li>
                                <li>Tax calculations are automatic</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-custom">Save Payment Settings</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleDayHours(day) {
    const checkbox = document.getElementById(day + '_open');
    const hoursDiv = document.getElementById(day + '_hours');
    
    if (checkbox.checked) {
        hoursDiv.style.display = 'block';
    } else {
        hoursDiv.style.display = 'none';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
