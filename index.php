<?php
session_start();
require_once 'includes/db_connect.php';

// Fetch active services
$stmt = $pdo->query("SELECT * FROM services WHERE status = 'active' ORDER BY service_type");
$services = $stmt->fetchAll();

// Group services by type
$grouped_services = [];
foreach ($services as $service) {
    $grouped_services[$service['service_type']][] = $service;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Laundry Service</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/laundry-bg.jpg');
            background-size: cover;
            background-position: center;
            height: 500px;
            display: flex;
            align-items: center;
            color: white;
        }
        .service-card {
            height: 100%;
            transition: transform 0.2s;
        }
        .service-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: #26a69a;
        }
        .nav-wrapper {
            padding: 0 20px;
        }
        .section {
            padding: 60px 0;
        }
        .cta-button {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="blue darken-3">
        <div class="nav-wrapper">
            <a href="#" class="brand-logo">
                <i class="material-icons left">local_laundry_service</i>
                Laundry Service
            </a>
            <a href="#" data-target="mobile-nav" class="sidenav-trigger"><i class="material-icons">menu</i></a>
            <ul class="right hide-on-med-and-down">
                <li><a href="#services">Services</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#contact">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn waves-effect waves-light">Dashboard</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn waves-effect waves-light">Login</a></li>
                    <li><a href="register.php" class="btn waves-effect waves-light">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Mobile Navigation -->
    <ul class="sidenav" id="mobile-nav">
        <li><a href="#services">Services</a></li>
        <li><a href="#how-it-works">How It Works</a></li>
        <li><a href="#contact">Contact</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>

    <!-- Hero Section -->
    <div class="hero">
        <div class="container center-align">
            <h1>Professional Laundry Services</h1>
            <h5>Quality care for your garments with free pickup and delivery</h5>
            <a href="register.php" class="btn-large waves-effect waves-light teal cta-button">
                Get Started
                <i class="material-icons right">arrow_forward</i>
            </a>
        </div>
    </div>

    <!-- Services Section -->
    <div id="services" class="section grey lighten-4">
        <div class="container">
            <h2 class="center-align">Our Services</h2>
            <div class="row">
                <?php foreach ($grouped_services as $type => $type_services): ?>
                    <?php foreach ($type_services as $service): ?>
                        <div class="col s12 m4">
                            <div class="card service-card">
                                <div class="card-content center-align">
                                    <i class="material-icons feature-icon">
                                        <?php
                                        echo match($type) {
                                            'wash' => 'local_laundry_service',
                                            'dry_clean' => 'dry_cleaning',
                                            'iron' => 'iron',
                                            'special' => 'star',
                                            default => 'local_laundry_service'
                                        };
                                        ?>
                                    </i>
                                    <span class="card-title"><?php echo htmlspecialchars($service['service_name']); ?></span>
                                    <p><?php echo htmlspecialchars($service['description']); ?></p>
                                    <p class="teal-text">From $<?php echo number_format($service['price_per_kg'], 2); ?>/kg</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- How It Works Section -->
    <div id="how-it-works" class="section">
        <div class="container">
            <h2 class="center-align">How It Works</h2>
            <div class="row">
                <div class="col s12 m3">
                    <div class="center-align">
                        <i class="material-icons feature-icon">app_registration</i>
                        <h5>1. Register</h5>
                        <p>Create your account in minutes</p>
                    </div>
                </div>
                <div class="col s12 m3">
                    <div class="center-align">
                        <i class="material-icons feature-icon">shopping_cart</i>
                        <h5>2. Book Service</h5>
                        <p>Choose your services and schedule pickup</p>
                    </div>
                </div>
                <div class="col s12 m3">
                    <div class="center-align">
                        <i class="material-icons feature-icon">local_shipping</i>
                        <h5>3. We Collect</h5>
                        <p>Free pickup from your location</p>
                    </div>
                </div>
                <div class="col s12 m3">
                    <div class="center-align">
                        <i class="material-icons feature-icon">delivery_dining</i>
                        <h5>4. We Deliver</h5>
                        <p>Clean clothes delivered to you</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <div id="contact" class="section grey lighten-4">
        <div class="container">
            <h2 class="center-align">Contact Us</h2>
            <div class="row">
                <div class="col s12 m4">
                    <div class="center-align">
                        <i class="material-icons">phone</i>
                        <h5>Call Us</h5>
                        <p>(123) 456-7890</p>
                    </div>
                </div>
                <div class="col s12 m4">
                    <div class="center-align">
                        <i class="material-icons">email</i>
                        <h5>Email Us</h5>
                        <p>support@laundry.com</p>
                    </div>
                </div>
                <div class="col s12 m4">
                    <div class="center-align">
                        <i class="material-icons">location_on</i>
                        <h5>Visit Us</h5>
                        <p>123 Laundry Street, City</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var elems = document.querySelectorAll('.sidenav');
            M.Sidenav.init(elems);

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>
</html>
