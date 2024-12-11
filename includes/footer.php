<footer class="page-footer blue darken-3">
    <div class="container">
        <div class="row">
            <div class="col l4 s12">
                <h5 class="white-text">
                    <i class="material-icons left">local_laundry_service</i>
                    Laundry Service
                </h5>
                <p class="grey-text text-lighten-4">
                    Professional laundry services at your convenience. Quality care for your garments.
                </p>
            </div>
            <div class="col l4 s12">
                <h5 class="white-text">Quick Links</h5>
                <ul>
                    <li><a class="grey-text text-lighten-3" href="#services">
                        <i class="material-icons tiny">local_laundry_service</i> Our Services
                    </a></li>
                    <li><a class="grey-text text-lighten-3" href="#how-it-works">
                        <i class="material-icons tiny">help_outline</i> How It Works
                    </a></li>
                    <li><a class="grey-text text-lighten-3" href="#contact">
                        <i class="material-icons tiny">contact_mail</i> Contact Us
                    </a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a class="grey-text text-lighten-3" href="<?php echo $_SESSION['role']; ?>/dashboard.php">
                            <i class="material-icons tiny">dashboard</i> Dashboard
                        </a></li>
                    <?php else: ?>
                        <li><a class="grey-text text-lighten-3" href="login.php">
                            <i class="material-icons tiny">login</i> Login
                        </a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col l4 s12">
                <h5 class="white-text">Contact Us</h5>
                <ul>
                    <li>
                        <a class="grey-text text-lighten-3" href="tel:+1234567890">
                            <i class="material-icons tiny">phone</i> (123) 456-7890
                        </a>
                    </li>
                    <li>
                        <a class="grey-text text-lighten-3" href="mailto:support@laundry.com">
                            <i class="material-icons tiny">email</i> support@laundry.com
                        </a>
                    </li>
                    <li class="grey-text text-lighten-3">
                        <i class="material-icons tiny">location_on</i> 
                        123 Laundry Street, City
                    </li>
                    <li class="grey-text text-lighten-3">
                        <i class="material-icons tiny">access_time</i>
                        Mon - Sat: 8:00 AM - 8:00 PM
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-copyright">
        <div class="container">
            © <?php echo date('Y'); ?> Laundry Service. All rights reserved.
            <div class="grey-text text-lighten-4 right hide-on-small-only">
                <a class="grey-text text-lighten-4" href="#!">Terms of Service</a> |
                <a class="grey-text text-lighten-4" href="#!">Privacy Policy</a>
            </div>
        </div>
    </div>
</footer>

<?php if(!isset($no_script)): ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<?php endif; ?> 