<?php
/**
 * Functions
 *
 * @package LifterLMS_WooCommerce/Functions
 *
 * @since 1.0.0
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retrieve array of product ids for LLMS courses/memberships attached to a WC_Order_Item_Product
 *
 * @param   obj $item WC_Order_Item_Prodct.
 * @return  array
 * @since   2.0.0
 * @version 2.0.0
 */
function llms_wc_get_order_item_products( $item ) {

	$ret = array();

	if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
		$ret = wc_get_order_item_meta( $item->get_id(), '_llms_pid', false );
	}

	return $ret;

}

/**
 * Locate LifterLMS Access Plan(s) accosicated with a WooCommerce Product
 *
 * @param    int $product_id  WC_Product ID or WC_Product_Variation ID.
 * @return   array
 * @since    2.0.0
 * @version  2.0.4
 */
function llms_get_llms_plans_by_wc_product_id( $product_id ) {

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return array();
	}

	$meta_query = array(
		'relation' => 'OR',
		array(
			'key'   => '_llms_wc_pid',
			'value' => $product_id,
		),
	);

	// If we've passed in a variation, also see if the parent product has any plans.
	// If variable product is set as the plan product that means that a purchase of ANY variation will result in enrollment.
	$type = $product->get_type();
	if ( 'variation' === $type || 'subscription_variation' === $type ) {
		$meta_query[] = array(
			'key'   => '_llms_wc_pid',
			'value' => $product->get_parent_id(),
		);
	}

	$query = new WP_Query(
		array(
			'meta_query'     => $meta_query,
			'post_status'    => 'publish',
			'post_type'      => 'llms_access_plan',
			'posts_per_page' => -1,
		)
	);

	return wp_list_pluck( $query->posts, 'ID' );

}

/**
 * Locate LifterLMS Product(s) associated with a WooCommerce Product
 *
 * @param    int $product_id  WC_Product ID or WC_Product_Variation ID.
 * @return   array
 * @since    1.2.0
 * @version  2.0.0
 */
function llms_get_llms_products_by_wc_product_id( $product_id ) {

	$ids = array();
	foreach ( llms_get_llms_plans_by_wc_product_id( $product_id ) as $plan_id ) {

		$plan  = llms_get_post( $plan_id );
		$ids[] = $plan->get( 'product_id' );

	}

	return array_unique( $ids );

}

/**
 * Retrieve an array of LifterLMS access plans attached to items in a WC Order
 *
 * @param    obj $order   instance of a WC_Order.
 * @return   array
 * @since    2.0.0
 * @version  2.0.0
 */
function llms_get_llms_plans_in_wc_order( $order ) {

	$plans = array();

	foreach ( $order->get_items() as $item ) {

		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			continue;
		}

		$pid   = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
		$plans = array_merge( $plans, llms_get_llms_plans_by_wc_product_id( $pid ) );

	}

	return array_unique( $plans );

}

/**
 * Retrieve an array of LifterLMS products attached to items in a WC Order
 *
 * @param    obj $order   instance of a WC_Order.
 * @return   array
 * @since    1.2.0
 * @version  2.0.0
 */
function llms_get_llms_products_in_wc_order( $order ) {

	$llms_products = array();

	foreach ( $order->get_items() as $item ) {

		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			continue;
		}

		$pid           = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
		$llms_products = array_merge( $llms_products, llms_get_llms_products_by_wc_product_id( $pid ) );

	}

	return array_unique( $llms_products );

}

/**
 * Get a list of course or membership links
 * Used to output associations on WC product page with other product metadata
 *
 * @param    array  $ids        list of Post IDs.
 * @param    string $post_type  post type name, used to get language for list label.
 * @return   string
 * @since    2.0.0
 * @version  2.0.8
 */
function llms_wc_get_product_association_list( $ids, $post_type ) {

	$obj = get_post_type_object( $post_type );

	$single = ( 1 === count( $ids ) );
	$links  = array();
	foreach ( $ids as $id ) {

		/**
		 * Filter: llms_wc_get_product_association_list_item_html
		 *
		 * Modify the the html output of each link output by llms_wc_get_product_association_list()
		 *
		 * @since    2.0.8
		 * @version  2.0.8
		 *
		 * @param  string $html The html of the anchor.
		 * @param  int  $id WP_Post ID of a course/membership.
		 */
		$links[] = apply_filters( 'llms_wc_get_product_association_list_item_html', sprintf( '<a href="%1$s">%2$s</a>', esc_url( get_permalink( $id ) ), get_the_title( $id ) ), $id );

	}

	ob_start();
	?>
	<span class="llms-wc-associations <?php echo $obj->name; ?>">
		<?php echo $single ? $obj->labels->singular_name : $obj->labels->name; ?>:
		<?php echo implode( ', ', $links ); ?>
	</span>
	<?php

	/**
	 * Filter: llms_wc_get_product_association_list_html
	 *
	 * Modify the default return of the `llms_wc_get_product_association_list()` function.
	 *
	 * @since    2.0.8
	 * @version  2.0.8
	 *
	 * @param  string $html HTML of the association list.
	 * @param  array $ids  list of Post IDs.
	 * @param  string $post_type post type name, used to get language for list label.
	 */
	return apply_filters( 'llms_wc_get_product_association_list_html', ob_get_clean(), $ids, $post_type );

}

/**
 * Determine if a product is a variable product or variable subscription product.
 *
 * @since 2.0.0
 * @since 2.3.0 Check the product isn't a falsy before trying to get its type, to avoid fatals.
 *
 * @param int|WC_Product $product WP Post ID or WC_Product (or subclass of WC_Product).
 * @return boolean
 */
function llms_wc_is_product_variable( $product ) {

	if ( is_numeric( $product ) ) {
		$product = wc_get_product( $product );
	}

	return $product ?
		in_array(
			$product->get_type(),
			array( 'variable', 'variable-subscription' ),
			true
		) :
		false;

}

/**
 * Determine if an LLMS_Access_Plan has an associated WC Product
 *
 * @param    obj $plan  LLMS_Access_Plan.
 * @return   boolean
 * @since    2.0.0
 * @version  2.0.0
 */
function llms_wc_plan_has_wc_product( $plan ) {
	/**
	 * Filter: llms_wc_plan_has_wc_product
	 *
	 * Modify the default return of the `llms_wc_plan_has_wc_product()` function
	 * which determines if an LLMS_Access_Plan has an associated WC Product
	 *
	 * @since    2.0.0
	 * @version  2.0.0
	 *
	 * @example  add_filter( 'llms_wc_plan_has_wc_product', '__return_false' );
	 *
	 * @param  bool $bool whether or not the plan has a WooCommerce product.
	 * @param  obj  $plan LLMS_Acces_Plan object.
	 */
	return apply_filters( 'llms_wc_plan_has_wc_product', ( ! $plan->is_free() && $plan->get( 'wc_pid' ) ), $plan );
}

/**
 * Determine if a WC_Product (or variation) is members only and the current user
 * is not a member of the required membership.
 *
 * @since 2.3.0
 *
 * @param WC_Product $product Instance of `WC_Product`.
 * @return bool
 */
function llms_wc_is_members_only_wc_product_restricted_for_user( $product ) {

	if ( ! $product ) {
		return false;
	}

	$membership_id = get_post_meta( $product->get_id(), '_llms_membership_id', true );

	return $membership_id && ! llms_is_user_enrolled( get_current_user_id(), $membership_id );

}
