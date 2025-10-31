<?php
/**
 * Handle custom product meta data and options
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 2.0.0
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_Product_Meta class.
 *
 * @since 2.0.0
 */
class LLMS_WC_Product_Meta {

	/**
	 * Constructor
	 *
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function __construct() {

		add_action( 'woocommerce_product_options_advanced', array( $this, 'add_advanced_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_advanced_fields' ) );

		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_variation_fields' ), 25, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_fields' ), 25, 2 );

		add_filter( 'woocommerce_available_variation', array( $this, 'modify_variation_array' ), 25, 3 );

	}

	/**
	 * Add some custom fields to WooCommerce Products Advanced tab
	 *
	 * @return   void
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function add_advanced_fields() {

		global $post;

		if ( llms_wc_is_product_variable( $post->ID ) ) {
			return;
		}

		woocommerce_wp_select(
			array(
				'desc_tip'    => true,
				'description' => __( 'Customers must belong to the selected LifterLMS membership in order to purchase this product.', 'lifterlms-woocommerce' ),
				'id'          => '_llms_membership_id',
				'label'       => __( 'Members Only', 'lifterlms-woocommerce' ),
				'options'     => $this->get_memberships(),
			)
		);

		woocommerce_wp_text_input(
			array(
				'desc_tip'    => true,
				'description' => __( 'Customize the text of the button displayed to non-members.', 'lifterlms-woocommerce' ),
				'id'          => '_llms_membership_btn_txt',
				'label'       => __( 'Members Button Text', 'lifterlms-woocommerce' ),
				'value'       => LLMS_WC_Availability_Buttons::get_button_text( $post->ID ),
			)
		);

	}

	/**
	 * Output custome fields for a variable product
	 *
	 * @param    int   $loop            index of the variation within the current loop.
	 * @param    array $variation_data  variation data as key => val pairs.
	 * @param    obj   $variation       WP_Post for the variation.
	 * @return   void
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function add_variation_fields( $loop, $variation_data, $variation ) {

		woocommerce_wp_select(
			array(
				'desc_tip'      => true,
				'description'   => __( 'Customers must belong to the selected LifterLMS membership in order to purchase this product.', 'lifterlms-woocommerce' ),
				'id'            => '_llms_membership_id_' . $loop,
				'name'          => '_llms_membership_id[' . $loop . ']',
				'label'         => __( 'Members Only', 'lifterlms-woocommerce' ),
				'value'         => get_post_meta( $variation->ID, '_llms_membership_id', true ),
				'options'       => $this->get_memberships(),
				'wrapper_class' => 'form-row form-row-first',
			)
		);

		woocommerce_wp_text_input(
			array(
				'desc_tip'      => true,
				'description'   => __( 'Customize the text of the button displayed to non-members.', 'lifterlms-woocommerce' ),
				'id'            => '_llms_membership_btn_txt' . $loop,
				'name'          => '_llms_membership_btn_txt[' . $loop . ']',
				'label'         => __( 'Members Button Text', 'lifterlms-woocommerce' ),
				'value'         => LLMS_WC_Availability_Buttons::get_button_text( $variation->ID ),
				'wrapper_class' => 'form-row form-row-last',
			)
		);

	}

	/**
	 * Retrieve a list of memberships and build an array used in product meta select boxes
	 *
	 * @return   array
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	private function get_memberships() {

		$query = new WP_Query(
			array(
				'order'          => 'ASC',
				'orderby'        => 'title',
				'post_status'    => 'publish',
				'post_type'      => 'llms_membership',
				'posts_per_page' => -1,
			)
		);

		$options = array(
			'' => __( 'Available to all customers', 'lifterlms-woocommerce' ),
		);

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$options[ $post->ID ] = $post->post_title . ' (ID# ' . $post->ID . ')';
			}
		}

		return $options;

	}

	/**
	 * Expose the membership restriction ID to the array of data added to the variation add to cart form
	 *
	 * @param    array $array      variation data array.
	 * @param    obj   $product    WC_Product.
	 * @param    obj   $variation  WC_Product_Variable.
	 * @return   array
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function modify_variation_array( $array, $product, $variation ) {

		$membership_id = get_post_meta( $variation->get_id(), '_llms_membership_id', true );
		$restriction   = 'no';

		if ( $membership_id && ! llms_is_user_enrolled( get_current_user_id(), $membership_id ) ) {
			$restriction = 'yes';
		}

		$array['llms_restriction'] = $restriction;

		return $array;
	}

	/**
	 * Update postmeta data for availability restrictions
	 *
	 * @param    int        $post_id        WP Post ID of a product/product variation.
	 * @param    int|string $membership_id  WP Post ID of an LLMS Membership or an empty string to remove restrictions.
	 * @return   void
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	private function update_membership_restrictions( $post_id, $membership_id ) {

		update_post_meta( $post_id, '_llms_membership_id', esc_attr( $membership_id ) );

	}

	/**
	 * Save custom WC Product fields.
	 *
	 * @since 2.0.0
	 * @since 2.0.4 Unknown.
	 * @since 2.3.0 Replaced use of the deprecated `FILTER_SANITIZE_STRING` constant.
	 *
	 * @param int $post_id WP Post ID of the WC Product.
	 * @return void
	 */
	public function save_advanced_fields( $post_id ) {

		$val = '';
		$id  = filter_input( INPUT_POST, '_llms_membership_id', FILTER_SANITIZE_NUMBER_INT );
		if ( $id ) {
			$val = $id;
		}
		$this->update_membership_restrictions( $post_id, $val );

		$txt = llms_filter_input_sanitize_string( INPUT_POST, '_llms_membership_btn_txt' );
		if ( $txt ) {
			update_post_meta( $post_id, '_llms_membership_btn_txt', $txt );
		}

	}

	/**
	 * Save custom WC Product variation fields
	 *
	 * @param    int $variation_id  WP Post ID of the WC Product Variation.
	 * @param    int $loop          Index of the variation in the variation list loop.
	 * @return   void
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function save_variation_fields( $variation_id, $loop ) {

		$ids = filter_input( INPUT_POST, '_llms_membership_id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( $ids && isset( $ids[ $loop ] ) ) {
			$this->update_membership_restrictions( $variation_id, absint( $ids[ $loop ] ) );
		}

		$texts = filter_input( INPUT_POST, '_llms_membership_btn_txt', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( $texts && isset( $texts[ $loop ] ) ) {
			update_post_meta( $variation_id, '_llms_membership_btn_txt', sanitize_text_field( $texts[ $loop ] ) );
		}

	}

}

return new LLMS_WC_Product_Meta();
