<?php
/**
 * Handle additions to the WC My Account page.
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 2.0.1
 * @version 2.2.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_My_Account class.
 *
 * @since 2.0.1
 */
class LLMS_WC_My_Account {

	/**
	 * Constructor.
	 *
	 * @since 2.0.1
	 * @since 2.0.10 Add endpoint rewrite handler.
	 */
	public function __construct() {

		add_action( 'wp', array( $this, 'add_actions' ) );
		add_filter( 'lifterlms_get_endpoint_url', array( $this, 'modify_endpoint_urls' ), 10, 2 );
	}

	/**
	 * Add actions after WP setup.
	 *
	 * @since 2.0.1
	 *
	 * @return void
	 */
	public function add_actions() {

		if ( is_account_page() ) {

			// Disables pagination rewriting on the Student Dashboard.
			add_filter( 'llms_modify_dashboard_pagination_links_disable', '__return_true' );

		}
	}

	/**
	 * Modify LifterLMS Dashboard Endpoint URLs
	 *
	 * @since 2.0.10
	 * @since 2.2.1 Only replace the URL if a WC dashboard lost password url exists.
	 *
	 * @param string $url Endpoint url.
	 * @param string $endpoint Endpoint query var.
	 * @return string
	 */
	public function modify_endpoint_urls( $url, $endpoint ) {

		if ( function_exists( 'llms_pa_get_option' ) && llms_pa_get_option( 'slug_area' ) === $endpoint && function_exists( 'wc_get_account_endpoint_url' ) ) {
			$wc_url = wc_get_account_endpoint_url( $endpoint );

			$url = ! empty( $wc_url ) ? $wc_url : $url;
		}

		// Resolve WC 3.6 & later conflict causing LifterLMS Dashboard lost password endpoint to stop working.
		if ( 'lost-password' === $endpoint ) {
			$wc_url = wc_lostpassword_url();
			/**
			 * Only replace the URL if a WC dashboard lost password url exists.
			 *
			 * @link https://github.com/gocodebox/lifterlms-integration-woocommerce/issues/112
			 */
			$url = ! empty( $wc_url ) ? $wc_url : $url;
		}
		return $url;
	}
}

return new LLMS_WC_My_Account();
