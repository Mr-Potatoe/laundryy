<?php
require_once '../includes/admin_middleware.php';
require_once '../includes/db_connect.php';

checkAdminAccess();

// Handle status updates via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    // First check if order is already cancelled
    $checkStmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
    $checkStmt->execute([$_POST['order_id']]);
    $currentStatus = $checkStmt->fetchColumn();

    if ($currentStatus === 'cancelled') {
        http_response_code(400);
        exit(json_encode([
            'success' => false,
            'message' => 'Cannot update cancelled orders'
        ]));
    }

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
                                <td><strong><?php echo number_format($order['total_price'], 2); ?></strong></td>
                                <td><?php echo $order['total_weight']; ?> kg</td>
                                <td><i class="material-icons tiny">event</i> <?php echo date('M d, Y', strtotime($order['pickup_datetime'])); ?></td>
                                <td><i class="material-icons tiny">event</i> <?php echo date('M d, Y', strtotime($order['delivery_datetime'])); ?></td>
                                <td>
                                    <div class="input-field" style="margin: 0;">
                                        <select class="status-select status-<?php echo $order['status']; ?>" 
                                                data-order-id="<?php echo $order['order_id']; ?>"
                                                data-original-status="<?php echo $order['status']; ?>"
                                                <?php echo $order['status'] === 'cancelled' ? 'disabled' : ''; ?>>
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

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal modal-fixed-footer">
        <div class="modal-content">
            <div class="row">
                <div class="col s12">
                    <h4 class="teal-text"><i class="material-icons left">receipt</i>Order Details</h4>
                    <div class="divider"></div>
                </div>
            </div>

            <div class="row">
                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title"><i class="material-icons left">info</i>Order Information</span>
                            <div class="collection">
                                <div class="collection-item">
                                    <span class="title">Order ID</span>
                                    <p id="modal-order-id" class="secondary-content"></p>
                                </div>
                                <div class="collection-item">
                                    <span class="title">Status</span>
                                    <p id="modal-status" class="secondary-content"></p>
                                </div>
                                <div class="collection-item">
                                    <span class="title">Pickup Date</span>
                                    <p id="modal-pickup-date" class="secondary-content"></p>
                                </div>
                                <div class="collection-item">
                                    <span class="title">Delivery Date</span>
                                    <p id="modal-delivery-date" class="secondary-content"></p>
                                </div>
                                <div class="collection-item">
                                    <span class="title">Total Weight</span>
                                    <p id="modal-total-weight" class="secondary-content"></p>
                                </div>
                                <div class="collection-item">
                                    <span class="title">Total Price</span>
                                    <p id="modal-total-price" class="secondary-content"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col s12 m6">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title"><i class="material-icons left">person</i>Customer Information</span>
                            <div class="collection">
                                <div class="collection-item">
                                    <span class="title">Name</span>
                                    <p id="modal-customer-name" class="secondary-content"></p>
                                </div>
                                <div class="collection-item">
                                    <span class="title">Phone</span>
                                    <p id="modal-customer-phone" class="secondary-content"></p>
                                </div>
                                <div class="collection-item">
                                    <span class="title">Email</span>
                                    <p id="modal-customer-email" class="secondary-content"></p>
                                </div>
                                <div class="collection-item">
                                    <span class="title">Address</span>
                                    <p id="modal-customer-address" class="secondary-content"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title"><i class="material-icons left">note</i>Special Instructions</span>
                            <blockquote id="modal-instructions" class="grey-text"></blockquote>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col s12">
                    <div class="card">
                        <div class="card-content">
                            <span class="card-title"><i class="material-icons left">list</i>Order Items</span>
                            <table class="highlight responsive-table">
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
            </div>
        </div>

        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-light btn-flat grey-text">Close</a>
            <a href="#" id="print-invoice" class="waves-effect waves-light btn teal">
                <i class="material-icons left">print</i>Print Invoice
            </a>
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
                const select = $(this);
                const orderId = select.data('order-id');
                const newStatus = select.val();
                const originalStatus = select.attr('data-original-status');
                
                // Check if order was cancelled
                if (originalStatus === 'cancelled') {
                    M.toast({html: '<i class="material-icons left">error</i> Cannot update cancelled orders!', classes: 'rounded red'});
                    select.val(originalStatus); // Reset to original value
                    select.formSelect(); // Refresh Materialize select
                    return;
                }
                
                M.toast({html: '<i class="material-icons left">refresh</i> Updating status...', classes: 'rounded'});
                
                $.post('orders.php', {
                    order_id: orderId,
                    status: newStatus
                })
                .done(function(response) {
                    M.toast({html: '<i class="material-icons left">check</i> Order status updated!', classes: 'rounded green'});
                })
                .fail(function(xhr) {
                    let errorMessage = 'Error updating status!';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    M.toast({html: `<i class="material-icons left">error</i> ${errorMessage}`, classes: 'rounded red'});
                    select.val(originalStatus); // Reset to original value
                    select.formSelect(); // Refresh Materialize select
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
                                <td>${parseFloat(item.price_per_kg).toFixed(2)}</td>
                                <td>${parseFloat(item.item_price).toFixed(2)}</td>
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