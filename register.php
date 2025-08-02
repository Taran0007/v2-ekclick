<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';
$user_type = $_GET['type'] ?? 'user';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $role = sanitize($_POST['role']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Username or email already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate referral code
            $referral_code = strtoupper(substr(md5($username . time()), 0, 8));
            
            try {
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, phone, role, address, city, referral_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $email, $full_name, $phone, $role, $address, $city, $referral_code]);
                
                $user_id = $pdo->lastInsertId();
                
                // If vendor, create vendor profile
                if ($role === 'vendor' && !empty($_POST['shop_name'])) {
                    $shop_name = sanitize($_POST['shop_name']);
                    $shop_description = sanitize($_POST['shop_description']);
                    $category = sanitize($_POST['category']);
                    
                    $stmt = $pdo->prepare("INSERT INTO vendors (user_id, shop_name, shop_description, category, address, city) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $shop_name, $shop_description, $category, $address, $city]);
                }
                
                $success = 'Registration successful! You can now login.';
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - DeliverEase</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6B6B;
            --secondary-color: #4ECDC4;
            --dark-color: #2C3E50;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            padding: 40px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header h2 {
            margin: 0;
            font-weight: 700;
        }
        
        .register-body {
            padding: 40px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
        }
        
        .btn-register {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            background: #FF5252;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .role-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .role-option:hover {
            border-color: var(--primary-color);
        }
        
        .role-option.active {
            border-color: var(--primary-color);
            background: rgba(255, 107, 107, 0.1);
        }
        
        .role-option i {
            font-size: 2rem;
            margin-bottom: 5px;
            display: block;
        }
        
        .vendor-fields {
            display: none;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2><i class="fas fa-truck"></i> DeliverEase</h2>
            <p class="mb-0">Create Your Account</p>
        </div>
        
        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- Role Selection -->
                <div class="mb-4">
                    <label class="form-label">I want to register as:</label>
                    <div class="role-selector">
                        <div class="role-option <?php echo $user_type == 'user' ? 'active' : ''; ?>" onclick="selectRole('user')">
                            <i class="fas fa-user" style="color: var(--primary-color);"></i>
                            <small>Customer</small>
                        </div>
                        <div class="role-option <?php echo $user_type == 'vendor' ? 'active' : ''; ?>" onclick="selectRole('vendor')">
                            <i class="fas fa-store" style="color: var(--secondary-color);"></i>
                            <small>Vendor</small>
                        </div>
                        <div class="role-option <?php echo $user_type == 'delivery' ? 'active' : ''; ?>" onclick="selectRole('delivery')">
                            <i class="fas fa-motorcycle" style="color: #3498db;"></i>
                            <small>Delivery</small>
                        </div>
                    </div>
                    <input type="hidden" name="role" id="role" value="<?php echo $user_type; ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                </div>
                
                <!-- Vendor-specific fields -->
                <div class="vendor-fields" id="vendorFields">
                    <h5 class="mb-3">Shop Information</h5>
                    <div class="mb-3">
                        <label for="shop_name" class="form-label">Shop Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="shop_name" name="shop_name">
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Select Category</option>
                            <option value="Restaurant">Restaurant</option>
                            <option value="Grocery">Grocery</option>
                            <option value="Pharmacy">Pharmacy</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Clothing">Clothing</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="shop_description" class="form-label">Shop Description</label>
                        <textarea class="form-control" id="shop_description" name="shop_description" rows="3"></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="text-center mt-3">
                <p class="mb-0">Already have an account? <a href="login.php" style="color: var(--primary-color);">Login</a></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectRole(role) {
            // Update active state
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('active');
            });
            event.target.closest('.role-option').classList.add('active');
            
            // Update hidden input
            document.getElementById('role').value = role;
            
            // Show/hide vendor fields
            if (role === 'vendor') {
                document.getElementById('vendorFields').style.display = 'block';
                document.getElementById('shop_name').required = true;
                document.getElementById('category').required = true;
            } else {
                document.getElementById('vendorFields').style.display = 'none';
                document.getElementById('shop_name').required = false;
                document.getElementById('category').required = false;
            }
        }
        
        // Initialize based on URL parameter
        window.onload = function() {
            const role = document.getElementById('role').value;
            if (role === 'vendor') {
                document.getElementById('vendorFields').style.display = 'block';
                document.getElementById('shop_name').required = true;
                document.getElementById('category').required = true;
            }
        };
    </script>
</body>
</html>
