<?php
/**
 * Insert the new endpoint into the My Account menu.
 *
 * @param array $items
 * @return array
 */
function wooaf_account_menu_items( $items ) {
	$logout = $items['customer-logout'];
	unset( $items['customer-logout'] );

	$items['wooaf-account-funds'] = __( 'Funds', 'woocommerce' );

	// Insert back the logout item.
	$items['customer-logout'] = $logout;
	return $items;
}

add_filter( 'woocommerce_account_menu_items', 'wooaf_account_menu_items' );

/**
 * Register new endpoint to use inside My Account page.
 *
 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
 */
function wooaf_account_endpoints() {
	add_rewrite_endpoint( 'wooaf-account-funds', EP_ROOT | EP_PAGES );
}

add_action( 'init', 'wooaf_account_endpoints' );

/**
 * Add new query var.
 *
 * @param array $vars
 * @return array
 */
function wooaf_account_query_vars( $vars ) {
	$vars[] = 'wooaf-account-funds';

	return $vars;
}

add_filter( 'query_vars', 'wooaf_account_query_vars', 0 );

/**
 * Endpoint HTML content.
 */
function wooaf_account_endpoint_content() {
	include WOOAF_ROOT.'/templates/myaccount/account-funds.php';
}

add_action( 'woocommerce_account_wooaf-account-funds_endpoint', 'wooaf_account_endpoint_content' );