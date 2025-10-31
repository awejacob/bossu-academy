<?php
/**
 * Handle custom fields and manage redirects related to course & membership sales page options
 *
 * @package  LifterLMS_WooCommerce/Classes
 *
 * @since    2.0.0
 * @version  2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_Sales_Pages.
 */
class LLMS_WC_Sales_Pages {

	/**
	 * Constructor
	 *
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function __construct() {

		add_filter( 'llms_sales_page_types', array( $this, 'register_sales_page_type' ) );
		add_filter( 'llms_metabox_fields_lifterlms_course_options', array( $this, 'register_fields' ) );
		add_filter( 'llms_metabox_fields_lifterlms_membership', array( $this, 'register_fields' ) );

		add_filter( 'llms_course_has_sales_page_redirect', array( $this, 'has_redirect' ), 25, 3 );
		add_filter( 'llms_membership_has_sales_page_redirect', array( $this, 'has_redirect' ), 25, 3 );

		add_filter( 'llms_course_get_sales_page_url', array( $this, 'get_url' ), 25, 3 );
		add_filter( 'llms_membership_get_sales_page_url', array( $this, 'get_url' ), 25, 3 );

		add_action( 'llms_metabox_after_save_lifterlms-course-options', array( $this, 'save_fields' ) );
		add_action( 'llms_metabox_after_save_lifterlms-membership', array( $this, 'save_fields' ) );

	}

	/**
	 * Filter the return of "has_sales_page_redirect" for courses & memberships
	 *
	 * @param    string $url           default url.
	 * @param    obj    $post          LLMS_Course or LLMS_Membership post model.
	 * @param    string $content_type  value of the sales page content type option.
	 * @return   bool
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function get_url( $url, $post, $content_type ) {

		if ( 'wc_product' === $content_type ) {
			$id = absint( $post->get( 'sales_page_content_wc_product_id' ) );
			if ( $id ) {
				$url = get_permalink( $id );
			}
		}

		return $url;
	}

	/**
	 * Filter the return of "has_sales_page_redirect" for courses & memberships
	 *
	 * @param    bool   $bool          default value.
	 * @param    obj    $post          LLMS_Course or LLMS_Membership post model.
	 * @param    string $content_type  value of the sales page content type option.
	 * @return   bool
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function has_redirect( $bool, $post, $content_type ) {

		if ( false === $bool && 'wc_product' === $content_type ) {
			$bool = true;
		}

		return $bool;
	}

	/**
	 * Register custom course & memberships meta fields
	 *
	 * @param    array $fields  array of fields data.
	 * @return   array
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function register_fields( $fields ) {
		global $post;
		$product_id            = get_post_meta( $post->ID, '_llms_sales_page_content_wc_product_id', true );
		$fields[0]['fields'][] = array(
			'controller'       => '#_llms_sales_page_content_type',
			'controller_value' => 'wc_product',
			'data_attributes'  => array(
				'post-type'   => 'product',
				'placeholder' => __( 'Select a product', 'lifterlms-woocommerce' ),
			),
			'class'            => 'llms-select2-post',
			'id'               => '_llms_sales_page_content_wc_product_id',
			'type'             => 'select',
			'label'            => __( 'Select a Product', 'lifterlms-woocommerce' ),
			'value'            => $product_id ? llms_make_select2_post_array( array( $product_id ) ) : array(),
		);
		return $fields;
	}

	/**
	 * Add a custom sales page content type to courses & memberships
	 *
	 * @param    array $types  default sales page content types.
	 * @return   array
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function register_sales_page_type( $types ) {
		$types['wc_product'] = __( 'Redirect to a WooCommerce Product', 'lifterlms-woocommerce' );
		return $types;
	}

	/**
	 * Save custom meta fields
	 *
	 * @param    int $post_id  WP Post ID.
	 * @return   void
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function save_fields( $post_id ) {

		$page_id = filter_input( INPUT_POST, '_llms_sales_page_content_wc_product_id', FILTER_SANITIZE_NUMBER_INT );
		if ( $page_id ) {
			update_post_meta( $post_id, '_llms_sales_page_content_wc_product_id', $page_id );
		}

	}

}

return new LLMS_WC_Sales_Pages();
