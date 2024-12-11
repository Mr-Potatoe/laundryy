<?php
require_once '../includes/admin_middleware.php';
require_once '../includes/db_connect.php';

checkAdminAccess();

if (!isset($_GET['id'])) {
    header('Location: orders.php');
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
    <title>Invoice #<?php echo str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { box-shadow: none !important; }
        }
        .invoice-header { padding: 20px 0; border-bottom: 2px solid #eee; }
        .invoice-company { font-size: 1.5em; }
        .invoice-details { margin: 20px 0; }
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8em;
        }
        .status-pending { background: #ff9800; }
        .status-processing { background: #2196f3; }
        .status-ready { background: #4caf50; }
        .status-delivered { background: #9e9e9e; }
        .status-cancelled { background: #f44336; }
        .invoice-total {
            font-size: 1.2em;
            font-weight: bold;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
    </style>
</head>
<body class="grey lighten-4">
    <div class="container">
        <!-- Print Button -->
        <div class="row no-print" style="margin-top: 20px;">
            <div class="col s12">
                <button class="btn waves-effect waves-light" onclick="window.print()">
                    <i class="material-icons left">print</i> Print Invoice
                </button>
                <a href="orders.php" class="btn grey waves-effect waves-light">
                    <i class="material-icons left">arrow_back</i> Back to Orders
                </a>
            </div>
        </div>

        <!-- Invoice Card -->
        <div class="card" style="margin-top: 20px; padding: 20px;">
            <!-- Invoice Header -->
            <div class="invoice-header">
                <div class="row">
                    <div class="col s6">
                        <div class="invoice-company">LAUNDRY SYSTEM</div>
                        <p>123 Laundry Street<br>
                           City, State 12345<br>
                           Phone: (123) 456-7890<br>
                           Email: contact@laundrysystem.com</p>
                    </div>
                    <div class="col s6 right-align">
                        <h4>INVOICE</h4>
                        <p><strong>Invoice #:</strong> <?php echo str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?><br>
                           <strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['order_date'])); ?><br>
                           <strong>Status:</strong> <span class="status-badge status-<?php echo $order['status']; ?>">
                               <?php echo ucfirst($order['status']); ?>
                           </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Customer Details -->
            <div class="row invoice-details">
                <div class="col s6">
                    <h5>Bill To:</h5>
                    <p><strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                       <?php echo nl2br(htmlspecialchars($order['address'])); ?><br>
                       Phone: <?php echo htmlspecialchars($order['phone']); ?><br>
                       Email: <?php echo htmlspecialchars($order['email']); ?></p>
                </div>
                <div class="col s6">
                    <h5>Service Details:</h5>
                    <p><strong>Service Type:</strong> <?php echo ucfirst($order['service_type']); ?><br>
                       <strong>Pickup Date:</strong> <?php echo date('M d, Y', strtotime($order['pickup_datetime'])); ?><br>
                       <strong>Delivery Date:</strong> <?php echo date('M d, Y', strtotime($order['delivery_datetime'])); ?></p>
                </div>
            </div>

            <!-- Order Items -->
            <div class="row">
                <div class="col s12">
                    <table class="striped">
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
                                <td class="right-align"><?php echo number_format($item['price_per_kg'], 2); ?></td>
                                <td class="right-align"><?php echo number_format($item['item_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="right-align"><strong>Subtotal:</strong></td>
                                <td class="right-align"><?php echo number_format($order['total_price'], 2); ?></td>
                            </tr>
                            <?php if($order['service_type'] == 'delivery'): ?>
                            <tr>
                                <td colspan="3" class="right-align"><strong>Delivery Fee:</strong></td>
                                <td class="right-align">5.00</td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="3" class="right-align"><strong>Total:</strong></td>
                                <td class="right-align invoice-total">
                                    <?php 
                                    $total = $order['total_price'];
                                    if($order['service_type'] == 'delivery') $total += 5;
                                    echo number_format($total, 2); 
                                    ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Special Instructions -->
            <?php if(!empty($order['special_instructions'])): ?>
            <div class="row">
                <div class="col s12">
                    <h5>Special Instructions:</h5>
                    <p><?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Thank You Note -->
            <div class="row" style="margin-top: 40px;">
                <div class="col s12 center-align">
                    <p>Thank you for your business!</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html> 