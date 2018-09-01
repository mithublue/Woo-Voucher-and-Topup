<?php

/**
 * WooCommerce Product Settings
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Settings_Account_Funds', false ) ) :

	/**
	 * WC_Settings_Products.
	 */
	class WC_Settings_Account_Funds extends WC_Settings_Page {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'account_funds';
			$this->label = __( 'Voucher and Topup', 'wauc' );
			parent::__construct();
			add_filter( 'wooaf_account_funds_settings', __CLASS__.'::account_funds_settings_free' );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {

			$sections = array(
			);

			return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			WC_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings.
		 */
		public function save() {
			global $current_section;

			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::save_fields( $settings );
		}

		/**
		 * Get settings array.
		 *
		 * @param string $current_section
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			if( 'general' == $current_section || !$current_section ) {
				$settings = apply_filters( 'wooaf_general_settings', array());

				$settings[] = array(
					'type' 	=> 'sectionend',
					'id' 	=> 'wauc_general_options',
				);
			} else {
				$settings = apply_filters( 'wauc_auction_'.$current_section.'_settings', array() );
			}

			return apply_filters( 'wauc_get_settings_' . $this->id, $settings, $current_section );
		}

		public static function account_funds_settings_free( $settings ) {
			if( Wooaf_Functions::is_pro() ) return $settings;
			return $settings;
		}
	}

endif;

return new WC_Settings_Account_Funds();