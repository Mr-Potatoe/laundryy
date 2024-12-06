<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
        $stmt->execute([
            $_POST['full_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_SESSION['user_id']
        ]);
        $success = "Profile updated successfully";
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        if (password_verify($_POST['current_password'], $user['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$new_password, $_SESSION['user_id']]);
                $success = "Password changed successfully";
            } else {
                $error = "New passwords do not match";
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
    
    // Refresh user data after update
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(to right, #26a69a, #4db6ac);
            color: white;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        .profile-avatar i {
            font-size: 60px;
            color: #26a69a;
        }
        .profile-card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-card .card-content {
            padding: 30px;
        }
        .input-field {
            margin-bottom: 25px;
        }
        .btn-update {
            width: 100%;
            margin-top: 20px;
        }
        .password-requirements {
            font-size: 0.9rem;
            color: #666;
            margin: 10px 0;
        }
        .requirement {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }
        .requirement i {
            font-size: 16px;
            margin-right: 5px;
        }
        .valid {
            color: #4CAF50;
        }
        .invalid {
            color: #FF5252;
        }
    </style>
</head>
<body>
    <?php include 'includes/customer_nav.php'; ?>
    
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header center-align">
            <div class="profile-avatar">
                <i class="material-icons">account_circle</i>
            </div>
            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
            <p>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="card-panel green lighten-4 green-text center-align">
                <i class="material-icons left">check_circle</i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="card-panel red lighten-4 red-text center-align">
                <i class="material-icons left">error</i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Profile Information -->
            <div class="col s12 m6">
                <div class="card profile-card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">person</i>
                            Profile Information
                        </span>
                        <form method="POST" action="" id="profileForm">
                            <div class="input-field">
                                <i class="material-icons prefix">account_circle</i>
                                <input type="text" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                <label for="full_name">Full Name</label>
                            </div>
                            
                            <div class="input-field">
                                <i class="material-icons prefix">email</i>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <label for="email">Email</label>
                            </div>
                            
                            <div class="input-field">
                                <i class="material-icons prefix">phone</i>
                                <input type="text" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                <label for="phone">Phone</label>
                            </div>
                            
                            <div class="input-field">
                                <i class="material-icons prefix">home</i>
                                <textarea id="address" name="address" 
                                          class="materialize-textarea" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                                <label for="address">Address</label>
                            </div>
                            
                            <button type="submit" name="update_profile" 
                                    class="btn-large waves-effect waves-light btn-update">
                                <i class="material-icons left">save</i>
                                Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Password Change -->
            <div class="col s12 m6">
                <div class="card profile-card">
                    <div class="card-content">
                        <span class="card-title">
                            <i class="material-icons left">lock</i>
                            Change Password
                        </span>
                        <form method="POST" action="" id="passwordForm">
                            <div class="input-field">
                                <i class="material-icons prefix">lock_outline</i>
                                <input type="password" id="current_password" name="current_password" required>
                                <label for="current_password">Current Password</label>
                            </div>
                            
                            <div class="input-field">
                                <i class="material-icons prefix">lock</i>
                                <input type="password" id="new_password" name="new_password" required>
                                <label for="new_password">New Password</label>
                            </div>
                            
                            <div class="input-field">
                                <i class="material-icons prefix">lock</i>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <label for="confirm_password">Confirm New Password</label>
                            </div>

                            <div class="password-requirements">
                                <p><strong>Password Requirements:</strong></p>
                                <div class="requirement" id="length-check">
                                    <i class="material-icons">check_circle</i>
                                    At least 8 characters
                                </div>
                                <div class="requirement" id="uppercase-check">
                                    <i class="material-icons">check_circle</i>
                                    One uppercase letter
                                </div>
                                <div class="requirement" id="number-check">
                                    <i class="material-icons">check_circle</i>
                                    One number
                                </div>
                                <div class="requirement" id="match-check">
                                    <i class="material-icons">check_circle</i>
                                    Passwords match
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" 
                                    class="btn-large waves-effect waves-light red btn-update">
                                <i class="material-icons left">lock</i>
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            M.updateTextFields();
            M.textareaAutoResize($('#address'));

            // Password validation
            $('#new_password, #confirm_password').on('input', function() {
                const password = $('#new_password').val();
                const confirm = $('#confirm_password').val();

                // Length check
                if(password.length >= 8) {
                    $('#length-check').addClass('valid').removeClass('invalid');
                    $('#length-check i').text('check_circle');
                } else {
                    $('#length-check').addClass('invalid').removeClass('valid');
                    $('#length-check i').text('cancel');
                }

                // Uppercase check
                if(/[A-Z]/.test(password)) {
                    $('#uppercase-check').addClass('valid').removeClass('invalid');
                    $('#uppercase-check i').text('check_circle');
                } else {
                    $('#uppercase-check').addClass('invalid').removeClass('valid');
                    $('#uppercase-check i').text('cancel');
                }

                // Number check
                if(/\d/.test(password)) {
                    $('#number-check').addClass('valid').removeClass('invalid');
                    $('#number-check i').text('check_circle');
                } else {
                    $('#number-check').addClass('invalid').removeClass('valid');
                    $('#number-check i').text('cancel');
                }

                // Match check
                if(password === confirm && password !== '') {
                    $('#match-check').addClass('valid').removeClass('invalid');
                    $('#match-check i').text('check_circle');
                } else {
                    $('#match-check').addClass('invalid').removeClass('valid');
                    $('#match-check i').text('cancel');
                }
            });
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html> 