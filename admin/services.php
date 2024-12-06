<?php
require_once '../includes/admin_middleware.php';
require_once '../includes/db_connect.php';

checkAdminAccess();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT INTO services (service_name, description, price_per_kg, service_type) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$_POST['service_name'], $_POST['description'], $_POST['price_per_kg'], $_POST['service_type']]);
                    $response = ['success' => true, 'message' => 'Service added successfully'];
                    break;

                case 'update':
                    $stmt = $pdo->prepare("UPDATE services SET service_name = ?, description = ?, price_per_kg = ?, service_type = ?, status = ? WHERE service_id = ?");
                    $stmt->execute([
                        $_POST['service_name'],
                        $_POST['description'],
                        $_POST['price_per_kg'],
                        $_POST['service_type'],
                        $_POST['status'],
                        $_POST['service_id']
                    ]);
                    $response = ['success' => true, 'message' => 'Service updated successfully'];
                    break;

                case 'delete':
                    // Check if service is used in any orders
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE service_id = ?");
                    $stmt->execute([$_POST['service_id']]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Cannot delete: Service is used in existing orders");
                    }

                    $stmt = $pdo->prepare("DELETE FROM services WHERE service_id = ?");
                    $stmt->execute([$_POST['service_id']]);
                    $response = ['success' => true, 'message' => 'Service deleted successfully'];
                    break;
            }
        } catch (Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        header('Location: services.php');
        exit();
    }
}

// Get all services
$services = $pdo->query("SELECT * FROM services ORDER BY service_name")->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Service Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .page-header { 
            padding: 20px 0;
            background: #f5f5f5;
            margin-bottom: 20px;
        }
        .service-card {
            padding: 20px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .table-container {
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background: white;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            color: white;
            font-size: 0.9rem;
        }
        .status-active { background-color: #4CAF50; }
        .status-inactive { background-color: #9e9e9e; }
        .service-type {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .type-wash { background-color: #e3f2fd; color: #1565c0; }
        .type-dry_clean { background-color: #f3e5f5; color: #7b1fa2; }
        .type-iron { background-color: #fff3e0; color: #ef6c00; }
        .type-special { background-color: #e8f5e9; color: #2e7d32; }
        .price-tag {
            font-weight: bold;
            color: #2196f3;
        }
    </style>
</head>

<body class="grey lighten-4">
    <?php include 'includes/admin_nav.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="row valign-wrapper">
                <div class="col s6">
                    <h4 class="grey-text text-darken-2">Service Management</h4>
                </div>
                <div class="col s6 right-align">
                    <button class="btn-large waves-effect waves-light modal-trigger" data-target="addServiceModal">
                        <i class="material-icons left">add</i>Add New Service
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Service Type Stats -->
        <div class="row">
            <?php
            $serviceTypes = ['wash', 'dry_clean', 'iron', 'special'];
            foreach($serviceTypes as $type):
                $count = count(array_filter($services, function($s) use ($type) { 
                    return $s['service_type'] == $type && $s['status'] == 'active'; 
                }));
            ?>
            <div class="col s12 m3">
                <div class="service-card white">
                    <div class="center-align">
                        <i class="material-icons medium type-<?php echo $type; ?>">
                            <?php
                            echo match($type) {
                                'wash' => 'local_laundry_service',
                                'dry_clean' => 'dry_cleaning',
                                'iron' => 'iron',
                                'special' => 'star',
                            };
                            ?>
                        </i>
                        <h5 class="grey-text"><?php echo ucfirst(str_replace('_', ' ', $type)); ?></h5>
                        <div class="price-tag"><?php echo $count; ?> Active Services</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Services Table -->
        <div class="row">
            <div class="col s12">
                <div class="table-container">
                    <table class="striped responsive-table">
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Description</th>
                                <th>Price/kg</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td><strong><?php echo $service['service_name']; ?></strong></td>
                                <td><?php echo $service['description']; ?></td>
                                <td class="price-tag">$<?php echo number_format($service['price_per_kg'], 2); ?></td>
                                <td>
                                    <span class="service-type type-<?php echo $service['service_type']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $service['service_type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $service['status']; ?>">
                                        <?php echo ucfirst($service['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-floating waves-effect waves-light blue modal-trigger" 
                                            data-target="editServiceModal"
                                            onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                        <i class="material-icons">edit</i>
                                    </button>
                                    <button class="btn-floating waves-effect waves-light red modal-trigger"
                                            data-target="deleteServiceModal"
                                            onclick="deleteService(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                        <i class="material-icons">delete</i>
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

    <!-- Update modal styles -->
    <div id="addServiceModal" class="modal">
        <div class="modal-content">
            <h4><i class="material-icons left">add_circle</i> Add New Service</h4>
            <div class="divider"></div>
            <br>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="input-field">
                    <input id="service_name" name="service_name" type="text" required>
                    <label for="service_name">Service Name</label>
                </div>
                <div class="input-field">
                    <textarea id="description" name="description" class="materialize-textarea"></textarea>
                    <label for="description">Description</label>
                </div>
                <div class="input-field">
                    <input id="price_per_kg" name="price_per_kg" type="number" step="0.01" required>
                    <label for="price_per_kg">Price per KG</label>
                </div>
                <div class="input-field">
                    <select name="service_type" required>
                        <option value="wash">Wash</option>
                        <option value="dry_clean">Dry Clean</option>
                        <option value="iron">Iron</option>
                        <option value="special">Special</option>
                    </select>
                    <label>Service Type</label>
                </div>
                <button class="btn waves-effect waves-light" type="submit">Add Service</button>
            </form>
        </div>
    </div>

    <div id="editServiceModal" class="modal">
        <div class="modal-content">
            <h4><i class="material-icons left">edit</i> Edit Service</h4>
            <div class="divider"></div>
            <br>
            <form method="POST" action="" id="editServiceForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="service_id" id="edit_service_id">

                <div class="input-field">
                    <input id="edit_service_name" name="service_name" type="text" required>
                    <label for="edit_service_name">Service Name</label>
                </div>

                <div class="input-field">
                    <textarea id="edit_description" name="description" class="materialize-textarea"></textarea>
                    <label for="edit_description">Description</label>
                </div>

                <div class="input-field">
                    <input id="edit_price_per_kg" name="price_per_kg" type="number" step="0.01" required>
                    <label for="edit_price_per_kg">Price per KG</label>
                </div>

                <div class="input-field">
                    <select name="service_type" id="edit_service_type" required>
                        <option value="wash">Wash</option>
                        <option value="dry_clean">Dry Clean</option>
                        <option value="iron">Iron</option>
                        <option value="special">Special</option>
                    </select>
                    <label>Service Type</label>
                </div>

                <div class="input-field">
                    <select name="status" id="edit_status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <label>Status</label>
                </div>

                <button class="btn waves-effect waves-light" type="submit">Update Service</button>
            </form>
        </div>
    </div>

    <div id="deleteServiceModal" class="modal">
        <div class="modal-content">
            <h4><i class="material-icons left">delete</i> Delete Service</h4>
            <div class="divider"></div>
            <br>
            <p>Are you sure you want to delete this service? This action cannot be undone.</p>
            <input type="hidden" id="delete_service_id">
            <div class="service-details">
                <p><strong>Service Name:</strong> <span id="delete_service_name"></span></p>
                <p><strong>Type:</strong> <span id="delete_service_type"></span></p>
                <p><strong>Price:</strong> $<span id="delete_service_price"></span> per kg</p>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-close waves-effect waves-green btn-flat">Cancel</a>
            <button class="btn-flat red-text waves-effect waves-red" onclick="confirmDelete()">Delete</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Materialize components
            $('.modal').modal();
            $('select').formSelect();
            M.updateTextFields(); // Ensure labels are active for pre-filled inputs

            // Function to edit service
            window.editService = function(service) {
                // Populate the edit form with service data
                $('#edit_service_id').val(service.service_id);
                $('#edit_service_name').val(service.service_name);
                $('#edit_description').val(service.description);
                $('#edit_price_per_kg').val(service.price_per_kg);
                $('#edit_service_type').val(service.service_type);
                $('#edit_status').val(service.status);

                // Reinitialize Materialize select dropdowns
                $('select').formSelect();

                // Update labels to active state
                M.updateTextFields();

                // Initialize textarea
                M.textareaAutoResize($('#edit_description'));

                // Open the modal
                $('#editServiceModal').modal('open');
            };

            // Function to prepare delete modal
            window.deleteService = function(service) {
                $('#delete_service_id').val(service.service_id);
                $('#delete_service_name').text(service.service_name);
                $('#delete_service_type').text(service.service_type.replace('_', ' '));
                $('#delete_service_price').text(service.price_per_kg);
                $('#deleteServiceModal').modal('open');
            };

            // Handle Add Service Form Submission
            $('form[action=""][method="POST"]').on('submit', function(e) {
                if ($(this).find('input[name="action"]').val() === 'add') {
                    e.preventDefault();
                    const submitBtn = $(this).find('button[type="submit"]');
                    submitBtn.attr('disabled', true)
                        .html('<i class="material-icons left">refresh</i> Adding...');

                    $.ajax({
                        url: 'services.php',
                        method: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json'
                    })
                    .done(function(response) {
                        if (response.success) {
                            M.toast({
                                html: '<i class="material-icons left">check</i> Service added successfully',
                                classes: 'rounded green'
                            });
                            $('#addServiceModal').modal('close');
                            location.reload();
                        } else {
                            M.toast({
                                html: '<i class="material-icons left">error</i> ' + response.message,
                                classes: 'rounded red'
                            });
                        }
                    })
                    .fail(function() {
                        M.toast({
                            html: '<i class="material-icons left">error</i> Error adding service',
                            classes: 'rounded red'
                        });
                    })
                    .always(function() {
                        submitBtn.attr('disabled', false)
                            .html('<i class="material-icons left">add</i> Add Service');
                    });
                }
            });

            // Handle Edit Service Form Submission
            $('#editServiceForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.attr('disabled', true)
                    .html('<i class="material-icons left">refresh</i> Updating...');

                $.ajax({
                    url: 'services.php',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json'
                })
                .done(function(response) {
                    if (response.success) {
                        M.toast({
                            html: '<i class="material-icons left">check</i> ' + response.message,
                            classes: 'rounded green'
                        });
                        $('#editServiceModal').modal('close');
                        location.reload();
                    } else {
                        M.toast({
                            html: '<i class="material-icons left">error</i> ' + response.message,
                            classes: 'rounded red'
                        });
                    }
                })
                .fail(function() {
                    M.toast({
                        html: '<i class="material-icons left">error</i> Error updating service',
                        classes: 'rounded red'
                    });
                })
                .always(function() {
                    submitBtn.attr('disabled', false)
                        .html('<i class="material-icons left">save</i> Update Service');
                });
            });

            // Handle Delete Service
            window.confirmDelete = function() {
                const serviceId = $('#delete_service_id').val();
                const deleteBtn = $('#deleteServiceModal .modal-footer button');
                deleteBtn.attr('disabled', true)
                    .html('<i class="material-icons left">refresh</i> Deleting...');

                $.ajax({
                    url: 'services.php',
                    method: 'POST',
                    data: {
                        action: 'delete',
                        service_id: serviceId
                    },
                    dataType: 'json'
                })
                .done(function(response) {
                    if (response.success) {
                        M.toast({
                            html: '<i class="material-icons left">check</i> Service deleted successfully',
                            classes: 'rounded green'
                        });
                        $('#deleteServiceModal').modal('close');
                        location.reload();
                    } else {
                        M.toast({
                            html: '<i class="material-icons left">error</i> ' + response.message,
                            classes: 'rounded red'
                        });
                    }
                })
                .fail(function() {
                    M.toast({
                        html: '<i class="material-icons left">error</i> Error deleting service',
                        classes: 'rounded red'
                    });
                })
                .always(function() {
                    deleteBtn.attr('disabled', false)
                        .html('<i class="material-icons left">delete</i> Delete');
                });
            };

            // Form validation
            function validateForm(form) {
                let isValid = true;
                const requiredFields = form.find('[required]');
                
                requiredFields.each(function() {
                    if (!$(this).val()) {
                        const fieldName = $(this).attr('name').replace('_', ' ');
                        M.toast({
                            html: `<i class="material-icons left">warning</i> ${fieldName} is required`,
                            classes: 'rounded orange'
                        });
                        isValid = false;
                    }
                });

                // Validate price is positive
                const priceField = form.find('input[name="price_per_kg"]');
                if (priceField.length && parseFloat(priceField.val()) <= 0) {
                    M.toast({
                        html: '<i class="material-icons left">warning</i> Price must be greater than 0',
                        classes: 'rounded orange'
                    });
                    isValid = false;
                }

                return isValid;
            }

            // Add validation to forms before submission
            $('form').on('submit', function(e) {
                if (!validateForm($(this))) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>