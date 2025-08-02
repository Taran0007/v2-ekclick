<?php
require_once '../config.php';
$page_title = 'Checkout';
$current_page = 'checkout';

// Check if user is customer
if (getUserRole() !== 'user') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.image, p.description, 
           v.shop_name, v.id as vendor_id, v.delivery_fee, v.min_order_amount, v.tax_rate
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN vendors v ON p.vendor_id = v.id
    WHERE c.user_id = ?
    ORDER BY v.shop_name, p.name
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Redirect if cart is empty
if (empty($cart_items)) {
    redirect('cart.php');
}

// Group items by vendor
$vendors = [];
$total_amount = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $vendor_id = $item['vendor_id'];
    if (!isset($vendors[$vendor_id])) {
        $vendors[$vendor_id] = [
            'shop_name' => $item['shop_name'],
            'delivery_fee' => $item['delivery_fee'],
            'min_order_amount' => $item['min_order_amount'],
            'tax_rate' => $item['tax_rate'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    
    $item_total = $item['price'] * $item['quantity'];
    $vendors[$vendor_id]['items'][] = $item;
    $vendors[$vendor_id]['subtotal'] += $item_total;
    $total_amount += $item_total;
    $total_items += $item['quantity'];
}

// Calculate totals for each vendor
foreach ($vendors as $vendor_id => &$vendor) {
    $vendor['tax_amount'] = $vendor['subtotal'] * ($vendor['tax_rate'] / 100);
    $vendor['delivery_fee_amount'] = $vendor['delivery_fee'];
    $vendor['total'] = $vendor['subtotal'] + $vendor['tax_amount'] + $vendor['delivery_fee_amount'];
}

$grand_total = array_sum(array_column($vendors, 'total'));

// Check minimum order requirements
$can_checkout = true;
$min_order_issues = [];
foreach ($vendors as $vendor_id => $vendor) {
    if ($vendor['subtotal'] < $vendor['min_order_amount']) {
        $can_checkout = false;
        $min_order_issues[] = $vendor['shop_name'];
    }
}

if (!$can_checkout) {
    redirect('cart.php');
}

// Get user addresses
$address_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$address_stmt->execute([$user_id]);
$addresses = $address_stmt->fetchAll();

// Handle order placement
if ($_POST && isset($_POST['place_order'])) {
    $delivery_address = sanitize($_POST['delivery_address']);
    $payment_method = sanitize($_POST['payment_method']);
    $special_instructions = sanitize($_POST['special_instructions'] ?? '');
    
    if (empty($delivery_address) || empty($payment_method)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Create orders for each vendor
            foreach ($vendors as $vendor_id => $vendor) {
                $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                
                $stmt = $pdo->prepare("
                    INSERT INTO orders (order_number, user_id, vendor_id, total_amount, delivery_address, payment_method, special_instructions, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $order_number,
                    $user_id,
                    $vendor_id,
                    $vendor['total'],
                    $delivery_address,
                    $payment_method,
                    $special_instructions
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                // Add order items
                foreach ($vendor['items'] as $item) {
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $order_id,
                        $item['product_id'],
                        $item['quantity'],
                        $item['price']
                    ]);
                }
            }
            
            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            
            // Redirect to success page
            redirect('order-success.php');
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error placing order: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

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
                <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                <li class="breadcrumb-item active">Checkout</li>
            </ol>
        </nav>
        <h4>Checkout</h4>
        <p class="text-muted">Review your order and complete your purchase</p>
    </div>
</div>

<form method="POST">
    <div class="row g-4">
        <!-- Order Details -->
        <div class="col-lg-8">
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
                    
                    <div class="text-center">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#newAddressForm">
                            <i class="fas fa-plus me-2"></i>Add New Address
                        </button>
                    </div>
                    
                    <div class="collapse mt-3" id="newAddressForm">
                        <div class="border rounded p-3">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Street Address</label>
                                    <textarea class="form-control" name="new_street_address" rows="2" placeholder="Enter full address"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="new_city">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <input type="text" class="form-control" name="new_state">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" name="new_zip_code">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Street Address *</label>
                            <textarea class="form-control" name="delivery_address" rows="2" placeholder="Enter full delivery address" required></textarea>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Payment Method -->
            <div class="dashboard-card mb-4">
                <h5 class="mb-4 d-flex align-items-center">
                    <i class="fas fa-credit-card me-2 text-primary"></i>Payment Method
                </h5>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" checked>
                            <label class="form-check-label w-100" for="cash">
                                <div class="border rounded p-3 d-flex align-items-center">
                                    <i class="fas fa-money-bill-wave fa-2x text-success me-3"></i>
                                    <div>
                                        <strong>Cash on Delivery</strong>
                                        <p class="mb-0 text-muted small">Pay when your order arrives</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="card" value="card">
                            <label class="form-check-label w-100" for="card">
                                <div class="border rounded p-3 d-flex align-items-center">
                                    <i class="fas fa-credit-card fa-2x text-primary me-3"></i>
                                    <div>
                                        <strong>Credit/Debit Card</strong>
                                        <p class="mb-0 text-muted small">Pay securely online</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Special Instructions -->
            <div class="dashboard-card">
                <h5 class="mb-3 d-flex align-items-center">
                    <i class="fas fa-sticky-note me-2 text-primary"></i>Special Instructions
                </h5>
                <textarea class="form-control" name="special_instructions" rows="3" 
                          placeholder="Any special delivery instructions or notes for the restaurant..."></textarea>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="dashboard-card sticky-top" style="top: 20px;">
                <h5 class="mb-4 d-flex align-items-center">
                    <i class="fas fa-receipt me-2 text-primary"></i>Order Summary
                </h5>
                
                <!-- Order Items -->
                <?php foreach ($vendors as $vendor_id => $vendor): ?>
                <div class="vendor-section mb-4">
                    <h6 class="text-primary mb-3">
                        <i class="fas fa-store me-2"></i><?php echo htmlspecialchars($vendor['shop_name']); ?>
                    </h6>
                    
                    <?php foreach ($vendor['items'] as $item): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="flex-grow-1">
                            <span class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></span>
                            <small class="text-muted d-block">Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></small>
                        </div>
                        <span class="fw-bold">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="border-top pt-2 mt-2">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Subtotal:</small>
                            <small>$<?php echo number_format($vendor['subtotal'], 2); ?></small>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Tax:</small>
                            <small>$<?php echo number_format($vendor['tax_amount'], 2); ?></small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">Delivery:</small>
                            <small>$<?php echo number_format($vendor['delivery_fee_amount'], 2); ?></small>
                        </div>
                        <div class="d-flex justify-content-between fw-bold text-primary">
                            <span>Vendor Total:</span>
                            <span>$<?php echo number_format($vendor['total'], 2); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <hr class="my-3">
                
                <!-- Grand Total -->
                <div class="d-flex justify-content-between mb-4">
                    <strong class="fs-5">Grand Total:</strong>
                    <strong class="text-primary fs-4">$<?php echo number_format($grand_total, 2); ?></strong>
                </div>
                
                <!-- Place Order Button -->
                <div class="d-grid">
                    <button type="submit" name="place_order" class="btn btn-primary-custom btn-lg py-3">
                        <i class="fas fa-check-circle me-2"></i>Place Order
                    </button>
                </div>
                
                <!-- Estimated Delivery -->
                <div class="text-center mt-4 p-3 bg-light rounded-3">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas fa-clock text-primary me-2"></i>
                        <span class="fw-medium">Estimated Delivery</span>
                    </div>
                    <span class="text-muted">45-60 minutes</span>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.vendor-section {
    border-left: 3px solid var(--primary-color);
    padding-left: 1rem;
}

.form-check-input:checked + .form-check-label .border {
    border-color: var(--primary-color) !important;
    background-color: rgba(var(--primary-color-rgb), 0.1);
}
</style>

<?php require_once '../includes/footer.php'; ?>
