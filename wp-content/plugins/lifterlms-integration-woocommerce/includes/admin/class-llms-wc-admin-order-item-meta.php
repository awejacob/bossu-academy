<?php
/**
 * Handle display and saving of enrollment & related course data for order items linked to courses and memberships
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 2.0.0
 * @version 2.2.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_Order_Item_Meta class.
 *
 * @since 2.0.0
 * @since 2.0.10 Output on both subscription and order pages.
 * @since 2.0.11 Verify post existence before outputting order item meta course/membership info.
 * @since 2.0.12 Fix issue producing malformed enrollment trigger, When updating the enrollment status to "enrolled" during order updates.
 * @since 2.1.2 When updating the enrollment status from a WC Subscription order screen, always use the parent order as enrollment trigger.
 */
class LLMS_WC_Admin_Order_Item_Meta {

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 * @since 2.2.2 Save enrollment updates status hoking at 'woocommerce_process_shop_order_meta'|9.
	 *                  @see https://github.com/gocodebox/lifterlms-integration-woocommerce/issues/123#issuecomment-1061912244
	 *
	 * @return void
	 */
	public function __construct() {

		add_action( 'admin_head', array( $this, 'output_css' ) );

		// Output enrollment data in order meta area.
		add_action( 'woocommerce_after_order_itemmeta', array( $this, 'output' ), 10, 3 );

		// Save enrollment updates when updating an order.
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save' ), 9, 1 );

		// Hide pid relationship metadata.
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_order_itemmeta' ) );

	}

	/**
	 * Hides private order item meta from display on the admin panel.
	 *
	 * @since 2.0.0
	 *
	 * @param array $metas Meta keys to be hidden.
	 * @return array
	 */
	public function hide_order_itemmeta( $metas ) {
		$metas[] = '_llms_pid';
		$metas[] = '_llms_access_plan';
		return $metas;
	}

	/**
	 * Output Course / Membership enrollment info on the order item meta area on the admin panel
	 *
	 * @since 1.2.0
	 * @since 2.0.0 Update for 2.x release.
	 * @since 2.0.11 Verify post existence before outputting view.
	 *
	 * @param int $item_id WC Item ID.
	 * @param obj $item    WC Item obj.
	 * @param obj $product WC Product obj.
	 * @return void
	 */
	public function output( $item_id, $item, $product ) {

		if ( ! method_exists( $item, 'get_product_id' ) || ! $item->get_product_id() ) {
			return;
		}

		$llms_products = wc_get_order_item_meta( $item_id, '_llms_pid', false );
		if ( ! $llms_products ) {
			return;
		}

		// Verify the course/membership exists before proceeding.
		foreach ( $llms_products as $key => $id ) {
			$product = llms_get_post( $id );
			if ( ! $product ) {
				unset( $llms_products[ $key ] );
			}
		}

		if ( ! $llms_products ) {
			return;
		}

		$order = $item->get_order();

		$customer_id = $order->get_customer_id();
		if ( ! $customer_id ) {
			return;
		}
		$student = llms_get_student( $customer_id );

		include 'views/html-order-item-meta.php';

	}

	/**
	 * Output inline CSS for order item meta data related to enrollments & expirations on the admin panel
	 *
	 * @since 2.0.0
	 * @since 2.0.10 Output on both subscription and order pages.
	 *
	 * @return void
	 */
	public function output_css() {

		$screen = get_current_screen();
		if ( ! in_array( $screen->id, array( 'shop_order', 'shop_subscription' ), true ) ) {
			return;
		}

		add_action( 'admin_footer', array( $this, 'output_js' ) );

		?>
		<style type="text/css">
			.llms-wc-enrollment-data {
				margin-top: 10px;
			}
			.llms-wc-enrollment-row {
				border-bottom: 1px solid #eee;
				margin-bottom: 5px;
			}
			.llms-wc-enrollment-row:last-child {
				border-bottom: none;
			}
			.llms-wc-enrollment-row,
			.llms-wc-enrollment-row a,
			.llms-wc-enrollment-row input,
			.llms-wc-enrollment-row select {
				color: #888;
				font-size: 0.92em !important;
			}
			.llms-wc-enrollment-row select,
			.llms-wc-enrollment-row input[type="text"],
			.llms-wc-enrollment-row input[type="number"] {
				height: auto !important;
				padding: 2px 3px !important;
			}
			.llms-wc-enrollment-row input.date-picker {
				width: 75px;
			}
			.llms-wc-enrollment-row input.hour,
			.llms-wc-enrollment-row input.minute {
				width: 35px;
			}
			.llms-wc-cell {
				display:inline-block;
				padding-right: 5px;
			}
			.llms-wc-cell.llms-product {
				vertical-align: top;
				width: 45%;
			}
			.llms-wc-cell.llms-status {
				vertical-align: top;
				width: 15%;
			}
			.llms-wc-cell.llms-expiration {
				width: 35%;
			}
		</style>
		<?php

	}

	/**
	 * Output inline JS to handle UX of expiration data UI on the admin panel
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function output_js() {
		?>
		<script>
		( function( $ ) {

			$( 'a[href="#llms-wc-add-expiration"]' ).on( 'click', function() {
				$( this ).hide().next( '.llms-wc-expiration-fields' ).show().find( 'input' ).removeAttr( 'disabled' );
			} );

			$( 'a[href="#llms-wc-del-expiration"]' ).on( 'click', function() {
				var $fields = $( this ).closest( '.llms-wc-expiration-fields' );
				$fields.hide().find( 'input' ).attr( 'disabled', 'disabled' );
				$fields.prev( 'a[href="#llms-wc-add-expiration"]' ).show();
			} );

		} )( jQuery );
		</script>
		<?php
	}

	/**
	 * Update enrollment statuses when an order is saved and enrollments have been changed from the order item metabox
	 *
	 * @since 2.0.0
	 *
	 * @param int $order_id WC Order ID.
	 * @return void
	 */
	public function save( $order_id ) {

		$enrollment_data = filter_input( INPUT_POST, 'llms_wc', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! $enrollment_data ) {
			return;
		}

		$order = wc_get_order( $order_id );

		$student = llms_get_student( $order->get_customer_id() );
		if ( ! $student ) {
			return;
		}

		foreach ( $enrollment_data as $product_id => $data ) {

			$product_id = absint( $product_id );

			$this->update_enrollment_status( $student, $order, $product_id, $data['status'] );

			// Update expiration if expiration data is set && student is enrolled.
			if ( isset( $data['expiration'] ) && 'enrolled' === $data['status'] ) {

				$date = sprintf( '%1$s %2$s:%3$s', $data['expiration']['date'], $data['expiration']['hour'], $data['expiration']['minute'] );
				$time = strtotime( $date, current_time( 'timestamp' ) );
				LLMS_WC_Order_Actions::schedule_expiration( $time, $order_id, $product_id );

			} else {

				LLMS_WC_Order_Actions::unschedule_expiration( $order_id, $product_id );

			}
		}

	}

	/**
	 * Update the enrollment status during order updates
	 *
	 * @since 2.0.0
	 * @since 2.0.12 Fix issue producing malformed enrollment trigger when manually enrolling a student.
	 * @since 2.1.2 When in a WC Subscription always use the parent order as enrollment trigger.
	 *
	 * @param obj    $student    LLMS_Student obj.
	 * @param obj    $order      WC_Order obj.
	 * @param int    $product_id WP_Post ID of a product.
	 * @param string $new_status LLMS enrollment status.
	 * @return void
	 */
	private function update_enrollment_status( $student, $order, $product_id, $new_status ) {
		// Only proceed if the status is different.
		if ( $new_status === $student->get_enrollment_status( $product_id ) ) {
			return;
		}

		if ( class_exists( 'WC_Subscription' ) && $order instanceof WC_Subscription ) {
			$order = $order->get_parent();
		}

		if ( ! $order ) {
			return;
		}

		$llmswc = LLMS_WooCommerce()->get_integration();

		if ( 'enrolled' === $new_status ) {
			$update = $student->enroll( $product_id, 'wc_order_' . $order->get_id() ) ? 'enrollment' : false;
		} else {
			$update = $student->unenroll( $product_id, 'any', $new_status ) ? 'unenrollment' : false;
		}

		if ( $update ) {
			$llmswc->add_order_note( $order, $product_id, $update );
		}

	}

}

return new LLMS_WC_Admin_Order_Item_Meta();
