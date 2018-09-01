<?php

add_filter( 'wooaf_general_settings', function ( $settings ) {
	$settings = array_merge( $settings, array(
		array(
			'name' => __( 'Discount Settings (Pro)', 'wooaf' ),
			'type' => 'title',
			'desc' => '',
			'id'   => ''
		),
		array(
			'name'     => __( 'Give Discount (Pro)', 'wooaf' ),
			'type'     => 'checkbox',
			'desc'     => __( 'Apply a discount when account funds are used to purchase items', 'wooaf' ),
			'id'       => '',
		),
		array(
			'name'     => __( 'Discount Type (Pro)', 'wooaf' ),
			'type'     => 'select',
			'options'  => array(
				'fixed'      => __( 'Fixed Price', 'wooaf' ),
				'percentage' => __( 'Percentage', 'wooaf' )
			),
			'desc'     => __( 'Percentage discounts will be based on the amount of funds used.', 'wooaf' ),
			'id'       => '',
			'desc_tip' => false
		),
		array(
			'name'    => __( 'Discount Amount (Pro)', 'wooaf' ),
			'type'    => 'text',
			'desc'    => __( 'Enter numbers only. Do not include the percentage sign.', 'wooaf' ),
			'default' => '',
			'id'      => '',
			'desc_tip' => true
		),
		array( 'type' => 'sectionend', 'id' => 'account_funds_title' ),
		array(
			'name' => __( 'Funding (Pro)', 'wooaf' ),
			'type' => 'title',
			'desc' => '',
			'id'   => 'account_funds_funding_title'
		),
		array(
			'name'            => __( 'Enable "My Account" Top-up (Pro)', 'wooaf' ),
			'type'            => 'checkbox',
			'desc'            => __( 'Allow customers to top up funds via their account page.', 'wooaf' ),
			'id'              => ''
		),
		array(
			'name'            => __( 'Minimum Top-up (Pro)', 'wooaf' ),
			'type'            => 'text',
			'desc'            => '',
			'default'         => '',
			'placeholder'     => 0,
			'id'              => '',
			'desc_tip'        => true
		),
		array(
			'name'            => __( 'Maximum Top-up (Pro)', 'wooaf' ),
			'type'            => 'text',
			'desc'            => '',
			'default'         => '',
			'placeholder'     => __( 'n/a', 'wooaf' ),
			'id'              => '',
			'desc_tip'        => true
		),
		array( 'type' => 'sectionend', 'id' => 'account_funds_funding_title' ),
		array( 'type' => 'sectionend', 'id' => 'account_funds_payment_title' ),
	));
	return $settings;
} );