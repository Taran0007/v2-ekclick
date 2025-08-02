<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-CLICK - Multi-Vendor Local Delivery Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6B6B;
            --secondary-color: #4ECDC4;
            --dark-color: #2C3E50;
            --light-bg: #F8F9FA;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff20" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat;
            opacity: 0.1;
        }
        
        .feature-card {
            border: none;
            border-radius: 15px;
            padding: 30px;
            height: 100%;
            transition: transform 0.3s, box-shadow 0.3s;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }
        
        .btn-custom {
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary-custom {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-primary-custom:hover {
            background: #FF5252;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }
        
        .stats-section {
            background: var(--light-bg);
            padding: 80px 0;
        }
        
        .stat-card {
            text-align: center;
            padding: 30px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .how-it-works {
            padding: 80px 0;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            background: var(--secondary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-truck"></i> E-CLICK
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary-custom ms-2" href="register.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Everything You Need, Delivered Fast</h1>
                    <p class="lead mb-4">Connect with local shops and get anything delivered quickly by our reliable delivery partners. From groceries to books, electronics to food - we deliver it all!</p>
                    <div class="d-flex gap-3">
                        <a href="register.php" class="btn btn-light btn-lg btn-custom">Get Started</a>
                        <a href="#how-it-works" class="btn btn-outline-light btn-lg btn-custom">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://via.placeholder.com/600x400/FF6B6B/FFFFFF?text=Delivery+Service" alt="Delivery" class="img-fluid rounded-3 shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose E-CLICK?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: rgba(255, 107, 107, 0.1); color: var(--primary-color);">
                            <i class="fas fa-store"></i>
                        </div>
                        <h4 class="text-center mb-3">Multiple Local Shops</h4>
                        <p class="text-center text-muted">Choose from a wide variety of local shops and services. Find everything you need all in one place.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: rgba(78, 205, 196, 0.1); color: var(--secondary-color);">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h4 class="text-center mb-3">Fast Delivery</h4>
                        <p class="text-center text-muted">Our network of delivery partners ensures your orders reach you hot and fresh, right on time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: rgba(44, 62, 80, 0.1); color: var(--dark-color);">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4 class="text-center mb-3">Custom Orders</h4>
                        <p class="text-center text-muted">Can't find what you need? Place a custom order and let local shops provide quotes for exactly what you want.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">500+</div>
                        <p class="text-muted">Local Shops</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">10K+</div>
                        <p class="text-muted">Happy Customers</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">50K+</div>
                        <p class="text-muted">Orders Delivered</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-number">200+</div>
                        <p class="text-muted">Delivery Partners</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <h2 class="text-center mb-5">How It Works</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="step-number">1</div>
                        <h5>Browse & Select</h5>
                        <p class="text-muted">Browse through various local shops and select your favorite items or place custom orders</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="step-number">2</div>
                        <h5>Place Order</h5>
                        <p class="text-muted">Add items to cart and place your order with delivery details</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="step-number">3</div>
                        <h5>Track Delivery</h5>
                        <p class="text-muted">Track your order in real-time as it's prepared and delivered</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <div class="step-number">4</div>
                        <h5>Enjoy & Review</h5>
                        <p class="text-muted">Receive your order and share your experience with others</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5" style="background: var(--primary-color); color: white;">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Get Started?</h2>
            <p class="lead mb-4">Join thousands of satisfied customers and local shops on E-CLICK</p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="register.php?type=customer" class="btn btn-light btn-lg btn-custom">Start Shopping</a>
                <a href="register.php?type=vendor" class="btn btn-outline-light btn-lg btn-custom">List Your Shop</a>
                <a href="register.php?type=delivery" class="btn btn-outline-light btn-lg btn-custom">Join as Delivery Partner</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-truck"></i> E-CLICK</h5>
                    <p class="mb-0">Your trusted multi-vendor local delivery service platform</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2025 E-CLICK. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
