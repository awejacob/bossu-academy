<?php
/**
 * Handle order actions such as enrollments and expirations.
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 2.0.0
 * @version 2.4.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_Order_Actions class.
 *
 * @since 2.0.0
 * @since 2.0.10 Fix issues encountered when Subscriptions with no parent order are passed into enrollment & unenrollment actions.
 * @since 2.0.13 Added logic to delete enrollments on order permanent deletion, and unenroll/enroll students on order
 *               or subscription trashing/untrashing.
 *               Fixed an issue that made users to be unenrolled from a course when a subscription, that was part of the same
 *               wc order of the course, was 'cancelled'.
 *               Made sure that user enrollment/unenrollment related to a subscription happened only on the
 *               subscription status changes and not on their parent order status changes.
 *               Use `gmdate()` in favor of `date()` for timestamps recorded to logs.
 */
class LLMS_WC_Order_Actions {

	/**
	 * Integration instance.
	 *
	 * @var LLMS_Integration_WooCommerce
	 */
	public $integration;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function __construct() {

		$this->integration = LLMS_WooCommerce()->get_integration();
		add_action( 'init', array( $this, 'add_status_actions' ) );
		add_action( 'llms_wc_access_plan_expiration', array( $this, 'expire_access' ), 10, 2 );
		add_action( 'woocommerce_before_order_item_object_save', array( $this, 'add_order_item_meta' ) );

	}

	/**
	 * Add order item meta data to qualifying orders when a new order is created during checkout.
	 *
	 * @since 2.0.0
	 * @since 2.0.8 Unknown.
	 * @since 2.0.13 Added strict comparison when using `in_array()`, hence made sure related llms product
	 *               ids array is an array of positive integers.
	 *
	 * @param WC_Order_Item $item WC_Order_Item.
	 * @return void
	 */
	public function add_order_item_meta( $item ) {

		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return;
		}

		$this->integration->log( sprintf( 'Adding product associations $item %1$d', $item->get_id() ) );

		$pid   = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
		$plans = llms_get_llms_plans_by_wc_product_id( $pid );

		if ( ! $plans ) {
			$this->integration->log( 'No $plans found.' );
			return;
		}

		$this->integration->log( '$plans found:', $plans );

		// Don't proceed if meta already exists.
		if ( $item->meta_exists( '_llms_access_plan' ) ) {
			return;
		}

		foreach ( $plans as $plan_id ) {

			$plan = llms_get_post( $plan_id );
			if ( ! $plan ) {
				continue;
			}

			// Save the access plan, used for expiration info during enrollment.
			$item->add_meta_data( '_llms_access_plan', $plan->get( 'id' ) );

			// Save the related llms products.
			$links = wp_list_pluck( $item->get_meta( '_llms_pid', false ), 'value' );
			if ( ! in_array( $plan->get( 'product_id' ), array_map( 'absint', $links ), true ) ) {
				$item->add_meta_data( '_llms_pid', $plan->get( 'product_id' ), false );
			}
		}

	}

	/**
	 * Add enrollment and unenrollment actions based on integration settings, and remove enrollments on order permanent deletion.
	 *
	 * @since 2.0.0
	 * @since 2.0.13 Added action to 'woocommerce_delete_order_items' hook to delete enrollments on order permanent deletion.
	 *               Also handle students unenrollment/enrollment on order (and subscription) trashed/untrashed.
	 * @since 2.2.0 Added action to maybe trigger purchase actions (used by engagements) on WooCommerce order completed.
	 * @since 2.4.1 Use default values from integration settings.
	 *
	 * @return void
	 */
	public function add_status_actions() {

		$enroll = $this->integration->get_option( 'enrollment_status', 'wc-completed' );
		add_action( 'woocommerce_order_status_' . $this->unprefix_status( $enroll ), array( $this, 'do_order_enrollments' ), 10, 1 );

		// When an order becomes completed we consider that the related access plans and products are purchased.
		add_action( 'woocommerce_order_status_completed', array( $this, 'do_purchased_actions' ), 10, 1 );

		// If no statuses set, will be an empty string.
		$unenrolls = $this->integration->get_option( 'unenrollment_statuses' );
		if ( ! empty( $unenrolls ) && is_array( $unenrolls ) ) {
			foreach ( $unenrolls as $status ) {
				add_action( 'woocommerce_order_status_' . $this->unprefix_status( $status ), array( $this, 'do_order_unenrollments' ), 10, 1 );
			}
		}

		// Unenroll/enroll students on order trashed/untrashed.
		add_action( 'trashed_post', array( $this, 'on_post_trashed' ) );
		add_action( 'untrashed_post', array( $this, 'on_post_untrashed' ) );

		if ( function_exists( 'llms_delete_student_enrollment' ) ) {
			// This action adds lifterlms specific action before wc order items are deleted from the DB.
			// Note: this action is also triggered when a Subscription is permanently deleted.
			add_action( 'woocommerce_delete_order_items', array( $this, 'do_order_delete_enrollments' ) );
		}

		// Add subscription actions.
		if ( class_exists( 'WC_Subscriptions' ) ) {

			$sub_enroll = $this->integration->get_option( 'subscription_enrollment_status', 'wc-active' );
			add_action( 'woocommerce_subscription_status_' . $this->unprefix_status( $sub_enroll ), array( $this, 'do_order_enrollments' ), 10, 1 );

			$sub_unenrolls = $this->integration->get_option( 'subscription_unenrollment_statuses' );
			if ( ! empty( $sub_unenrolls ) && is_array( $sub_unenrolls ) ) {
				foreach ( $sub_unenrolls as $status ) {
					add_action( 'woocommerce_subscription_status_' . $this->unprefix_status( $status ), array( $this, 'do_order_unenrollments' ), 10, 1 );
				}
			}
		}

	}

	/**
	 * Trigger purchase actions used by engagements.
	 *
	 * @since 2.2.0
	 *
	 * @param integer $order_id WC_Order ID.
	 * @return void
	 */
	public function do_purchased_actions( $order_id ) {

		$order = $this->get_order_from_action_args( $order_id );
		if ( ! $order ) {
			$this->integration->log( '`do_purchased_actions()` failed, no order found.' );
			$this->integration->log( $order_id );
			return;
		}

		$this->integration->log( '`do_purchased_actions()` started for order_id "' . $order_id . '"' );

		$user_id = $order->get_user_id();

		// If no user id exists we do nothing.
		if ( empty( $user_id ) ) {
			$this->integration->log( '`do_purchased_actions()` ended for order_id "' . $order_id . '". No user ID was supplied.' );
			return;
		}

		foreach ( $order->get_items() as $item ) {

			$plans = wc_get_order_item_meta( $item->get_id(), '_llms_access_plan', false );

			foreach ( $plans as $plan ) {

				$plan = $plan ? llms_get_post( $plan ) : false;
				if ( $plan ) {
					// Trigger purchased actions, used by engagements.
					/* This action is documented in lifterlms/includes/controllers/class.llms.controller.orders.php */
					do_action( 'lifterlms_product_purchased', $user_id, $plan->get( 'product_id' ) );
					/* This action is documented in lifterlms/includes/controllers/class.llms.controller.orders.php */
					do_action( 'lifterlms_access_plan_purchased', $user_id, $plan->get( 'id' ) );
				}
			}
		}

		$this->integration->log( '`do_purchased_actions()` finished for order_id "' . $order_id . '"' );

	}

	/**
	 * Enroll the customer in all llms products associated with all items in the order.
	 *
	 * Called upon order status change to the user-defined "Enrollment Status" setting.
	 *
	 * @since 2.0.0
	 * @since 2.0.10 Return early when a subscription with no parent order is passed.
	 * @since 2.0.13 Made sure only courses/memberships linked to the subscription being processed
	 *               were subject of the user's enrollment.
	 *               Made sure that user enrollment related to a subscription happened only on a
	 *               subscription status change and not on their parent order status changes.
	 *
	 * @param mixed $order_id WC_Order ID (int) or WC_Subscription (obj).
	 * @return void
	 */
	public function do_order_enrollments( $order_id ) {

		$order = $this->get_order_from_action_args( $order_id );

		if ( ! $order ) {
			$this->integration->log( '`do_order_enrollments()` failed, no order found.' );
			$this->integration->log( $order_id );
			return;
		}

		$user_id = $order->get_user_id();

		$this->integration->log( '`do_order_enrollments()` started for order_id "' . $order->get_id() . '"' );

		// If no user id exists we do nothing. Gotta have a user to assign the course to.
		if ( empty( $user_id ) ) {
			$this->integration->log( '`do_order_enrollments()` ended for order_id "' . $order->get_id() . '". No user ID was supplied.' );
			return;
		}

		$order_type             = $this->get_order_type_from_action_args( $order_id );
		$order_items_collection = 'wc_subscription' === $order_type ? $order_id : $order;

		foreach ( $order_items_collection->get_items() as $item ) {

			/**
			 * If we're processing a WC_Order, skip subscription product items,
			 * they will be taken into account when processing a WC_Subscription.
			 * Only if WC_Subscription class exists.
			 */
			if ( class_exists( 'WC_Subscriptions' ) && 'wc_subscription' !== $order_type ) {
				$wc_product = $item->get_product();
				if ( $wc_product && WC_Subscriptions_Product::is_subscription( $wc_product ) ) {
					continue;
				}
			}

			$products = llms_wc_get_order_item_products( $item );

			$this->integration->log( sprintf( '$products found for $item %s', $item->get_id() ), $products );

			foreach ( $products as $product_id ) {

				if ( ! llms_enroll_student( $user_id, $product_id, 'wc_order_' . $order->get_id() ) ) {
					continue;
				}

				$this->integration->add_order_note( $order, $product_id, 'enrollment' );

				$plans = wc_get_order_item_meta( $item->get_id(), '_llms_access_plan', false );

				foreach ( $plans as $plan ) {

					$plan = $plan ? llms_get_post( $plan ) : false;
					if ( ! $plan || ! $plan->can_expire() ) {
						continue;
					}

					$time = $this->get_expiration_time_from_plan( $plan );
					if ( ! $time ) {
						continue;
					}

					$this->schedule_expiration( $time, $order->get_id(), $plan->get( 'product_id' ) );

				}
			}
		}

		$this->integration->log( '`do_order_enrollments()` finished for order_id "' . $order->get_id() . '"' );

	}

	/**
	 * Unenroll the customer from all llms products associated with all items in the order.
	 *
	 * Called upon order status change to any status in the user-defined "Unenrollment Statuses" setting.
	 *
	 * @since 2.0.0
	 * @since 2.0.10 Return early when a subscription with no parent order is passed.
	 * @since 2.0.13 Made sure only courses/memberships linked to the subscription being processed
	 *               were subject of the user's unenrollment.
	 *               Made sure that user unenrollment related to a subscription happened only on a
	 *               subscription status change and not on their parent order status changes.
	 *
	 * @param mixed $order_id WP_Order ID (int) or WC_Subscription (obj).
	 * @return void
	 */
	public function do_order_unenrollments( $order_id ) {

		$order = $this->get_order_from_action_args( $order_id );

		if ( ! $order ) {
			$this->integration->log( '`do_order_unenrollments()` failed, no order found.' );
			$this->integration->log( $order_id );
			return;
		}

		$user_id = $order->get_user_id();

		$this->integration->log( '`do_order_unenrollments()` started for order_id "' . $order->get_id() . '"' );

		// If no user id exists we do nothing. Gotta have a user to assign the course to.
		if ( empty( $user_id ) ) {
			return;
		}

		$order_type             = $this->get_order_type_from_action_args( $order_id );
		$order_items_collection = 'wc_subscription' === $order_type ? $order_id : $order;

		foreach ( $order_items_collection->get_items() as $item ) {

			/**
			 * If we're processing a WC_Order, skip subscription product items,
			 * they will be taken into account when processing a WC_Subscription.
			 * Only if WC_Subscription class exists.
			 */
			if ( class_exists( 'WC_Subscriptions' ) && 'wc_subscription' !== $order_type ) {
				$wc_product = $item->get_product();
				if ( $wc_product && WC_Subscriptions_Product::is_subscription( $wc_product ) ) {
					continue;
				}
			}

			$products = llms_wc_get_order_item_products( $item );
			if ( $products ) {
				$this->integration->log( '$products: ', $products );
				foreach ( $products as $product_id ) {
					/**
					 * Filter: llms_wc_unenrollment_new_status
					 *
					 * Customize the student unenrollment status when the student is unenrolled as a result of WC order status changes.
					 *
					 * @since 2.0.0
					 *
					 * @example add_filter( 'llms_wc_plan_has_wc_product', '__return_false' );
					 *
					 * @param string $status   The new status, should be a valid LifterLMS enrollment status. Defaults to 'expired'.
					 * @param int    $order_id WC_Post ID of the WooCommerce Order.
					 */
					if ( llms_unenroll_student( $user_id, $product_id, apply_filters( 'llms_wc_unenrollment_new_status', 'expired', $order->get_id() ), 'wc_order_' . $order->get_id() ) ) {
						$this->integration->add_order_note( $order, $product_id, 'unenrollment' );
					}
				}
			}
		}

		$this->integration->log( '`do_order_unenrollments()` finished for order_id "' . $order->get_id() . '"' );

	}

	/**
	 * Called when a WC_Order items are permanently deleted from the db.
	 *
	 * Will delete any enrollment records linked to the WC_Order.
	 *
	 * @since 2.0.13
	 * @since 2.1.2 Make sure the subscription's parent order exists to avoid fatals.
	 *
	 * @param int $order_id WC_Order or WC_Subscription ID.
	 * @return void
	 */
	public function do_order_delete_enrollments( $order_id ) {

		// Get the WC_Order or WC_Subscription from the being deleted post id.
		$order_or_subscription = wc_get_order( $order_id );

		if ( $order_or_subscription ) {
			// Make sure to get the parent order in case of a subscription.
			$order = $this->get_order_from_action_args( $order_or_subscription );
		}

		if ( empty( $order ) ) {
			$this->integration->log( '`do_order_delete_enrollments()` failed, no order found.' );
			$this->integration->log( $order_id );
			return;
		}

		$this->integration->log( '`do_order_delete_enrollments()` started for order_id "' . $order->get_id() . '"' );

		$user_id = $order->get_user_id();

		$order_type             = $this->get_order_type_from_action_args( $order_or_subscription );
		$order_items_collection = 'wc_subscription' === $order_type ? $order_or_subscription : $order;

		foreach ( $order_items_collection->get_items() as $item ) {

			$products = llms_wc_get_order_item_products( $item );

			if ( $products ) {

				$this->integration->log( '$products: ', $products );
				foreach ( $products as $product_id ) {
					llms_delete_student_enrollment( $user_id, $product_id, 'wc_order_' . $order->get_id() );
				}
			}
		}

		$this->integration->log( '`do_order_delete_enrollments()` finished for order_id "' . $order->get_id() . '"' );
	}

	/**
	 * Maybe unenroll students when trashing a post which is a WC_Order.
	 *
	 * @since 2.0.13
	 *
	 * @param int $post_id WP_Post ID.
	 * @return void
	 */
	public function on_post_trashed( $post_id ) {

		$order = wc_get_order( $post_id );

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$this->do_order_unenrollments( $order );

	}

	/**
	 * Maybe enroll students when untrashing a post which is a WC_Order.
	 *
	 * Only if the untrashed WC_Order status matches the '(subscription_)enrollment_status' user option.
	 *
	 * @since 2.0.13
	 *
	 * @param int $post_id WP_Post ID.
	 * @return void
	 */
	public function on_post_untrashed( $post_id ) {

		$order = wc_get_order( $post_id );

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		if ( is_a( $order, 'WC_Subscription' ) ) {
			$enroll = $this->integration->get_option( 'subscription_enrollment_status', 'wc-active' );
		} else {
			$enroll = $this->integration->get_option( 'enrollment_status', 'wc-completed' );
		}

		if ( $this->unprefix_status( $enroll ) === $order->get_status() ) {
			$this->do_order_enrollments( $order );
		}

	}

	/**
	 * Get the timestamp of a scheduled expiration for a given order & product (course or membership).
	 *
	 * @since 2.0.0
	 *
	 * @param int $order_id   WP Post ID of the WC order.
	 * @param int $product_id WP Post ID of the LLMS course or membership.
	 * @return int|false Timestamp of the scheduled event or false if none is scheduled.
	 */
	public static function get_expiration( $order_id, $product_id ) {

		$order_id   = absint( $order_id );
		$product_id = absint( $product_id );

		if ( function_exists( 'as_next_scheduled_action' ) ) {
			return as_next_scheduled_action( 'llms_wc_access_plan_expiration', compact( 'order_id', 'product_id' ) );
		}

		return wc_next_scheduled_action( 'llms_wc_access_plan_expiration', compact( 'order_id', 'product_id' ) );

	}

	/**
	 * Retrieve a WC_Order from either an order_id or a WC_Subscription obj.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $order_id_or_subscription WP_Order ID (int) or WC_Subscription (obj).
	 * @return mixed|false WC_Order or false if it's a subscription order with no parent.
	 */
	public function get_order_from_action_args( $order_id_or_subscription ) {

		if ( 'wc_subscription' === $this->get_order_type_from_action_args( $order_id_or_subscription ) ) {
			$order = $order_id_or_subscription->get_parent();
		} else {
			$order = wc_get_order( $order_id_or_subscription );
		}

		return $order;

	}

	/**
	 * Retrieve the type of the order from either an order ID or a WC_Subscription obj.
	 *
	 * @since 2.0.13
	 *
	 * @param mixed $order_id_or_subscription WP_Order ID (int) or WC_Subscription (obj).
	 * @return string Either 'wc_order' or a 'wc_subscription'.
	 */
	public function get_order_type_from_action_args( $order_id_or_subscription ) {
		return ( ! is_numeric( $order_id_or_subscription ) && $order_id_or_subscription instanceof WC_Subscription ) ? 'wc_subscription' : 'wc_order';
	}

	/**
	 * Retrieve expiration timestamp for a plan.
	 *
	 * @since 2.0.0
	 *
	 * @param LLMS_Access_Plan $plan LLMS_Access_Plan instance.
	 * @return int|false
	 */
	public function get_expiration_time_from_plan( $plan ) {

		$time       = false;
		$expiration = $plan->get( 'access_expiration' );

		if ( 'limited-date' === $expiration ) {

			$time = $plan->get_date( 'access_expires', 'U' );

		} elseif ( 'limited-period' === $expiration ) {

			$time = strtotime(
				sprintf( '+%1$d %2$s', $plan->get( 'access_length' ), $plan->get( 'access_period' ) ),
				strtotime( llms_current_time( 'Y-m-d' ), llms_current_time( 'timestamp' ) ) + ( DAY_IN_SECONDS - 1 )
			);

		}

		return $time;

	}

	/**
	 * Expires access for a given order & product.
	 *
	 * @since 2.0.0
	 * @since 2.0.13 Log timestamps using `gmdate()` in favor of `date()`.
	 * @since 2.1.2 Make sure the wc order exists before performing any manipulation to avoid fatal.
	 *              Also fix reference to an undefined `$time` variable.
	 *
	 * @param int $order_id   WP Post ID of the WC order.
	 * @param int $product_id WP Post ID of the LLMS course or membership.
	 * @return void
	 */
	public function expire_access( $order_id, $product_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$this->integration->log( sprintf( 'Access expiration called for order %1$d & product %2$d', gmdate( 'Y-m-d H:i', time() ), $order_id, $product_id ) );

		if ( llms_unenroll_student( $order->get_customer_id(), $product_id, 'expired', 'wc_order_' . $order_id ) ) {

			// Translators: %s = Title of the course or membership.
			$note = sprintf( __( 'Student unenrolled from "%s" due to automatic access plan expiration.', 'lifterlms-woocommerce' ), get_the_title( $product_id ) );
			$order->add_order_note( $note );

		}

	}

	/**
	 * Schedule access expiration for an order & product (course or membership).
	 *
	 * @since 2.0.0
	 * @since 2.0.13 Log timestamps using `gmdate()` in favor of `date()`.
	 *
	 * @param int $time       Timestamp.
	 * @param int $order_id   WP Post ID of the WC order.
	 * @param int $product_id WP Post ID of the LLMS course or membership.
	 * @return void
	 */
	public static function schedule_expiration( $time, $order_id, $product_id ) {

		$order_id   = absint( $order_id );
		$product_id = absint( $product_id );

		self::unschedule_expiration( $order_id, $product_id );

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( $time, 'llms_wc_access_plan_expiration', compact( 'order_id', 'product_id' ) );
		} else {
			wc_schedule_single_action( $time, 'llms_wc_access_plan_expiration', compact( 'order_id', 'product_id' ) );
		}

		$integration = LLMS_WooCommerce()->get_integration();
		$integration->log( sprintf( 'Expiration scheduled at %1$s for order %2$d & product %3$d', gmdate( 'Y-m-d H:i', $time ), $order_id, $product_id ) );

	}

	/**
	 * Utility to remove "wc-" prefix from a status string.
	 *
	 * @since 2.0.0
	 *
	 * @param string $status Prefixed string.
	 * @return string
	 */
	private function unprefix_status( $status ) {
		return str_replace( 'wc-', '', $status );
	}

	/**
	 * Removes a scheduled expiration.
	 *
	 * @since 2.0.0
	 *
	 * @param int $order_id   WP Post ID of the WC order.
	 * @param int $product_id WP Post ID of the LLMS course or membership.
	 * @return void
	 */
	public static function unschedule_expiration( $order_id, $product_id ) {

		$order_id   = absint( $order_id );
		$product_id = absint( $product_id );

		if ( self::get_expiration( $order_id, $product_id ) ) {
			if ( function_exists( 'as_unschedule_action' ) ) {
				as_unschedule_action( 'llms_wc_access_plan_expiration', compact( 'order_id', 'product_id' ) );
			} else {
				wc_unschedule_action( 'llms_wc_access_plan_expiration', compact( 'order_id', 'product_id' ) );
			}
			$integration = LLMS_WooCommerce()->get_integration();
			$integration->log( sprintf( 'Expiration unscheduled for order %1$d & product %2$d', $order_id, $product_id ) );
		}

	}

}

return new LLMS_WC_Order_Actions();
