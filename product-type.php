<?php
add_action( 'plugins_loaded', function () {

	/**
	 * This should be in its own separate file.
	 */
	class WC_Product_Wooaf_Deposit extends WC_Product {
	    protected $current_product;
		public function __construct( $product ) {
            if( is_numeric( $product ) ) {
			    $this->current_product = $product;
            }
			$this->product_type = 'wooaf_deposit';
			parent::__construct( $product );
		}

		/** Purchasable */
		public function is_purchasable() {
			return true;
		}

		/**
		 * Not a visible product
		 * @return boolean
		 */
		public function is_visible() {
		    if( $this->current_product == Wooaf_Functions_Pro::get_topup_product_id() ) return false;
			return true;
		}
		/**
		 * Get the add to url used mainly in loops.
		 *
		 * @return string
		 */
		public function add_to_cart_url() {
			$url = $this->is_purchasable() && $this->is_in_stock() ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->id ) ) : get_permalink( $this->id );

			return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
		}

		/**
		 * Get the add to cart button text.
		 *
		 * @return string
		 */
		public function add_to_cart_text() {
			$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Add to cart', 'woocommerce' ) : __( 'Read more', 'woocommerce' );

			return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
		}

		/**
		 * Get the add to cart button text description - used in aria tags.
		 *
		 * @since 3.3.0
		 * @return string
		 */
		public function add_to_cart_description() {
			/* translators: %s: Product title */
			$text = $this->is_purchasable() && $this->is_in_stock() ? __( 'Add &ldquo;%s&rdquo; to your cart', 'woocommerce' ) : __( 'Read more about &ldquo;%s&rdquo;', 'woocommerce' );

			return apply_filters( 'woocommerce_product_add_to_cart_description', sprintf( $text, $this->get_name() ), $this );
		}
	}
} );

add_filter( 'product_type_selector', function ($types) {
	// Key should be exactly the same as in the class
	$types[ 'wooaf_deposit' ] = __( 'Product Voucher', 'wooaf' );
	return $types;
} );
/**
 * Show pricing fields for simple_rental product.
 */
function simple_rental_custom_js() {
	if ( 'product' != get_post_type() ) :
		return;
	endif;
	?><script type='text/javascript'>
        jQuery( document ).ready( function() {
            //for Price tab
            jQuery('.product_data_tabs .general_tab').addClass('show_if_simple_rental').show();
            jQuery('#general_product_data .pricing').addClass('show_if_simple_rental').show();
            //for Inventory tab
            jQuery('.inventory_options').addClass('show_if_simple_rental').show();
            jQuery('#inventory_product_data ._manage_stock_field').addClass('show_if_simple_rental').show();
            jQuery('#inventory_product_data ._sold_individually_field').parent().addClass('show_if_simple_rental').show();
            jQuery('#inventory_product_data ._sold_individually_field').addClass('show_if_simple_rental').show();
        });
	</script><?php
}
add_action( 'admin_footer', 'simple_rental_custom_js' );