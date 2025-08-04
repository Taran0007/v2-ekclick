<?php
require_once 'config.php';
$page_title = 'Business Documents';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Ek-Click</title>
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
            background-color: var(--light-bg);
        }

        .doc-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .doc-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .doc-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .btn-download {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-download:hover {
            background: #ff5252;
            transform: translateY(-2px);
            color: white;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            background: var(--light-bg);
            clip-path: polygon(0 100%, 100% 100%, 100% 0);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo getBaseUrl(); ?>">
                <i class="fas fa-truck"></i> Ek-Click
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo getBaseUrl(); ?>">Home</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header mt-5">
        <div class="container">
            <h1 class="display-4 fw-bold">Business Documents</h1>
            <p class="lead">Access our company's official documentation and certifications</p>
        </div>
    </div>

    <!-- Documents Section -->
    <div class="container my-5">
        <div class="row">
            <div class="col-md-6">
                <div class="doc-card">
                    <div class="text-center">
                        <i class="fas fa-building doc-icon"></i>
                        <h3 class="mb-4">Company Profile</h3>
                        <p class="text-muted mb-4">Comprehensive overview of our company, mission, vision, and services.</p>
                        <a href="<?php echo getBaseUrl(); ?>/docs/company-profile.pdf" class="btn btn-download" target="_blank">
                            <i class="fas fa-download me-2"></i>View Document
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="doc-card">
                    <div class="text-center">
                        <i class="fas fa-certificate doc-icon"></i>
                        <h3 class="mb-4">Business Approval</h3>
                        <p class="text-muted mb-4">Official business certifications and approval documentation.</p>
                        <a href="<?php echo getBaseUrl(); ?>/docs/business-approval.pdf" class="btn btn-download" target="_blank">
                            <i class="fas fa-download me-2"></i>View Document
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer py-4 bg-light mt-5">
        <div class="container">
            <p class="text-center mb-0">
                Made with <i class="fas fa-heart text-danger animate-pulse"></i> by 
                <a href="mailto:Trnjeet@gmail.com" class="text-decoration-none footer-link">Taran Jeet</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
