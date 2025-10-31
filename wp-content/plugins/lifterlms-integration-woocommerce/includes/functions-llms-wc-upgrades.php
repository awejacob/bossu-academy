<?php
/**
 * Upgrade & Migration functions.
 *
 * @package  LifterLMS_WooCommerce/Functions
 *
 * @since 2.0.0
 * @version 2.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Migrate endpoint information from 1.3.5 or earlier.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function llms_wc_upgrade_136_migrate_endpoints() {

	$ep_migrate = array(
		'courses'       => 'view-courses',
		'memberships'   => 'view-memberships',
		'achievements'  => 'view-achievements',
		'certificates'  => 'view-certificates',
		'notifications' => 'notifications',
		'vouchers'      => 'redeem-voucher',
	);

	$opts = get_option( 'lifterlms_woocommerce_account_endpoints' );
	if ( ! $opts ) {
		$opts = array();
	}

	$new_opts = array();

	foreach ( $opts as $opt ) {

		array_push( $new_opts, $ep_migrate[ $opt ] );
	}

	update_option( 'lifterlms_woocommerce_account_endpoints', $new_opts );

	return true;

}

/**
 * Update DB version when 1.3.6 migration is complete.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function llms_wc_upgrade_136_update_version() {
	LLMS_WC_Install::update_version( '1.3.6' );
	return false;
}

/**
 * Create access plans from all existing products attached to courses / memberships.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function llms_wc_upgrade_200_migrate_products_to_plans() {

	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT * FROM {$wpdb->postmeta}
		 WHERE meta_key = '_llms_wc_product_id'
		   AND meta_value != ''
		 LIMIT 100"
	);

	// Finished.
	if ( ! $results ) {
		return false;
	}

	foreach ( $results as $result ) {

		// Unserialize saved array.
		$products = maybe_unserialize( $result->meta_value );

		// If we still have a number make it an array, this data is old af.
		if ( is_numeric( $products ) ) {
			$products = array( $products );
		}

		// Don't proceed if we don't have an array.
		if ( ! is_array( $products ) ) {
			continue;
		}

		foreach ( $products as $wc_product_id ) {

			$post = get_post( $wc_product_id );
			if ( ! $post ) {
				continue;
			}

			wp_insert_post(
				array(
					'post_type'   => 'llms_access_plan',
					'post_title'  => $post->post_title,
					'post_status' => 'publish',
					'meta_input'  => array(
						'_llms_product_id' => absint( $result->post_id ),
						'_llms_is_free'    => 'no',
						'_llms_price'      => '-',
						'_llms_wc_pid'     => absint( $wc_product_id ),
					),
				)
			);

		}

		delete_post_meta( $result->post_id, '_llms_wc_product_id' );

	}

	// Check for more.
	return true;

}

/**
 * Check existing orders and add order item meta for each order to relate them to LifterLMS products.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function llms_wc_upgrade_200_migrate_order_item_meta() {

	$page = get_option( 'llms_wc_upgrade_200_migrate_order_item_meta_page', 1 );

	$query = new WP_Query(
		array(
			'post_status'    => 'any',
			'post_type'      => 'shop_order',
			'posts_per_page' => 250,
			'paged'          => $page,
		)
	);

	// Finished.
	if ( ! $query->have_posts() ) {
		delete_option( 'llms_wc_upgrade_200_migrate_order_item_meta_page' );
		return false;
	}

	update_option( 'llms_wc_upgrade_200_migrate_order_item_meta_page', $page + 1 );

	foreach ( $query->posts as $post ) {

		$order = wc_get_order( $post->ID );
		foreach ( $order->get_items() as $item ) {

			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}

			// Saving the items will cause data to be updated via filters attached to saving order items.
			$item->save();

		}
	}

	// More to process.
	return true;

}

/**
 * Update DB version when 2.0.0 migration is complete.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function llms_wc_upgrade_200_update_version() {
	LLMS_WC_Install::update_version( '2.0.0' );
	return false;
}

/**
 * Migrate invalid access plan prices set by Access Plans created prior to LifterLMS 3.29.0.
 *
 * @since 2.0.6
 *
 * @return bool
 */
function llms_wc_upgrade_206_migrate_access_plan_price() {

	global $wpdb;
	$wpdb->update(
		$wpdb->postmeta,
		array(
			'meta_value' => 1,
		),
		array(
			'meta_key'   => '_llms_price',
			'meta_value' => '-',
		),
		array( '%d' )
	);

	return false;

}

/**
 * Update DB version to 2.0.6 when migration is complete.
 *
 * @since 2.0.6
 *
 * @return bool
 */
function llms_wc_upgrade_206_update_version() {
	LLMS_WC_Install::update_version( '2.0.6' );
	return false;
}

/**
 * Migrate default options.
 *
 * @since 2.5.0
 *
 * @return bool
 */
function llms_wc_upgrade_250_migrate_default_options() {

	// Options.
	$options = array(
		// Option name (un-prefixed) => defaults.
		'unenrollment_statuses'          => array(
			'wc-refunded',
			'wc-cancelled',
			'wc-failed',
		),
		'subscription_enrollment_status' => array(
			'wc-cancelled',
			'wc-expired',
			'wc-on-hold',
		),
		'account_endpoints'              => array(
			'view-courses',
			'my-grades',
			'view-memberships',
			'view-groups',
			'view-achievements',
			'view-certificates',
			'notifications',
			'redeem-voucher',
		),
	);

	foreach ( $options as $option_name => $default ) {
		if ( ! get_option( "lifterlms_woocommerce_{$option_name}", false ) ) {
			add_option(
				"lifterlms_woocommerce_{$option_name}",
				$default
			);
		}
	}

	return false;

}

/**
 * Update DB version to 2.5.0 when migration is complete.
 *
 * @since 2.5.0
 *
 * @return bool
 */
function llms_wc_upgrade_250_update_version() {
	LLMS_WC_Install::update_version( '2.5.0' );
	return false;
}
