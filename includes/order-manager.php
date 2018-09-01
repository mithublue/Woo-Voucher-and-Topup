<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( !class_exists( 'Wooaf_Order_Manager' ) ) {

	class Wooaf_Order_Manager {

		public function __construct() {
			add_action( 'woocommerce_before_checkout_process', array( $this, 'force_registration_during_checkout' ), 10 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'woocommerce_checkout_update_order_meta' ), 10, 2 );

			add_action( 'woocommerce_payment_complete', array( $this, 'maybe_remove_funds' ) );
			add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_remove_funds' ) );
			add_action( 'woocommerce_order_status_on-hold', array( $this, 'maybe_remove_funds' ) );
			add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_remove_funds' ) );

			add_action( 'woocommerce_order_status_cancelled', array( $this, 'maybe_restore_funds' ) );
			add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_increase_funds' ) );

			add_filter( 'woocommerce_get_order_item_totals', array( $this, 'woocommerce_get_order_item_totals' ), 10, 2 );
			add_filter( version_compare( WC_VERSION, '3.0', '<' ) ? 'woocommerce_order_amount_total' : 'woocommerce_order_get_total', array( 'Wooaf_Order_Manager', 'adjust_total_to_include_funds' ), 10, 2 );
			add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_meta' ), 10, 3 );
			add_filter( 'woocommerce_order_item_product', array( $this, 'order_item_product' ), 10, 2 );
		}

		/**
		 * Remove user funds when an order is created
		 *
		 * @param  int $order_id
		 */
		public function woocommerce_checkout_update_order_meta( $order_id, $posted ) {
			if ( $posted['payment_method'] !== 'accountfunds' && Wooaf_Cart_Manager::using_funds() ) {
				$used_funds = Wooaf_Cart_Manager::used_funds_amount();
				update_post_meta( $order_id, '_funds_used', $used_funds );
				add_post_meta( $order_id, '_funds_removed', 0 );
			}
		}

		/**
		 * Restore user funds when an order is cancelled
		 *
		 * @param  int $order_id
		 */
		public function maybe_restore_funds( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $funds = get_post_meta( $order_id, '_funds_used', true ) ) {
				Wooaf_Functions::add_funds( $order->get_user_id(), $funds );
				$order->add_order_note( sprintf( __( 'Restored %s funds to user #%d', 'wooaf' ), wc_price( $funds ), $order->get_user_id() ) );
			}
		}


		/**
		 * Handle order complete events
		 *
		 * @param  int $order_id
		 */
		public function maybe_increase_funds( $order_id ) {
			$order          = wc_get_order( $order_id );
			$items          = $order->get_items();
			$customer_id    = $order->get_user_id();

			if ( $customer_id && ! get_post_meta( $order_id, '_funds_deposited', true ) ) {
				foreach ( $items as $item ) {
					$product = $order->get_product_from_item( $item );
					if ( ! is_a( $product, 'WC_Product' ) ) {
						continue;
					}

					$funds = 0;
					if ( $product->is_type( 'wooaf_deposit' ) || $product->is_type( 'deposit' ) || $product->is_type( 'topup' ) ) {
						$funds = $item['line_total'];
					} else {
						continue;
					}

					Wooaf_Functions::add_funds( $customer_id, $funds );

					$order->add_order_note( sprintf( __( 'Added %s funds to user #%d', 'wooaf' ), wc_price( $funds ), $customer_id ) );

					update_post_meta( $order_id, '_funds_deposited', 1 );
				}
			}
		}

		/**
		 * Order total display
		 */
		public function woocommerce_get_order_item_totals( $rows, $order ) {
			if ( $_funds_used = get_post_meta( version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id(), '_funds_used', true ) ) {
				$rows['funds_used'] = array(
					'label' => __( 'Funds Used:', 'wooaf' ),
					'value'	=> wc_price( $_funds_used )
				);
			}
			return $rows;
		}

		public function maybe_remove_funds( $order_id ) {
			if ( null !== WC()->session ) {
				WC()->session->set( 'use-account-funds', false );
				WC()->session->set( 'used-account-funds', false );
			}

			$order       = wc_get_order( $order_id );
			$customer_id = $order->get_user_id();

			if ( $customer_id && ! get_post_meta( $order_id, '_funds_removed', true ) ) {
				if ( $funds = get_post_meta( $order_id, '_funds_used', true ) ) {
					Wooaf_Functions::remove_funds( $customer_id, $funds );
					$order->add_order_note( sprintf( __( 'Removed %s funds from user #%d', 'wooaf' ), wc_price( $funds ), $customer_id ) );
				}
				update_post_meta( $order_id, '_funds_removed', 1 );
			}
		}

		/**
		 * Store top-up info.
		 *
		 * This meta data only applies to store with WC >= 3.0.
		 *
		 * @since 2.1.3
		 *
		 * @version 2.1.3
		 *
		 * @param WC_Order_Item $item          Order item object.
		 * @param string        $cart_item_key Cart item key.
		 * @param array         $values        Cart item values.
		 */
		public function add_order_item_meta( $item, $cart_item_key, $values ) {
			if ( ! empty( $values['top_up_amount'] ) ) {
				$item->add_meta_data( '_top_up_amount', $values['top_up_amount'], true );
				$item->add_meta_data( '_top_up_product', 'yes', true );
			}
		}

		/**
		 * Update order item product with instance of WC_Product_Topup.
		 *
		 * Data store introduced in WC 3.0.0 validates item product. AF pre 2.1.3
		 * with WC < 3.0 stores product item ID as page ID of myaccount.
		 *
		 * @since 2.1.3
		 *
		 * @version 2.1.3
		 *
		 * @param bool|WC_Product       $product Product object. False otherwise.
		 * @param WC_Order_Item_Product $item    Order item product.
		 *
		 * @return WC_Product Product object.
		 */
		public function order_item_product( $product, $item ) {
			return apply_filters( 'wooaf_order_item_product', $product, $item );
		}

		/**
		 * Adjust total to include amount paid with funds
		 *
		 * @version 2.1.3
		 *
		 * @param float    $total Order total.
		 * @param WC_Order $order Order object.
		 *
		 * @return float Order total.
		 */
		public static function adjust_total_to_include_funds( $total, $order ) { return $total;
			// Don't interfere with total while paying for order.
			if ( is_checkout() || ! empty( $wp->query_vars['order-pay'] ) ) {
				return $total;
			}
			$_funds_used = get_post_meta( version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id(), '_funds_used', true );

			// Calling `$order->get_total()` means firing again woocommerce_order_get_total
			// or woocommerce_order_amount_total hook. We need to remove the filter
			// temporarily.
			//
			// @see https://github.com/woocommerce/woocommerce-account-funds/issues/75.
			if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
				remove_filter( 'woocommerce_order_get_total', array( __CLASS__, 'adjust_total_to_include_funds' ), 10, 2 );
			} else {
				remove_filter( 'woocommerce_order_amount_total', array( __CLASS__, 'adjust_total_to_include_funds' ), 10, 2 );
			}

			$total = floatval( $order->get_total() ) + floatval( $_funds_used );

			if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
				add_filter( 'woocommerce_order_get_total', array( __CLASS__, 'adjust_total_to_include_funds' ), 10, 2 );
			} else {
				add_filter( 'woocommerce_order_amount_total', array( __CLASS__, 'adjust_total_to_include_funds' ), 10, 2 );
			}

			return $total;
		}
	}

	new Wooaf_Order_Manager();
}