<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

// Get user's orders with service details
$stmt = $pdo->prepare("
    SELECT o.*, GROUP_CONCAT(s.service_name) as services
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN services s ON oi.service_id = s.service_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_orders = $stmt->fetchAll();

// Get order statistics
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(total_price) as total_spent
    FROM orders 
    WHERE user_id = ?
");
$stats->execute([$_SESSION['user_id']]);
$order_stats = $stats->fetch();

// Get active services
$services = $pdo->query("SELECT * FROM services WHERE status = 'active' LIMIT 4")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .dashboard-card {
            height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .dashboard-card i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .stats-card {
            text-align: center;
            padding: 20px;
        }
        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            color: white;
            font-size: 0.9rem;
        }
        .status-pending { background-color: #ff9800; }
        .status-processing { background-color: #2196f3; }
        .status-ready { background-color: #4caf50; }
        .status-delivered { background-color: #9e9e9e; }
        .status-cancelled { background-color: #f44336; }
        .quick-service-card {
            height: 100%;
            transition: transform 0.2s;
        }
        .quick-service-card:hover {
            transform: translateY(-5px);
        }
        .welcome-section {
            background: linear-gradient(to right, #26a69a, #4db6ac);
            color: white;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/customer_nav.php'; ?>
    
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="row">
                <div class="col s12 m8">
                    <h3>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
                    <p>Manage your laundry services and track your orders from your personal dashboard.</p>
                </div>
                <div class="col s12 m4">
                    <a href="book-service.php" class="btn-large waves-effect waves-light white teal-text" style="width: 100%;">
                        <i class="material-icons left">add_circle</i>
                        Book New Service
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col s12 m3">
                <div class="card stats-card">
                    <i class="material-icons blue-text">shopping_cart</i>
                    <h5><?php echo $order_stats['total_orders']; ?></h5>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card stats-card">
                    <i class="material-icons orange-text">pending</i>
                    <h5><?php echo $order_stats['pending_orders']; ?></h5>
                    <p>Pending Orders</p>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card stats-card">
                    <i class="material-icons green-text">local_laundry_service</i>
                    <h5><?php echo $order_stats['processing_orders']; ?></h5>
                    <p>Processing</p>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card stats-card">
                    <i class="material-icons purple-text">account_balance_wallet</i>
                    <h5>$<?php echo number_format($order_stats['total_spent'], 2); ?></h5>
                    <p>Total Spent</p>
                </div>
            </div>
        </div>

        <!-- Quick Services -->
        <div class="row">
            <div class="col s12">
                <h5><i class="material-icons left">flash_on</i> Quick Services</h5>
            </div>
            <?php foreach($services as $service): ?>
            <div class="col s12 m3">
                <div class="card quick-service-card">
                    <div class="card-content">
                        <span class="card-title truncate"><?php echo $service['service_name']; ?></span>
                        <p class="truncate"><?php echo $service['description']; ?></p>
                        <p class="teal-text">$<?php echo number_format($service['price_per_kg'], 2); ?>/kg</p>
                    </div>
                    <div class="card-action">
                        <a href="book-service.php" class="teal-text">Book Now</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Orders -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="d-flex" style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="card-title">
                                <i class="material-icons left">history</i>
                                Recent Orders
                            </span>
                            <a href="orders.php" class="btn-flat waves-effect">View All</a>
                        </div>
                        <table class="striped responsive-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Services</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td class="truncate" style="max-width: 200px;">
                                        <?php echo $order['services']; ?>
                                    </td>
                                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td>
                                        <span class="order-status status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <a href="view_order.php?id=<?php echo $order['order_id']; ?>" 
                                           class="btn-small waves-effect waves-light">
                                            <i class="material-icons">visibility</i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($recent_orders)): ?>
                                <tr>
                                    <td colspan="6" class="center-align">No orders yet</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.tooltipped').tooltip();
            $('.sidenav').sidenav();
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>