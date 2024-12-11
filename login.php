<?php
session_start();
require_once 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
            
            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: customer/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } catch(PDOException $e) {
        $error = "Login failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Laundry Service</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        main {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
        }
        .login-card {
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        .login-header {
            text-align: center;
            color: #26a69a;
            margin-bottom: 2rem;
        }
        .login-header i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .input-field input:focus + label {
            color: #26a69a !important;
        }
        .input-field input:focus {
            border-bottom: 1px solid #26a69a !important;
            box-shadow: 0 1px 0 0 #26a69a !important;
        }
        .btn-large {
            width: 100%;
            margin-top: 1rem;
        }
        .social-login {
            margin-top: 2rem;
            text-align: center;
        }
        .divider {
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <main>
        <div class="container">
            <div class="row">
                <div class="col s12 m8 offset-m2 l6 offset-l3">
                    <div class="card login-card">
                        <div class="login-header">
                            <i class="material-icons">local_laundry_service</i>
                            <h4>Welcome Back</h4>
                            <p class="grey-text">Please login to your account</p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="card-panel red lighten-4 red-text center-align">
                                <i class="material-icons tiny">error</i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="input-field">
                                <i class="material-icons prefix">person</i>
                                <input id="username" name="username" type="text" required>
                                <label for="username">Username</label>
                            </div>

                            <div class="input-field">
                                <i class="material-icons prefix">lock</i>
                                <input id="password" name="password" type="password" required>
                                <label for="password">Password</label>
                            </div>

                            <div class="row">
                                <div class="col s12 m6">
                                    <label>
                                        <input type="checkbox" />
                                        <span>Remember me</span>
                                    </label>
                                </div>
                                <div class="col s12 m6 right-align">
                                    <a href="forgot-password.php" class="teal-text">Forgot Password?</a>
                                </div>
                            </div>

                            <button class="btn-large waves-effect waves-light" type="submit">
                                Login <i class="material-icons right">send</i>
                            </button>
                        </form>

                        <div class="divider"></div>

                        <div class="center-align">
                            <p>Don't have an account? <a href="register.php" class="teal-text">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html> 