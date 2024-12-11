<?php
require_once '../includes/admin_middleware.php';
require_once '../includes/db_connect.php';

checkAdminAccess();

// Get statistics
$stmt = $pdo->query("SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
    FROM orders");
$stats = $stmt->fetch();

// Get recent orders
$recentOrders = $pdo->query("SELECT o.*, u.username 
    FROM orders o  
    JOIN users u ON o.user_id = u.user_id 
    ORDER BY order_date DESC LIMIT 5")->fetchAll();

// Add this new query for total revenue
$stmt = $pdo->query("SELECT SUM(total_price) as total_revenue FROM orders WHERE status = 'delivered'");
$revenue = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .dashboard-card {
            border-radius: 8px;
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .card-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            color: white;
        }
        .status-pending { background-color: #ff9800; }
        .status-processing { background-color: #2196f3; }
        .status-delivered { background-color: #4caf50; }
        .status-cancelled { background-color: #f44336; }
    </style>
</head>
<body class="grey lighten-4">
    <?php include 'includes/admin_nav.php'; ?>
    
    <div class="container">
        <div class="row" style="margin-top: 20px;">
            <div class="col s12">
                <h4><i class="material-icons left">dashboard</i> Dashboard Overview</h4>
            </div>
        </div>
        
        <div class="row">
            <div class="col s12 m3">
                <div class="card dashboard-card orange darken-1">
                    <div class="card-content white-text center-align">
                        <i class="material-icons card-icon">hourglass_empty</i>
                        <span class="card-title">Pending</span>
                        <h3><?php echo $stats['pending_orders']; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="col s12 m3">
                <div class="card dashboard-card blue darken-1">
                    <div class="card-content white-text center-align">
                        <i class="material-icons card-icon">refresh</i>
                        <span class="card-title">Processing</span>
                        <h3><?php echo $stats['processing_orders']; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="col s12 m3">
                <div class="card dashboard-card green darken-1">
                    <div class="card-content white-text center-align">
                        <i class="material-icons card-icon">check_circle</i>
                        <span class="card-title">Completed</span>
                        <h3><?php echo $stats['completed_orders']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col s12 m3">
                <div class="card dashboard-card purple darken-1">
                    <div class="card-content white-text center-align">
                        <i class="material-icons card-icon">attach_money</i>
                        <span class="card-title">Revenue</span>
                        <h3><?php echo number_format($revenue['total_revenue'], 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <span class="card-title"><i class="material-icons left">receipt</i>Recent Orders</span>
                        <div class="input-field">
                            <i class="material-icons prefix">search</i>
                            <input type="text" id="orderSearch" onkeyup="filterOrders()">
                            <label for="orderSearch">Search orders...</label>
                        </div>
                        <table class="striped responsive-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTable">
                                <?php foreach($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?php echo str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><i class="material-icons tiny">person</i> <?php echo $order['username']; ?></td>
                                    <td><b><?php echo number_format($order['total_price'], 2); ?></b></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <a href="view_order.php?id=<?php echo $order['order_id']; ?>" 
                                           class="btn-floating btn-small waves-effect waves-light blue">
                                            <i class="material-icons">visibility</i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Materialize components
        M.AutoInit();
        
        // Refresh dashboard data every 30 seconds
        setInterval(refreshDashboard, 30000);
    });

    function filterOrders() {
        let input = document.getElementById('orderSearch');
        let filter = input.value.toLowerCase();
        let tbody = document.getElementById('ordersTable');
        let tr = tbody.getElementsByTagName('tr');

        for (let i = 0; i < tr.length; i++) {
            let td = tr[i].getElementsByTagName('td');
            let found = false;
            for (let j = 0; j < td.length; j++) {
                if (td[j].textContent.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
            tr[i].style.display = found ? '' : 'none';
        }
    }

    function refreshDashboard() {
        $.ajax({
            url: 'ajax/get_dashboard_stats.php',
            method: 'GET',
            success: function(response) {
                // Update dashboard numbers
                const stats = JSON.parse(response);
                // Update the statistics cards
                updateStats(stats);
            }
        });
    }

    function updateStats(stats) {
        // Update card values with animation
        $('.dashboard-card h3').each(function() {
            const $this = $(this);
            const key = $this.data('stat');
            if (stats[key] !== undefined) {
                animateValue($this, parseInt($this.text()), stats[key], 500);
            }
        });
    }

    function animateValue($element, start, end, duration) {
        let current = start;
        const range = end - start;
        const increment = end > start ? 1 : -1;
        const stepTime = Math.abs(Math.floor(duration / range));
        
        const timer = setInterval(function() {
            current += increment;
            $element.text(current);
            if (current == end) {
                clearInterval(timer);
            }
        }, stepTime);
    }
    </script>
    <?php $no_script = true; ?>
    <?php include 'includes/footer.php'; ?>
</body>
</html> 