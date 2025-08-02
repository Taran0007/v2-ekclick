<?php
require_once '../config.php';
$page_title = 'Browse Restaurants';
$current_page = 'browse';

// Check if user is customer
if (getUserRole() !== 'user') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get search parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$cuisine = isset($_GET['cuisine']) ? sanitize($_GET['cuisine']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'rating';
$min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : 0;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query conditions
$where_conditions = ["v.status = 'active'", "v.is_open = 1"];
$params = [];

if ($search) {
    $where_conditions[] = "(v.shop_name LIKE ? OR v.description LIKE ? OR v.cuisine_type LIKE ? OR v.category LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $where_conditions[] = "v.category = ?";
    $params[] = $category;
}

if ($cuisine) {
    $where_conditions[] = "v.cuisine_type = ?";
    $params[] = $cuisine;
}

if ($min_rating > 0) {
    $where_conditions[] = "v.rating >= ?";
    $params[] = $min_rating;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Determine sort order
$order_clause = "ORDER BY ";
switch ($sort) {
    case 'rating':
        $order_clause .= "v.rating DESC, v.total_reviews DESC";
        break;
    case 'delivery_time':
        $order_clause .= "v.estimated_delivery_time ASC";
        break;
    case 'delivery_fee':
        $order_clause .= "v.delivery_fee ASC";
        break;
    case 'alphabetical':
        $order_clause .= "v.shop_name ASC";
        break;
    default:
        $order_clause .= "v.rating DESC";
}

// Get total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM vendors v $where_clause");
$count_stmt->execute($params);
$total_vendors = $count_stmt->fetchColumn();
$total_pages = ceil($total_vendors / $limit);

// Get vendors
$stmt = $pdo->prepare("
    SELECT v.*, u.full_name as owner_name
    FROM vendors v 
    JOIN users u ON v.user_id = u.id 
    $where_clause 
    $order_clause 
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$vendors = $stmt->fetchAll();

// Get available cuisines
try {
    $cuisine_stmt = $pdo->query("SELECT DISTINCT cuisine_type FROM vendors WHERE status = 'active' AND cuisine_type IS NOT NULL AND cuisine_type != '' ORDER BY cuisine_type");
    $cuisines = $cuisine_stmt->fetchAll();
} catch (PDOException $e) {
    // If cuisine_type column doesn't exist, use category instead
    $cuisine_stmt = $pdo->query("SELECT DISTINCT category as cuisine_type FROM vendors WHERE status = 'active' AND category IS NOT NULL ORDER BY category");
    $cuisines = $cuisine_stmt->fetchAll();
}

// Get categories
$category_stmt = $pdo->query("SELECT DISTINCT category FROM vendors WHERE status = 'active' AND category IS NOT NULL ORDER BY category");
$categories = $category_stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h4>Browse Local Shops</h4>
        <p class="text-muted">Discover local businesses and services near you</p>
    </div>
    <div class="col-md-6 text-end">
        <div class="d-flex gap-2 justify-content-end">
            <a href="custom-order.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Custom Order
            </a>
            <div class="btn-group" role="group">
                <button class="btn btn-outline-primary" id="gridView" onclick="toggleView('grid')">
                    <i class="fas fa-th"></i>
                </button>
                <button class="btn btn-outline-primary active" id="listView" onclick="toggleView('list')">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="dashboard-card mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <input type="text" class="form-control" name="search" placeholder="Search shops..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-2">
            <select class="form-select" name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="cuisine">
                <option value="">All Cuisines</option>
                <?php foreach ($cuisines as $c): ?>
                <option value="<?php echo htmlspecialchars($c['cuisine_type']); ?>" <?php echo $cuisine == $c['cuisine_type'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['cuisine_type']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="sort">
                <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                <option value="delivery_time" <?php echo $sort == 'delivery_time' ? 'selected' : ''; ?>>Fastest Delivery</option>
                <option value="delivery_fee" <?php echo $sort == 'delivery_fee' ? 'selected' : ''; ?>>Lowest Delivery Fee</option>
                <option value="alphabetical" <?php echo $sort == 'alphabetical' ? 'selected' : ''; ?>>A-Z</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="min_rating">
                <option value="0">Any Rating</option>
                <option value="4" <?php echo $min_rating == 4 ? 'selected' : ''; ?>>4+ Stars</option>
                <option value="4.5" <?php echo $min_rating == 4.5 ? 'selected' : ''; ?>>4.5+ Stars</option>
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary-custom w-100">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>
</div>

<!-- Results Summary -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">
        Showing <?php echo count($vendors); ?> of <?php echo $total_vendors; ?> shops
        <?php if ($search): ?>
            for "<?php echo htmlspecialchars($search); ?>"
        <?php endif; ?>
    </p>
    <a href="browse.php" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
</div>

<!-- Vendors List -->
<div id="vendorsContainer">
    <?php if (empty($vendors)): ?>
    <div class="dashboard-card text-center">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5>No shops found</h5>
        <p class="text-muted">Try adjusting your search criteria or browse all shops.</p>
        <div class="d-flex gap-2 justify-content-center">
            <a href="browse.php" class="btn btn-primary-custom">Browse All</a>
            <a href="custom-order.php" class="btn btn-success">Place Custom Order</a>
        </div>
    </div>
    <?php else: ?>
    <div class="row" id="vendorsList">
        <?php foreach ($vendors as $vendor): ?>
        <div class="col-md-6 col-lg-4 mb-4 vendor-item">
            <div class="dashboard-card h-100">
                <div class="position-relative">
                    <?php if ($vendor['logo']): ?>
                    <img src="../uploads/<?php echo $vendor['logo']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($vendor['shop_name']); ?>" style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                        <i class="fas fa-store fa-3x text-muted"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-success">Open</span>
                    </div>
                    
                    <?php if ($vendor['delivery_fee'] == 0): ?>
                    <div class="position-absolute top-0 start-0 m-2">
                        <span class="badge bg-primary">Free Delivery</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title"><?php echo htmlspecialchars($vendor['shop_name']); ?></h6>
                    <p class="text-muted small mb-2">
                        <?php echo htmlspecialchars($vendor['business_type'] ?? $vendor['category'] ?? 'Shop'); ?>
                        <?php if ($vendor['cuisine_type'] && $vendor['cuisine_type'] != $vendor['category']): ?>
                        • <?php echo htmlspecialchars($vendor['cuisine_type']); ?>
                        <?php elseif ($vendor['category']): ?>
                        • <?php echo htmlspecialchars($vendor['category']); ?>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($vendor['description']): ?>
                    <p class="card-text small text-muted mb-3">
                        <?php echo htmlspecialchars(substr($vendor['description'], 0, 100)); ?>
                        <?php if (strlen($vendor['description']) > 100): ?>...<?php endif; ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-star text-warning me-1"></i>
                                <span class="fw-bold"><?php echo number_format($vendor['rating'] ?? 0, 1); ?></span>
                                <small class="text-muted ms-1">(<?php echo $vendor['total_reviews'] ?? 0; ?>)</small>
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo $vendor['estimated_delivery_time'] ?? 30; ?> min
                            </small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">
                                <i class="fas fa-truck me-1"></i>
                                <?php if ($vendor['delivery_fee'] == 0): ?>
                                    Free
                                <?php else: ?>
                                    $<?php echo number_format($vendor['delivery_fee'], 2); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="col-6 text-end">
                            <small class="text-muted">
                                <i class="fas fa-dollar-sign me-1"></i>
                                Min $<?php echo number_format($vendor['min_order_amount'] ?? 0, 2); ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="mt-auto">
                        <div class="d-flex gap-2">
                            <a href="vendor-menu.php?id=<?php echo $vendor['id']; ?>" class="btn btn-primary-custom flex-fill">
                                <i class="fas fa-shopping-bag me-2"></i>View Shop
                            </a>
                            <?php if ($vendor['accepts_custom_orders'] ?? true): ?>
                            <a href="custom-order.php?vendor_id=<?php echo $vendor['id']; ?>" class="btn btn-success">
                                <i class="fas fa-plus"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
        </li>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Quick Filters -->
<div class="row mt-4">
    <div class="col-12">
        <div class="dashboard-card">
            <h6 class="mb-3">Quick Filters</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="?sort=rating" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-star"></i> Top Rated
                </a>
                <a href="?sort=delivery_time" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-clock"></i> Fast Delivery
                </a>
                <a href="?delivery_fee=0" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-truck"></i> Free Delivery
                </a>
                <a href="?category=Food" class="btn btn-outline-warning btn-sm">
                    <i class="fas fa-utensils"></i> Food
                </a>
                <a href="?category=Books" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-book"></i> Books
                </a>
                <a href="?category=Electronics" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-laptop"></i> Electronics
                </a>
                <a href="?min_rating=4" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-medal"></i> 4+ Stars
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleView(view) {
    const vendorsList = document.getElementById('vendorsList');
    const gridBtn = document.getElementById('gridView');
    const listBtn = document.getElementById('listView');
    
    if (view === 'grid') {
        vendorsList.className = 'row';
        vendorsList.querySelectorAll('.vendor-item').forEach(item => {
            item.className = 'col-md-6 col-lg-4 mb-4 vendor-item';
        });
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
    } else {
        vendorsList.className = '';
        vendorsList.querySelectorAll('.vendor-item').forEach(item => {
            item.className = 'mb-4 vendor-item';
        });
        listBtn.classList.add('active');
        gridBtn.classList.remove('active');
    }
}

// Initialize with list view
document.addEventListener('DOMContentLoaded', function() {
    toggleView('list');
});
</script>

<?php require_once '../includes/footer.php'; ?>
