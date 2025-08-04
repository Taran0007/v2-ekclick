<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'admin':
            redirect(getBaseUrl() . '/admin/index.php');
            break;
        case 'vendor':
            redirect(getBaseUrl() . '/vendor/index.php');
            break;
        case 'delivery':
            redirect(getBaseUrl() . '/delivery/index.php');
            break;
        default:
            redirect(getBaseUrl() . '/customer/index.php');
            break;
    }
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
                    redirect(getBaseUrl() . '/admin/index.php');
                    break;
                case 'vendor':
                    redirect(getBaseUrl() . '/vendor/index.php');
                    break;
                case 'delivery':
                    redirect(getBaseUrl() . '/delivery/index.php');
                    break;
                default:
                    redirect(getBaseUrl() . '/customer/index.php');
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
    <title>Login - Ek-Click</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6B6B;
            --secondary-color: #4ECDC4;
            --dark-color: #2C3E50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            overflow: hidden;
            background-color: #050505;
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content-container {
            position: relative;
            z-index: 10;
            text-align: center;
        }

        .gradient-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .gradient-sphere {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
        }

        .sphere-1 {
            width: 40vw;
            height: 40vw;
            background: linear-gradient(40deg, rgba(255, 0, 128, 0.8), rgba(255, 102, 0, 0.4));
            top: -10%;
            left: -10%;
            animation: float-1 15s ease-in-out infinite alternate;
        }

        .sphere-2 {
            width: 45vw;
            height: 45vw;
            background: linear-gradient(240deg, rgba(72, 0, 255, 0.8), rgba(0, 183, 255, 0.4));
            bottom: -20%;
            right: -10%;
            animation: float-2 18s ease-in-out infinite alternate;
        }

        .sphere-3 {
            width: 30vw;
            height: 30vw;
            background: linear-gradient(120deg, rgba(133, 89, 255, 0.5), rgba(98, 216, 249, 0.3));
            top: 60%;
            left: 20%;
            animation: float-3 20s ease-in-out infinite alternate;
        }

        .noise-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.05;
            z-index: 5;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
        }

        @keyframes float-1 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(10%, 10%) scale(1.1); }
        }

        @keyframes float-2 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-10%, -5%) scale(1.15); }
        }

        @keyframes float-3 {
            0% { transform: translate(0, 0) scale(1); opacity: 0.3; }
            100% { transform: translate(-5%, 10%) scale(1.05); opacity: 0.6; }
        }

        .grid-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: 40px 40px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            z-index: 2;
        }

        .glow {
            position: absolute;
            width: 40vw;
            height: 40vh;
            background: radial-gradient(circle, rgba(72, 0, 255, 0.15), transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            animation: pulse 8s infinite alternate;
            filter: blur(30px);
        }

        @keyframes pulse {
            0% { opacity: 0.3; transform: translate(-50%, -50%) scale(0.9); }
            100% { opacity: 0.7; transform: translate(-50%, -50%) scale(1.1); }
        }

        .particles-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 3;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: white;
            border-radius: 50%;
            opacity: 0;
            pointer-events: none;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.1);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            animation: slideIn 0.5s ease-out;
            color: white;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            background: transparent;
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
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
            background: rgba(255, 255, 255, 0.2);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .btn-login {
            background: linear-gradient(90deg, #ff3a82, #5233ff);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 58, 130, 0.4);
        }
        
        .demo-credentials {
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .demo-credentials h6 {
            color: white;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .credential-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .credential-item:hover {
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .role-badge {
            background: var(--secondary-color);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .form-label, .text-center a {
            color: white !important;
        }
        .text-center p {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <div class="gradient-background">
        <div class="gradient-sphere sphere-1"></div>
        <div class="gradient-sphere sphere-2"></div>
        <div class="gradient-sphere sphere-3"></div>
        <div class="glow"></div>
        <div class="grid-overlay"></div>
        <div class="noise-overlay"></div>
        <div class="particles-container" id="particles-container"></div>
    </div>

    <div class="content-container">
        <div class="login-container">
            <div class="login-header">
                <a href="index.php" class="text-white" style="text-decoration: none;"><h2><i class="fas fa-truck"></i> Ek-Click</h2></a>
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
    </script>
    <script>
        // Create particle effect
        const particlesContainer = document.getElementById('particles-container');
        const particleCount = 80;
        
        // Create particles
        for (let i = 0; i < particleCount; i++) {
            createParticle();
        }
        
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'particle';
            
            // Random size (small)
            const size = Math.random() * 3 + 1;
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            
            // Initial position
            resetParticle(particle);
            
            particlesContainer.appendChild(particle);
            
            // Animate
            animateParticle(particle);
        }
        
        function resetParticle(particle) {
            // Random position
            const posX = Math.random() * 100;
            const posY = Math.random() * 100;
            
            particle.style.left = `${posX}%`;
            particle.style.top = `${posY}%`;
            particle.style.opacity = '0';
            
            return {
                x: posX,
                y: posY
            };
        }
        
        function animateParticle(particle) {
            // Initial position
            const pos = resetParticle(particle);
            
            // Random animation properties
            const duration = Math.random() * 10 + 10;
            const delay = Math.random() * 5;
            
            // Animate with GSAP-like timing
            setTimeout(() => {
                particle.style.transition = `all ${duration}s linear`;
                particle.style.opacity = Math.random() * 0.3 + 0.1;
                
                // Move in a slight direction
                const moveX = pos.x + (Math.random() * 20 - 10);
                const moveY = pos.y - Math.random() * 30; // Move upwards
                
                particle.style.left = `${moveX}%`;
                particle.style.top = `${moveY}%`;
                
                // Reset after animation completes
                setTimeout(() => {
                    animateParticle(particle);
                }, duration * 1000);
            }, delay * 1000);
        }
        
        // Mouse interaction
        document.addEventListener('mousemove', (e) => {
            // Create particles at mouse position
            const mouseX = (e.clientX / window.innerWidth) * 100;
            const mouseY = (e.clientY / window.innerHeight) * 100;
            
            // Create temporary particle
            const particle = document.createElement('div');
            particle.className = 'particle';
            
            // Small size
            const size = Math.random() * 4 + 2;
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            
            // Position at mouse
            particle.style.left = `${mouseX}%`;
            particle.style.top = `${mouseY}%`;
            particle.style.opacity = '0.6';
            
            particlesContainer.appendChild(particle);
            
            // Animate outward
            setTimeout(() => {
                particle.style.transition = 'all 2s ease-out';
                particle.style.left = `${mouseX + (Math.random() * 10 - 5)}%`;
                particle.style.top = `${mouseY + (Math.random() * 10 - 5)}%`;
                particle.style.opacity = '0';
                
                // Remove after animation
                setTimeout(() => {
                    particle.remove();
                }, 2000);
            }, 10);
            
            // Subtle movement of gradient spheres
            const spheres = document.querySelectorAll('.gradient-sphere');
            const moveX = (e.clientX / window.innerWidth - 0.5) * 5;
            const moveY = (e.clientY / window.innerHeight - 0.5) * 5;
            
            spheres.forEach(sphere => {
                const currentTransform = getComputedStyle(sphere).transform;
                sphere.style.transform = `translate(${moveX}px, ${moveY}px)`;
            });
        });
    </script>
</body>
</html>
