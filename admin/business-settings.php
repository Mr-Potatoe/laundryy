<?php
require_once '../includes/admin_middleware.php';
require_once '../includes/db_connect.php';

checkAdminAccess();

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        if ($_POST['action'] === 'delete') {
            // Don't actually delete, just reset to defaults
            $stmt = $pdo->prepare("
                UPDATE business_settings SET 
                    business_days = 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
                    opening_time = '09:00:00',
                    closing_time = '17:00:00',
                    slot_duration = 60,
                    advance_booking_days = 30,
                    delivery_fee = 5.00
                WHERE setting_id = ?
            ");
            $stmt->execute([$_POST['setting_id']]);
            
            $response = ['success' => true, 'message' => 'Settings reset to defaults'];
        }
    } catch (PDOException $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update':
                    $businessDays = implode(',', $_POST['business_days']);
                    
                    $stmt = $pdo->prepare("
                        UPDATE business_settings SET 
                            opening_time = ?,
                            closing_time = ?,
                            business_days = ?,
                            slot_duration = ?,
                            advance_booking_days = ?,
                            delivery_fee = ?
                        WHERE setting_id = ?
                    ");
                    
                    $stmt->execute([
                        $_POST['opening_time'],
                        $_POST['closing_time'],
                        $businessDays,
                        $_POST['slot_duration'],
                        $_POST['advance_booking_days'],
                        $_POST['delivery_fee'],
                        $_POST['setting_id']
                    ]);
                    
                    $success_message = "Settings updated successfully!";
                    break;
            }
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all settings
$settings = $pdo->query("SELECT * FROM business_settings")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Business Settings Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .action-buttons { margin-top: 20px; }
        .settings-card { margin: 10px 0; }
    </style>
</head>

<body class="grey lighten-4">
    <?php include 'includes/admin_nav.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col s12">
                <h4><i class="material-icons left">settings</i> Business Settings Management</h4>
                
                <?php if (isset($success_message)): ?>
                    <div class="card-panel green lighten-4 green-text text-darken-4">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php foreach ($settings as $setting): ?>
                    <div class="card settings-card">
                        <div class="card-content">
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="setting_id" value="<?php echo $setting['setting_id']; ?>">
                                
                                <div class="row">
                                    <div class="col s12 m6">
                                        <h5>Business Hours</h5>
                                        <div class="input-field">
                                            <input type="time" name="opening_time" 
                                                   value="<?php echo $setting['opening_time']; ?>" required>
                                            <label>Opening Time</label>
                                        </div>
                                        <div class="input-field">
                                            <input type="time" name="closing_time" 
                                                   value="<?php echo $setting['closing_time']; ?>" required>
                                            <label>Closing Time</label>
                                        </div>

                                        <h5>Business Days</h5>
                                        <div class="business-days">
                                            <?php
                                            $current_days = explode(',', $setting['business_days']);
                                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                            foreach ($days as $day):
                                            ?>
                                            <label>
                                                <input type="checkbox" name="business_days[]" 
                                                       value="<?php echo $day; ?>" class="filled-in"
                                                       <?php echo in_array($day, $current_days) ? 'checked' : ''; ?>>
                                                <span><?php echo $day; ?></span>
                                            </label><br>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col s12 m6">
                                        <h5>Other Settings</h5>
                                        <div class="input-field">
                                            <input type="number" name="slot_duration" 
                                                   value="<?php echo $setting['slot_duration']; ?>" required>
                                            <label>Time Slot Duration (minutes)</label>
                                        </div>
                                        <div class="input-field">
                                            <input type="number" name="advance_booking_days" 
                                                   value="<?php echo $setting['advance_booking_days']; ?>" required>
                                            <label>Advance Booking Days</label>
                                        </div>
                                        <div class="input-field">
                                            <input type="number" step="0.01" name="delivery_fee" 
                                                   value="<?php echo $setting['delivery_fee']; ?>" required>
                                            <label>Delivery Fee</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="action-buttons right-align">
                                    <button type="submit" class="btn waves-effect waves-light blue">
                                        <i class="material-icons left">save</i> Update
                                    </button>
                                    <button type="button" class="btn waves-effect waves-light red reset-settings" 
                                            data-id="<?php echo $setting['setting_id']; ?>">
                                        <i class="material-icons left">refresh</i> Reset to Defaults
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Materialize components
            M.updateTextFields();

            // Form validation
            $('.settings-form').on('submit', function(e) {
                if ($('input[name="business_days[]"]:checked').length === 0) {
                    e.preventDefault();
                    M.toast({html: 'Please select at least one business day'});
                    return false;
                }
            });

            // Reset settings
            $('.reset-settings').click(function() {
                if (confirm('Are you sure you want to reset these settings to defaults?')) {
                    const settingId = $(this).data('id');
                    const button = $(this);
                    
                    button.attr('disabled', true);
                    
                    $.post('business-settings.php', {
                        action: 'delete',
                        setting_id: settingId
                    })
                    .done(function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            M.toast({html: data.message});
                            location.reload();
                        } else {
                            M.toast({html: 'Error: ' + data.message, classes: 'red'});
                        }
                    })
                    .fail(function() {
                        M.toast({html: 'Error resetting settings', classes: 'red'});
                    })
                    .always(function() {
                        button.attr('disabled', false);
                    });
                }
            });
        });
    </script>
</body>
</html> 