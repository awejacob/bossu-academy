<?php
/**
 * Modify LLMS core access plans
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 2.0.0
 * @version 2.1.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_Access_Plans class
 *
 * @since 2.0.0
 * @since 2.0.9 Add support for checkout redirect settings.
 * @since 2.0.12 Made sure `wc_print_notices()` is defined before adding it as hook callback.
 * @since 2.0.13 Fix the fatal error throwing on a course/membership page when a product/variation is deleted.
 * @since 2.0.14 Made sure free access plans are displayed on front,
 *               also notice not shown in free access plans metagoxes.
 * @since 2.1.0 Added ability to retrieve the price of an access plan linked to a product in `raw` and `float` formats.
 * @since 2.1.1 Use WooCommerce currencty symbol.
 * @since 2.1.2 Fix checkout url pointing to the llms checkout page even if the the plan was availble for the current user.
 */
class LLMS_WC_Access_Plans {

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 * @since 2.0.12 Made sure `wc_print_notices()` is defined before adding it as hook callback.
	 * @since 2.0.13 Add action hooks to:
	 *               - handle the cases when a product/variation is deleted linked to an access plan are deleted,
	 *               - handle the cases when an access plan is not linked to any product/variation.
	 * @since 2.1.1 Filter `lifterlms_currency_symbol` so to use WooCommerce currency symbol settings.
	 */
	public function __construct() {

		// Hide access plan metabox rows in the most elegant way possible.
		add_action( 'admin_head', array( $this, 'output_css' ) );
		add_action( 'admin_footer', array( $this, 'output_js' ) );

		// Output custom metabox fields.
		add_action( 'llms_access_plan_mb_after_row_three', array( $this, 'output_fields' ), 10, 3 );

		// Prevent validation issues since no price will be saved.
		add_filter( 'llms_access_before_save_plan', array( $this, 'before_save_plan' ), 10, 2 );

		// Add custom plan props to the plan model.
		add_filter( 'llms_get_access_plan_properties', array( $this, 'register_properties' ), 10, 1 );

		// Remove display of trial information.
		remove_action( 'llms_acces_plan_footer', 'llms_template_access_plan_trial', 10 );

		add_filter( 'llms_plan_get_checkout_url', array( $this, 'get_checkout_url' ), 25, 2 );
		add_filter( 'llms_plan_get_price', array( $this, 'get_price' ), 25, 5 );
		if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
			add_filter( 'lifterlms_currency_symbol', 'get_woocommerce_currency_symbol', 10, 0 );
		}

		add_filter( 'llms_get_product_schedule_details', array( $this, 'get_schedule_details' ), 25, 2 );
		add_filter( 'llms_get_access_plan_availability', array( $this, 'get_availability' ), 25, 2 );
		add_filter( 'llms_get_access_plan_availability_restrictions', array( $this, 'get_availability_restrictions' ), 25, 2 );
		add_filter( 'llms_product_is_purchasable', array( $this, 'is_purchasable' ), 25, 2 );

		// Replace sale dates.
		add_filter( 'llms_plan_is_on_sale', array( $this, 'get_is_on_sale' ), 25, 2 );
		add_filter( 'llms_get_access_plan_sale_start', array( $this, 'get_sale_start' ), 10, 2 );
		add_filter( 'llms_get_access_plan_sale_end', array( $this, 'get_sale_end' ), 10, 2 );

		// Show WC Notices on Course & Membership Pages.
		if ( function_exists( 'wc_print_notices' ) ) {
			add_action( 'lifterlms_single_course_before_summary', 'wc_print_notices' );
			add_action( 'lifterlms_single_membership_before_summary', 'wc_print_notices' );
		}

		// Handle access plan not linked to any wc products.
		add_filter( 'llms_get_product_access_plans', array( $this, 'maybe_filter_unlinked_plans' ) );

		add_action( 'llms_access_plan_mb_before_body', array( $this, 'access_plan_mb_notice' ) );
		// Unlink access plan on product/variation deletion.
		add_action( 'deleted_post', array( $this, 'on_product_deleted' ) );

		// Price is not required as the price comes from the WooCommerce product.
		add_filter( 'llms_access_plan_price_required', '__return_false' );

		// We don't want to show tha access plan popup when a new plan is created, since pricing etc is from the Woo product.
		add_filter( 'llms_show_access_plan_dialog', '__return_false' );
	}

	/**
	 * Validate access plan data on save.
	 *
	 * @since 2.0.0
	 * @since 2.0.7 Unknown.
	 *
	 * @param array $data    Array of posted plan data.
	 * @param obj   $metabox LLMS_Admin_Metabox instance.
	 * @return array
	 */
	public function before_save_plan( $data, $metabox ) {

		if ( empty( $data['wc_pid'] ) ) {
			return $data;
		}

		// Set a price so validation errors from core don't get thrown.
		if ( empty( $data['price'] ) ) {
			$data['price'] = 1;
		}

		return $data;
	}

	/**
	 * Filter return of access plan availability getter.
	 *
	 * @since 2.0.0
	 *
	 * @param string           $availability Default availability.
	 * @param LLMS_Access_Plan $plan         LLMS_Access_Plan.
	 * @return string
	 */
	public function get_availability( $availability, $plan ) {
		if ( llms_wc_plan_has_wc_product( $plan ) ) {
			if ( get_post_meta( $plan->get( 'wc_pid' ), '_llms_membership_id', true ) ) {
				return 'members';
			}
		}
		return $availability;
	}

	/**
	 * Filter return of access plan availability restriction getter.
	 *
	 * @since 2.0.0
	 *
	 * @param array            $restrictions Default restrictions.
	 * @param LLMS_Access_Plan $plan         LLMS_Access_Plan.
	 * @return string
	 */
	public function get_availability_restrictions( $restrictions, $plan ) {
		if ( llms_wc_plan_has_wc_product( $plan ) ) {
			$restrictions = array();
			$meta         = get_post_meta( $plan->get( 'wc_pid' ), '_llms_membership_id', true );
			if ( $meta ) {
				$restrictions[] = $meta;
			}
		}
		return $restrictions;
	}

	/**
	 * Modify the access plan checkout button URL for access plans with a WC product association.
	 *
	 * @since 2.0.0
	 * @since 2.0.13 Prevent fatals when the wc product associated to the access plan doesn't exist anymore
	 *               and return an empty string.
	 * @since 2.1.2 Fix checkout url pointing to the llms checkout page even if the the plan was availble for the current user.
	 *
	 * @param string           $url  Default checkout URL.
	 * @param LLMS_Access_Plan $plan LLMS_Access_Plan.
	 * @return string
	 */
	public function get_checkout_url( $url, $plan ) {

		if ( llms_wc_plan_has_wc_product( $plan ) && $plan->is_available_to_user() ) {

			$product = wc_get_product( $plan->get( 'wc_pid' ) );
			return $product ? $product->add_to_cart_url() : '';

		}

		return $url;
	}

	/**
	 * Modify the access plan checkout button URL for access plans with a WC product association.
	 *
	 * @since 2.0.0
	 * @since 2.0.13 Prevent fatals when the wc product associated to the access plan doesn't exist anymore
	 *               and return false.
	 *
	 * @param bool             $bool Default result of `$plan->is_on_sale()`.
	 * @param LLMS_Access_Plan $plan LLMS_Access_Plan.
	 * @return bool
	 */
	public function get_is_on_sale( $bool, $plan ) {

		if ( llms_wc_plan_has_wc_product( $plan ) ) {

			$product = wc_get_product( $plan->get( 'wc_pid' ) );
			return $product && $product->is_on_sale();

		}

		return $bool;
	}

	/**
	 * Modify the access plan price for access plans with a WC product association.
	 *
	 * When asking for the 'price' key in 'html' or the 'raw' formats:
	 * - if the plan is on sale, this returns an empty string
	 * - if the plan is not on sale, this will return the result of WooCommerce's `$product->get_price_html()`, with all the tags stripped when askingfor the 'raw' format.
	 *
	 * When asking forthe 'sale_price' key in 'html' or the 'raw' formats:
	 * - this will return the result of WooCommerce's `$product->get_price_html()`, with all the tags stripped when askingfor the 'raw' format.
	 * if the product is on sale, the html will be like:
	 * `<del><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>10.00</span></del> <ins><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>8</span></ins>`
	 *
	 *
	 * When asking for the 'price' key in 'float' format:
	 * - if the product is on sale this returns the woocommerce's product regular price.
	 * - if the product is not on sale this returns the woocommerce's product price which is equal to the regular price.
	 *
	 * When asking for the 'sale_price' key in 'float' format:
	 * - if the product is on sale this returns the woocommerce's product price which is equal to the sale price.
	 * - if the product is not on sale this returns the woocommerce's product price which is equal to the regular price.
	 *
	 * @since 2.0.0
	 * @since 2.0.13 Prevent fatals when the wc product associated to the access plan doesn't exist anymore
	 *               and return an empty string.
	 * @since 2.1.0 Added ability to retrieve the price in `raw` and `float` formats.
	 *
	 * @param string           $price      Default price.
	 * @param string           $key        Price field key name.
	 * @param array            $price_args Price display arguments.
	 * @param string           $format     Price display format.
	 * @param LLMS_Access_Plan $plan       LLMS_Access_Plan.
	 * @return string
	 */
	public function get_price( $price, $key, $price_args, $format, $plan ) {

		if ( llms_wc_plan_has_wc_product( $plan ) ) {

			$product = wc_get_product( $plan->get( 'wc_pid' ) );

			if ( ! $product ) {
				return '';
			}

			if ( 'html' === $format || 'raw' === $format ) {

				$price = '';

				if ( ( 'price' === $key && ! $plan->is_on_sale() ) || 'sale_price' === $key ) {

					$price = $product->get_price_html();

					if ( 'raw' === $format ) {
						$price = wp_strip_all_tags( $price );
					}
				}
			} elseif ( 'float' === $format ) {

				$price = '';

				if ( in_array( $key, array( 'price', 'sale_price' ), true ) ) {

					$price_args = array();

					if ( 'price' === $key && $plan->is_on_sale() ) {
						$price_args = array( 'price' => $product->get_regular_price() );
					}

					$price = wc_get_price_to_display( $product, $price_args );
				}
			}
		}

		return $price;
	}

	/**
	 * Modify the access plan sale end date.
	 *
	 * @since 2.0.5
	 * @since 2.0.13 Prevent fatals when the wc product associated to the access plan doesn't exist anymore
	 *               and return an empty string.
	 *
	 * @param string           $sale_end Default sale end date of the access plan.
	 * @param LLMS_Access_Plan $plan     LLMS_Access_Plan.
	 * @return string
	 */
	public function get_sale_end( $sale_end, $plan ) {
		if ( ! llms_wc_plan_has_wc_product( $plan ) ) {
			return $sale_end;
		}

		$product = wc_get_product( $plan->get( 'wc_pid' ) );
		return $product ? $product->get_date_on_sale_to() : '';
	}

	/**
	 * Modify the access plan sale start date.
	 *
	 * @since 2.0.5
	 *
	 * @param string           $sale_start Default sale start date of the access plan.
	 * @param LLMS_Access_Plan $plan       LLMS_Access_Plan.
	 * @return string
	 */
	public function get_sale_start( $sale_start, $plan ) {
		if ( ! llms_wc_plan_has_wc_product( $plan ) ) {
			return $sale_start;
		}

		$product = wc_get_product( $plan->get( 'wc_pid' ) );
		return $product ? $product->get_date_on_sale_from() : '';
	}

	/**
	 * Modify the access plan schedule details string for access plans with a WC product association.
	 *
	 * @since 2.0.0
	 *
	 * @param string           $string Default schedule details string.
	 * @param LLMS_Access_Plan $plan   LLMS_Access_Plan.
	 * @return bool
	 */
	public function get_schedule_details( $string, $plan ) {

		if ( llms_wc_plan_has_wc_product( $plan ) ) {

			return '';

		}

		return $string;
	}

	/**
	 * Filter the return of the `LLMS_Product->is_purchasable()` method to skip checking for the presence of LifterLMS gateways.
	 *
	 * @since 2.0.0
	 *
	 * @param bool         $bool    True if purchaseable, false otherwise.
	 * @param LLMS_Product $product LLMS_Product.
	 * @return bool
	 */
	public function is_purchasable( $bool, $product ) {
		return ( 0 !== count( $product->get_access_plans( false, false ) ) );
	}

	/**
	 * Output inline CSS to modify access plan templates.
	 *
	 * @since 2.0.0
	 * @since 2.0.9 Hide access plan redirect settings.
	 *
	 * @return void
	 */
	public function output_css() {

		$screen = get_current_screen();
		if ( ! in_array( $screen->post_type, array( 'course', 'llms_membership' ), true ) ) {
			return;
		}

		// .llms-plan-row-3,h4:has(+ .llms-plan-row-3),
		echo '<style type="text/css">
			.llms-plan-row-3 > div:not(:first-child),
			.llms-plan-row-5,
			.llms-plan-row-4,
			.llms-plan-row-wc .llms-button-secondary.small{ display: none !important; }
			.llms-plan-row-3 > div:last-child { display: block !important; }
			.llms-plan-row-7 { display: none; }
			.llms-redirection-settings { display: none; }
		</style>';
	}

	/**
	 * Output inline JS to handle UX for access plans.
	 *
	 * @since 2.0.0
	 * @since 2.0.9 Show redirect settings only for free plans.
	 *
	 * @return void
	 */
	public function output_js() {

		$screen = get_current_screen();
		if ( ! in_array( $screen->post_type, array( 'course', 'llms_membership' ), true ) ) {
			return;
		}
		?>
		<script>( function( $ ){

			// When a new plan is initialized automatically remove the "required" attribute from the hidden price box.
			$( document ).on( 'llms-plan-init', function( event, html ) {
				var $clone = $( html );
				$clone.find( 'input.llms-plan-price' ).removeAttr( 'required' );
				window.llms.metaboxes.post_select( $clone.find( 'select.llms-wc-plan-pid' ) );
			} );

			// When "is free" is checked, toggle the visibility of the plan availability options.
			// Free items use availability from here, product connections use availability of the product/variation in WC settings.
			$( document ).on( 'change', 'input[type="checkbox"][name^="_llms_plans"][name*="is_free"]', function() {
				var $box = $( this ),
					$plan = $box.closest( '.llms-access-plan' ),
					$price = $plan.find( 'input.llms-plan-price' );
					$availability = $plan.find( 'select[name^="_llms_plans"][name*="availability"]').closest( '.d-1of2' ),
					$redirects = $plan.find( '.llms-plan-row-7' );
					console.log( $redirects );
				if ( $box.is( ':checked' ) ) {
					$availability.show();
					$redirects.show();
				} else {
					$availability.hide();
					$redirects.hide();
					$price.val( 1 ); // Set a price to prevent validation issues.
				}
			} );

		} )( jQuery );

		</script>
		<?php
	}

	/**
	 * Output custom access plan fields.
	 *
	 * @since 2.0.0
	 * @since 2.0.6 Unknown.
	 *
	 * @param LLMS_Access_Plan $plan  LLMS_Access_Plan.
	 * @param int              $id    Access Plan ID.
	 * @param int              $order Access Plan order.
	 * @return void
	 */
	public function output_fields( $plan, $id, $order ) {

		$selected = array();
		if ( $plan ) {
			$pid = $plan->get( 'wc_pid' );
			if ( $pid ) {
				$selected = llms_make_select2_post_array( $pid );
			}
		}
		?>

		<div class="llms-plan-row-wc" data-controller="llms-is-free" data-value-is-not="yes">

			<div class="llms-metabox-field d-all">
				<label><?php _e( 'WooCommerce Product', 'lifterlms-woocommerce' ); ?> <span class="llms-required">*</span></label>
				<select class="llms-wc-plan-pid<?php echo $plan ? ' llms-select2-post' : ''; ?>" data-placeholder="<?php esc_attr_e( 'Select a product or product variation', 'lifterlms-woocommerce' ); ?>" data-post-type="product,product_variation" name="_llms_plans[<?php echo $order; ?>][wc_pid]">
					<?php foreach ( $selected as $opt ) : ?>
						<option value="<?php echo absint( $opt['key'] ); ?>" selected="selected"><?php echo esc_attr( $opt['title'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

		</div>

		<div class="clear"></div>

		<?php
	}

	/**
	 * Register custom access plan properties with the model.
	 *
	 * @since 2.0.12
	 *
	 * @param array $props Existing properties.
	 * @return array
	 */
	public function register_properties( $props ) {

		$props['wc_pid'] = 'absint';
		return $props;
	}

	/**
	 * Filter the access plans of an llms product to exclude those
	 * which are not linked to any wc products.
	 *
	 * Only in front.
	 *
	 * @since 2.0.14
	 *
	 * @param array $plans Array of access plans.
	 * @return array
	 */
	public function maybe_filter_unlinked_plans( $plans ) {

		// Do not run in admin (metaboxes).
		/**
		 * Filters whether or not filtering the unlinked access plan
		 * By default filtered on front.
		 *
		 * @since 2.0.13
		 *
		 * @param bool $filter Whether to filter or not.
		 */
		if ( ! apply_filters( 'llms_wc_filter_unlinked_plans', ! is_admin() ) ) {
			return $plans;
		}

		$to_return = array();

		foreach ( $plans as $plan ) {
			if ( $plan->is_free() || ( llms_wc_plan_has_wc_product( $plan ) && wc_get_product( $plan->get( 'wc_pid' ) ) ) ) {
				$to_return[] = $plan;
			}
		}

		return $to_return;
	}

	/**
	 * Filter the args used to query the access plans of an llms product
	 * to exclude those which are not linked to any wc products.
	 *
	 * Only in front.
	 *
	 * @since 2.0.13
	 * @deprecated 2.0.14
	 *
	 * @param array $args Array of query args.
	 * @return array
	 */
	public function filter_unlinked_plans( $args ) {

		llms_deprecated_function( 'LLMS_WC_Access_Plans::filter_unlinked_plans', '2.0.14' );

		// Do not run in admin (metaboxes).
		/**
		 * Filters whether or not filtering the unlinked access plan
		 * By default filtered on front.
		 *
		 * @since 2.0.13
		 *
		 * @param bool $filter Whether to filter or not.
		 */
		if ( ! apply_filters( 'llms_wc_filter_unlinked_plans', ! is_admin() ) ) {
			return $args;
		}

		// Only retrieve access plans that have a wc product linked.
		// Note that you can actually save an access plan not linked to any product, in that case `_llms_wc_pid` would be 0.
		$mq = array(
			array(
				'key'     => '_llms_wc_pid',
				'value'   => '0',
				'compare' => '!=',
				'type'    => 'NUMERIC',
			),
		);

		if ( isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
			$args['meta_query'] = array_merge( $args['meta_query'], $mq );
		} else {
			$args['meta_query'] = $mq;
		}

		return $args;
	}

	/**
	 * Maybe add a notice in the access plan metabox if it's not linked to any wc products.
	 *
	 * @since 2.0.13
	 * @since 2.0.14 Made sure the notice is not shown for free access plans.
	 *
	 * @param LLMS_Access_Plan $plan LLMS_Access_Plan.
	 * @return void
	 */
	public function access_plan_mb_notice( $plan ) {

		// Write an error message if there's no product linked.
		if ( $plan && ! $plan->is_free() && ( ! llms_wc_plan_has_wc_product( $plan ) || ! wc_get_product( $plan->get( 'wc_pid' ) ) ) ) {
			printf(
				'<p class="notice notice-error">%s</p>',
				esc_html__(
					'This access plan is associated with a product that doesn\'t exist anymore. You can either delete it or add a new product connection',
					'lifterlms-woocommerce'
				)
			);
		}
	}

	/**
	 * Remove `_llms_wc_pid meta` on wc product deletion.
	 *
	 * @since 2.0.13
	 *
	 * @param int $post_id The ID of the deleted post.
	 * @return void
	 */
	public function on_product_deleted( $post_id ) {

		if ( empty( wc_get_product( $post_id ) ) ) {
			return;
		}

		// Use delete_metadata in place of a direct query because the first also takes care of cleaning the post meta caches.
		delete_metadata( 'post', $object_id = 0, '_llms_wc_pid', absint( $post_id ), $delete_all = true );
	}
}

return new LLMS_WC_Access_Plans();
