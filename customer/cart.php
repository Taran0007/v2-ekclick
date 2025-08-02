<?php
require_once '../config.php';
$page_title = 'Shopping Cart';
$current_page = 'cart';

// Check if user is customer
if (getUserRole() !== 'user') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Create cart table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        product_id INT,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id),
        UNIQUE KEY unique_user_product (user_id, product_id)
    )");
} catch (PDOException $e) {
    // Table might already exist
}

// Handle cart actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $product_id = (int)$_POST['product_id'];
                $quantity = (int)$_POST['quantity'];
                
                if ($quantity > 0) {
                    try {
                        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                        $stmt->execute([$quantity, $user_id, $product_id]);
                        $success = "Cart updated successfully!";
                    } catch (PDOException $e) {
                        $error = "Error updating cart: " . $e->getMessage();
                    }
                } else {
                    // Remove item if quantity is 0
                    try {
                        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                        $stmt->execute([$user_id, $product_id]);
                        $success = "Item removed from cart!";
                    } catch (PDOException $e) {
                        $error = "Error removing item: " . $e->getMessage();
                    }
                }
                break;
                
            case 'remove_item':
                $product_id = (int)$_POST['product_id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$user_id, $product_id]);
                    $success = "Item removed from cart!";
                } catch (PDOException $e) {
                    $error = "Error removing item: " . $e->getMessage();
                }
                break;
                
            case 'clear_cart':
                try {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $success = "Cart cleared successfully!";
                } catch (PDOException $e) {
                    $error = "Error clearing cart: " . $e->getMessage();
                }
                break;
        }
    }
}

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
    $vendor['delivery_fee_amount'] = $vendor['subtotal'] >= ($vendor['min_order_amount'] ?? 0) ? $vendor['delivery_fee'] : $vendor['delivery_fee'];
    $vendor['total'] = $vendor['subtotal'] + $vendor['tax_amount'] + $vendor['delivery_fee_amount'];
}

$grand_total = array_sum(array_column($vendors, 'total'));

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
        <h4>Shopping Cart</h4>
        <p class="text-muted"><?php echo $total_items; ?> items in your cart</p>
    </div>
    <div class="col-md-6 text-end">
        <?php if (!empty($cart_items)): ?>
        <button class="btn btn-outline-danger" onclick="clearCart()">
            <i class="fas fa-trash"></i> Clear Cart
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($cart_items)): ?>
<!-- Empty Cart -->
<div class="dashboard-card text-center">
    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
    <h5>Your cart is empty</h5>
    <p class="text-muted">Add some delicious items to get started!</p>
    <a href="browse.php" class="btn btn-primary-custom">
        <i class="fas fa-utensils me-2"></i>Browse Restaurants
    </a>
</div>
<?php else: ?>

<!-- Cart Items -->
<div class="row g-4">
    <div class="col-lg-8">
        <?php foreach ($vendors as $vendor_id => $vendor): ?>
        <div class="dashboard-card mb-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <div class="vendor-avatar me-3">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <h6 class="mb-1"><?php echo htmlspecialchars($vendor['shop_name']); ?></h6>
                        <small class="text-muted">
                            <?php if ($vendor['subtotal'] < $vendor['min_order_amount']): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Need $<?php echo number_format($vendor['min_order_amount'] - $vendor['subtotal'], 2); ?> more
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Minimum order met
                                </span>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="cart-items">
                <?php foreach ($vendor['items'] as $item): ?>
                <div class="cart-item d-flex align-items-center p-3 mb-3 rounded-3 border">
                    <div class="item-image me-3">
                        <?php if ($item['image']): ?>
                        <img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="rounded-3" style="width: 80px; height: 80px; object-fit: cover;">
                        <?php else: ?>
                        <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-utensils text-muted"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-grow-1">
                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 80)); ?><?php echo strlen($item['description'] ?? '') > 80 ? '...' : ''; ?></p>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-primary fs-6">$<?php echo number_format($item['price'], 2); ?></span>
                            <div class="quantity-controls d-flex align-items-center">
                                <button class="btn btn-outline-secondary btn-sm rounded-circle" type="button" 
                                        onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)"
                                        style="width: 32px; height: 32px;">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="mx-3 fw-medium"><?php echo $item['quantity']; ?></span>
                                <button class="btn btn-outline-secondary btn-sm rounded-circle" type="button" 
                                        onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                        style="width: 32px; height: 32px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end ms-3">
                        <div class="fw-bold mb-2 fs-6">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                        <button class="btn btn-outline-danger btn-sm rounded-circle" onclick="removeItem(<?php echo $item['product_id']; ?>)"
                                style="width: 32px; height: 32px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Vendor Totals -->
            <div class="vendor-summary mt-4 p-3 bg-light rounded-3">
                <div class="row">
                    <div class="col-md-8">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Subtotal:</small>
                            <small>$<?php echo number_format($vendor['subtotal'], 2); ?></small>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Tax (<?php echo $vendor['tax_rate']; ?>%):</small>
                            <small>$<?php echo number_format($vendor['tax_amount'], 2); ?></small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Delivery Fee:</small>
                            <small>$<?php echo number_format($vendor['delivery_fee_amount'], 2); ?></small>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="fw-bold text-primary">
                            Vendor Total: $<?php echo number_format($vendor['total'], 2); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Continue Shopping -->
        <div class="text-center mt-4">
            <a href="browse.php" class="btn btn-outline-primary btn-lg px-4">
                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
            </a>
        </div>
    </div>
    
    <!-- Order Summary -->
    <div class="col-lg-4">
        <div class="dashboard-card sticky-top shadow-sm" style="top: 20px;">
            <h5 class="mb-4 d-flex align-items-center">
                <i class="fas fa-receipt me-2 text-primary"></i>Order Summary
            </h5>
            
            <div class="summary-item d-flex justify-content-between mb-3">
                <span>Items (<?php echo $total_items; ?>)</span>
                <span class="fw-medium">$<?php echo number_format($total_amount, 2); ?></span>
            </div>
            
            <?php
            $total_tax = array_sum(array_column($vendors, 'tax_amount'));
            $total_delivery = array_sum(array_column($vendors, 'delivery_fee_amount'));
            ?>
            
            <div class="summary-item d-flex justify-content-between mb-3">
                <span>Tax</span>
                <span class="fw-medium">$<?php echo number_format($total_tax, 2); ?></span>
            </div>
            
            <div class="summary-item d-flex justify-content-between mb-3">
                <span>Delivery</span>
                <span class="fw-medium">$<?php echo number_format($total_delivery, 2); ?></span>
            </div>
            
            <hr class="my-3">
            
            <div class="d-flex justify-content-between mb-4">
                <strong class="fs-5">Total</strong>
                <strong class="text-primary fs-4">$<?php echo number_format($grand_total, 2); ?></strong>
            </div>
            
            <!-- Check minimum order requirements -->
            <?php
            $can_checkout = true;
            $min_order_issues = [];
            foreach ($vendors as $vendor_id => $vendor) {
                if ($vendor['subtotal'] < $vendor['min_order_amount']) {
                    $can_checkout = false;
                    $min_order_issues[] = $vendor['shop_name'] . ' (need $' . number_format($vendor['min_order_amount'] - $vendor['subtotal'], 2) . ' more)';
                }
            }
            ?>
            
            <?php if (!$can_checkout): ?>
            <div class="alert alert-warning border-0 mb-4">
                <div class="d-flex align-items-start">
                    <i class="fas fa-exclamation-triangle text-warning me-2 mt-1"></i>
                    <div>
                        <strong>Minimum order not met:</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            <?php foreach ($min_order_issues as $issue): ?>
                            <li><small><?php echo $issue; ?></small></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="d-grid mb-4">
                <?php if ($can_checkout): ?>
                <a href="checkout.php" class="btn btn-primary-custom btn-lg py-3">
                    <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                </a>
                <?php else: ?>
                <button class="btn btn-secondary btn-lg py-3" disabled>
                    <i class="fas fa-credit-card me-2"></i>Minimum Order Required
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Promo Code -->
            <div class="promo-section mb-4">
                <label class="form-label small fw-medium">Have a promo code?</label>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Enter code" id="promoCode">
                    <button class="btn btn-outline-primary" type="button" onclick="applyPromo()">Apply</button>
                </div>
            </div>
            
            <!-- Estimated Delivery -->
            <div class="delivery-info text-center p-3 bg-light rounded-3">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-clock text-primary me-2"></i>
                    <span class="fw-medium">Estimated Delivery</span>
                </div>
                <span class="text-muted">30-45 minutes</span>
            </div>
        </div>
    </div>
</div>

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

.cart-item {
    transition: all 0.3s ease;
    background: white;
}

.cart-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.quantity-controls button {
    transition: all 0.2s ease;
}

.quantity-controls button:hover {
    transform: scale(1.1);
}

.summary-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.summary-item:last-child {
    border-bottom: none;
}

.vendor-summary {
    border: 1px solid #e9ecef;
}

.delivery-info {
    border: 1px solid #e9ecef;
}
</style>

<!-- Clear Cart Confirmation Modal -->
<div class="modal fade" id="clearCartModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Cart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to clear your entire cart? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="btn btn-danger">Clear Cart</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
function updateQuantity(productId, quantity) {
    if (quantity < 0) return;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_quantity">
        <input type="hidden" name="product_id" value="${productId}">
        <input type="hidden" name="quantity" value="${quantity}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function removeItem(productId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="remove_item">
            <input type="hidden" name="product_id" value="${productId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function clearCart() {
    new bootstrap.Modal(document.getElementById('clearCartModal')).show();
}

function applyPromo() {
    const promoCode = document.getElementById('promoCode').value;
    if (promoCode.trim()) {
        // Implement promo code logic
        alert('Promo code functionality will be implemented soon!');
    }
}

// Auto-save cart changes
let saveTimeout;
function autoSaveCart() {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        // Auto-save logic if needed
    }, 1000);
}
</script>

<?php require_once '../includes/footer.php'; ?>
