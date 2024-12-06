<nav class="blue darken-3">
    <div class="nav-wrapper container">
        <a href="dashboard.php" class="brand-logo">
            <i class="material-icons left">local_laundry_service</i>
            <span class="hide-on-small-only">Laundry Service</span>
        </a>
        <a href="#" data-target="mobile-nav" class="sidenav-trigger"><i class="material-icons">menu</i></a>
        
        <ul class="right hide-on-med-and-down">
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="dashboard.php"><i class="material-icons left">dashboard</i>Dashboard</a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'book-service.php' ? 'active' : ''; ?>">
                <a href="book-service.php"><i class="material-icons left">add_circle</i>Book Service</a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                <a href="orders.php">
                    <i class="material-icons left">list</i>My Orders
                    <?php 
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $pending_count = $stmt->fetchColumn();
                    if($pending_count > 0): 
                    ?>
                    <span class="new badge red"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <a href="profile.php"><i class="material-icons left">person</i>Profile</a>
            </li>
            <li>
                <a href="../logout.php"><i class="material-icons left">exit_to_app</i>Logout</a>
            </li>
        </ul>
    </div>
</nav>

<!-- Mobile Sidenav -->
<ul class="sidenav" id="mobile-nav">
    <li>
        <div class="user-view">
            <div class="background blue darken-3"></div>
            <a href="profile.php"><i class="material-icons white-text large">account_circle</i></a>
            <a href="profile.php"><span class="white-text name"><?php echo $_SESSION['username']; ?></span></a>
            <span class="white-text email"><?php echo $_SESSION['email']; ?></span>
        </div>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <a href="dashboard.php" class="waves-effect"><i class="material-icons">dashboard</i>Dashboard</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'book-service.php' ? 'active' : ''; ?>">
        <a href="book-service.php" class="waves-effect"><i class="material-icons">add_circle</i>Book Service</a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
        <a href="orders.php" class="waves-effect">
            <i class="material-icons">list</i>My Orders
            <?php if($pending_count > 0): ?>
            <span class="new badge red"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
        <a href="profile.php" class="waves-effect"><i class="material-icons">person</i>Profile</a>
    </li>
    <li><div class="divider"></div></li>
    <li>
        <a href="../logout.php" class="waves-effect"><i class="material-icons">exit_to_app</i>Logout</a>
    </li>
</ul>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var elems = document.querySelectorAll('.sidenav');
    M.Sidenav.init(elems);
});
</script> 