<?php
require_once '../includes/admin_middleware.php';
require_once '../includes/db_connect.php';

checkAdminAccess();

// Handle user status updates via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'], $_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->execute([$_POST['status'], $_POST['user_id']]);
    exit(json_encode(['success' => true]));
}

// Handle AJAX request for user orders
if (isset($_GET['get_user_orders'])) {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               GROUP_CONCAT(s.service_name) as services,
               GROUP_CONCAT(oi.quantity) as quantities
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN services s ON oi.service_id = s.service_id
        WHERE o.user_id = ?
        GROUP BY o.order_id
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$_GET['user_id']]);
    $orders = $stmt->fetchAll();
    exit(json_encode($orders));
}

// Get all users except current admin
$users = $pdo->prepare("SELECT * FROM users WHERE user_id != ? ORDER BY created_at DESC");
$users->execute([$_SESSION['user_id']]);
$users = $users->fetchAll();

// Get statistics
$stats = [
    'total' => count($users),
    'active' => count(array_filter($users, function($u) { return $u['status'] === 'active'; })),
    'blocked' => count(array_filter($users, function($u) { return $u['status'] === 'blocked'; })),
    'customers' => count(array_filter($users, function($u) { return $u['role'] === 'customer'; }))
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .stats-card {
            padding: 10px;
            text-align: center;
            border-radius: 4px;
        }
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .search-box {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .status-active {
            color: #4CAF50;
        }
        .status-blocked {
            color: #F44336;
        }
        .filter-chip {
            margin: 5px;
        }
        .filter-chip.active {
            background-color: #26a69a;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_nav.php'; ?>
    
    <div class="container">
        <h2>User Management</h2>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col s12 m3">
                <div class="card-panel stats-card">
                    <i class="material-icons blue-text">people</i>
                    <h5><?php echo $stats['total']; ?></h5>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card-panel stats-card">
                    <i class="material-icons green-text">check_circle</i>
                    <h5><?php echo $stats['active']; ?></h5>
                    <p>Active Users</p>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card-panel stats-card">
                    <i class="material-icons red-text">block</i>
                    <h5><?php echo $stats['blocked']; ?></h5>
                    <p>Blocked Users</p>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card-panel stats-card">
                    <i class="material-icons orange-text">shopping_cart</i>
                    <h5><?php echo $stats['customers']; ?></h5>
                    <p>Customers</p>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card search-box">
            <div class="row">
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix">search</i>
                    <input type="text" id="searchInput" placeholder="Search users...">
                </div>
                <div class="col s12 m6">
                    <div class="chip filter-chip" data-filter="all">All</div>
                    <div class="chip filter-chip" data-filter="active">Active</div>
                    <div class="chip filter-chip" data-filter="blocked">Blocked</div>
                    <div class="chip filter-chip" data-filter="customer">Customers</div>
                </div>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <table class="striped responsive-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $user): ?>
                                <tr data-status="<?php echo $user['status']; ?>" data-role="<?php echo $user['role']; ?>">
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['full_name']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['phone']; ?></td>
                                    <td><?php echo ucfirst($user['role']); ?></td>
                                    <td>
                                        <span class="status-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-small blue view-orders" 
                                                data-user-id="<?php echo $user['user_id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                            <i class="material-icons">visibility</i>
                                        </button>
                                        <?php if($user['role'] !== 'admin'): ?>
                                        <button class="btn-small <?php echo $user['status'] === 'active' ? 'red' : 'green'; ?> toggle-status"
                                                data-user-id="<?php echo $user['user_id']; ?>"
                                                data-current-status="<?php echo $user['status']; ?>">
                                            <i class="material-icons"><?php echo $user['status'] === 'active' ? 'block' : 'check_circle'; ?></i>
                                        </button>
                                        <?php endif; ?>
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

    <!-- User Orders Modal -->
    <div id="userOrdersModal" class="modal modal-fixed-footer">
        <div class="modal-content">
            <h4>Orders for <span id="modal-username"></span></h4>
            <table class="striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Services</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Pickup Date</th>
                        <th>Delivery Date</th>
                    </tr>
                </thead>
                <tbody id="orders-table-body">
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.modal').modal();

            // Search functionality
            $('#searchInput').on('keyup', function() {
                const value = $(this).val().toLowerCase();
                $("#usersTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Filter functionality
            $('.filter-chip').click(function() {
                $('.filter-chip').removeClass('active');
                $(this).addClass('active');
                
                const filter = $(this).data('filter');
                $("#usersTable tbody tr").show();
                
                if (filter !== 'all') {
                    $("#usersTable tbody tr").filter(function() {
                        return !$(this).data('status').includes(filter) && 
                               !$(this).data('role').includes(filter);
                    }).hide();
                }
            });

            // Handle View Orders button click
            $('.view-orders').click(function() {
                const userId = $(this).data('user-id');
                const username = $(this).data('username');
                
                $('#modal-username').text(username);
                $('#orders-table-body').empty().append('<tr><td colspan="6" class="center">Loading...</td></tr>');
                
                $('#userOrdersModal').modal('open');
                
                $.get('users.php', {
                    get_user_orders: true,
                    user_id: userId
                })
                .done(function(response) {
                    const orders = JSON.parse(response);
                    let html = '';
                    
                    orders.forEach(function(order) {
                        html += `
                            <tr>
                                <td>${order.order_id}</td>
                                <td>${order.services}</td>
                                <td>$${parseFloat(order.total_price).toFixed(2)}</td>
                                <td>${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</td>
                                <td>${new Date(order.pickup_date).toLocaleDateString()}</td>
                                <td>${new Date(order.delivery_date).toLocaleDateString()}</td>
                            </tr>
                        `;
                    });
                    
                    if (orders.length === 0) {
                        html = '<tr><td colspan="6" class="center">No orders found</td></tr>';
                    }
                    
                    $('#orders-table-body').html(html);
                })
                .fail(function() {
                    $('#orders-table-body').html('<tr><td colspan="6" class="center red-text">Error loading orders</td></tr>');
                });
            });

            // Handle status toggle button click
            $('.toggle-status').click(function() {
                const userId = $(this).data('user-id');
                const currentStatus = $(this).data('current-status');
                const newStatus = currentStatus === 'active' ? 'blocked' : 'active';
                const button = $(this);
                
                $.post('users.php', {
                    user_id: userId,
                    status: newStatus
                })
                .done(function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Update button appearance
                        button.data('current-status', newStatus);
                        button.text(newStatus === 'active' ? 'Block' : 'Unblock');
                        button.removeClass('red green').addClass(newStatus === 'active' ? 'red' : 'green');
                        
                        M.toast({html: `User ${newStatus === 'active' ? 'unblocked' : 'blocked'} successfully`});
                    }
                })
                .fail(function() {
                    M.toast({html: 'Error updating user status', classes: 'red'});
                });
            });
        });
    </script>
    <?php $no_script = true; ?>
    <?php include 'includes/footer.php'; ?>
</body>
</html> 