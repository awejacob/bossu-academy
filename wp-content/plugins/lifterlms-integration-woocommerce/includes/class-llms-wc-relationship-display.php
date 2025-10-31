<?php
/**
 * Output course/membership relatinoship info on the frontend.
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 2.0.0
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_Relationship_Display class..
 */
class LLMS_WC_Relationship_Display {

	/**
	 * Expiration current item.
	 *
	 * @var WC_Order
	 */
	private $expiration_current_item;

	/**
	 * Constructor.
	 *
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function __construct() {

		// Product box on single product pages.
		add_action( 'woocommerce_product_meta_end', array( $this, 'product_meta_end' ) );

		// Order details on order confirmation page.
		add_action( 'woocommerce_order_item_meta_end', array( $this, 'order_item_meta_end' ), 10, 2 );

	}

	/**
	 * Output course & membership association metadata on order thank you pages.
	 *
	 * @param   int $item_id WC_Order_Item ID.
	 * @param   obj $item    WC_Order_Item.
	 * @return  void
	 * @since   2.0.0
	 * @version 2.0.8
	 */
	public function order_item_meta_end( $item_id, $item ) {

		$this->expiration_current_item = $item;
		add_filter( 'llms_wc_get_product_association_list_item_html', array( $this, 'maybe_output_expiration' ), 10, 2 );

		$ids = llms_wc_get_order_item_products( $item );
		if ( $ids ) {
			echo '<div class="llms-wc-associations-wrap">';
			$this->output_lists( $ids );
			echo '</div>';
		}

		unset( $this->expiration_current_item );
		remove_filter( 'llms_wc_get_product_association_list_item_html', array( $this, 'maybe_output_expiration' ), 10, 2 );

	}

	/**
	 * Output course / membership expiration information on order review and thank you page order item tables.
	 * Filter callback function for `llms_wc_get_product_association_list_html`
	 *
	 * @param   string $html    HTML of the course/membership association.
	 * @param   int    $post_id WP_Post ID of the course/membership.
	 * @return  string
	 * @since   2.0.8
	 * @version 2.0.8
	 */
	public function maybe_output_expiration( $html, $post_id ) {

		if ( ! $this->expiration_current_item || ! is_a( $this->expiration_current_item, 'WC_Order_Item_Product' ) ) {
			return $html;
		}

		$expires = LLMS_WC_Order_Actions::get_expiration( $this->expiration_current_item->get_order_id(), $post_id );

		if ( $expires ) {
			$html .= sprintf( ' <small class="llms-wc-access-expires">(%1$s: <time>%2$s</time>)</small>', __( 'Expires', 'lifterlms-woocommerce' ), date_i18n( get_option( 'date_format' ), $expires ) );
		}

		return $html;

	}

	/**
	 * Output course/membership association list html.
	 *
	 * @param   array $ids WP_Post IDs of related courses/memberships.
	 * @return  void
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	private function output_lists( $ids ) {

		$prepared = $this->prepare_ids( $ids );
		if ( ! $prepared ) {
			return;
		}

		foreach ( $prepared as $post_type => $ids ) {

			/**
			 * Filter: llms_wc_allowed_relationship_types
			 *
			 * Determines what post types can be associated with a WooCommerce product.
			 *
			 * @since    2.0.0
			 * @version  2.0.0
			 *
			 * @param  array $post_types Indexed array of post types. Default is [ 'course', 'llms_membership' ].
			 */
			if ( ! in_array( $post_type, apply_filters( 'llms_wc_allowed_relationship_types', array( 'course', 'llms_membership' ) ), true ) ) {
				continue;
			}

			echo llms_wc_get_product_association_list( $ids, $post_type );

		}

	}

	/**
	 * Converts a list of post IDs to an associative array with the post type as the key and the value a list of the post ids.
	 *
	 * @param   array $ids WP_Post IDs list.
	 * @return  array
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	private function prepare_ids( $ids ) {

		$ret = array();

		$ids = array_unique( $ids );

		foreach ( array_unique( $ids ) as $id ) {

			$type = get_post_type( $id );
			if ( ! isset( $ret[ $type ] ) ) {
				$ret[ $type ] = array();
			}
			$ret[ $type ][] = $id;

		}

		return $ret;

	}

	/**
	 * Output course & membership association metadata on single product pages.
	 *
	 * @since 2.0.0
	 * @since 2.3.0 Use `wc_get_product()` to get the current product rather than relying on the global `$post` ID.
	 *
	 * @return void
	 */
	public function product_meta_end() {

		$product = wc_get_product();

		if ( ! $product ) {
			return;
		}

		$ids = llms_get_llms_products_by_wc_product_id( $product->get_id() );

		if ( llms_wc_is_product_variable( $product ) ) {
			foreach ( $product->get_available_variations() as $variation ) {
				$ids = array_merge(
					$ids,
					llms_get_llms_products_by_wc_product_id( $variation['variation_id'] )
				);
			}
		}

		$this->output_lists( $ids );

	}

}


return new LLMS_WC_Relationship_Display();
