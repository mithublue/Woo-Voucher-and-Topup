<div class="woocommerce woocommerce-account-funds">
    <p><?php printf( __( 'You currently have <strong>%s</strong> worth of funds in your account.', 'wooaf' ), Wooaf_Functions::get_account_funds() ); ?></p>
    <?php
    do_action( 'wooaf_after_myaccount_funds_notice' );
    ?>
</div>
