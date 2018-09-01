<?php

if( !class_exists( 'Wooaf_Shortcode_handler' ) ) {

	class Wooaf_Shortcode_handler {

		public function __construct() {
			add_shortcode( 'wooaf_vouchers', array( $this, 'shortcode_handler' ) );
			add_action( 'woocommerce_product_query', array( $this, 'remove_voucher_products' ) );

			add_filter( 'woocommerce_shortcode_products_query', array( $this, 'add_voucher_products' ),10,3 );
			/*add_action( 'pre_get_posts', array( $this, 'custom_pre_get_posts' ), 999,2 );*/
		}

		public function shortcode_handler( $atts, $content ) {
			$atts = shortcode_atts( array(
			), $atts, 'wooaf_vouchers' );

			do_action( 'wooaf_voucher_loop_starts' );

			echo do_shortcode( '[products]' );
		}

		public function remove_voucher_products( $q ) {
			if ( ! $q->is_main_query() ) return;
			if ( ! $q->is_post_type_archive() ) return;

			if ( ! is_admin() && is_shop() ) {
				$q->set( 'tax_query', array(array(
					'taxonomy' => 'product_type',
					'field' => 'slug',
					'terms' => array( 'wooaf_deposit' ), // Don't display products in the private-clients category on the shop page
					'operator' => 'NOT IN'
				)));
			}
		}

		function add_voucher_products( $query_args, $attributes, $type ) {

			//if not voucher product loop
			if( did_action( 'wooaf_voucher_loop_starts' ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_type',
					'field' => 'slug',
					'terms' => array( 'wooaf_deposit' ), // Don't display products in the private-clients category on the shop page
					'operator' => 'IN'
				);
			}

			return $query_args;
		}
	}

	new Wooaf_Shortcode_handler();
}