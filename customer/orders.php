<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

// Handle AJAX requests
if (isset($_GET['get_order_details'])) {
    $order_id = $_GET['order_id'];
    
    // Get order details with items
    $stmt = $pdo->prepare("
        SELECT o.*, 
               oi.quantity, 
               oi.item_price,
               s.service_name,
               s.price_per_kg
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN services s ON oi.service_id = s.service_id
        WHERE o.order_id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $items = $stmt->fetchAll();
    
    if ($items) {
        $order_details = [
            'order' => [
                'order_id' => $items[0]['order_id'],
                'status' => $items[0]['status'],
                'total_weight' => $items[0]['total_weight'],
                'total_price' => $items[0]['total_price'],
                'pickup_datetime' => $items[0]['pickup_datetime'],
                'delivery_datetime' => $items[0]['delivery_datetime'],
                'special_instructions' => $items[0]['special_instructions']
            ],
            'items' => array_map(function($item) {
                return [
                    'service_name' => $item['service_name'],
                    'quantity' => $item['quantity'],
                    'price_per_kg' => $item['price_per_kg'],
                    'total' => $item['item_price']
                ];
            }, $items)
        ];
        echo json_encode($order_details);
        exit;
    }
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
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Get order statistics
$stats = [
    'total' => count($orders),
    'pending' => count(array_filter($orders, fn($o) => $o['status'] == 'pending')),
    'processing' => count(array_filter($orders, fn($o) => $o['status'] == 'processing')),
    'completed' => count(array_filter($orders, fn($o) => $o['status'] == 'delivered'))
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 4px;
        }
        .stats-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .order-card {
            margin: 10px 0;
            transition: transform 0.2s;
        }
        .order-card:hover {
            transform: translateY(-2px);
        }
        .status-badge {
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
        .order-details {
            margin: 10px 0;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }
        .filter-chip {
            margin: 5px;
        }
        .filter-chip.active {
            background-color: #26a69a;
            color: white;
        }
        .modal-lg {
            width: 90% !important;
            max-height: 90% !important;
        }
        .search-box {
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/customer_nav.php'; ?>
    
    <div class="container">
        <h2>My Orders</h2>
        
        <!-- Statistics Row -->
        <div class="row">
            <div class="col s12 m3">
                <div class="card stats-card">
                    <i class="material-icons blue-text">shopping_cart</i>
                    <h5><?php echo $stats['total']; ?></h5>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card stats-card">
                    <i class="material-icons orange-text">pending</i>
                    <h5><?php echo $stats['pending']; ?></h5>
                    <p>Pending</p>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card stats-card">
                    <i class="material-icons blue-text">local_laundry_service</i>
                    <h5><?php echo $stats['processing']; ?></h5>
                    <p>Processing</p>
                </div>
            </div>
            <div class="col s12 m3">
                <div class="card stats-card">
                    <i class="material-icons green-text">check_circle</i>
                    <h5><?php echo $stats['completed']; ?></h5>
                    <p>Completed</p>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card search-box">
            <div class="row">
                <div class="input-field col s12 m6">
                    <i class="material-icons prefix">search</i>
                    <input type="text" id="searchInput" placeholder="Search orders...">
                </div>
                <div class="col s12 m6">
                    <div class="chip filter-chip active" data-filter="all">All</div>
                    <div class="chip filter-chip" data-filter="pending">Pending</div>
                    <div class="chip filter-chip" data-filter="processing">Processing</div>
                    <div class="chip filter-chip" data-filter="delivered">Completed</div>
                    <div class="chip filter-chip" data-filter="cancelled">Cancelled</div>
                </div>
            </div>
        </div>

        <!-- Orders List -->
        <div class="row">
            <div class="col s12">
                <?php foreach($orders as $order): ?>
                <div class="card order-card" data-status="<?php echo $order['status']; ?>">
                    <div class="card-content">
                        <div class="row">
                            <div class="col s12 m3">
                                <h5>Order #<?php echo $order['order_id']; ?></h5>
                                <p class="grey-text"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
                            </div>
                            <div class="col s12 m3">
                                <strong>Services:</strong>
                                <p class="truncate"><?php echo $order['services']; ?></p>
                            </div>
                            <div class="col s12 m2">
                                <strong>Total:</strong>
                                <p><?php echo number_format($order['total_price'], 2); ?></p>
                            </div>
                            <div class="col s12 m2">
                                <strong>Status:</strong><br>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            <div class="col s12 m2">
                                <button class="btn waves-effect waves-light view-details" 
                                        data-order-id="<?php echo $order['order_id']; ?>">
                                    <i class="material-icons left">visibility</i> Details
                                </button>
                                <?php if($order['status'] == 'pending'): ?>
                                <button class="btn red waves-effect waves-light cancel-order"
                                        data-order-id="<?php echo $order['order_id']; ?>">
                                    <i class="material-icons">cancel</i>Cancel
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($orders)): ?>
                <div class="card-panel center-align">
                    <i class="material-icons large grey-text">shopping_cart</i>
                    <h5>No orders yet</h5>
                    <a href="book-service.php" class="btn waves-effect waves-light">Book Your First Service</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal modal-lg">
        <div class="modal-content">
            <h4>Order Details</h4>
            <div class="row">
                <div class="col s12 m6">
                    <div class="order-details">
                        <h6>Order Information</h6>
                        <p><strong>Order ID:</strong> #<span id="modal-order-id"></span></p>
                        <p><strong>Status:</strong> <span id="modal-status"></span></p>
                        <p><strong>Total Weight:</strong> <span id="modal-total-weight"></span> kg</p>
                        <p><strong>Total Price:</strong> <span id="modal-total-price"></span></p>
                    </div>
                </div>
                <div class="col s12 m6">
                    <div class="order-details">
                        <h6>Dates</h6>
                        <p><strong>Pickup Date:</strong> <span id="modal-pickup-date"></span></p>
                        <p><strong>Delivery Date:</strong> <span id="modal-delivery-date"></span></p>
                    </div>
                </div>
            </div>
            
            <div class="order-details">
                <h6>Services</h6>
                <table class="striped">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Weight (kg)</th>
                            <th>Price/kg</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="modal-items">
                    </tbody>
                </table>
            </div>

            <div class="order-details">
                <h6>Special Instructions</h6>
                <p id="modal-instructions"></p>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
            <a href="#!" id="print-invoice" class="waves-effect waves-light btn">
                <i class="material-icons left">print</i> Print Invoice
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.modal').modal();

            // View Details
            $('.view-details').click(function() {
                const orderId = $(this).data('order-id');
                
                $.get('orders.php', {
                    get_order_details: true,
                    order_id: orderId
                })
                .done(function(response) {
                    const data = JSON.parse(response);
                    updateModalContent(data);
                    $('#orderDetailsModal').modal('open');
                });
            });

            // Cancel Order
            $('.cancel-order').click(function() {
                if (confirm('Are you sure you want to cancel this order?')) {
                    const orderId = $(this).data('order-id');
                    
                    $.ajax({
                        url: 'cancel_order.php',
                        type: 'POST',
                        data: { order_id: orderId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                M.toast({html: response.message, classes: 'green'});
                                location.reload();
                            } else {
                                M.toast({html: response.message, classes: 'red'});
                            }
                        },
                        error: function() {
                            M.toast({html: 'An error occurred while cancelling the order', classes: 'red'});
                        }
                    });
                }
            });

            // Search functionality
            $('#searchInput').on('keyup', function() {
                const value = $(this).val().toLowerCase();
                $('.order-card').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Filter functionality
            $('.filter-chip').click(function() {
                $('.filter-chip').removeClass('active');
                $(this).addClass('active');
                
                const filter = $(this).data('filter');
                $('.order-card').show();
                
                if (filter !== 'all') {
                    $('.order-card').not(`[data-status="${filter}"]`).hide();
                }
            });
        });

        function updateModalContent(data) {
            const order = data.order;
            
            // Update order information
            $('#modal-order-id').text(order.order_id);
            $('#modal-status').html(`<span class="status-badge status-${order.status}">${order.status}</span>`);
            $('#modal-total-weight').text(order.total_weight);
            $('#modal-total-price').text('$' + parseFloat(order.total_price).toFixed(2));
            
            // Update dates
            $('#modal-pickup-date').text(new Date(order.pickup_datetime).toLocaleString());
            $('#modal-delivery-date').text(new Date(order.delivery_datetime).toLocaleString());
            
            // Update special instructions
            $('#modal-instructions').text(order.special_instructions || 'No special instructions');
            
            // Update services table
            let itemsHtml = '';
            data.items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td>${item.service_name}</td>
                        <td>${item.quantity}</td>
                        <td>$${parseFloat(item.price_per_kg).toFixed(2)}</td>
                        <td>$${parseFloat(item.total).toFixed(2)}</td>
                    </tr>
                `;
            });
            $('#modal-items').html(itemsHtml);
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html> 