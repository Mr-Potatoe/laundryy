<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

// Get active services grouped by type
$stmt = $pdo->query("SELECT *, 
    CASE service_type 
        WHEN 'wash' THEN 1 
        WHEN 'dry_clean' THEN 2 
        WHEN 'iron' THEN 3 
        WHEN 'special' THEN 4 
    END as type_order 
    FROM services 
    WHERE status = 'active' 
    ORDER BY type_order, service_name");
$services = $stmt->fetchAll();

// Group services by type
$grouped_services = [];
foreach ($services as $service) {
    $grouped_services[$service['service_type']][] = $service;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();
        
        $total_weight = $_POST['total_weight'];
        $pickup_date = $_POST['pickup_date'];
        $delivery_date = $_POST['delivery_date'];
        $special_instructions = $_POST['special_instructions'];
        
        // Calculate total price
        $total_price = 0;
        foreach ($_POST['services'] as $service_id => $weight) {
            if ($weight > 0) {
                $service = $pdo->query("SELECT price_per_kg FROM services WHERE service_id = $service_id")->fetch();
                $total_price += $weight * $service['price_per_kg'];
            }
        }

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_weight, total_price, pickup_date, delivery_date, special_instructions) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $total_weight, $total_price, $pickup_date, $delivery_date, $special_instructions]);
        
        $order_id = $pdo->lastInsertId();

        // Create order items
        foreach ($_POST['services'] as $service_id => $weight) {
            if ($weight > 0) {
                $service = $pdo->query("SELECT price_per_kg FROM services WHERE service_id = $service_id")->fetch();
                $item_price = $weight * $service['price_per_kg'];
                
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, service_id, quantity, item_price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $service_id, $weight, $item_price]);
            }
        }

        $pdo->commit();
        header("Location: orders.php?message=Order placed successfully");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error placing order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Service</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .service-card {
            height: 100%;
        }
        .service-card .card-content {
            padding-bottom: 60px;
        }
        .service-card .price {
            position: absolute;
            bottom: 20px;
            left: 24px;
            color: #26a69a;
            font-weight: bold;
        }
        .service-card .weight-input {
            position: absolute;
            bottom: 20px;
            right: 24px;
            width: 100px;
        }
        .date-warning {
            display: none;
            color: red;
            font-size: 0.9rem;
        }
        .summary-card {
            position: sticky;
            top: 20px;
        }
        .service-type-section {
            margin-bottom: 30px;
        }
        .service-type-header {
            margin-bottom: 20px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .service-icon {
            vertical-align: middle;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/customer_nav.php'; ?>
    
    <div class="container">
        <h2 class="center-align">Book Laundry Service</h2>
        
        <form method="POST" action="" id="bookingForm">
            <?php if (isset($error)): ?>
                <div class="card-panel red lighten-4 red-text"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- Services Selection -->
                <div class="col s12 m8">
                    <?php 
                    $icons = [
                        'wash' => 'local_laundry_service',
                        'dry_clean' => 'dry_cleaning',
                        'iron' => 'iron',
                        'special' => 'star'
                    ];
                    
                    foreach ($grouped_services as $type => $type_services): ?>
                        <div class="service-type-section">
                            <div class="service-type-header">
                                <h5>
                                    <i class="material-icons service-icon"><?php echo $icons[$type]; ?></i>
                                    <?php echo ucwords(str_replace('_', ' ', $type)); ?> Services
                                </h5>
                            </div>
                            <div class="row">
                                <?php foreach($type_services as $service): ?>
                                <div class="col s12 m6">
                                    <div class="card service-card">
                                        <div class="card-content">
                                            <span class="card-title"><?php echo $service['service_name']; ?></span>
                                            <p><?php echo $service['description']; ?></p>
                                            <div class="price">
                                                $<?php echo number_format($service['price_per_kg'], 2); ?>/kg
                                            </div>
                                            <div class="weight-input input-field">
                                                <input type="number" step="0.1" min="0" 
                                                       name="services[<?php echo $service['service_id']; ?>]" 
                                                       class="service-weight" 
                                                       data-price="<?php echo $service['price_per_kg']; ?>"
                                                       data-name="<?php echo $service['service_name']; ?>">
                                                <label>Weight (kg)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Summary -->
                <div class="col s12 m4">
                    <div class="card summary-card">
                        <div class="card-content">
                            <span class="card-title">Order Summary</span>
                            <div id="selected-services">
                                <p class="center-align grey-text" id="no-services-message">
                                    No services selected yet
                                </p>
                            </div>
                            <div class="divider" style="margin: 20px 0;"></div>
                            <p>
                                <strong>Total Weight:</strong> 
                                <span id="display_total_weight">0.0</span> kg
                                <input type="hidden" name="total_weight" id="total_weight" required>
                            </p>
                            <p>
                                <strong>Estimated Total:</strong> 
                                $<span id="estimated_total">0.00</span>
                            </p>
                            
                            <div class="input-field">
                                <i class="material-icons prefix">event</i>
                                <input type="date" name="pickup_date" id="pickup_date" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                                <label>Pickup Date</label>
                                <span class="date-warning" id="pickup_warning">
                                    Pickup date must be at least today
                                </span>
                            </div>

                            <div class="input-field">
                                <i class="material-icons prefix">event</i>
                                <input type="date" name="delivery_date" id="delivery_date" required 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                <label>Delivery Date</label>
                                <span class="date-warning" id="delivery_warning">
                                    Delivery date must be after pickup date
                                </span>
                            </div>

                            <div class="input-field">
                                <i class="material-icons prefix">note</i>
                                <textarea name="special_instructions" id="special_instructions" 
                                          class="materialize-textarea"></textarea>
                                <label for="special_instructions">Special Instructions</label>
                            </div>

                            <button type="submit" class="btn waves-effect waves-light" style="width: 100%;">
                                Place Order
                                <i class="material-icons right">send</i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.service-weight').on('input', function() {
                updateOrderSummary();
            });

            function updateOrderSummary() {
                let totalWeight = 0;
                let totalPrice = 0;
                let selectedServices = [];
                
                $('.service-weight').each(function() {
                    const weight = parseFloat($(this).val()) || 0;
                    if (weight > 0) {
                        const price = parseFloat($(this).data('price'));
                        const name = $(this).data('name');
                        totalWeight += weight;
                        totalPrice += weight * price;
                        selectedServices.push({
                            name: name,
                            weight: weight,
                            price: weight * price
                        });
                    }
                });
                
                // Update summary display
                $('#display_total_weight').text(totalWeight.toFixed(1));
                $('#total_weight').val(totalWeight.toFixed(1));
                $('#estimated_total').text(totalPrice.toFixed(2));
                
                // Update selected services list
                const servicesHtml = selectedServices.map(service => `
                    <div class="selected-service">
                        <p>
                            <strong>${service.name}</strong><br>
                            ${service.weight} kg × $${(service.price / service.weight).toFixed(2)} 
                            = $${service.price.toFixed(2)}
                        </p>
                    </div>
                `).join('');
                
                $('#selected-services').html(
                    selectedServices.length ? servicesHtml : 
                    '<p class="center-align grey-text">No services selected yet</p>'
                );
            }

            // Date validation
            $('#pickup_date, #delivery_date').change(function() {
                const pickup = new Date($('#pickup_date').val());
                const delivery = new Date($('#delivery_date').val());
                const today = new Date();
                today.setHours(0,0,0,0);

                if (pickup < today) {
                    $('#pickup_warning').show();
                    $('#pickup_date').val('');
                } else {
                    $('#pickup_warning').hide();
                }

                if (delivery <= pickup) {
                    $('#delivery_warning').show();
                    $('#delivery_date').val('');
                } else {
                    $('#delivery_warning').hide();
                }
            });
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html> 