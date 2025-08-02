<?php
require_once '../config.php';
$page_title = 'Product Management';
$current_page = 'products';

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
    redirect('index.php');
}

$vendor_id = $vendor['id'];

// Create products table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vendor_id INT,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category VARCHAR(100),
        image VARCHAR(255),
        is_available BOOLEAN DEFAULT TRUE,
        preparation_time INT DEFAULT 15,
        ingredients TEXT,
        allergens TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    )");
} catch (PDOException $e) {
    // Table might already exist
}

// Handle product actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $price = (float)$_POST['price'];
                $category = sanitize($_POST['category']);
                $preparation_time = (int)$_POST['preparation_time'];
                $ingredients = sanitize($_POST['ingredients']);
                $allergens = sanitize($_POST['allergens']);
                
                $image = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $image = uploadFile($_FILES['image'], 'products');
                }
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO products (vendor_id, name, description, price, category, image, preparation_time, ingredients, allergens) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$vendor_id, $name, $description, $price, $category, $image, $preparation_time, $ingredients, $allergens]);
                    $success = "Product added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding product: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $price = (float)$_POST['price'];
                $category = sanitize($_POST['category']);
                $preparation_time = (int)$_POST['preparation_time'];
                $ingredients = sanitize($_POST['ingredients']);
                $allergens = sanitize($_POST['allergens']);
                
                $image_update = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $image = uploadFile($_FILES['image'], 'products');
                    $image_update = ", image = '$image'";
                }
                
                try {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, preparation_time = ?, ingredients = ?, allergens = ? $image_update WHERE id = ? AND vendor_id = ?");
                    $params = [$name, $description, $price, $category, $preparation_time, $ingredients, $allergens, $id, $vendor_id];
                    $stmt->execute($params);
                    $success = "Product updated successfully!";
                } catch (PDOException $e) {
                    $error = "Error updating product: " . $e->getMessage();
                }
                break;
                
            case 'toggle_availability':
                $id = (int)$_POST['id'];
                $is_available = (int)$_POST['is_available'];
                try {
                    $stmt = $pdo->prepare("UPDATE products SET is_available = ? WHERE id = ? AND vendor_id = ?");
                    $stmt->execute([$is_available, $id, $vendor_id]);
                    $success = "Product availability updated!";
                } catch (PDOException $e) {
                    $error = "Error updating availability: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?");
                    $stmt->execute([$id, $vendor_id]);
                    $success = "Product deleted successfully!";
                } catch (PDOException $e) {
                    $error = "Error deleting product: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$availability_filter = isset($_GET['availability']) ? sanitize($_GET['availability']) : '';

$where_conditions = ["vendor_id = ?"];
$params = [$vendor_id];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

if ($availability_filter !== '') {
    $where_conditions[] = "is_available = ?";
    $params[] = (int)$availability_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products $where_clause");
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Get products
$stmt = $pdo->prepare("SELECT * FROM products $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories
$cat_stmt = $pdo->prepare("SELECT DISTINCT category FROM products WHERE vendor_id = ? AND category IS NOT NULL ORDER BY category");
$cat_stmt->execute([$vendor_id]);
$categories = $cat_stmt->fetchAll();

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
        <h4>Product Management</h4>
        <p class="text-muted">Manage your menu items and products</p>
    </div>
    <div class="col-md-6 text-end">
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus"></i> Add New Product
        </button>
    </div>
</div>

<!-- Filters -->
<div class="dashboard-card">
    <form method="GET" class="row g-3">
        <div class="col-md-4">
            <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="availability">
                <option value="">All Items</option>
                <option value="1" <?php echo $availability_filter === '1' ? 'selected' : ''; ?>>Available</option>
                <option value="0" <?php echo $availability_filter === '0' ? 'selected' : ''; ?>>Unavailable</option>
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="products.php" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
    </form>
</div>

<!-- Products Grid -->
<div class="row">
    <?php if (empty($products)): ?>
    <div class="col-12">
        <div class="dashboard-card text-center">
            <i class="fas fa-box fa-3x text-muted mb-3"></i>
            <h5>No Products Found</h5>
            <p class="text-muted">Start by adding your first product to your menu.</p>
            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> Add Product
            </button>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($products as $product): ?>
    <div class="col-md-4 mb-4">
        <div class="dashboard-card h-100">
            <div class="position-relative">
                <?php if ($product['image']): ?>
                <img src="../uploads/<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                <?php else: ?>
                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="fas fa-image fa-3x text-muted"></i>
                </div>
                <?php endif; ?>
                
                <div class="position-absolute top-0 end-0 m-2">
                    <span class="badge bg-<?php echo $product['is_available'] ? 'success' : 'danger'; ?>">
                        <?php echo $product['is_available'] ? 'Available' : 'Unavailable'; ?>
                    </span>
                </div>
            </div>
            
            <div class="card-body d-flex flex-column">
                <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                <p class="card-text text-muted small flex-grow-1">
                    <?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>
                    <?php if (strlen($product['description']) > 100): ?>...<?php endif; ?>
                </p>
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="h5 mb-0 text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                    <small class="text-muted"><?php echo $product['preparation_time']; ?> min</small>
                </div>
                
                <?php if ($product['category']): ?>
                <div class="mb-2">
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="btn-group w-100" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-<?php echo $product['is_available'] ? 'warning' : 'success'; ?>" onclick="toggleAvailability(<?php echo $product['id']; ?>, <?php echo $product['is_available'] ? 0 : 1; ?>)">
                        <i class="fas fa-<?php echo $product['is_available'] ? 'eye-slash' : 'eye'; ?>"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&availability=<?php echo urlencode($availability_filter); ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price ($)</label>
                                <input type="number" step="0.01" class="form-control" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <input type="text" class="form-control" name="category" placeholder="e.g., Appetizers, Main Course">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Preparation Time (minutes)</label>
                                <input type="number" class="form-control" name="preparation_time" value="15" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ingredients</label>
                                <textarea class="form-control" name="ingredients" rows="2" placeholder="List main ingredients"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Allergens</label>
                                <input type="text" class="form-control" name="allergens" placeholder="e.g., Nuts, Dairy, Gluten">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Product Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="editProductForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price ($)</label>
                                <input type="number" step="0.01" class="form-control" name="price" id="edit_price" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <input type="text" class="form-control" name="category" id="edit_category">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Preparation Time (minutes)</label>
                                <input type="number" class="form-control" name="preparation_time" id="edit_preparation_time" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ingredients</label>
                                <textarea class="form-control" name="ingredients" id="edit_ingredients" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Allergens</label>
                                <input type="text" class="form-control" name="allergens" id="edit_allergens">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Product Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this product? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editProduct(product) {
    document.getElementById('edit_id').value = product.id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_category').value = product.category || '';
    document.getElementById('edit_preparation_time').value = product.preparation_time;
    document.getElementById('edit_description').value = product.description || '';
    document.getElementById('edit_ingredients').value = product.ingredients || '';
    document.getElementById('edit_allergens').value = product.allergens || '';
    
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

function toggleAvailability(id, availability) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle_availability">
        <input type="hidden" name="id" value="${id}">
        <input type="hidden" name="is_available" value="${availability}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteProduct(id) {
    document.getElementById('delete_id').value = id;
    new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
