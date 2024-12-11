<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get order details with items
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.phone, u.email, u.address
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Get order items
$items_stmt = $pdo->prepare("
    SELECT oi.*, s.service_name, s.price_per_kg
    FROM order_items oi
    JOIN services s ON oi.service_id = s.service_id
    WHERE oi.order_id = ?
");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Details #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
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
        .details-section {
            margin: 20px 0;
            padding: 20px;
            border-radius: 4px;
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <?php include 'includes/customer_nav.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col s12">
                <div class="card">
                    <div class="card-content">
                        <div class="d-flex" style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="card-title">
                                <i class="material-icons left">receipt</i>
                                Order #<?php echo $order_id; ?>
                            </span>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>

                        <div class="row">
                            <div class="col s12 m6">
                                <div class="details-section">
                                    <h6>Order Information</h6>
                                    <p><strong>Order Date:</strong> <?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
                                    <p><strong>Pickup Date:</strong> <?php echo date('M d, Y', strtotime($order['pickup_datetime'])); ?></p>
                                    <p><strong>Delivery Date:</strong> <?php echo date('M d, Y', strtotime($order['delivery_datetime'])); ?></p>
                                    <p><strong>Total Weight:</strong> <?php echo $order['total_weight']; ?> kg</p>
                                    <p><strong>Total Price:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
                                </div>
                            </div>
                            <div class="col s12 m6">
                                <div class="details-section">
                                    <h6>Delivery Information</h6>
                                    <p><strong>Name:</strong> <?php echo $order['username']; ?></p>
                                    <p><strong>Phone:</strong> <?php echo $order['phone']; ?></p>
                                    <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
                                    <p><strong>Address:</strong> <?php echo $order['address']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="details-section">
                            <h6>Order Items</h6>
                            <table class="striped">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Weight (kg)</th>
                                        <th>Price/kg</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['service_name']; ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['price_per_kg'], 2); ?></td>
                                        <td>$<?php echo number_format($item['item_price'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if($order['special_instructions']): ?>
                        <div class="details-section">
                            <h6>Special Instructions</h6>
                            <p><?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="center-align" style="margin-top: 20px;">
                            <a href="orders.php" class="btn waves-effect waves-light">
                                <i class="material-icons left">arrow_back</i>
                                Back to Orders
                            </a>
                            
                            <?php if($order['status'] == 'pending'): ?>
                            <button class="btn waves-effect waves-light red cancel-order" 
                                    data-order-id="<?php echo $order_id; ?>">
                                <i class="material-icons left">cancel</i>
                                Cancel Order
                            </button>
                            <?php endif; ?>
                            
                            <?php if($order['status'] == 'delivered'): ?>
                            <a href="invoice.php?id=<?php echo $order_id; ?>" 
                               class="btn waves-effect waves-light green"
                               target="_blank">
                                <i class="material-icons left">receipt</i>
                                Download Invoice
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.cancel-order').click(function() {
                if (confirm('Are you sure you want to cancel this order?')) {
                    const orderId = $(this).data('order-id');
                    const button = $(this);
                    
                    // Disable the button to prevent double submission
                    button.prop('disabled', true);
                    
                    $.ajax({
                        url: 'cancel_order.php',
                        type: 'POST',
                        data: { order_id: orderId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                M.toast({html: response.message, classes: 'green'});
                                // Update the status display immediately
                                $('.order-status')
                                    .removeClass()
                                    .addClass('order-status status-cancelled')
                                    .text('Cancelled');
                                // Remove the cancel button
                                button.remove();
                                // Redirect after delay
                                setTimeout(() => {
                                    window.location.href = 'orders.php';
                                }, 1500);
                            } else {
                                M.toast({html: response.message, classes: 'red'});
                                // Re-enable the button on error
                                button.prop('disabled', false);
                                // Log debug info if available
                                if (response.debug) {
                                    console.error('Cancel Error:', response.debug);
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            M.toast({
                                html: 'An error occurred while cancelling the order. Please try again.',
                                classes: 'red'
                            });
                            console.error('Ajax Error:', status, error);
                            // Re-enable the button on error
                            button.prop('disabled', false);
                        }
                    });
                }
            });
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html> 