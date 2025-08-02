<?php
require_once '../config.php';
$page_title = 'Place Custom Order';
$current_page = 'custom-order';

// Check if user is customer
if (getUserRole() !== 'user') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;

// Get vendor details if specified
$vendor = null;
if ($vendor_id) {
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ? AND status = 'active'");
    $stmt->execute([$vendor_id]);
    $vendor = $stmt->fetch();
    
    if (!$vendor) {
        redirect('browse.php');
    }
}

// Get all active vendors that accept custom orders
$stmt = $pdo->query("
    SELECT v.*, u.full_name 
    FROM vendors v 
    JOIN users u ON v.user_id = u.id 
    WHERE v.status = 'active' AND v.accepts_custom_orders = 1
    ORDER BY v.shop_name ASC
");
$vendors = $stmt->fetchAll();

// Get user addresses
$address_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$address_stmt->execute([$user_id]);
$addresses = $address_stmt->fetchAll();

// Handle order submission
if ($_POST && isset($_POST['place_custom_order'])) {
    $selected_vendor_id = (int)$_POST['vendor_id'];
    $order_description = sanitize($_POST['order_description']);
    $delivery_address = sanitize($_POST['delivery_address']);
    $delivery_map_link = sanitize($_POST['delivery_map_link'] ?? '');
    $special_instructions = sanitize($_POST['special_instructions'] ?? '');
    
    // Handle image upload
    $order_image = null;
    if (isset($_FILES['order_image']) && $_FILES['order_image']['error'] === UPLOAD_ERR_OK) {
        $order_image = uploadFile($_FILES['order_image'], 'orders');
    }
    
    if (empty($selected_vendor_id) || empty($order_description) || empty($delivery_address)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $order_number = 'CUSTOM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Get vendor's pickup address
            $vendor_stmt = $pdo->prepare("SELECT address, map_link FROM vendors WHERE id = ?");
            $vendor_stmt->execute([$selected_vendor_id]);
            $vendor_info = $vendor_stmt->fetch();
            
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    order_number, user_id, vendor_id, total_amount, delivery_address, 
                    delivery_map_link, pickup_address, pickup_map_link, order_type, 
                    order_description, order_image, special_instructions, status, created_at
                ) VALUES (?, ?, ?, 0, ?, ?, ?, ?, 'custom', ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $order_number,
                $user_id,
                $selected_vendor_id,
                $delivery_address,
                $delivery_map_link,
                $vendor_info['address'],
                $vendor_info['map_link'] ?? '',
                $order_description,
                $order_image,
                $special_instructions
            ]);
            
            $success = "Custom order placed successfully! The vendor will review and provide pricing.";
            
            // Clear form
            $_POST = [];
            
        } catch (PDOException $e) {
            $error = "Error placing order: " . $e->getMessage();
        }
    }
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
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="browse.php">Browse Shops</a></li>
                <li class="breadcrumb-item active">Custom Order</li>
            </ol>
        </nav>
        <h4>Place Custom Order</h4>
        <p class="text-muted">Describe what you need and let local shops provide quotes</p>
    </div>
</div>

<form method="POST" enctype="multipart/form-data">
    <div class="row g-4">
        <!-- Order Details -->
        <div class="col-lg-8">
            <!-- Vendor Selection -->
            <div class="dashboard-card mb-4">
                <h5 class="mb-4 d-flex align-items-center">
                    <i class="fas fa-store me-2 text-primary"></i>Select Shop
                </h5>
                
                <?php if ($vendor): ?>
                    <div class="selected-vendor p-3 border rounded-3 bg-light">
                        <div class="d-flex align-items-center">
                            <div class="vendor-avatar me-3">
                                <i class="fas fa-store"></i>
                            </div>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($vendor['shop_name']); ?></h6>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($vendor['category']); ?> • <?php echo htmlspecialchars($vendor['city']); ?></p>
                            </div>
                        </div>
                        <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($vendors as $v): ?>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vendor_id" id="vendor_<?php echo $v['id']; ?>" value="<?php echo $v['id']; ?>" required>
                                <label class="form-check-label w-100" for="vendor_<?php echo $v['id']; ?>">
                                    <div class="border rounded p-3 vendor-option">
                                        <div class="d-flex align-items-center">
                                            <div class="vendor-avatar me-3">
                                                <i class="fas fa-store"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($v['shop_name']); ?></h6>
                                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($v['category']); ?> • <?php echo htmlspecialchars($v['city']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Description -->
            <div class="dashboard-card mb-4">
                <h5 class="mb-4 d-flex align-items-center">
                    <i class="fas fa-clipboard-list me-2 text-primary"></i>What do you need?
                </h5>
                
                <div class="mb-3">
                    <label class="form-label">Order Description *</label>
                    <textarea class="form-control" name="order_description" rows="5" 
                              placeholder="Describe what you need in detail. Include quantities, specifications, preferences, etc."
                              required><?php echo htmlspecialchars($_POST['order_description'] ?? ''); ?></textarea>
                    <div class="form-text">Be as specific as possible to get accurate quotes</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Upload Image (Optional)</label>
                    <input type="file" class="form-control" name="order_image" accept="image/*">
                    <div class="form-text">Upload a photo to help explain what you need</div>
                </div>
            </div>
            
            <!-- Delivery Address -->
            <div class="dashboard-card mb-4">
                <h5 class="mb-4 d-flex align-items-center">
                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>Delivery Address
                </h5>
                
                <?php if (!empty($addresses)): ?>
                    <div class="row g-3 mb-3">
                        <?php foreach ($addresses as $address): ?>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_address" 
                                       id="address_<?php echo $address['id']; ?>" 
                                       value="<?php echo htmlspecialchars($address['street_address'] . ', ' . $address['city'] . ', ' . $address['state'] . ' ' . $address['zip_code']); ?>"
                                       <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                <label class="form-check-label w-100" for="address_<?php echo $address['id']; ?>">
                                    <div class="border rounded p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-<?php echo $address['address_type'] == 'home' ? 'home' : ($address['address_type'] == 'work' ? 'briefcase' : 'map-marker-alt'); ?> me-2"></i>
                                            <strong><?php echo ucfirst($address['address_type']); ?></strong>
                                            <?php if ($address['is_default']): ?>
                                            <span class="badge bg-primary ms-2">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-0 text-muted small">
                                            <?php echo htmlspecialchars($address['street_address']); ?><br>
                                            <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['zip_code']); ?>
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Delivery Address *</label>
                        <textarea class="form-control" name="delivery_address" rows="2" 
                                  placeholder="Enter your complete delivery address" required><?php echo htmlspecialchars($_POST['delivery_address'] ?? ''); ?></textarea>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">Map Link (Optional)</label>
                    <input type="url" class="form-control" name="delivery_map_link" 
                           placeholder="https://maps.google.com/..." 
                           value="<?php echo htmlspecialchars($_POST['delivery_map_link'] ?? ''); ?>">
                    <div class="form-text">Share your location link to help delivery person find you easily</div>
                </div>
            </div>
            
            <!-- Special Instructions -->
            <div class="dashboard-card">
                <h5 class="mb-3 d-flex align-items-center">
                    <i class="fas fa-sticky-note me-2 text-primary"></i>Special Instructions
                </h5>
                <textarea class="form-control" name="special_instructions" rows="3" 
                          placeholder="Any special delivery instructions, time preferences, or additional notes..."><?php echo htmlspecialchars($_POST['special_instructions'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="dashboard-card sticky-top" style="top: 20px;">
                <h5 class="mb-4 d-flex align-items-center">
                    <i class="fas fa-info-circle me-2 text-primary"></i>How it Works
                </h5>
                
                <div class="process-steps">
                    <div class="step-item d-flex align-items-start mb-3">
                        <div class="step-number me-3">1</div>
                        <div>
                            <h6 class="mb-1">Describe Your Need</h6>
                            <p class="text-muted small mb-0">Tell us exactly what you want to order</p>
                        </div>
                    </div>
                    
                    <div class="step-item d-flex align-items-start mb-3">
                        <div class="step-number me-3">2</div>
                        <div>
                            <h6 class="mb-1">Shop Reviews</h6>
                            <p class="text-muted small mb-0">The shop will review and provide pricing</p>
                        </div>
                    </div>
                    
                    <div class="step-item d-flex align-items-start mb-3">
                        <div class="step-number me-3">3</div>
                        <div>
                            <h6 class="mb-1">Admin & Delivery Approval</h6>
                            <p class="text-muted small mb-0">Both admin and delivery person will approve</p>
                        </div>
                    </div>
                    
                    <div class="step-item d-flex align-items-start mb-4">
                        <div class="step-number me-3">4</div>
                        <div>
                            <h6 class="mb-1">Order Fulfilled</h6>
                            <p class="text-muted small mb-0">Your order will be prepared and delivered</p>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info border-0 mb-4">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-lightbulb text-info me-2 mt-1"></i>
                        <div>
                            <strong>Pro Tip:</strong>
                            <p class="mb-0 small">Include as much detail as possible in your description to get the most accurate pricing and faster approval.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Place Order Button -->
                <div class="d-grid">
                    <button type="submit" name="place_custom_order" class="btn btn-primary-custom btn-lg py-3">
                        <i class="fas fa-paper-plane me-2"></i>Submit Custom Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.vendor-avatar {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.vendor-option {
    transition: all 0.3s ease;
}

.form-check-input:checked + .form-check-label .vendor-option {
    border-color: var(--primary-color) !important;
    background-color: rgba(var(--primary-color-rgb), 0.1);
}

.step-number {
    width: 30px;
    height: 30px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
}

.selected-vendor {
    border: 2px solid var(--primary-color) !important;
}
</style>

<?php require_once '../includes/footer.php'; ?>
