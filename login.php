<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check user credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    redirect('admin/index.php');
                    break;
                case 'vendor':
                    redirect('vendor/index.php');
                    break;
                case 'delivery':
                    redirect('delivery/index.php');
                    break;
                default:
                    redirect('customer/index.php');
                    break;
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DeliverEase</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
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
        
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 700;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
        }
        
        .btn-login {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background: #FF5252;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .demo-credentials h6 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .credential-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .credential-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .role-badge {
            background: var(--secondary-color);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-truck"></i> DeliverEase</h2>
            <p class="mb-0">Welcome Back!</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="text-center mt-3">
                <p class="mb-0">Don't have an account? <a href="register.php" style="color: var(--primary-color);">Sign up</a></p>
            </div>
            
            <!-- Demo Credentials -->
            <div class="demo-credentials">
                <h6><i class="fas fa-info-circle"></i> Demo Credentials</h6>
                <div class="credential-item" onclick="fillCredentials('admin', '123456')">
                    <div>
                        <strong>admin</strong> / 123456
                    </div>
                    <span class="role-badge">Admin</span>
                </div>
                <div class="credential-item" onclick="fillCredentials('vendor1', '123456')">
                    <div>
                        <strong>vendor1</strong> / 123456 (or vendor2, vendor3)
                    </div>
                    <span class="role-badge">Vendor</span>
                </div>
                <div class="credential-item" onclick="fillCredentials('delivery1', '123456')">
                    <div>
                        <strong>delivery1</strong> / 123456
                    </div>
                    <span class="role-badge">Delivery</span>
                </div>
                <div class="credential-item" onclick="fillCredentials('customer1', '123456')">
                    <div>
                        <strong>customer1</strong> / 123456
                    </div>
                    <span class="role-badge">Customer</span>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
