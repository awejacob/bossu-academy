<?php
/**
 * LifterLMS Social Learning Compatibility
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 2.1.0
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_Social_Learning class.
 *
 * @since 2.1.0
 * @since 2.1.1 Ensure that SL integration is active before attaching hooks.
 */
class LLMS_WC_Social_Learning {

	/**
	 * Constructor
	 *
	 * @since 2.1.0
	 * @since 2.1.1 Ensure that SL integration is active before attaching hooks.
	 * @since 2.2.0 Added compatibility with LifterLMS > 5.0.
	 *
	 * @return void
	 */
	public function __construct() {

		if ( class_exists( 'LifterLMS_Social_Learning' ) && LifterLMS_Social_Learning::instance()->get_integration() ) {

			add_filter( 'llms_wc_account_endpoints', array( $this, 'modify_endpoints' ) );
			add_filter( 'woocommerce_get_endpoint_url', array( $this, 'get_endpoint_url' ), 20, 2 );

			// LifterLMS < 5.0 compatibility.
			add_filter( 'lifterlms_user_update_data', array( $this, 'validate_llms_user_data' ), 200, 3 ); // Social Learning runs at 100.
			// LifterLMS > 5.0 compatibility.
			add_filter( 'lifterlms_user_update_required_data', array( $this, 'validate_llms_user_data' ), 10, 3 );

			add_action( 'woocommerce_edit_account_form', array( $this, 'output_account_fields' ) );
			add_action( 'woocommerce_save_account_details_errors', array( $this, 'save_account_fields' ), 20, 2 );

			add_filter( 'llms_sl_directory_profile_navigation', array( $this, 'modify_directory_profile_navigation' ), 20 );

		}

	}

	/**
	 * Retrieves and filters down a list of LifterLMS Account Fields added by the Social Learning add-on
	 *
	 * @since 2.1.0
	 *
	 * @return array[]
	 */
	protected function get_account_fields() {

		$sl_dashboard = new LLMS_SL_Student_Dashboard();
		$all_fields   = $sl_dashboard->person_fields( array(), 'account' );
		$fields       = array();
		$skip_fields  = array(
			// WC already has a display name field.
			'display_name',
			// We don't need our heading.
			'llms-sl-profile-heading',
		);

		foreach ( $all_fields as $field ) {

			// Skip anything without an ID and fields we've determined we can skip.
			if ( empty( $field['id'] ) || in_array( $field['id'], $skip_fields, true ) ) {
				continue;
			}

			$fields[ $field['id'] ] = $field;

		}

		return $fields;

	}

	/**
	 * Modify the URL of WC account page tabs
	 *
	 * This method converts the `sl_profile` tab from an endpoint on the dashboard
	 * to a physical URL.
	 *
	 * @since 2.1.0
	 *
	 * @param string $url      Tab URL.
	 * @param string $endpoint Dashboard endpoint identifier.
	 * @return string
	 */
	public function get_endpoint_url( $url, $endpoint ) {

		if ( 'sl_profile' === $endpoint ) {
			$url = LLMS_SL_Directory::get_profile_url( llms_get_student() );
		}

		return $url;

	}

	/**
	 * Retrieve an array of SL account field from $_POST
	 *
	 * Used by the `save_account_fields()` method.
	 *
	 * The resulting array is formatted to be passed to `llms_update_user()`.
	 *
	 * @since 2.1.0
	 * @since 2.3.0 Replaced use of the deprecated `FILTER_SANITIZE_STRING` constant.
	 *
	 * @param int $user_id The WP_User ID of the current user.
	 * @return array
	 */
	protected function get_posted_account_fields( $user_id ) {

		$posted = compact( 'user_id' );

		foreach ( array_keys( $this->get_account_fields() ) as $field ) {
			if ( isset( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WC before this method is called.
				$posted[ $field ] = llms_filter_input_sanitize_string( INPUT_POST, $field );
			}
		}

		return $posted;

	}

	/**
	 * Modify the Social Learning profile tabs
	 *
	 * This method converts the "Dashboard" tab (displayed to logged in users
	 * when they are viewing their own profile) to direct to the WooCommerce
	 * My Account page instead of the LifterLMS Student Dashboard.
	 *
	 * @since 2.1.0
	 * @since 2.1.3 Made sure the wc account page option is set and the page exists.
	 *
	 * @param array[] $nav_items Array of navigation items.
	 * @return array[]
	 */
	public function modify_directory_profile_navigation( $nav_items ) {

		if ( isset( $nav_items['dashboard'] ) ) {
			$page_id = get_option( 'woocommerce_myaccount_page_id' );
			if ( $page_id && get_post( $page_id ) ) {
				$nav_items['dashboard']['url']   = get_permalink( $page_id );
				$nav_items['dashboard']['title'] = get_the_title( $page_id );
			}
		}

		return $nav_items;

	}

	/**
	 * Modify dashboard endpoints ported from LifterLMS to WooCommerce
	 *
	 * This methods converts the social learning profile (which is not a true endpoint)
	 * to still have an endpoint property so that we can identify the link when
	 * it is passed through `woocommerce_get_endpoint_url`.
	 *
	 * @since 2.1.0
	 *
	 * @param array[] $endpoints Array of endpoint data.
	 * @return array[]
	 */
	public function modify_endpoints( $endpoints ) {

		if ( isset( $endpoints['sl_profile'] ) ) {
			$endpoints['sl_profile']['endpoint'] = 'sl_profile';
		}

		return $endpoints;

	}

	/**
	 * Modify the markup of the html generated by `llms_form_field()` to be more like WC account field markup.
	 *
	 * This doesn't make the LifterLMS form field markup identical to the WC form field markup but it gets somewhat
	 * close and makes them look pretty close to visually identical on the frontend.
	 *
	 * @since 2.1.0
	 *
	 * @param string $field Field HTML.
	 * @return string
	 */
	public function modify_form_field_markup( $field ) {

		$search_replace = array(

			// Remove clearfix divs.
			'<div class="clear"></div>' => '',

			// Wrap content within LLMS divs with the paragraph wrapper used by other WC fields on the page.
			'<label'                    => '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide"><label',

			// Close the WC paragraph wrapper.
			'</div>'                    => '</p></div>',

		);

		return str_replace( array_keys( $search_replace ), array_values( $search_replace ), $field );

	}

	/**
	 * Output LifterLMS Social Learning user profile information fields on the WC Account Edit screen.
	 *
	 * This method is kind of dirty. The markup won't be identical and it adds some inline CSS in order to avoid
	 * having to output a whole stylesheet in order to normalize the spacing.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function output_account_fields() {

		$uid = get_current_user_id();

		// Dirty inline CSS to normalize the spacing.
		echo '<style type="text/css">.llms-form-field { padding: 0; }</style>';

		// Wrap profile fields in a fieldset like WC does.
		echo '<fieldset><legend>' . esc_html__( 'Profile Information', 'lifterlms-woocommerce' ) . '</legend>';

		// Add a filter to modify the LifterLMS form field markup.
		add_filter( 'llms_form_field', array( $this, 'modify_form_field_markup' ) );
		foreach ( $this->get_account_fields() as $field ) {

			$field['value'] = get_user_meta( $uid, $field['id'], true );
			llms_form_field( $field );

		}
		remove_filter( 'llms_form_field', array( $this, 'modify_form_field_markup' ) );

		echo '</fieldset>';

	}

	/**
	 * Modify a `WP_Error` from `validate_llms_user_data()`
	 *
	 * Removes all errors from the object that are not expected by Social Learning.
	 *
	 * @since 2.1.0
	 *
	 * @param WP_Error $wp_err Error object.
	 * @return bool|WP_Error
	 */
	protected function remove_non_sl_validation_errors( $wp_err ) {

		// These are the only possible validation errors that can be found by Social Learning.
		$sl_errors = array( 'url-format', 'handle' );

		// Loop through all errors.
		foreach ( $wp_err->get_error_codes() as $err ) {

			// If it's not a SL error, remove it from the object.
			if ( ! in_array( $err, $sl_errors, true ) ) {
				$wp_err->remove( $err );
			}
		}

		// If there are no more errors, return `true` to signify the data is valid.
		return empty( $wp_err->get_error_codes() ) ? true : $wp_err;

	}

	/**
	 * Callback function run after WC core account fields are saved on the my account page
	 *
	 * This method gathers post data for the social learning fields added to the WC account page
	 * and passes them into `llms_update_user()` to store.
	 *
	 * Since we're not passing in an entire user data as would be found on the LLMS dashboard, errors
	 * will be returned, so we filter out these errors in the `validate_llms_user_data()` method.
	 *
	 * @since 2.1.0
	 *
	 * @param WP_Error $errors Error object from WC. Passed by reference.
	 * @param object   $user   User object from WC. This is *not* a WP_User, rather a stdClass with a few user properties.
	 * @return void
	 */
	public function save_account_fields( &$errors, $user ) {

		$update = llms_update_user( $this->get_posted_account_fields( $user->ID ) );

		// If we encounter errors during the update, push those errors into the original WP_Error from the WC hook.
		if ( is_wp_error( $update ) ) {
			foreach ( $update->errors as $code => $msg ) {
				$errors->add( $code, is_array( $msg ) ? $msg[0] : $msg );
			}
		}

	}

	/**
	 * Modify LifterLMS user update validation responses
	 *
	 * When updating a user from the WC account edit screen, we will not have all the fields
	 * which *might* be required by LifterLMS.
	 *
	 * This method has a whitelist of Social Learning errors that may be encountered when updating the SL
	 * fields.
	 *
	 * We'll remove any errors not on this whitelist so that we can pass validation and update
	 * the user with only the social learning fields.
	 *
	 * @since 2.1.0
	 *
	 * @param bool|WP_Error $valid  Whether the validation was successful.
	 * @param array         $data   Array of data from the update.
	 * @param string        $screen Screen where the validation is taking place.
	 * @return bool|WP_Error
	 */
	public function validate_llms_user_data( $valid, $data, $screen ) {

		if ( 'account' !== $screen || ! is_account_page() ) {
			return $valid;
		}

		return is_wp_error( $valid ) ? $this->remove_non_sl_validation_errors( $valid ) : $valid;

	}

}

return new LLMS_WC_Social_Learning();
