<?php
require_once '../includes/admin_middleware.php';
require_once '../includes/db_connect.php';

checkAdminAccess();

// Get admin user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            $stmt = $pdo->prepare("UPDATE users SET 
                username = ?, 
                full_name = ?, 
                email = ?, 
                phone = ?, 
                address = ? 
                WHERE user_id = ?");
            
            $stmt->execute([
                $_POST['username'],
                $_POST['full_name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['address'],
                $_SESSION['user_id']
            ]);
            
            $success_message = "Profile updated successfully!";
        }
        
        if (isset($_POST['update_password'])) {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $current_hash = $stmt->fetchColumn();
            
            if (!password_verify($_POST['current_password'], $current_hash)) {
                throw new Exception("Current password is incorrect");
            }
            
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                throw new Exception("New passwords do not match");
            }
            
            $new_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$new_hash, $_SESSION['user_id']]);
            
            $success_message = "Password updated successfully!";
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .profile-header {
            padding: 20px;
            margin-bottom: 20px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e0e0e0;
            border-radius: 50%;
        }
        .profile-avatar i {
            font-size: 100px;
            color: #757575;
        }
        .card-panel {
            border-radius: 8px;
        }
        .input-field .prefix {
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="grey lighten-4">
    <?php include 'includes/admin_nav.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col s12">
                <div class="profile-header center-align">
                    <div class="profile-avatar">
                        <i class="material-icons">account_circle</i>
                    </div>
                    <h4><?php echo htmlspecialchars($admin['full_name']); ?></h4>
                    <p class="grey-text">Administrator</p>
                </div>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="row">
                <div class="col s12">
                    <div class="card-panel green lighten-4 green-text text-darken-4">
                        <i class="material-icons left">check_circle</i>
                        <?php echo $success_message; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="row">
                <div class="col s12">
                    <div class="card-panel red lighten-4 red-text text-darken-4">
                        <i class="material-icons left">error</i>
                        <?php echo $error_message; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col s12 m6">
                <div class="card-panel">
                    <h5><i class="material-icons left">person</i>Profile Information</h5>
                    <div class="divider"></div>
                    <br>
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="input-field">
                            <i class="material-icons prefix">account_circle</i>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                            <label for="username">Username</label>
                        </div>

                        <div class="input-field">
                            <i class="material-icons prefix">badge</i>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                            <label for="full_name">Full Name</label>
                        </div>

                        <div class="input-field">
                            <i class="material-icons prefix">email</i>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            <label for="email">Email</label>
                        </div>

                        <div class="input-field">
                            <i class="material-icons prefix">phone</i>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>">
                            <label for="phone">Phone</label>
                        </div>

                        <div class="input-field">
                            <i class="material-icons prefix">home</i>
                            <textarea id="address" name="address" class="materialize-textarea"><?php echo htmlspecialchars($admin['address']); ?></textarea>
                            <label for="address">Address</label>
                        </div>

                        <button class="btn waves-effect waves-light blue" type="submit">
                            <i class="material-icons left">save</i>
                            Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="col s12 m6">
                <div class="card-panel">
                    <h5><i class="material-icons left">lock</i>Change Password</h5>
                    <div class="divider"></div>
                    <br>
                    <form method="POST" action="" id="passwordForm">
                        <input type="hidden" name="update_password" value="1">
                        
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

                        <button class="btn waves-effect waves-light green" type="submit">
                            <i class="material-icons left">security</i>
                            Change Password
                        </button>
                    </form>
                </div>

                <!-- Account Information -->
                <div class="card-panel">
                    <h5><i class="material-icons left">info</i>Account Information</h5>
                    <div class="divider"></div>
                    <br>
                    <p><strong>Account Type:</strong> Administrator</p>
                    <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($admin['created_at'])); ?></p>
                    <p><strong>Last Login:</strong> <?php echo date('F j, Y H:i', strtotime($admin['last_login'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            M.updateTextFields();
            $('.materialize-textarea').characterCounter();

            // Password validation
            $('#passwordForm').on('submit', function(e) {
                const newPass = $('#new_password').val();
                const confirmPass = $('#confirm_password').val();

                if (newPass !== confirmPass) {
                    e.preventDefault();
                    M.toast({html: '<i class="material-icons left">error</i> Passwords do not match!', classes: 'red'});
                    return false;
                }

                if (newPass.length < 6) {
                    e.preventDefault();
                    M.toast({html: '<i class="material-icons left">error</i> Password must be at least 6 characters!', classes: 'red'});
                    return false;
                }
            });
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html> 