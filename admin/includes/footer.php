<footer class="page-footer blue darken-3">
    <div class="container">
        <div class="row">
            <div class="col l6 s12">
                <h5>
                    <i class="material-icons left">local_laundry_service</i>
                    Laundry Service
                </h5>
                <div class="divider"></div>
                <div class="row">
                    <div class="col s12">
                        <a href="tel:+1234567890" class="white-text">
                            <i class="material-icons left tiny">phone</i> (123) 456-7890
                        </a>
                    </div>
                    <div class="col s12">
                        <a href="mailto:support@laundry.com" class="white-text">
                            <i class="material-icons left tiny">email</i> support@laundry.com
                        </a>
                    </div>
                    <div class="col s12 white-text">
                        <i class="material-icons left tiny">location_on</i> 
                        123 Laundry Street, City
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="footer-copyright">
        <div class="container">
            © <?php echo date('Y'); ?> Laundry Service
            <a class="white-text right" href="#!">Terms & Privacy</a>
        </div>
    </div>
</footer>

<?php if(!isset($no_script)): ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<?php endif; ?> 