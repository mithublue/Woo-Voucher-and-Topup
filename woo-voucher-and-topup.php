<?php
/*
 * Plugin Name: Woo Voucher and Topup
 * Description: Allow customers to deposit funds into their accounts and pay with account funds during checkout.
 * Plugin URI:
 * Author URI: https://cybercraftit.com/
 * Author: CyberCraft
 * Text Domain: wooaf
 * Domain Path: /languages
 * Version: 1.0
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WOOAF_VERSION', '1.0' );
define( 'WOOAF_ROOT', dirname(__FILE__) );
define( 'WOOAF_ASSET_PATH', plugins_url('assets',__FILE__) );
define( 'WOOAF_BASE_FILE', __FILE__ );
define( 'WOOAF_PRODUCTION', true );

Class Wooaf_Init {

	/**
	 * @var Singleton The reference the *Singleton* instance of this class
	 */
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'on_activate' ) );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'action_links' ) );
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
		$this->includes();
	}

	function on_activate() {
		if( !Wooaf_Functions::get_data( 'voucher_id' ) ) {
			Wooaf_Functions::create_voucher_page();
		}
		do_action( 'wooaf_on_activate' );
		flush_rewrite_rules();
	}

	public function action_links($links) {
		$links[] = '<a href="https://cybercraftit.com/contact/" target="_blank">'.__( 'Ask for Modification', 'wooaf' ).'</a>';
		if( ! Wooaf_Functions::is_pro() ) {
			$links[] = '<a href="https://cybercraftit.com/woo-voucher-topup-pro/" style="color: #fa0000;" target="_blank">'.__( 'Upgrade to Pro', 'wooaf' ).'</a>';
		}
		return $links;
	}

	public static function add_settings_page ( $settings ) {
		$settings[] = include( WOOAF_ROOT.'/admin/settings.php' );
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'wooaf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function includes() {
		include_once 'functions.php';
		include_once 'product-type.php';
		include_once 'includes/shortcode.php';

		include_once 'includes/order-manager.php';
		include_once 'includes/cart-manager.php';
		include_once 'public/account.php';

		if( Wooaf_Functions::is_pro() ) {
			include_once 'pro/loader.php';
		} else {
			include_once 'pro-data.php';
		}
	}

}

Wooaf_Init::get_instance();