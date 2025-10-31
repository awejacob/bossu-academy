<?php
/**
 * Handle syncing of user / customer information between LifterLMS & WooCommerce.
 *
 * @package  LifterLMS_WooCommerce/Classes
 *
 * @since    2.0.0
 * @version  2.2.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_User_Information class..
 */
class LLMS_WC_User_Information {

	/**
	 * Constructor.
	 *
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function __construct() {

		add_action( 'woocommerce_created_customer', array( $this, 'created' ) );
		add_action( 'woocommerce_checkout_update_user_meta', array( $this, 'sync_info' ) );
		add_action( 'woocommerce_customer_save_address', array( $this, 'sync_info' ) );

	}

	/**
	 * When a new customer is created, trigger an action which will trigger LLMS Registration actions after WC actions are complete.
	 * Running LifterLMS actions after meta updates are complete ensures that LifterLMS merge codes (like name) will work.
	 *
	 * @param   int $customer_id WP_User Id.
	 * @return  void
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public function created( $customer_id ) {

		add_action( 'woocommerce_checkout_update_user_meta', array( $this, 'trigger_llms_actions' ), 20, 2 );

	}

	/**
	 * Trigger LifterLMS user registration actions.
	 *
	 * @since 2.0.0
	 * @since 2.2.2 Deprecated the `lifterlms_created_person` action hook.
	 *
	 * @param int   $customer_id WP_User ID.
	 * @param array $data        Array of customer information.
	 * @return void
	 */
	public function trigger_llms_actions( $customer_id, $data ) {

		/**
		 * Filter: llms_wc_trigger_registration_actions
		 *
		 * Determine if LifterLMS registration actions should be triggered during WooCommerce account registration.
		 *
		 * @example add_filter( 'llms_wc_plan_has_wc_product', '__return_false' );
		 *
		 * @since 2.0.0
		 *
		 * @param bool  $bool        Whether or not to trigger LLMS actions. Defaults to true.
		 * @param int   $customer_id WP_User ID of the customer
		 * @param array $data        Associative array of customer information.
		 */
		if ( apply_filters( 'llms_wc_trigger_registration_actions', true, $customer_id, $data ) ) {

			// This action is documented at https://developer.lifterlms.com/reference/hooks/lifterlms_user_registered/.
			do_action( 'lifterlms_user_registered', $customer_id, $data, 'wc_integration' );

			// This action is documented at https://developer.lifterlms.com/reference/hooks/lifterlms_created_person/.
			do_action_deprecated(
				'lifterlms_created_person',
				array( $customer_id ),
				'[version]',
				'lifterlms_user_registered'
			);
		}

	}

	/**
	 * Sync customer keys during WC account updates.
	 *
	 * @param   int $customer_id WP_User Id.
	 * @return  void
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public function sync_info( $customer_id ) {

		$student = llms_get_student( $customer_id );
		if ( ! $student ) {
			return;
		}

		/**
		 * Filter: llms_wc_customer_keys_map
		 *
		 * Maps WooCommerce customer information fileds to LifterLMS customer information fields.
		 *
		 * @since    2.0.0
		 * @version  2.0.0
		 *
		 * @param  array $array Associative array where the key is the WC field name and the value is the LLMS field name.
		 */
		$fields = apply_filters(
			'llms_wc_customer_keys_map',
			array(
				'billing_country'   => 'billing_country',
				'billing_address_1' => 'billing_address_1',
				'billing_address_2' => 'billing_address_2',
				'billing_city'      => 'billing_city',
				'billing_state'     => 'billing_state',
				'billing_postcode'  => 'billing_zip',
				'billing_phone'     => 'phone',
			)
		);

		foreach ( $fields as $wc_key => $llms_key ) {

			$val = get_user_meta( $customer_id, $wc_key, true );
			$student->set( $llms_key, $val );

		}

	}

}

return new LLMS_WC_User_Information();
