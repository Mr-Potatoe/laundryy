<?php
require_once 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $full_name, $phone, $address]);
        header("Location: login.php?message=Registration successful");
    } catch(PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Laundry Service</title>
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
            padding: 2rem 0;
        }
        .register-card {
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .register-header {
            text-align: center;
            color: #26a69a;
            margin-bottom: 2rem;
        }
        .register-header i {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .input-field input:focus + label,
        .input-field textarea:focus + label {
            color: #26a69a !important;
        }
        .input-field input:focus,
        .input-field textarea:focus {
            border-bottom: 1px solid #26a69a !important;
            box-shadow: 0 1px 0 0 #26a69a !important;
        }
        .btn-large {
            width: 100%;
            margin-top: 1rem;
        }
        .progress-section {
            margin: 2rem 0;
        }
        .step {
            text-align: center;
            color: #9e9e9e;
        }
        .step.active {
            color: #26a69a;
        }
        .step i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <main>
        <div class="container">
            <div class="row">
                <div class="col s12 m10 offset-m1">
                    <div class="card register-card">
                        <div class="register-header">
                            <i class="material-icons">person_add</i>
                            <h4>Create Account</h4>
                            <p class="grey-text">Join our laundry service today</p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="card-panel red lighten-4 red-text center-align">
                                <i class="material-icons tiny">error</i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="registerForm">
                            <div class="row">
                                <div class="input-field col s12 m6">
                                    <i class="material-icons prefix">account_circle</i>
                                    <input id="username" name="username" type="text" required>
                                    <label for="username">Username</label>
                                </div>
                                <div class="input-field col s12 m6">
                                    <i class="material-icons prefix">email</i>
                                    <input id="email" name="email" type="email" required>
                                    <label for="email">Email</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="input-field col s12 m6">
                                    <i class="material-icons prefix">lock</i>
                                    <input id="password" name="password" type="password" required>
                                    <label for="password">Password</label>
                                </div>
                                <div class="input-field col s12 m6">
                                    <i class="material-icons prefix">lock_outline</i>
                                    <input id="confirm_password" type="password" required>
                                    <label for="confirm_password">Confirm Password</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="input-field col s12">
                                    <i class="material-icons prefix">person</i>
                                    <input id="full_name" name="full_name" type="text" required>
                                    <label for="full_name">Full Name</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="input-field col s12 m6">
                                    <i class="material-icons prefix">phone</i>
                                    <input id="phone" name="phone" type="tel" required>
                                    <label for="phone">Phone</label>
                                </div>
                                <div class="input-field col s12 m6">
                                    <i class="material-icons prefix">location_on</i>
                                    <textarea id="address" name="address" class="materialize-textarea" required></textarea>
                                    <label for="address">Address</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col s12">
                                    <label>
                                        <input type="checkbox" required />
                                        <span>I agree to the <a href="#" class="teal-text">Terms and Conditions</a></span>
                                    </label>
                                </div>
                            </div>

                            <button class="btn-large waves-effect waves-light" type="submit">
                                Register <i class="material-icons right">how_to_reg</i>
                            </button>
                        </form>

                        <div class="center-align" style="margin-top: 2rem;">
                            <p>Already have an account? <a href="login.php" class="teal-text">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Materialize components
            M.updateTextFields();
            $('.materialize-textarea').characterCounter();

            // Form validation
            $('#registerForm').on('submit', function(e) {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();

                if (password !== confirmPassword) {
                    e.preventDefault();
                    M.toast({html: 'Passwords do not match!', classes: 'red'});
                }
            });
        });
    </script>
</body>
</html> 