<div class="navbar-fixed">
    <nav class="blue-grey darken-3">
        <div class="nav-wrapper container">
            <a href="dashboard.php" class="brand-logo">
                <i class="material-icons left">local_laundry_service</i>
                Laundry Admin
            </a>
            <a href="#" data-target="mobile-nav" class="sidenav-trigger">
                <i class="material-icons">menu</i>
            </a>
            <ul class="right hide-on-med-and-down">
                <li>
                    <a href="dashboard.php" class="waves-effect">
                        <i class="material-icons left">dashboard</i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="orders.php" class="waves-effect">
                        <i class="material-icons left">receipt</i>
                        Orders
                        <?php 
                        // Add pending orders count if available
                        if (isset($pendingOrders) && $pendingOrders > 0): 
                        ?>
                        <span class="new badge red" data-badge-caption=""><?php echo $pendingOrders; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="services.php" class="waves-effect">
                        <i class="material-icons left">local_offer</i>
                        Services
                    </a>
                </li>
                <li>
                    <a href="users.php" class="waves-effect">
                        <i class="material-icons left">people</i>
                        Users
                    </a>
                </li>
                <li>
                    <a class="dropdown-trigger waves-effect" href="#!" data-target="admin-dropdown">
                        <i class="material-icons left">account_circle</i>
                        Admin
                        <i class="material-icons right">arrow_drop_down</i>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</div>

<!-- Dropdown Structure -->
<ul id="admin-dropdown" class="dropdown-content">
    <li>
        <a href="profile.php" class="waves-effect">
            <i class="material-icons">person</i>
            Profile
        </a>
    </li>
    <li class="divider"></li>
    <li>
        <a href="../logout.php" class="waves-effect red-text">
            <i class="material-icons">exit_to_app</i>
            Logout
        </a>
    </li>
</ul>

<!-- Mobile Navigation -->
<ul class="sidenav" id="mobile-nav">
    <li>
        <div class="user-view">
            <div class="background blue-grey darken-3"></div>
            <a href="profile.php">
                <i class="material-icons white-text" style="font-size: 64px;">account_circle</i>
            </a>
            <a href="profile.php">
                <span class="white-text name">Admin User</span>
            </a>
            <a href="profile.php">
                <span class="white-text email">admin@example.com</span>
            </a>
        </div>
    </li>
    <li>
        <a href="dashboard.php" class="waves-effect">
            <i class="material-icons">dashboard</i>
            Dashboard
        </a>
    </li>
    <li>
        <a href="orders.php" class="waves-effect">
            <i class="material-icons">receipt</i>
            Orders
            <?php if (isset($pendingOrders) && $pendingOrders > 0): ?>
            <span class="new badge red" data-badge-caption=""><?php echo $pendingOrders; ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li>
        <a href="services.php" class="waves-effect">
            <i class="material-icons">local_offer</i>
            Services
        </a>
    </li>
    <li>
        <a href="users.php" class="waves-effect">
            <i class="material-icons">people</i>
            Users
        </a>
    </li>
    <li><div class="divider"></div></li>
    <li>
        <a href="profile.php" class="waves-effect">
            <i class="material-icons">person</i>
            Profile
        </a>
    </li>
    <li>
        <a href="../logout.php" class="waves-effect red-text">
            <i class="material-icons">exit_to_app</i>
            Logout
        </a>
    </li>
</ul>

<!-- Initialize Materialize Components -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sidenav
    var elems = document.querySelectorAll('.sidenav');
    var instances = M.Sidenav.init(elems);
    
    // Initialize dropdown
    var dropdowns = document.querySelectorAll('.dropdown-trigger');
    var dropdownInstances = M.Dropdown.init(dropdowns, {
        coverTrigger: false,
        constrainWidth: false
    });
    
    // Highlight active nav item
    var currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.sidenav a, .navbar-fixed a').forEach(function(link) {
        if (link.getAttribute('href') === currentPage) {
            link.parentElement.classList.add('active');
            link.classList.add('active');
        }
    });
});
</script>

<style>
    .navbar-fixed {
        z-index: 999;
    }
    nav .brand-logo {
        font-size: 1.8rem;
    }
    .dropdown-content {
        min-width: 200px;
    }
    .dropdown-content li > a {
        color: #333;
        padding: 14px 16px;
    }
    .dropdown-content li > a > i {
        margin: 0 16px 0 0;
    }
    .sidenav .user-view {
        padding: 32px 32px 16px;
    }
    .sidenav li > a {
        padding: 0 32px;
        color: rgba(0,0,0,0.87);
    }
    .sidenav li > a > i {
        margin: 0 32px 0 0;
        color: rgba(0,0,0,0.54);
    }
    .active {
        background-color: rgba(0,0,0,0.05);
    }
    .navbar-fixed nav {
        box-shadow: 0 2px 2px 0 rgba(0,0,0,0.14);
    }
    nav .badge {
        position: relative;
        top: -5px;
    }
    .brand-logo i {
        margin-right: 7px !important;
    }
    @media only screen and (max-width: 992px) {
        nav .brand-logo {
            left: 50%;
            transform: translateX(-50%);
        }
        nav .brand-logo i {
            display: none;
        }
    }
</style> 