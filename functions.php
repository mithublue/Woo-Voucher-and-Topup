<?php

if( !function_exists('pri' ) ) {
	function pri( $data ) {
		echo '<pre>'; print_r($data);echo '</pre>';
	}
}

if( !function_exists( 'sm_get_notice' ) ) {
	function sm_get_notice ( $notice_name =  'sm_admin_notices'  ) {
		$notice = get_option( $notice_name );
		if( !is_array( $notice ) ) $notice = array();
		return $notice;
	}
}

class Wooaf_Functions {

	public static function is_pro() {
		if( is_file( dirname(__FILE__).'/pro/loader.php' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Add funds to user account
	 * @param int $customer_id
	 * @param float $amount
	 */
	public static function add_funds( $customer_id, $amount ) {
		$funds = get_user_meta( $customer_id, 'account_funds', true );
		$funds = $funds ? $funds : 0;
		$funds += floatval( $amount );
		update_user_meta( $customer_id, 'account_funds', $funds );
	}

	/**
	 * Get a users funds amount
	 * @param  int  $user_id
	 * @param  boolean $formatted
	 * @return string
	 */
	public static function get_account_funds( $user_id = null, $formatted = true, $exclude_order_id = 0 ) {
		$user_id = $user_id ? $user_id : get_current_user_id();

		if ( $user_id ) {
			$funds = max( 0, get_user_meta( $user_id, 'account_funds', true ) );

			// Account for pending orders
			$orders_with_pending_funds = get_posts( array(
				'numberposts' => -1,
				'post_type'   => 'shop_order',
				'post_status' => array_keys( wc_get_order_statuses() ),
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'key'   => '_customer_user',
						'value' => $user_id
					),
					array(
						'key'   => '_funds_removed',
						'value' => '0',
					),
					array(
						'key'     => '_funds_used',
						'value'   => '0',
						'compare' => '>'
					)
				)
			) );

			foreach ( $orders_with_pending_funds as $order_id ) {
				if ( null !== WC()->session && ! empty( WC()->session->order_awaiting_payment ) && $order_id == WC()->session->order_awaiting_payment ) {
					continue;
				}
				if ( $exclude_order_id === $order_id ) {
					continue;
				}
				$funds = $funds - floatval( get_post_meta( $order_id, '_funds_used', true ) );
			}
		} else {
			$funds = 0;
		}

		return $formatted ? wc_price( $funds ) : $funds;
	}

	/**
	 * Remove funds from user account
	 * @param int $customer_id
	 * @param float $amount
	 */
	public static function remove_funds( $customer_id, $amount ) {
		$funds = get_user_meta( $customer_id, 'account_funds', true );
		$funds = $funds ? $funds : 0;
		$funds = $funds - floatval( $amount );
		update_user_meta( $customer_id, 'account_funds', max( 0, $funds ) );
	}

	public static function has_funds() {
		if( !is_user_logged_in() ) return;
		return get_user_meta( get_current_user_id(), 'account_funds', true );
	}

	public static function create_voucher_page() {
		$postattr = array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_title' => 'Vouchers',
			'post_content' => '[wooaf_vouchers]'
		);

		$id = wp_insert_post($postattr);

		if( $id ) {
			self::set_data( 'voucher_id', $id );
		}

		return $id;
	}

	public static function get_data( $key ) {
		$wooaf_data = get_option( 'wooaf_data' );
		!is_array( $wooaf_data ) ? $wooaf_data = array() : '';
		if( !isset( $wooaf_data[$key] ) || !$wooaf_data[$key] ) return false;
		else return $wooaf_data[$key];
	}
	/**
	 * Set data
	 * @param $key
	 * @param $val
	 *
	 * @return bool
	 */
	public static function set_data( $key, $val ) {
		$wooaf_data = get_option( 'wooaf_data' );
		!is_array( $wooaf_data ) ? $wooaf_data = array() : '';
		$wooaf_data[$key] = $val;
		return update_option( 'wooaf_data', $wooaf_data );
	}
}