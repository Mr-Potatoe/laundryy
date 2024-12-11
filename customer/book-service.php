<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/check_user_status.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

// Check user status
$userStatus = checkUserStatus($pdo, $_SESSION['user_id']);
$isBlocked = !$userStatus; // Convert boolean to blocked status
$blockMessage = "Your account is currently blocked. Please contact support for assistance.";

// Insert default settings if none exist
$stmt = $pdo->query("SELECT COUNT(*) FROM business_settings");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO business_settings (business_days, opening_time, closing_time, slot_duration, advance_booking_days, delivery_fee) 
                VALUES ('Monday,Tuesday,Wednesday,Thursday,Friday,Saturday', '09:00:00', '17:00:00', 60, 30, 5.00)");
}

// Get business settings
$stmt = $pdo->query("SELECT * FROM business_settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Generate time slots
function generateTimeSlots($opening_time, $closing_time, $slot_duration) {
    $slots = [];
    $start = strtotime($opening_time);
    $end = strtotime($closing_time);
    
    for ($time = $start; $time <= $end; $time += ($slot_duration * 60)) {
        $slots[] = date('H:i', $time);
    }
    
    return $slots;
}

$timeSlots = generateTimeSlots($settings['opening_time'], $settings['closing_time'], $settings['slot_duration']);
$businessDays = explode(',', $settings['business_days']);
$maxBookingDate = date('Y-m-d', strtotime("+{$settings['advance_booking_days']} days"));

$icons = [
    'wash' => 'local_laundry_service',
    'dry_clean' => 'dry_cleaning',
    'iron' => 'iron',
    'special' => 'star'
];

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
    if ($isBlocked) {
        header("Location: book-service.php?error=Your account is blocked");
        exit();
    }

    try {
        $pdo->beginTransaction();
        
        $service_type = $_POST['service_type'];
        $pickup_datetime = $_POST['pickup_date'] . ' ' . $_POST['pickup_time'];
        $delivery_datetime = $_POST['delivery_date'] . ' ' . $_POST['delivery_time'];
        $total_weight = $_POST['total_weight'];
        $special_instructions = $_POST['special_instructions'];
        
        // Calculate total price
        $total_price = 0;
        foreach ($_POST['services'] as $service_id => $weight) {
            if ($weight > 0) {
                $service = $pdo->query("SELECT price_per_kg FROM services WHERE service_id = $service_id")->fetch();
                $total_price += $weight * $service['price_per_kg'];
            }
        }
        
        // Add delivery fee if door-to-door service
        if ($service_type === 'delivery') {
            $delivery_fee = 5.00; // You can adjust this or make it configurable
            $total_price += $delivery_fee;
        }
        
        // Create order with the new fields
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, service_type, total_weight, total_price, 
                              pickup_datetime, delivery_datetime, special_instructions) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $service_type, $total_weight, $total_price, 
                       $pickup_datetime, $delivery_datetime, $special_instructions]);
        
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
        .stepper {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 20px;
            position: relative;
        }
        
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            right: 0;
            width: 100%;
            height: 2px;
            background: #e0e0e0;
            z-index: -1;
        }
        
        .step.active .step-title {
            color: #2196F3;
            font-weight: bold;
        }
        
        .step.completed .step-title {
            color: #4CAF50;
        }
        
        .service-option {
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .service-option:hover {
            transform: translateY(-5px);
        }
        
        .service-option-content {
            text-align: center;
            padding: 20px;
        }
        
        .service-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .service-option input[type="radio"]:checked + .service-option-content {
            background: #e3f2fd;
            border: 2px solid #2196F3;
        }
        
        .service-card {
            transition: all 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .section-step {
            transition: all 0.3s ease;
        }
        
        .service-type-header {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .service-type-header h5 {
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .service-icon {
            margin-right: 10px;
        }
        
        .service-card {
            height: 100%;
            position: relative;
            padding-bottom: 60px;
        }
        
        .service-card .card-content {
            height: 100%;
        }
        
        .service-description {
            min-height: 60px;
        }
        
        .price {
            position: absolute;
            bottom: 20px;
            left: 24px;
            font-size: 1.2rem;
            font-weight: bold;
            color: #2196F3;
        }
        
        .weight-input {
            position: absolute;
            bottom: 15px;
            right: 24px;
            width: 100px;
        }
        
        .summary-card {
            position: sticky;
            top: 20px;
        }
        
        .service-type-section {
            margin-bottom: 30px;
        }
        
        @media only screen and (max-width: 600px) {
            .weight-input {
                position: relative;
                bottom: auto;
                right: auto;
                width: 100%;
                margin-top: 20px;
            }
            
            .price {
                position: relative;
                bottom: auto;
                left: auto;
                margin-top: 10px;
            }
            
            .service-card {
                padding-bottom: 0;
            }
        }
        
        .scheduling-section {
            margin-bottom: 20px;
        }
        
        .service-type-summary {
            margin-bottom: 20px;
        }
        
        .chip {
            font-size: 1rem;
        }
        
        .chip i {
            margin-right: 8px;
        }
        
        .date-warning {
            margin-top: 5px;
            font-size: 0.9rem;
        }
        
        .date-warning i {
            vertical-align: middle;
            margin-right: 5px;
        }
        
        .card-panel i {
            vertical-align: middle;
            margin-right: 8px;
        }
        
        form.disabled {
            opacity: 0.7;
            pointer-events: none;
        }
        
        form.disabled input,
        form.disabled select,
        form.disabled textarea,
        form.disabled button {
            cursor: not-allowed !important;
        }
        
        .blocked-message {
            margin: 20px 0;
            padding: 15px;
            background-color: #ffebee;
            border-radius: 4px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/customer_nav.php'; ?>
    
    <?php if ($isBlocked): ?>
        <div class="container">
            <div class="card-panel red lighten-4 center-align">
                <i class="material-icons medium">block</i>
                <h5><?php echo $blockMessage; ?></h5>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="container">
        <h2 class="center-align">Book Laundry Service</h2>
        
        <!-- Progress Indicator -->
        <div class="row">
            <div class="col s12">
                <ul class="stepper horizontal">
                    <li class="step active">
                        <div class="step-title waves-effect">Select Services</div>
                    </li>
                    <li class="step">
                        <div class="step-title waves-effect">Schedule</div>
                    </li>
                </ul>
            </div>
        </div>

        <form method="POST" action="" id="bookingForm" <?php echo $isBlocked ? 'class="disabled"' : ''; ?>>
            <!-- Step 1: Select Services (formerly Step 2) -->
            <div class="section-step" id="step1">
                <div class="row">
                    <div class="col s12 m8">
                        <?php if (empty($grouped_services)): ?>
                            <div class="card">
                                <div class="card-content center">
                                    <p>No services available at the moment.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($grouped_services as $type => $type_services): ?>
                                <div class="service-type-section">
                                    <div class="service-type-header">
                                        <h5>
                                            <i class="material-icons service-icon"><?php echo $icons[$type] ?? 'local_laundry_service'; ?></i>
                                            <?php echo ucwords(str_replace('_', ' ', $type)); ?> Services
                                        </h5>
                                    </div>
                                    <div class="row">
                                        <?php foreach($type_services as $service): ?>
                                            <div class="col s12 m6">
                                                <div class="card service-card hoverable">
                                                    <div class="card-content">
                                                        <span class="card-title truncate"><?php echo htmlspecialchars($service['service_name']); ?></span>
                                                        <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                                                        <div class="price">
                                                            $<?php echo number_format($service['price_per_kg'], 2); ?>/kg
                                                        </div>
                                                        <div class="weight-input input-field">
                                                            <input type="number" 
                                                                   step="0.1" 
                                                                   min="0" 
                                                                   name="services[<?php echo $service['service_id']; ?>]" 
                                                                   class="service-weight validate" 
                                                                   data-price="<?php echo $service['price_per_kg']; ?>"
                                                                   data-name="<?php echo htmlspecialchars($service['service_name']); ?>">
                                                            <label>Weight (kg)</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col s6">
                                <button type="button" class="btn-large waves-effect waves-light prev-step">
                                    <i class="material-icons left">arrow_back</i> Back
                                </button>
                            </div>
                            <div class="col s6 right-align">
                                <button type="button" class="btn-large waves-effect waves-light next-step">
                                    Continue <i class="material-icons right">arrow_forward</i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col s12 m4">
                        <div class="card summary-card">
                            <div class="card-content">
                                <span class="card-title">Order Summary</span>
                                <div id="selected-services">
                                    <p class="center-align grey-text">No services selected yet</p>
                                </div>
                                <div class="divider" style="margin: 20px 0;"></div>
                                <p>
                                    <strong>Total Weight:</strong> 
                                    <span id="display_total_weight">0.0</span> kg
                                    <input type="hidden" name="total_weight" id="total_weight" value="0">
                                </p>
                                <p>
                                    <strong>Estimated Total:</strong> $
                                    <span id="estimated_total">0.00</span>
                                    <span id="delivery-fee-note" style="display: none;">
                                        <br><small class="grey-text">(Includes $5.00 delivery fee)</small>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Schedule (formerly Step 3) -->
            <div class="section-step" id="step2" style="display: none;">
                <div class="row">
                    <div class="col s12 m8">
                        <div class="card">
                            <div class="card-content">
                                <span class="card-title">Schedule Your Service</span>
                                
                                <!-- Business Hours Notice -->
                                <div class="card-panel blue lighten-5">
                                    <i class="material-icons left">info</i>
                                    Business Hours: <?php echo date('g:i A', strtotime($settings['opening_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($settings['closing_time'])); ?>
                                    <br>
                                    Available Days: <?php echo $settings['business_days']; ?>
                                </div>

                                <!-- Service Type Selection -->
                                <div class="input-field">
                                    <p>Select Service Type:</p>
                                    <p>
                                        <label>
                                            <input name="service_type" type="radio" value="delivery" checked />
                                            <span><i class="material-icons left">local_shipping</i> Door-to-Door Service (+ $<?php echo number_format($settings['delivery_fee'], 2); ?>)</span>
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            <input name="service_type" type="radio" value="self" />
                                            <span><i class="material-icons left">store</i> Self Drop-off & Pick-up</span>
                                        </label>
                                    </p>
                                </div>

                                <div class="row">
                                    <!-- Pickup Schedule -->
                                    <div class="col s12 m6">
                                        <h6 class="blue-text" id="pickup-label">Pickup Details</h6>
                                        <div class="input-field">
                                            <i class="material-icons prefix">event</i>
                                            <input type="date" 
                                                   id="pickup_date" 
                                                   name="pickup_date" 
                                                   class="validate"
                                                   min="<?php echo date('Y-m-d'); ?>"
                                                   max="<?php echo $maxBookingDate; ?>">
                                            <label for="pickup_date">Date</label>
                                        </div>
                                        
                                        <div class="input-field">
                                            <i class="material-icons prefix">access_time</i>
                                            <select id="pickup_time" name="pickup_time">
                                                <option value="" disabled selected>Choose time</option>
                                                <?php foreach($timeSlots as $slot): ?>
                                                    <option value="<?php echo $slot; ?>">
                                                        <?php echo date('g:i A', strtotime($slot)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label>Time</label>
                                        </div>
                                        <div class="red-text" id="pickup-warning" style="display: none;"></div>
                                    </div>

                                    <!-- Delivery Schedule -->
                                    <div class="col s12 m6">
                                        <h6 class="blue-text" id="delivery-label">Delivery Details</h6>
                                        <div class="input-field">
                                            <i class="material-icons prefix">event</i>
                                            <input type="date" 
                                                   id="delivery_date" 
                                                   name="delivery_date" 
                                                   class="validate"
                                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                                   max="<?php echo $maxBookingDate; ?>">
                                            <label for="delivery_date">Date</label>
                                        </div>
                                        
                                        <div class="input-field">
                                            <i class="material-icons prefix">access_time</i>
                                            <select id="delivery_time" name="delivery_time">
                                                <option value="" disabled selected>Choose time</option>
                                                <?php foreach($timeSlots as $slot): ?>
                                                    <option value="<?php echo $slot; ?>">
                                                        <?php echo date('g:i A', strtotime($slot)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label>Time</label>
                                        </div>
                                        <div class="red-text" id="delivery-warning" style="display: none;"></div>
                                    </div>
                                </div>

                                <!-- Special Instructions -->
                                <div class="input-field">
                                    <i class="material-icons prefix">note</i>
                                    <textarea id="special_instructions" name="special_instructions" 
                                              class="materialize-textarea"></textarea>
                                    <label for="special_instructions">Special Instructions (Optional)</label>
                                </div>

                                <!-- Navigation Buttons -->
                                <div class="row">
                                    <div class="col s6">
                                        <button type="button" class="btn waves-effect waves-light prev-step">
                                            <i class="material-icons left">arrow_back</i> Previous
                                        </button>
                                    </div>
                                    <div class="col s6 right-align">
                                        <button type="submit" class="btn waves-effect waves-light">
                                            Place Order <i class="material-icons right">send</i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="col s12 m4">
                        <div class="card sticky-summary">
                            <div class="card-content">
                                <span class="card-title">Order Summary</span>
                                <div id="selected-services"></div>
                                <div class="divider"></div>
                                <p><strong>Subtotal:</strong> $<span id="subtotal">0.00</span></p>
                                <p><strong>Delivery Fee:</strong> $<span id="delivery-fee">0.00</span></p>
                                <p><strong>Total:</strong> $<span id="total">0.00</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        // Add passive event listeners for better performance
        jQuery.event.special.touchstart = {
            setup: function( _, ns, handle ) {
                this.addEventListener("touchstart", handle, { passive: !ns.includes("noPreventDefault") });
            }
        };
        jQuery.event.special.touchmove = {
            setup: function( _, ns, handle ) {
                this.addEventListener("touchmove", handle, { passive: !ns.includes("noPreventDefault") });
            }
        };
        jQuery.event.special.wheel = {
            setup: function( _, ns, handle ){
                this.addEventListener("wheel", handle, { passive: true });
            }
        };
        jQuery.event.special.mousewheel = {
            setup: function( _, ns, handle ){
                this.addEventListener("mousewheel", handle, { passive: true });
            }
        };

        // Update your existing document ready function
        $(document).ready(function() {
            // Initialize Materialize components with passive event listeners
            $('select').formSelect({
                touchstart: { passive: true },
                touchmove: { passive: true }
            });
            
            // Initialize stepper with passive events
            $('.stepper').each(function() {
                $(this).on('touchstart', function(e) {
                    // Your stepper code
                }, { passive: true });
                
                $(this).on('touchmove', function(e) {
                    // Your stepper code
                }, { passive: true });
            });

            // Form submission
            $('form').submit(function(e) {
                // Check if we're on step 3
                if ($('#step3').is(':visible')) {
                    // Validate required fields
                    if (!$('#pickup_date').val()) {
                        M.toast({html: 'Please select pickup date', classes: 'red'});
                        e.preventDefault();
                        return false;
                    }
                    if (!$('#pickup_time').val()) {
                        M.toast({html: 'Please select pickup time', classes: 'red'});
                        e.preventDefault();
                        return false;
                    }
                    if (!$('#delivery_date').val()) {
                        M.toast({html: 'Please select delivery date', classes: 'red'});
                        e.preventDefault();
                        return false;
                    }
                    if (!$('#delivery_time').val()) {
                        M.toast({html: 'Please select delivery time', classes: 'red'});
                        e.preventDefault();
                        return false;
                    }

                    // Validate dates
                    if (!validateDates()) {
                        e.preventDefault();
                        M.toast({html: 'Please correct the scheduling errors', classes: 'red'});
                        return false;
                    }
                }
            });

            // Date validation function
            function validateDates() {
                if (!$('#pickup_date').val() || !$('#pickup_time').val() || 
                    !$('#delivery_date').val() || !$('#delivery_time').val()) {
                    return false;
                }

                const pickupDate = new Date($('#pickup_date').val() + ' ' + $('#pickup_time').val());
                const deliveryDate = new Date($('#delivery_date').val() + ' ' + $('#delivery_time').val());
                const today = new Date();
                let isValid = true;

                // Clear previous warnings
                $('#pickup-warning, #delivery-warning').hide().text('');

                // Validation checks
                if (pickupDate <= today) {
                    $('#pickup-warning').show().text('Pickup must be in the future');
                    isValid = false;
                }

                if (deliveryDate <= pickupDate) {
                    $('#delivery-warning').show().text('Delivery must be after pickup');
                    isValid = false;
                }

                // Business days validation
                const businessDays = '<?php echo $settings['business_days']; ?>'.split(',');
                const pickupDay = pickupDate.toLocaleString('en-us', {weekday: 'long'});
                const deliveryDay = deliveryDate.toLocaleString('en-us', {weekday: 'long'});

                if (!businessDays.includes(pickupDay)) {
                    $('#pickup-warning').show().text('Selected day is not a business day');
                    isValid = false;
                }

                if (!businessDays.includes(deliveryDay)) {
                    $('#delivery-warning').show().text('Selected day is not a business day');
                    isValid = false;
                }

                return isValid;
            }

            // Update total when service type changes
            function updateTotal() {
                const subtotal = parseFloat($('#subtotal').text());
                const isDelivery = $('input[name="service_type"]:checked').val() === 'delivery';
                const deliveryFee = isDelivery ? <?php echo $settings['delivery_fee']; ?> : 0;
                
                $('#delivery-fee').text(deliveryFee.toFixed(2));
                $('#total').text((subtotal + deliveryFee).toFixed(2));
            }

            // Track current step
            let currentStep = 1;
            const totalSteps = 2;

            // Function to update stepper UI
            function updateStepper(step) {
                $('.stepper .step').removeClass('active');
                $('.stepper .step').each(function(index) {
                    if (index < step) {
                        $(this).addClass('completed');
                    } else if (index === step - 1) {
                        $(this).addClass('active');
                    } else {
                        $(this).removeClass('completed');
                    }
                });
            }

            // Function to show/hide steps
            function showStep(step) {
                $('.section-step').hide();
                $(`#step${step}`).show();
                updateStepper(step);
                window.scrollTo(0, 0);
            }

            // Validate Step 1
            function validateStep1() {
                let totalWeight = 0;
                let hasServices = false;
                
                $('.service-weight').each(function() {
                    const weight = parseFloat($(this).val()) || 0;
                    if (weight > 0) {
                        hasServices = true;
                    }
                    totalWeight += weight;
                });

                if (!hasServices) {
                    M.toast({html: 'Please select at least one service', classes: 'red'});
                    return false;
                }

                $('#total_weight').val(totalWeight);
                $('#display_total_weight').text(totalWeight.toFixed(1));
                return true;
            }

            // Next step button handler
            $('.next-step').click(function() {
                let isValid = true;

                switch(currentStep) {
                    case 1:
                        isValid = validateStep1();
                        break;
                }

                if (isValid && currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            });

            // Previous step button handler
            $('.prev-step').click(function() {
                if (currentStep > 1) {
                    currentStep--;
                    showStep(currentStep);
                }
            });

            // Update service type display in summary
            $('input[name="service_type"]').change(function() {
                const type = $(this).val();
                const isDelivery = type === 'delivery';
                $('#delivery-fee-note').toggle(isDelivery);
                updateTotal();
            });

            // Calculate total when weights change
            $('.service-weight').on('input', function() {
                let total = 0;
                let totalWeight = 0;
                const selectedServices = [];

                $('.service-weight').each(function() {
                    const weight = parseFloat($(this).val()) || 0;
                    const price = parseFloat($(this).data('price'));
                    const name = $(this).data('name');
                    
                    if (weight > 0) {
                        total += weight * price;
                        totalWeight += weight;
                        selectedServices.push(`
                            <div class="chip">
                                <i class="material-icons">local_laundry_service</i>
                                ${name} (${weight}kg)
                            </div>
                        `);
                    }
                });

                // Update summary
                $('#selected-services').html(
                    selectedServices.length ? selectedServices.join('') : 
                    '<p class="center-align grey-text">No services selected yet</p>'
                );
                
                $('#estimated_total').text(total.toFixed(2));
                $('#total_weight').val(totalWeight);
                $('#display_total_weight').text(totalWeight.toFixed(1));
                
                // Update delivery fee if applicable
                updateTotal();
            });

            // Initialize first step
            showStep(1);

            // Add these variables at the top to track order summary
            let orderSummary = {
                services: [],
                totalWeight: 0,
                subtotal: 0,
                deliveryFee: <?php echo $settings['delivery_fee']; ?>,
                total: 0
            };

            // Update the service weight change handler
            $('.service-weight').on('input', function() {
                updateOrderSummary();
            });

            // New function to update order summary
            function updateOrderSummary() {
                orderSummary.services = [];
                orderSummary.totalWeight = 0;
                orderSummary.subtotal = 0;

                $('.service-weight').each(function() {
                    const weight = parseFloat($(this).val()) || 0;
                    const price = parseFloat($(this).data('price'));
                    const name = $(this).data('name');
                    
                    if (weight > 0) {
                        orderSummary.services.push({
                            name: name,
                            weight: weight,
                            price: price,
                            subtotal: weight * price
                        });
                        orderSummary.totalWeight += weight;
                        orderSummary.subtotal += weight * price;
                    }
                });

                // Update delivery fee based on service type
                orderSummary.deliveryFee = $('input[name="service_type"]:checked').val() === 'delivery' 
                    ? <?php echo $settings['delivery_fee']; ?> 
                    : 0;
                
                orderSummary.total = orderSummary.subtotal + orderSummary.deliveryFee;

                // Update UI in both steps
                updateOrderSummaryUI();
            }

            // New function to update UI
            function updateOrderSummaryUI() {
                // Generate services HTML
                const servicesHTML = orderSummary.services.length 
                    ? orderSummary.services.map(service => `
                        <div class="chip">
                            <i class="material-icons">local_laundry_service</i>
                            ${service.name} (${service.weight}kg - $${service.subtotal.toFixed(2)})
                        </div>
                    `).join('')
                    : '<p class="center-align grey-text">No services selected yet</p>';

                // Update both summary sections
                $('#step1 #selected-services, #step2 #selected-services').html(servicesHTML);
                
                // Update weight and totals in step 1
                $('#display_total_weight').text(orderSummary.totalWeight.toFixed(1));
                $('#total_weight').val(orderSummary.totalWeight);
                $('#estimated_total').text(orderSummary.total.toFixed(2));
                
                // Update totals in step 2
                $('#subtotal').text(orderSummary.subtotal.toFixed(2));
                $('#delivery-fee').text(orderSummary.deliveryFee.toFixed(2));
                $('#total').text(orderSummary.total.toFixed(2));
                
                // Show/hide delivery fee note
                $('#delivery-fee-note').toggle(orderSummary.deliveryFee > 0);
            }

            // Update service type handler
            $('input[name="service_type"]').change(function() {
                updateOrderSummary();
            });

            // Modify the validateStep1 function
            function validateStep1() {
                if (orderSummary.services.length === 0) {
                    M.toast({html: 'Please select at least one service', classes: 'red'});
                    return false;
                }
                return true;
            }

            // Add this at the beginning
            const userBlocked = <?php echo $isBlocked ? 'true' : 'false'; ?>;
            
            if (userBlocked) {
                // Disable all form inputs
                $('input, select, textarea').prop('disabled', true);
                
                // Disable all buttons
                $('.btn, .btn-large').addClass('disabled');
                
                // Prevent form submission
                $('#bookingForm').on('submit', function(e) {
                    e.preventDefault();
                    M.toast({html: '<?php echo $blockMessage; ?>', classes: 'red'});
                    return false;
                });
                
                // Disable weight input changes
                $('.service-weight').off('input');
                
                // Disable service type radio changes
                $('input[name="service_type"]').off('change');
                
                // Disable step navigation
                $('.next-step, .prev-step').off('click').on('click', function(e) {
                    e.preventDefault();
                    M.toast({html: '<?php echo $blockMessage; ?>', classes: 'red'});
                    return false;
                });
            }
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html> 