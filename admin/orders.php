<?php
require_once '../includes/admin_middleware.php';
require_once '../includes/db_connect.php';

checkAdminAccess();

// Handle status updates via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    exit(json_encode(['success' => true]));
}

// Add AJAX handler for order details
if (isset($_GET['get_order_details'])) {
    $order_id = $_GET['order_id'];
    
    // Get order items with service details
    $stmt = $pdo->prepare("
        SELECT oi.*, s.service_name, s.price_per_kg
        FROM order_items oi
        JOIN services s ON oi.service_id = s.service_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
    
    // Get order and user details
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.phone, u.email, u.address
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    echo json_encode(['items' => $items, 'order' => $order]);
    exit;
}

// Get all orders with user information
$orders = $pdo->query("SELECT o.*, u.username, u.phone 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    ORDER BY o.order_date DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .modal-lg { width: 90% !important; max-height: 90% !important; }
        .page-header { 
            padding: 20px 0;
            background: #f5f5f5;
            margin-bottom: 20px;
        }
        .status-pending { color: #ff9800; }
        .status-processing { color: #2196f3; }
        .status-ready { color: #4caf50; }
        .status-delivered { color: #9e9e9e; }
        .status-cancelled { color: #f44336; }
        .card-stats {
            padding: 20px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }
        .table-container {
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background: white;
        }
    </style>
</head>
<body class="grey lighten-4">
    <?php include 'includes/admin_nav.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <div class="row valign-wrapper">
                <div class="col s6">
                    <h4 class="grey-text text-darken-2">Order Management</h4>
                </div>
                <div class="col s6 right-align">
                    <a class="waves-effect waves-light btn-large blue"><i class="material-icons left">refresh</i>Refresh Orders</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Stats Cards -->
        <div class="row">
            <div class="col s12 m3">
                <div class="card-stats white">
                    <div class="center-align">
                        <i class="material-icons medium orange-text">pending</i>
                        <div class="stats-number orange-text">
                            <?php echo count(array_filter($orders, function($o) { return $o['status'] == 'pending'; })); ?>
                        </div>
                        <div class="grey-text">Pending Orders</div>
                    </div>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card-stats white">
                    <div class="center-align">
                        <i class="material-icons medium blue-text">loop</i>
                        <div class="stats-number blue-text">
                            <?php echo count(array_filter($orders, function($o) { return $o['status'] == 'processing'; })); ?>
                        </div>
                        <div class="grey-text">Processing</div>
                    </div>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card-stats white">
                    <div class="center-align">
                        <i class="material-icons medium green-text">check_circle</i>
                        <div class="stats-number green-text">
                            <?php echo count(array_filter($orders, function($o) { return $o['status'] == 'ready'; })); ?>
                        </div>
                        <div class="grey-text">Ready</div>
                    </div>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card-stats white">
                    <div class="center-align">
                        <i class="material-icons medium grey-text">local_shipping</i>
                        <div class="stats-number grey-text">
                            <?php echo count(array_filter($orders, function($o) { return $o['status'] == 'delivered'; })); ?>
                        </div>
                        <div class="grey-text">Delivered</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="row">
            <div class="col s12">
                <div class="table-container">
                    <table class="striped responsive-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Total</th>
                                <th>Weight</th>
                                <th>Pickup Date</th>
                                <th>Delivery Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td>#<?php echo str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><i class="material-icons tiny">person</i> <?php echo $order['username']; ?></td>
                                <td><i class="material-icons tiny">phone</i> <?php echo $order['phone']; ?></td>
                                <td><strong>$<?php echo number_format($order['total_price'], 2); ?></strong></td>
                                <td><?php echo $order['total_weight']; ?> kg</td>
                                <td><i class="material-icons tiny">event</i> <?php echo date('M d, Y', strtotime($order['pickup_date'])); ?></td>
                                <td><i class="material-icons tiny">event</i> <?php echo date('M d, Y', strtotime($order['delivery_date'])); ?></td>
                                <td>
                                    <div class="input-field" style="margin: 0;">
                                        <select class="status-select status-<?php echo $order['status']; ?>" data-order-id="<?php echo $order['order_id']; ?>">
                                            <?php
                                            $statuses = ['pending', 'processing', 'ready', 'delivered', 'cancelled'];
                                            foreach($statuses as $status) {
                                                $selected = ($status == $order['status']) ? 'selected' : '';
                                                echo "<option value='$status' $selected>" . ucfirst($status) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn-floating waves-effect waves-light blue view-order" data-order-id="<?php echo $order['order_id']; ?>">
                                        <i class="material-icons">visibility</i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Update modal code to match new styling -->
    <div id="orderDetailsModal" class="modal modal-lg">
        <div class="modal-content">
            <h4><i class="material-icons left">receipt</i> Order Details</h4>
            <div class="row">
                <div class="col s12 m6">
                    <h5>Order Information</h5>
                    <p><strong>Order ID:</strong> <span id="modal-order-id"></span></p>
                    <p><strong>Status:</strong> <span id="modal-status"></span></p>
                    <p><strong>Pickup Date:</strong> <span id="modal-pickup-date"></span></p>
                    <p><strong>Delivery Date:</strong> <span id="modal-delivery-date"></span></p>
                    <p><strong>Total Weight:</strong> <span id="modal-total-weight"></span> kg</p>
                    <p><strong>Total Price:</strong> $<span id="modal-total-price"></span></p>
                </div>
                <div class="col s12 m6">
                    <h5>Customer Information</h5>
                    <p><strong>Name:</strong> <span id="modal-customer-name"></span></p>
                    <p><strong>Phone:</strong> <span id="modal-customer-phone"></span></p>
                    <p><strong>Email:</strong> <span id="modal-customer-email"></span></p>
                    <p><strong>Address:</strong> <span id="modal-customer-address"></span></p>
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <h5>Special Instructions</h5>
                    <p id="modal-instructions"></p>
                </div>
            </div>
            <div class="row">
                <div class="col s12">
                    <h5>Order Items</h5>
                    <table class="striped">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Weight (kg)</th>
                                <th>Price per kg</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="modal-items">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
            <a href="#" id="print-invoice" class="waves-effect waves-green btn blue-text">Print Invoice</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.modal').modal();
            $('select').formSelect();
            
            // Add preloader for status updates
            $('.status-select').change(function() {
                const orderId = $(this).data('order-id');
                const newStatus = $(this).val();
                
                M.toast({html: '<i class="material-icons left">refresh</i> Updating status...', classes: 'rounded'});
                
                $.post('orders.php', {
                    order_id: orderId,
                    status: newStatus
                })
                .done(function(response) {
                    M.toast({html: '<i class="material-icons left">check</i> Order status updated!', classes: 'rounded green'});
                })
                .fail(function() {
                    M.toast({html: '<i class="material-icons left">error</i> Error updating status!', classes: 'rounded red'});
                });
            });
            
            // Handle view order details
            $('.view-order').click(function() {
                const orderId = $(this).data('order-id');
                
                $.get('orders.php', {
                    get_order_details: true,
                    order_id: orderId
                })
                .done(function(response) {
                    const data = JSON.parse(response);
                    const order = data.order;
                    const items = data.items;
                    
                    // Update modal content - Order Info
                    $('#modal-order-id').text(order.order_id);
                    $('#modal-status').text(order.status.charAt(0).toUpperCase() + order.status.slice(1));
                    $('#modal-pickup-date').text(new Date(order.pickup_date).toLocaleDateString());
                    $('#modal-delivery-date').text(new Date(order.delivery_date).toLocaleDateString());
                    $('#modal-total-weight').text(order.total_weight);
                    $('#modal-total-price').text(parseFloat(order.total_price).toFixed(2));
                    
                    // Update modal content - Customer Info
                    $('#modal-customer-name').text(order.username);
                    $('#modal-customer-phone').text(order.phone);
                    $('#modal-customer-email').text(order.email);
                    $('#modal-customer-address').text(order.address);
                    
                    // Update modal content - Special Instructions
                    $('#modal-instructions').text(order.special_instructions || 'None');
                    
                    // Update items table
                    let itemsHtml = '';
                    items.forEach(function(item) {
                        itemsHtml += `
                            <tr>
                                <td>${item.service_name}</td>
                                <td>${item.quantity}</td>
                                <td>$${parseFloat(item.price_per_kg).toFixed(2)}</td>
                                <td>$${parseFloat(item.item_price).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    $('#modal-items').html(itemsHtml);
                    
                    // Update print invoice link
                    $('#print-invoice').attr('href', `invoice.php?id=${order.order_id}`);
                    
                    // Open modal
                    $('#orderDetailsModal').modal('open');
                })
                .fail(function() {
                    M.toast({html: 'Error loading order details'});
                });
            });
        });
    </script>
    <?php $no_script = true; ?>
<?php include 'includes/footer.php'; ?>
</body>
</html> 