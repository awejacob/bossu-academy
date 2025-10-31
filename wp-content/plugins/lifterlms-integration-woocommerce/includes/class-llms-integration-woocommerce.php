<?php
/**
 * LLMS_Integration_WooCommerce class file
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 1.0.0
 * @version 2.4.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * LifterLMS WooCommerce Integration Class.
 *
 * @since 1.0.0
 * @since 2.0.1 Use `lifterlms_template_student_dashboard_my_notifications()` for notifications endpoint output.
 * @since 2.4.1 Upgraded to version 2 of the LLMS_Abstract_Option_Data abstract.
 */
class LLMS_Integration_WooCommerce extends LLMS_Abstract_Integration {

	/**
	 * Integration ID
	 *
	 * @var string
	 */
	public $id = 'woocommerce';

	/**
	 * Available endpoints.
	 *
	 * @var array
	 */
	private $endpoints = array();

	/**
	 * Options data abstract version.
	 *
	 * This is used to determine the behavior of the `get_option()` method.
	 *
	 * Concrete classes should use version 2 in order to use the new (future default)
	 * behavior of the method.
	 *
	 * @var int
	 */
	protected $version = 2;

	/**
	 * Integration Constructor.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Unknown.
	 * @since 2.2.1 Remove hook for deprecated method `add_account_query_vars()`.
	 *
	 * @return void
	 */
	public function configure() {

		$this->title       = __( 'WooCommerce', 'lifterlms-woocommerce' );
		$this->description = __( 'Sell LifterLMS Courses and Memberships using WooCommerce', 'lifterlms-woocommerce' );
		// Translators: %1$s = opening anchor tag; %2$s = closing anchor tag.
		$this->description_missing = sprintf( __( 'You need to install the %1$sWooCommerce core%2$s plugin to use this integration.', 'lifterlms-woocommerce' ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">', '</a>' );

		add_action( 'lifterlms_settings_save_integrations', array( $this, 'after_settings_save' ), 10 );

		if ( $this->is_available() ) {

			add_action( 'init', array( $this, 'add_account_endpoints' ) );

			add_filter( 'woocommerce_account_menu_items', array( $this, 'add_account_menu_item' ) );

			// Output message on core settings checkout pages.
			add_action( 'lifterlms_sections_checkout', array( $this, 'checkout_settings_message' ) );

			// Hide the LLMS Core Payment Gateway notice when WC is active.
			add_filter( 'llms_admin_notice_no_payment_gateways', '__return_true' );

			add_action( 'woocommerce_check_cart_items', array( $this, 'validate_products' ), 20 );

		}
	}

	/**
	 * Add LLMS page endpoint accessed via WC My ACcount Page.
	 *
	 * @since 1.0.0
	 * @since 1.3.6 Unknown.
	 * @since 2.2.1 Make the content display hook match the endpoint key (query var) rather than the endpoint slug.
	 *
	 * @return void
	 */
	public function add_account_endpoints() {

		$this->populate_account_endpoints();

		foreach ( $this->get_account_endpoints() as $endpoint_key => $endpoint ) {

			add_rewrite_endpoint( $endpoint['endpoint'], EP_ROOT | EP_PAGES );
			add_action( 'woocommerce_account_' . $endpoint_key . '_endpoint', array( $this, $endpoint['content'] ) );

		}
	}

	/**
	 * Add an order note for enrollment/unenrollment actions based on status changes.
	 *
	 * @since 1.3.0
	 *
	 * @param WC_Order $order      Order object.
	 * @param int      $product_id WP_Post ID of a course or membership.
	 * @param string   $type       Optional. Note type. Accepts "enrollment" or "unenrollment". Defaults to "enrollment".
	 */
	public function add_order_note( $order, $product_id, $type = 'enrollment' ) {

		/**
		 * Filter: llms_wc_add_{$type}_notes
		 *
		 * Determine whether or not notes should be recorded on a WC Order when enrollment or unenrollment occurs.
		 * {$type} can be either "enrollment" or "unenrollment".
		 *
		 * @since 1.3.0
		 *
		 * @param bool $bool Whether or not to record notes. Defaults to true.
		 */
		if ( apply_filters( "llms_wc_add_{$type}_notes", true ) ) {

			$product = llms_get_post( $product_id );
			if ( is_a( $product, 'WP_Post' ) ) {
				return;
			}

			switch ( $type ) {

				case 'enrollment':
					// Translators: %1$s = course/membership title; %2$s = course or membership name/label.
					$msg = __( 'Customer was enrolled into the "%1$s" %2$s.', 'lifterlms-woocommerce' );
					break;

				case 'unenrollment':
					// Translators: %1$s = course/membership title; %2$s = course or membership name/label.
					$msg = __( 'Customer was unenrolled from the "%1$s" %2$s.', 'lifterlms-woocommerce' );
					break;

			}

			$order->add_order_note( sprintf( $msg, $product->get( 'title' ), strtolower( $product->get_post_type_label() ) ) );

		}
	}

	/**
	 * Add LLMS page links to the WC My Account Page.
	 *
	 * @since 1.0.0
	 * @since 1.3.6 Unknown.
	 *
	 * @param array $items Array of existing menu items.
	 * @return array
	 */
	public function add_account_menu_item( $items ) {

		$logout = array();

		if ( isset( $items['customer-logout'] ) ) {

			$logout = array(
				'customer-logout' => $items['customer-logout'],
			);
			unset( $items['customer-logout'] );
		}

		$endpoints = array();

		foreach ( $this->get_account_endpoints() as $endpoint ) {
			$endpoints[ $endpoint['endpoint'] ] = $endpoint['title'];
		}

		$items = array_merge( $items, $endpoints, $logout );

		return $items;
	}

	/**
	 * Add LLMS query vars for the pages accessible via WC ACcount page.
	 *
	 * @since 1.0.0
	 * @since 1.3.6 Unknown.
	 * @deprecated 2.2.1 `LLMS_Integration_WooCommerce::add_account_query_vars()` is deprecated with no replacement.
	 *
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function add_account_query_vars( $vars ) {

		llms_deprecated_function( 'LLMS_Integration_WooCommerce::add_account_query_vars', '2.2.1' );

		foreach ( $this->get_account_endpoints() as $endpoint ) {
			array_push( $vars, $endpoint['endpoint'] );
		}

		return $vars;
	}

	/**
	 * After saving settings, Flush rewrite rules.
	 *
	 * @since 2.0.0
	 * @since 2.2.1 No need to add account endpoints.
	 * @since 2.3.0 Don't use deprecated `FILTER_SANITIZE_STRING` constant.
	 *
	 * @return void
	 */
	public function after_settings_save() {

		// Only run actions on WC integration settings page.
		$section = filter_input( INPUT_GET, 'section' );
		if ( ! ( $section && $section === $this->id ) ) {
			return;
		}

		// This needs to run again because of the order with which options are set.
		if ( ! $this->is_available() ) {
			return;
		}

		flush_rewrite_rules();
	}

	/**
	 * Outputs a message on LifterLMS Core checkout / gateway settings screens.
	 *
	 * Meant to help orient users to the correct settings to use (WC settings) when integration is enabled.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function checkout_settings_message() {
		$settings   = admin_url( 'admin.php?page=wc-settings&tab=checkout' );
		$extensions = admin_url( 'admin.php?page=wc-addons&section=payment-gateways' );
		?>
		<div class="notice notice-info">
			<p>
			<?php
			printf(
				// Translators: %1$s = opening anchor tag; %2$s opening anchor tag; %3$s = closing anchor tag.
				__(
					'It looks like you\'re using WooCommerce for checkout. When using WooCommerce these LifterLMS settings do not apply, instead, use the equivalent settings on the %1$sWooCommerce settings panel%3$s and install and configure %2$sWooCommerce payment gateways%3$s.',
					'lifterlms-woocommerce'
				),
				'<a href="' . esc_url( $settings ) . '">',
				'<a href="' . esc_url( $extensions ) . '">',
				'</a>'
			);
			?>
			</p>
		</div>
		<?php
	}

	/**
	 * Get a list of custom endpoints to add to WC My Account page
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Unknown.
	 * @since 2.4.1 Don't supply a default value to when retrieving `account_endpoints` option.
	 *
	 * @param bool $active_only Whether or not to retrieve only active endpoints.
	 * @return array
	 */
	public function get_account_endpoints( $active_only = true ) {

		$endpoints = $this->endpoints;

		if ( $active_only ) {

			$active = $this->get_option( 'account_endpoints' );

			// No endpoints are active so we can return early with an empty array.
			if ( empty( $active ) ) {
				return array();
			}

			foreach ( array_keys( $endpoints ) as $endpoint ) {

				// Remove endpoints that aren't stored in the settings.
				if ( ! in_array( $endpoint, $active, true ) ) {
					unset( $endpoints[ $endpoint ] );
				}
			}
		}

		// Remove endpoints that don't have an endpoint.
		foreach ( $endpoints as $ep_key => $endpoint ) {
			if ( empty( $endpoint['endpoint'] ) ) {
				unset( $endpoints[ $ep_key ] );
			}
		}

		return $endpoints;
	}

	/**
	 * Retrieve integration settings.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_integration_settings() {

		$settings = array();

		if ( $this->is_available() ) {

			if ( function_exists( 'wc_get_order_statuses' ) ) {

				$settings[] = array(
					'class'   => 'llms-select2',
					// Translators: %1$s = opening anchor tag; %2$s = closing anchor tag.
					'desc'    => '<br>' . sprintf( __( 'Customers will be enrolled when a WooCommerce Order reaches this status. See how to create products which will automatically complete orders %1$shere%2$s.', 'lifterlms-woocommerce' ), '<a href="https://lifterlms.com/docs/woocommerce-automatic-order-completion/" target="_blank">', '</a>' ),
					'default' => 'wc-completed',
					'id'      => $this->get_option_name( 'enrollment_status' ),
					'options' => wc_get_order_statuses(),
					'type'    => 'select',
					'title'   => __( 'Order Enrollment Status', 'lifterlms-woocommerce' ),
				);

				$settings[] = array(
					'class'   => 'llms-select2',
					'desc'    => '<br>' . __( 'Customers will be unenrolled when a WooCommerce Order reaches any of these statuses', 'lifterlms-woocommerce' ),
					'default' => array( 'wc-refunded', 'wc-cancelled', 'wc-failed' ),
					'id'      => $this->get_option_name( 'unenrollment_statuses' ),
					'options' => wc_get_order_statuses(),
					'type'    => 'multiselect',
					'title'   => __( 'Order Unenrollment Status', 'lifterlms-woocommerce' ),
				);

			}

			$subs_available = function_exists( 'wcs_get_subscription_statuses' );

			if ( ! $subs_available ) {

				$settings[] = array(
					'type'  => 'custom-html',
					// Translators: %1$s = opening anchor tag; %2$s = closing anchor tag.
					'value' => '<em>' . sprintf( __( 'Install the %1$sWooCommerce Subscriptions%2$s extension to create recurring subscriptions or payment plans for your course and memberships.', 'lifterlms-woocommerce' ), '<a href="https://woocommerce.com/products/woocommerce-subscriptions/" target="_blank">', '</a>' ) . '</em>',
				);

			}

			$settings[] = array(
				'class'    => 'llms-select2',
				'desc'     => '<br>' . __( 'Customers will be enrolled when a WooCommerce Subscription reaches this status', 'lifterlms-woocommerce' ),
				'default'  => 'wc-active',
				'disabled' => ( ! $subs_available ),
				'id'       => $this->get_option_name( 'subscription_enrollment_status' ),
				'options'  => $subs_available ? wcs_get_subscription_statuses() : array(),
				'type'     => 'select',
				'title'    => __( 'Subscription Enrollment Status', 'lifterlms-woocommerce' ),
			);

			$settings[] = array(
				'class'    => 'llms-select2',
				'desc'     => '<br>' . __( 'Customers will be unenrolled when a WooCommerce Subscription reaches any of these statuses', 'lifterlms-woocommerce' ),
				'default'  => array( 'wc-cancelled', 'wc-expired', 'wc-on-hold' ),
				'disabled' => ( ! $subs_available ),
				'id'       => $this->get_option_name( 'subscription_unenrollment_statuses' ),
				'options'  => $subs_available ? wcs_get_subscription_statuses() : array(),
				'type'     => 'multiselect',
				'title'    => __( 'Subscription Unenrollment Status', 'lifterlms-woocommerce' ),
			);

			$endpoints = $this->get_account_endpoints( false );

			$display_eps = array();

			foreach ( $endpoints as $ep_key => $endpoint ) {
				$display_eps[ $ep_key ] = $endpoint['title'];
			}

			$settings[] = array(
				'class'   => 'llms-select2',
				'desc'    => '<br>' . __( 'The following LifterLMS Student Dashboard areas will be added to the WooCommerce My Account Page', 'lifterlms-woocommerce' ),
				'default' => array_keys( $display_eps ),
				'id'      => $this->get_option_name( 'account_endpoints' ),
				'options' => $display_eps,
				'type'    => 'multiselect',
				'title'   => __( 'My Account Endpoints', 'lifterlms-woocommerce' ),
			);

			$settings[] = array(
				'desc'         => __( 'Enable debug logging', 'lifterlms-woocommerce' ),
				// Translators: %s = log file path.
				'desc_tooltip' => sprintf( __( 'When enabled, debugging information will be logged to "%s"', 'lifterlms-woocommerce' ), llms_get_log_path( 'woocommerce' ) ),
				'id'           => $this->get_option_name( 'logging_enabled' ),
				'title'        => __( 'Debug Log', 'lifterlms-woocommerce' ),
				'type'         => 'checkbox',
			);

		}

		return $settings;
	}

	/**
	 * Retrieve the option prefix for the integration
	 *
	 * Overrides the defaults from core to prevent the necessity of an options migration.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	protected function get_option_prefix() {
		return 'lifterlms_woocommerce_';
	}

	/**
	 * Populate list of endpoints from LifterLMS Dashboard Settings
	 *
	 * @since 1.3.6
	 *
	 * @return void
	 */
	private function populate_account_endpoints() {

		$exclude_llms_eps = array( 'dashboard', 'edit-account', 'orders', 'signout' );
		$endpoints        = array_diff_key( LLMS_Student_Dashboard::get_tabs(), array_flip( $exclude_llms_eps ) );

		foreach ( $endpoints as $ep_key => &$endpoint ) {

			unset( $endpoint['nav_item'] );
			unset( $endpoint['url'] );

			$endpoint['content'] = 'output_endpoint_' . str_replace( '-', '_', $ep_key );

		}

		/**
		 * Filter: llms_wc_account_endpoints
		 *
		 * Modify the LifterLMS dashboard endpoints which can be added to the WC My Account page as custom tabs.
		 *
		 * @since 1.3.6
		 *
		 * @param array $endpoints Array of endpoint data.
		 */
		$this->endpoints = apply_filters( 'llms_wc_account_endpoints', $endpoints );
	}

	/**
	 * Determine if WooCommerce is installed & activated
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Unknown.
	 *
	 * @return boolean
	 */
	public function is_installed() {
		return ( function_exists( 'WC' ) );
	}

	/**
	 * Log data to the log woocommerce log file
	 *
	 * Only logs if logging is enabled so it's redundant to check logging berofe calling this
	 * accepts any number of arguments of various data types, each will be logged.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function log() {
		if ( llms_parse_bool( $this->get_option( 'logging_enabled', 'no' ) ) ) {
			foreach ( func_get_args() as $data ) {
				llms_log( $data, 'woocommerce' );
			}
		}
	}

	/**
	 * Output the "My Grades"
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function output_endpoint_my_grades() {
		echo '<h2 class="llms-sd-title">' . __( 'My Grades', 'lifterlms-woocommerce' ) . '</h2>';
		lifterlms_template_student_dashboard_my_grades();
	}

	/**
	 * Output Student Courses.
	 *
	 * @since 1.3.6
	 * @since 2.2.1 Use proper endpoint key for pagination.
	 * @since 2.3.0 Remove unneded backward compatibility with LifterLMS 3.14.0-.
	 *
	 * @return void
	 */
	public function output_endpoint_view_courses() {
		$this->setup_endpoint_pagination( 'view-courses' );
		lifterlms_template_student_dashboard_my_courses( false );
	}

	/**
	 * Output student achievements.
	 *
	 * @since 1.3.6
	 * @since 2.3.0 Remove unneded backward compatibility with LifterLMS 3.14.0-.
	 *
	 * @return void
	 */
	public function output_endpoint_view_achievements() {
		lifterlms_template_student_dashboard_my_achievements( false );
	}

	/**
	 * Output student certificates.
	 *
	 * @since 1.3.6
	 * @since 2.3.0 Remove unneded backward compatibility with LifterLMS 3.14.0-.
	 *
	 * @return void
	 */
	public function output_endpoint_view_certificates() {
		lifterlms_template_student_dashboard_my_certificates( false );
	}

	/**
	 * Output student memberships.
	 *
	 * @since 1.3.6
	 *
	 * @return void
	 */
	public function output_endpoint_view_memberships() {
		if ( function_exists( 'LLMS' ) && version_compare( '3.14.0', LLMS()->version, '<=' ) ) {
			lifterlms_template_student_dashboard_my_memberships();
		} else {
			llms_get_template( 'myaccount/my-memberships.php' );
		}
	}

	/**
	 * Output student notifications.
	 *
	 * @since 1.3.0
	 * @since 2.0.1 Use `lifterlms_template_student_dashboard_my_notifications()`.
	 * @since 2.1.0 Remove fallback to deprecated `LLMS_Student_Dashboard::output_notifications_content()`.
	 *
	 * @return void
	 */
	public function output_endpoint_notifications() {
		echo '<h2 class="llms-sd-title">' . __( 'My Notifications', 'lifterlms-woocommerce' ) . '</h2>';
		lifterlms_template_student_dashboard_my_notifications();
	}

	/**
	 * Output voucher redemeption endpoint.
	 *
	 * @since 1.3.6
	 *
	 * @return void
	 */
	public function output_endpoint_redeem_voucher() {
		echo '<h2 class="llms-sd-title">' . __( 'Redeem a Voucher', 'lifterlms-woocommerce' ) . '</h2>';
		LLMS_Student_Dashboard::output_redeem_voucher_content();
	}

	/**
	 * Output student favorites.
	 *
	 * @since 2.5.1
	 *
	 * @return void
	 */
	public function output_endpoint_view_favorites() {
		if ( function_exists( 'llms_is_favorites_enabled' ) && llms_is_favorites_enabled() ) {
			echo '<h2 class="llms-sd-title">' . __( 'My Favorites', 'lifterlms-woocommerce' ) . '</h2>';
			llms_template_student_dashboard_my_favorites( false );
		}
	}

	/**
	 * Output student notes.
	 *
	 * @since 2.5.1
	 *
	 * @return void
	 */
	public function output_endpoint_view_notes() {
		if ( class_exists( 'LLMS_Notes_Student_Dashboard' ) && method_exists( 'LLMS_Notes_Student_Dashboard', 'llms_notes_template_student_dashboard_my_notes' ) ) {
			echo '<h2 class="llms-sd-title">' . __( 'My Notes', 'lifterlms-woocommerce' ) . '</h2>';
			LLMS_Notes_Student_Dashboard::llms_notes_template_student_dashboard_my_notes();
		}
	}

	/**
	 * Output student private area.
	 *
	 * @since 2.7.0
	 *
	 * @return void
	 */
	public function output_endpoint_view_private_areas() {
		if ( function_exists( 'llms_pa_template_student_dashboard_my_private_areas' ) && function_exists( 'llms_pa_get_option' ) ) {
			echo '<h2 class="llms-sd-title">' . esc_html( llms_pa_get_option( 'name_area' ) ) . '</h2>';
			llms_pa_template_student_dashboard_my_private_areas();
		}
	}

	/**
	 * Output student groups.
	 *
	 * @since 2.5.2
	 *
	 * @return void
	 */
	public function output_endpoint_view_groups() {
		if ( function_exists( 'llms_groups_template_student_dashboard_my_groups' ) ) {
			echo '<h2 class="llms-sd-title">' . __( 'My Groups', 'lifterlms-woocommerce' ) . '</h2>';
			llms_groups_template_student_dashboard_my_groups();
		}
	}

	/**
	 * Setup endpoint pagination variables on the $wp_query global.
	 *
	 * @since 1.3.4
	 *
	 * @param string $endpoint The endpoint slug.
	 * @return void
	 */
	private function setup_endpoint_pagination( $endpoint ) {
		global $wp_query;
		if ( ! empty( $wp_query->query[ $endpoint ] ) ) {
			$parts = explode( '/', $wp_query->query[ $endpoint ] );
			$page  = isset( $parts[1] ) && is_numeric( $parts[1] ) ? absint( $parts[1] ) : 1;
			$wp_query->set( 'paged', $page );
		}
	}

	/**
	 * Looks through cart items and checks they can be bought:
	 *
	 * - They don't require the user to be already enrolled into a membership they're not enrolled into.
	 * - They're not associated with any llms product already enrolled by the current user.
	 *
	 * @since 2.0.15
	 * @since 2.3.0 Added check on whether, if the product is limited to members only, the current
	 *              user is enrolled in the required membership. Refactoring moving some code into private methods.
	 *
	 * @return bool
	 */
	public function validate_products() {

		$error = new WP_Error();

		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

			$wc_product = isset( $values['data'] ) ? $values['data'] : null;

			if ( ! method_exists( $wc_product, 'get_id' ) ) {
				continue;
			}

			$user_can_buy_members_only_wc_product = self::user_can_buy_members_only_wc_product( $wc_product );

			if ( is_wp_error( $user_can_buy_members_only_wc_product ) ) {
				$error = $user_can_buy_members_only_wc_product;
			}

			$not_enrolled = self::is_user_not_already_enrolled_into_related_llms_products( $wc_product );
			if ( is_wp_error( $not_enrolled ) ) {
				$error->merge_from( $not_enrolled );
			}
		}

		if ( $error->has_errors() ) {
			$messages = $error->get_error_messages();
			foreach ( $messages as $message ) {
				wc_add_notice( $message, 'error' );
			}
			return false;
		}

		return true;
	}

	/**
	 * Whether or not the product is for members only and current user cannot buy it.
	 *
	 * @since 2.3.0
	 *
	 * @param WC_Product $wc_product Instance of WC_Product.
	 * @return true|WP_Error
	 */
	private static function user_can_buy_members_only_wc_product( $wc_product ) {

		$membership_id = get_post_meta( $wc_product->get_id(), '_llms_membership_id', true );
		$user_id       = get_current_user_id();

		if ( llms_wc_is_members_only_wc_product_restricted_for_user( $wc_product ) ) {

			$error_message = sprintf(
				// Translators: %1$s is the WooCommerce product's title.
				esc_html__(
					'You cannot buy "%1$s". Please remove "%1$s" from your cart to proceed.',
					'lifterlms-woocommerce'
				),
				$wc_product->get_title()
			);

			$membership = llms_get_post( $membership_id );
			if ( $membership ) {
				$error_message = sprintf(
					// Translators: %1$s is the membership title, %2$s is the WooCommerce product's title.
					esc_html__(
						'You need to be a member of "%1$s" to buy "%2$s".',
						'lifterlms-woocommerce'
					),
					$membership->get( 'title' ),
					$wc_product->get_title()
				);
			}

			$error = new WP_Error(
				'llms-invalid-product-members-only',
				$error_message
			);

			/**
			 * Whether or not the user can buy a members ony product.
			 *
			 * @since 2.3.0
			 *
			 * @param bool|WP_Error $can_buy_wc_members_only_product Whether or not the user can buy a members ony product.
			 * @param int           $user_id                         WP User ID.
			 * @param WC_Product    $wc_product                      Instance of WC_Product.
			 * @param int           $membership_id                   WP Post ID of the related membership.
			 */
			return apply_filters( 'llms_user_can_buy_members_only_wc_product', $error, $user_id, $wc_product, $membership_id );
		}

		/** This filter is documented above */
		return apply_filters( 'llms_wc_user_can_buy_members_only_wc_product', true, $user_id, $wc_product, $membership_id );
	}

	/**
	 * Check whether the current user is already enrolled into any of the related
	 * LifterLMS products (Course or Membership) to the WooCommerce product.
	 *
	 * @since 2.3.0
	 *
	 * @param WC_Product $wc_product Instance of WC_Product.
	 * @return true|WP_Error
	 */
	private static function is_user_not_already_enrolled_into_related_llms_products( $wc_product ) {

		$user_id = get_current_user_id();

		if ( ! $user_id ) { // All good.
			/**
			 * Whether or not the user is already enrolled into any related llms products.
			 *
			 * @since 2.3.0
			 *
			 * @param bool|WP_Error $can_buy_wc_members_only_product Whether or not the user is already enrolled into any related llms products.
			 * @param int           $user_id                         WP User ID.
			 * @param WC_Product    $wc_product                      Instance of WC_Product.
			 */
			return apply_filters( 'llms_wc_user_not_already_enrolled_into_related_llms_products', true, $user_id, $wc_product );
		}

		$llms_products = llms_get_llms_products_by_wc_product_id( $wc_product->get_id() );
		$error         = new WP_Error();

		foreach ( $llms_products as $llms_product_id ) {
			$llms_product = llms_get_post( $llms_product_id );

			if ( $llms_product && llms_is_user_enrolled( $user_id, $llms_product->get( 'id' ) ) ) {
				$error->add(
					'llms-invalid-product-already-enrolled',
					sprintf(
						// Translators: %1$s is the course/membership title, %2$s is the WooCommerce product's title.
						esc_html__(
							'You are already enrolled into "%1$s". Please remove "%2$s" from your cart to proceed.',
							'lifterlms-woocommerce'
						),
						$llms_product->get( 'title' ),
						$wc_product->get_title()
					)
				);
			}
		}

		$return = $error->has_errors() ? $error : true;
		/** This filter is documented above */
		return apply_filters( 'llms_wc_user_not_already_enrolled_into_related_llms_products', $return, $user_id, $wc_product );
	}
}
