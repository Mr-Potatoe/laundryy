<?php
require_once '../includes/admin_middleware.php';
require_once '../includes/db_connect.php';

checkAdminAccess();

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

// Get order details with user information
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.phone, u.email, u.address, u.full_name
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$_GET['id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: dashboard.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, s.service_name, s.price_per_kg
    FROM order_items oi
    JOIN services s ON oi.service_id = s.service_id
    WHERE oi.order_id = ?
");
$stmt->execute([$_GET['id']]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Order #<?php echo str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .order-header { 
            padding: 20px;
            background: #f5f5f5;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .status-pending { background: #ff9800; }
        .status-processing { background: #2196f3; }
        .status-ready { background: #4caf50; }
        .status-delivered { background: #9e9e9e; }
        .status-cancelled { background: #f44336; }
        .detail-card {
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .timeline-item {
            padding: 20px;
            border-left: 2px solid #2196f3;
            margin-left: 20px;
            position: relative;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -9px;
            top: 24px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #2196f3;
        }
        .action-buttons {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
        }
    </style>
</head>
<body class="grey lighten-4">
    <?php include 'includes/admin_nav.php'; ?>

    <div class="container" style="margin-top: 20px;">
        <!-- Order Header -->
        <div class="order-header white">
            <div class="row valign-wrapper">
                <div class="col s6">
                    <h4>Order #<?php echo str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></h4>
                    <p class="grey-text">
                        Placed on <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?>
                    </p>
                </div>
                <div class="col s6 right-align">
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Customer Information -->
            <div class="col s12 m6">
                <div class="card detail-card">
                    <h5><i class="material-icons left">person</i>Customer Information</h5>
                    <div class="divider"></div>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Address:</strong><br><?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
                </div>
            </div>

            <!-- Order Details -->
            <div class="col s12 m6">
                <div class="card detail-card">
                    <h5><i class="material-icons left">info</i>Order Details</h5>
                    <div class="divider"></div>
                    <p><strong>Service Type:</strong> <?php echo ucfirst($order['service_type']); ?></p>
                    <p><strong>Pickup Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['pickup_datetime'])); ?></p>
                    <p><strong>Delivery Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['delivery_datetime'])); ?></p>
                    <p><strong>Total Weight:</strong> <?php echo number_format($order['total_weight'], 2); ?> kg</p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="row">
            <div class="col s12">
                <div class="card detail-card">
                    <h5><i class="material-icons left">list</i>Order Items</h5>
                    <div class="divider"></div>
                    <table class="striped responsive-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th class="center-align">Weight (kg)</th>
                                <th class="right-align">Price/kg</th>
                                <th class="right-align">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['service_name']); ?></td>
                                <td class="center-align"><?php echo number_format($item['quantity'], 2); ?></td>
                                <td class="right-align">₱<?php echo number_format($item['price_per_kg'], 2); ?></td>
                                <td class="right-align">₱<?php echo number_format($item['item_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="right-align"><strong>Subtotal:</strong></td>
                                <td class="right-align">₱<?php echo number_format($order['total_price'], 2); ?></td>
                            </tr>
                            <?php if($order['service_type'] == 'delivery'): ?>
                            <tr>
                                <td colspan="3" class="right-align"><strong>Delivery Fee:</strong></td>
                                <td class="right-align">₱5.00</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="right-align"><strong>Total:</strong></td>
                                <td class="right-align"><strong>₱<?php echo number_format($order['total_price'] + 5, 2); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Special Instructions -->
        <?php if(!empty($order['special_instructions'])): ?>
        <div class="row">
            <div class="col s12">
                <div class="card detail-card">
                    <h5><i class="material-icons left">note</i>Special Instructions</h5>
                    <div class="divider"></div>
                    <p><?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Timeline -->
        <div class="row">
            <div class="col s12">
                <div class="card detail-card">
                    <h5><i class="material-icons left">timeline</i>Order Timeline</h5>
                    <div class="divider"></div>
                    <div class="timeline-item">
                        <strong>Order Placed</strong><br>
                        <span class="grey-text"><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></span>
                    </div>
                    <?php if($order['status'] == 'cancelled'): ?>
                    <div class="timeline-item">
                        <strong>Order Cancelled</strong><br>
                        <span class="grey-text"><?php echo date('M d, Y h:i A', strtotime($order['cancelled_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Floating Action Buttons -->
        <div class="action-buttons">
            <a href="orders.php" class="btn-floating btn-large grey">
                <i class="material-icons">arrow_back</i>
            </a>
            <a href="invoice.php?id=<?php echo $order['order_id']; ?>" class="btn-floating btn-large blue">
                <i class="material-icons">receipt</i>
            </a>
            <?php if($order['status'] !== 'cancelled'): ?>
            <a href="#updateStatusModal" class="btn-floating btn-large green modal-trigger">
                <i class="material-icons">edit</i>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Status Modal -->
    <?php if($order['status'] !== 'cancelled'): ?>
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <h4>Update Order Status</h4>
            <div class="input-field">
                <select id="newStatus">
                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <label>Order Status</label>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
            <a href="#!" onclick="updateOrderStatus()" class="waves-effect waves-green btn-flat">Update</a>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            M.AutoInit();
        });

        function updateOrderStatus() {
            const newStatus = document.getElementById('newStatus').value;
            const orderId = <?php echo $order['order_id']; ?>;

            $.post('orders.php', {
                order_id: orderId,
                status: newStatus
            })
            .done(function(response) {
                M.toast({html: 'Order status updated successfully!', classes: 'rounded green'});
                setTimeout(() => window.location.reload(), 1000);
            })
            .fail(function(xhr) {
                let errorMessage = 'Error updating status!';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                M.toast({html: errorMessage, classes: 'rounded red'});
            });
        }
    </script>
</body>
</html> 