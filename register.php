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
</head>
<body>
    <div class="container">
        <h2 class="center-align">Register</h2>
        <div class="row">
            <form class="col s12" method="POST" action="">
                <div class="row">
                    <div class="input-field col s6">
                        <input id="username" name="username" type="text" required>
                        <label for="username">Username</label>
                    </div>
                    <div class="input-field col s6">
                        <input id="email" name="email" type="email" required>
                        <label for="email">Email</label>
                    </div>
                </div>
                <div class="row">
                    <div class="input-field col s12">
                        <input id="password" name="password" type="password" required>
                        <label for="password">Password</label>
                    </div>
                </div>
                <div class="row">
                    <div class="input-field col s12">
                        <input id="full_name" name="full_name" type="text" required>
                        <label for="full_name">Full Name</label>
                    </div>
                </div>
                <div class="row">
                    <div class="input-field col s6">
                        <input id="phone" name="phone" type="text" required>
                        <label for="phone">Phone</label>
                    </div>
                    <div class="input-field col s6">
                        <textarea id="address" name="address" class="materialize-textarea" required></textarea>
                        <label for="address">Address</label>
                    </div>
                </div>
                <div class="row center-align">
                    <button class="btn waves-effect waves-light" type="submit">Register</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html> 