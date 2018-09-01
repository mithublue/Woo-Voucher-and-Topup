<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !class_exists( 'Wooaf_Cart_Manager' ) ) {

    class Wooaf_Cart_Manager {

        public function __construct() {
	        add_action( 'woocommerce_review_order_before_order_total', array( $this, 'display_used_funds' ) );
	        add_action( 'woocommerce_cart_totals_before_order_total', array( $this, 'display_used_funds' ) );

	        add_action( 'woocommerce_wooaf_deposit_add_to_cart', array( $this, 'add_to_cart' ) );

	        add_action( 'woocommerce_before_cart', array( $this, 'output_use_funds_notice' ), 6 );
	        add_action( 'woocommerce_before_checkout_form', array( $this, 'output_use_funds_notice' ), 6 );



	        //
	        add_action( 'wp', array( $this, 'maybe_use_funds' ) );

	        add_filter( 'woocommerce_calculated_total', array( $this, 'calculated_total' ) );
	        add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'calculate_totals' ) );

	        add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'get_discount_data' ), 10, 2 );
	        add_filter( 'woocommerce_coupon_message', array( $this, 'get_discount_applied_message' ), 10, 3 );
	        add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'coupon_label' ) );
	        add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'coupon_html' ), 10, 2 );
	        add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'get_discount_amount' ), 10, 5 );

	        add_filter( 'woocommerce_paypal_args', array( $this, 'pps_maybe_add_discount_to_line_items'), 10, 2 );
        }


	    /**
	     * See if an cart contains a deposit
	     * @param  int $order_id
	     * @return bool
	     */
	    public static function cart_contains_deposit() {
		    foreach ( WC()->cart->get_cart() as $item ) {
			    if ( $item['data']->is_type( 'deposit' ) || $item['data']->is_type( 'topup' ) ) {
				    return true;
			    }
		    }
		    return false;
	    }


	    /**
	     * Show amount of funds used
	     */
	    public function display_used_funds() {
		    if ( self::using_funds() ) {
			    $funds_amount = self::used_funds_amount();
			    if ( $funds_amount > 0 ) {
				    ?>
                    <tr class="order-discount account-funds-discount">
                        <th><?php _e( 'Account Funds', 'wooaf' ); ?></th>
                        <td>-<?php echo wc_price( $funds_amount ); ?> <a href="<?php echo esc_url( add_query_arg( 'remove_account_funds', true, get_permalink( is_cart() ? wc_get_page_id( 'cart' ) : wc_get_page_id( 'checkout' ) ) ) ); ?>"><?php _e( '[Remove]', 'wooaf' ); ?></a></td>
                    </tr>
				    <?php
			    }
		    }
	    }

	    /**
	     * Amount of funds being applied
	     * @return float
	     */
	    public static function used_funds_amount() {
		    return WC()->session->get( 'used-account-funds' );
	    }

        public function maybe_use_funds() {
	        if ( ! empty( $_POST['wc_account_funds_apply'] ) && self::can_apply_funds() ) {
		        WC()->session->set( 'use-account-funds', true );
		        wp_redirect( esc_url_raw( remove_query_arg( 'remove_account_funds' ) ) );
		        exit;
	        }

	        if ( ! empty( $_GET['remove_account_funds'] )  ) {
		        WC()->session->set( 'use-account-funds', false );
		        WC()->session->set( 'used-account-funds', false );
		        wp_redirect( esc_url_raw( remove_query_arg( 'remove_account_funds' ) ) );
		        exit;
	        }

	        if ( self::using_funds() ) {
		        $this->apply_discount();
	        }
        }


        /**
	     * Using funds right now?
	     */
	    public static function using_funds() {
		    return ! is_null( WC()->session ) && WC()->session->get( 'use-account-funds' ) && self::can_apply_funds();
	    }

	    /**
	     * Can the user actually apply funds to this cart?
	     * @return bool
	     */
	    public static function can_apply_funds() {
		    $can_apply = true;
		    return $can_apply;
	    }

	    /**
	     * Apply funds discount to cart
	     */
	    public function apply_discount() {
		    $account_funds_discount = apply_filters( 'account_funds_discount', false );
		    if( !$account_funds_discount ) return;

		    WC()->cart->add_discount( self::generate_discount_code() );
	    }

	    /**
	     * Returns the unique discount code generated for the applied discount if set
	     *
	     * @since 1.0
	     */
	    public static function get_discount_code() {
		    return WC()->session->get( 'wc_account_funds_discount_code' );
	    }

	    /**
	     * Generates a unique discount code tied to the current user ID and timestamp
	     * Made of current user ID + the current time in YYYY_MM_DD_H_M format
	     */
	    public static function generate_discount_code() {
		    $discount_code = sprintf( 'wc_account_funds_discount_%s_%s', get_current_user_id(), date( 'Y_m_d_h_i', current_time( 'timestamp' ) ) );
		    WC()->session->set( 'wc_account_funds_discount_code', $discount_code );
		    return $discount_code;
	    }

	    /**
	     * Calculated total
	     * @param  float $total
	     * @return float
	     */
	    public function calculated_total( $total ) {
		    if ( self::using_funds() ) {
			    $funds_amount = min( $total, Wooaf_Functions::get_account_funds( get_current_user_id(), false ) );
			    $total        = $total - $funds_amount;
			    WC()->session->set( 'used-account-funds', $funds_amount );
		    }
		    return $total;
	    }

	    /**
	     * Calculate totals
	     */
	    public function calculate_totals() {
		    if ( self::account_funds_gateway_chosen() ) {
			    $this->apply_discount();
			    WC()->cart->calculate_totals();
		    } elseif ( ! self::using_funds() && self::get_discount_code() && WC()->cart->has_discount( self::get_discount_code() ) ) {
			    WC()->cart->remove_coupon( self::get_discount_code() );
		    }
	    }

	    /**
	     * How can this cart be paid for using funds?
	     * @return string
	     */
	    public static function account_funds_gateway_chosen() {
		    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		    return ( isset( $available_gateways['accountfunds'] ) && $available_gateways['accountfunds']->chosen ) || ( ! empty( $_POST['payment_method'] ) && 'accountfunds' === $_POST['payment_method'] );
	    }

	    /**
	     * Generate the coupon data required for the discount
	     *
	     * @since 1.0
	     * @param array $data the coupon data
	     * @param string $code the coupon code
	     * @return array the custom coupon data
	     */
	    public function get_discount_data( $data, $code ) {
		    // Ignore data filtering if in admin. If there's a call to get_discount_code,
		    // then it'd be for front-end page.
		    if ( is_admin() ) {
			    return $data;
		    }

		    if ( strtolower( $code ) != $this->get_discount_code() ) {
			    return $data;
		    }

		    $data = apply_filters( 'wooaf_modify_discount_data', $data );
		    return $data;
	    }


        public function generate_coupon() {
	        if( WC()->session->get( 'use-account-funds' ) ) {
		        $coupon = new WC_Coupon( 'test_coup' );
		        $coupon->set_virtual(true);
		        $coupon->set_discount_type('fixed_cart');
		        $coupon->set_discount_type('fixed_cart');
		        $coupon->set_amount(20);
		        $coupon->set_amount(20);
		        WC()->cart->apply_coupon($coupon);
	        }
        }

	    /**
	     * Show add to cart button
	     */
	    public function add_to_cart() {
		    woocommerce_simple_add_to_cart();
	    }

	    /**
	     * Show a notice to apply points towards your purchase
	     */
	    public function output_use_funds_notice() {
	        if( !Wooaf_Functions::has_funds() ) return;
	        if( WC()->session->get( 'used-account-funds' ) ) return;

		    $message  = '<div class="woocommerce-info wc-account-funds-apply-notice">';
		    $message .= '<form class="wc-account-funds-apply" method="post">';
		    $message .= '<input type="submit" class="button wc-account-funds-apply-button" name="wc_account_funds_apply" value="' . __( 'Use Account Funds', 'wooaf' ) . '" />';
		    $message .= sprintf( __( 'You have <strong>%s</strong> worth of funds on your account.', 'wooaf' ), Wooaf_Functions::get_account_funds() );

		    $message = apply_filters( 'after_output_use_funds_notice', $message );
		    $message .= '</form>';
		    $message .= '</div>';

		    echo $message;
	    }

	    /**
	     * Change the "Coupon applied successfully" message to "Discount Applied Successfully"
	     *
	     * @since 1.0
	     * @param string $message the message text
	     * @param string $message_code the message code
	     * @param object $coupon the WC_Coupon instance
	     * @return string the modified messages
	     */
	    public function get_discount_applied_message( $message, $message_code, $coupon ) {

		    if ( $message_code === WC_Coupon::WC_COUPON_SUCCESS && $coupon->code === $this->get_discount_code() ) {
			    return __( 'Discount applied for using account funds!', 'wooaf' );
		    } else {
			    return $message;
		    }
	    }

	    /**
	     * Make the label for the coupon look nicer
	     * @param  string $label
	     * @return string
	     */
	    public function coupon_label( $label ) {

		    if ( strstr( strtoupper( $label ), 'WC_ACCOUNT_FUNDS_DISCOUNT' ) ) {
			    $label = esc_html( __( 'Discount', 'wooaf' ) );
		    }
		    return $label;
	    }

	    /**
	     * Make the html for the coupon look nicer
	     * @param  string $html
	     * @return string
	     */
	    public function coupon_html( $html, $coupon ) {
		    if ( 'no' === $this->give_discount ) {
			    return $html;
		    }

		    if ( $coupon->code === $this->get_discount_code() ) {
			    $html = current( explode( '<a ', $html ) );
		    }
		    return $html;
	    }

	    /**
	     * Get coupon discount amount
	     * @param  float $discount
	     * @param  float $discounting_amount
	     * @param  object $cart_item
	     * @param  bool $single
	     * @param  WC_Coupon $coupon
	     * @return float
	     */
	    public function get_discount_amount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
	        if ( strtolower( $coupon->code ) != $this->get_discount_code() ) {
			    return $discount;
		    }

		    $discount = apply_filters( 'wooaf_calculate_fund_discount', $discount, $discounting_amount, $cart_item, $single, $coupon );
		    return $discount;
	    }

	    /**
	     * Add a discounted line item to the payment gateway process for
	     * the WC built in Paypal Standard
	     *
	     * pps = paypal standard specific function
	     *
	     * @since 2.0.11
	     *
	     * @param array $paypal_args
	     * @param WC_Order $order
	     * @return array $paypal_args
	     */
	    public function pps_maybe_add_discount_to_line_items( $paypal_args, $order ) {

		    $funds_amount = get_post_meta( version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id(), '_funds_used', true );

		    if ( empty( $funds_amount ) ) {
			    return $paypal_args;
		    }

		    $item_indexes = $this->pps_get_item_indexes( $paypal_args );

		    foreach ( $item_indexes as $index ) {

			    if ( ! ( $funds_amount > 0 ) ) {
				    continue;
			    }

			    // get array values
			    $initial_item_amount = doubleval( $paypal_args['amount_' . $index] );
			    $item_name           = $paypal_args['item_name_' . $index];
			    $item_quantity       = $paypal_args['quantity_' . $index];

			    if ( ( $initial_item_amount * $item_quantity ) >= $funds_amount ) {

				    // divide funds amount each of the items as paypal
				    $new_item_amount  = $initial_item_amount - ( $funds_amount / $item_quantity );
				    $new_funds_amount = 0;
				    $funds_used       = $funds_amount;

			    } else {

				    // Funds must decrease cart line total not just item total
				    $new_funds_amount = $funds_amount - ( $initial_item_amount * $item_quantity );
				    $new_item_amount  = 0;
				    $funds_used       =  $funds_amount  - $new_funds_amount;

			    }

			    $item_name .= sprintf( __(' (%d %s applied from account funds)', 'wooaf'), $funds_used, get_woocommerce_currency() );

			    //values again
			    $funds_amount                       = $new_funds_amount;
			    $paypal_args['amount_' . $index]    = $new_item_amount;
			    $paypal_args['item_name_' . $index] = $item_name;

		    }

		    return $paypal_args;

	    }

	    /**
	     * Get the item indexes from all paypal itmes.
	     * This function looks for _digit at the end of items and creates
	     * a list of those digits.
	     *
	     * Only indexes with existing name, amount and quantity are added.
	     *
	     * pps = paypal standard specific function
	     *
	     * @since 2.0.11
	     *
	     * @param array $paypal_args
	     *
	     * @return array $item_indexes
	     */
	    public function pps_get_item_indexes( $paypal_args ) {

		    $item_indexes = array();

		    foreach ( $paypal_args as $key => $arg ) {

			    if ( preg_match( '/item_name_/', $key ) ) {

				    $index = str_replace( 'item_name_', '', $key );

				    // make sure the item name, amount and quantity values exist
				    if ( isset( $paypal_args['amount_' . $index] )
				         && isset( $paypal_args['item_name_' . $index] )
				         && isset( $paypal_args['quantity_' . $index] ) ) {

					    $item_indexes[] = $index;

				    }
			    }
		    }

		    return $item_indexes;
	    }
    }

    new Wooaf_Cart_Manager();

}