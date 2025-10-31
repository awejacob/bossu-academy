<?php
/**
 * Handle modification of product & shop loop templates for products with membership restrictions
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 2.0.0
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_Availability_Buttons class.
 *
 * @since 2.0.0
 * @since 2.0.6 Unknown.
 * @since 2.1.2 Added logic to avoid Astra theme doubling buttons in shop loop items.
 *              Changed logic to decide whether or not the default add to cart button should be removed.
 *              Fixed members only button link not pointing to the membership.
 */
class LLMS_WC_Availability_Buttons {

	/**
	 * Store whether we should add back the default add to cart button after removing it.
	 *
	 * @since 2.1.2
	 *
	 * @var boolean
	 */
	private $add_back_add_to_cart_btn;

	/**
	 * Store add to cart button priority.
	 *
	 * @since 2.1.2
	 *
	 * @var int
	 */
	private $add_back_add_to_cart_btn_priority;

	/**
	 * Constructor
	 *
	 * @since 2.0.0
	 * @since 2.1.2 Added Astra theme filter callback to avoid it adding the add to cart button in shop loop items
	 *              for members only products.
	 * @since 2.1.3 Added Astra Addon filter callback to avoid it adding the add to cart button in single products
	 *              for members only products.
	 * @since 2.3.0 Added compatibility for WC blockified templates.
	 *
	 * @return void
	 */
	public function __construct() {

		add_action( 'woocommerce_before_shop_loop_item', array( $this, 'before_product' ) ); // Loop.
		add_action( 'woocommerce_before_single_product', array( $this, 'before_product' ) ); // Single.

		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'output_membership_button' ), 10, 0 );

		add_filter( 'astra_woo_single_product_structure', array( $this, 'maybe_prevent_astra_add_to_cart_button' ), 50 );
		add_filter( 'astra_woo_shop_product_structure', array( $this, 'maybe_prevent_astra_add_to_cart_button' ), 50 );

		/** Blockified templates compatibility */
		add_filter( 'render_block_woocommerce/add-to-cart-form', array( $this, 'maybe_modify_blockified_add_to_cart_form' ) );
		add_filter( 'woocommerce_blocks_product_grid_item_html', array( $this, 'maybe_modify_blockified_product_grid_item' ), 10, 3 );
		// Since WooCommerce 8.7.0.
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'maybe_modify_blockified_loop_product_add_to_cart_link' ), 10, 3 );

	}

	/**
	 * Modify the blockified product add to cart link in loops: catalogs, related products ecc.
	 *
	 * @since 2.3.0
	 *
	 * @param string     $html    Link HTML.
	 * @param WC_Product $product Product object.
	 * @param array      $args    Arguments used to build the element.
	 * @return string
	 */
	public function maybe_modify_blockified_loop_product_add_to_cart_link( $html, $product, $args ) {

		if ( ! $product ) {
			return $html;
		}

		if ( llms_wc_is_product_variable( $product ) ) {
			return $html;
		}

		// Replace the add to cart button with the members only button.
		if ( llms_wc_is_members_only_wc_product_restricted_for_user( $product ) ) {
			$membership_id = get_post_meta( $product->get_id(), '_llms_membership_id', true );
			$search        = array(
				'<button', // In case of Ajax add-to-cart.
				'</button>', // In case of Ajax add-to-cart.
				'ajax_add_to_cart', // In case of Ajax add-to-cart.
				esc_url( $product->add_to_cart_url() ),
				esc_html( $product->add_to_cart_text() ), // Text.
			);
			$replace       = array(
				'<a',
				'</a>',
				'',
				esc_url( get_permalink( $membership_id ) ),
				self::get_button_text( $product->get_id() ),
			);

			if ( isset( $args['attributes'] ) ) {
				// Remove aria-label and rel no-dollow.
				$search[]  = 'aria-label="' . $args['attributes']['aria-label'] . '"';
				$search[]  = 'rel="' . $args['attributes']['rel'] . '"';
				$replace[] = '';
				$replace[] = '';
			}

			$html = str_replace(
				$search,
				$replace,
				$html
			);
		}

		return $html;

	}

	/**
	 * Modify the blockified product grid item to replace the add-to-cart button with
	 * the members only button.
	 *
	 * This kind of block is used, e.g., on the cart page when the cart is empyt to display
	 * the latest products of the shop.
	 *
	 * @since 2.3.0
	 *
	 * @param string     $html   Product grid item HTML.
	 * @param array      $data   Product data passed to the template.
	 * @param WC_Product $product Product object.
	 * @return string Updated product grid item HTML.
	 */
	public function maybe_modify_blockified_product_grid_item( $html, $data, $product ) {

		if ( ! $product ) {
			return $html;
		}

		if ( llms_wc_is_product_variable( $product ) ) {
			return $html;
		}

		// Replace the add to cart button with the members only button.
		if ( llms_wc_is_members_only_wc_product_restricted_for_user( $product ) ) {
			$membership_id     = get_post_meta( $product->get_id(), '_llms_membership_id', true );
			$membership_button = sprintf(
				'<div class="wp-block-button wc-block-grid__product-add-to-cart">%1$s</div>',
				$this->get_button_html( $product->get_id(), $membership_id )
			);

			$html = str_replace(
				$data->button,
				$membership_button,
				$html
			);
		}

		return $html;

	}

	/**
	 * Modify the blockified add to cart form in single products to replace it with
	 * the members only button.
	 *
	 * @since 2.3.0
	 *
	 * @param string $block_html  Add to cart form HTML.
	 * @return string
	 */
	public function maybe_modify_blockified_add_to_cart_form( $block_html ) {
		$product = wc_get_product();

		if ( ! $product ) {
			return $block_html;
		}

		// Nothing to do on variable products, the JS in the before_product.
		if ( llms_wc_is_product_variable( $product ) ) {
			return $block_html;
		}

		// Replace add to cart form with the members only button.
		if ( llms_wc_is_members_only_wc_product_restricted_for_user( $product ) ) {
			ob_start();
			$this->output_membership_button( 'div' );
			return ob_get_clean();
		}

		return $block_html;

	}

	/**
	 * Before displaying a WC Product (single and in loops).
	 *
	 * Check if it's a members only product and replace
	 * the button with a members only link instead.
	 *
	 * @since 2.0.0
	 * @since 2.0.3 Unknown.
	 * @since 2.1.2 Changed logic to decide whether or not the default add to cart button should be removed.
	 * @since 2.2.2 Prevented the output of JavaScript that hides a variable product's "add to cart" button when multiple products are being displayed.
	 * @since 2.3.0 Only re-add, the add to cart button on single products if it was previously removed.
	 *              Leverage new function `llms_wc_is_members_only_wc_product_restricted_for_user`.
	 *
	 * @return void
	 */
	public function before_product() {

		$product = wc_get_product();

		if ( ! $product ) {
			return;
		}

		if ( llms_wc_is_product_variable( $product ) ) {
			// No need for this in the shop loop items, e.g. in catalogs or in releated products.
			if ( 'woocommerce_before_shop_loop_item' === current_filter() ) {
				return;
			}

			echo "<script>(function($){
				$( 'body' ).on( 'change', 'form.variations_form select[data-attribute_name]', function() {
					var attr_val = $( this ).val(),
						attr_name = $( this ).attr( 'data-attribute_name' ),
						btn = $( '.woocommerce-variation-add-to-cart.variations_button' ),
						show = true;
					$( '[data-variation]' ).hide();
					$.each( JSON.parse( $( 'form.variations_form' ).attr( 'data-product_variations' ) ), function( i, v ) {
						if ( attr_val === v.attributes[ attr_name ] && 'yes' === v.llms_restriction ) {
							btn.hide();
							$( '[data-variation=\"' + v.variation_id + '\"]').show();
							show = false;
						}
					} );
					if ( show ) {
						btn.show();
					}
				} );
			})(jQuery);</script>";

		} else {

			if ( llms_wc_is_members_only_wc_product_restricted_for_user( $product ) ) {

				$has_loop_add_to_cart_btn = has_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
				if ( $has_loop_add_to_cart_btn ) {
					remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', $has_loop_add_to_cart_btn ); // Loop.
					$this->add_back_add_to_cart_btn          = true;
					$this->add_back_add_to_cart_btn_priority = $has_loop_add_to_cart_btn;
				}

				$has_single_add_to_cart = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' );
				if ( $has_single_add_to_cart ) {
					remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', $has_single_add_to_cart ); // Single.
				}

				if ( $has_loop_add_to_cart_btn ) {
					add_action( 'woocommerce_after_shop_loop_item', array( $this, 'output_membership_button' ), $has_loop_add_to_cart_btn, 0 ); // Loop.
				}

				if ( $has_single_add_to_cart ) {
					add_action( 'woocommerce_single_product_summary', array( $this, 'output_membership_button' ), $has_single_add_to_cart, 0 ); // Single.
				}
			}
		}

	}

	/**
	 * Retrieve the HTML for a members only button.
	 *
	 * @since 2.0.0
	 * @since 2.1.2 Fixed button link not pointing to the membership.
	 * @since 2.3.0 Added CSS classes for better compatibility with wc-blocks.
	 *
	 * @param int $post_id       WP Post ID of the WC Product/Variation.
	 * @param int $membership_id WP Post ID of the LLMS Membership.
	 * @return string
	 */
	private function get_button_html( $post_id, $membership_id ) {

		$classes = 'llms-wc-members-only-button button wp-element-button wp-block-button__link';
		if ( is_singular() ) {
			$classes .= ' alt';
		}

		/**
		 * Filter: llms_wc_members_only_button_html
		 *
		 * Modify the HTML of a WC Product "Members Only" button.
		 *
		 * @since 2.0.0
		 * @since 2.1.2 Third parameter `$membership_id` added.
		 *
		 * @param string $html          HTML of the button.
		 * @param int    $post_id       WP Post ID of the WC product or product variation.
		 * @param int    $membership_id WP Post ID of the LLMS Membership.
		 */
		return apply_filters(
			'llms_wc_members_only_button_html',
			sprintf(
				'<a class="%1$s" href="%2$s">%3$s</a>',
				$classes,
				esc_url( get_permalink( $membership_id ) ),
				self::get_button_text( $post_id )
			),
			$post_id,
			$membership_id
		);

	}

	/**
	 * Get the text for a members only button
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id WP Post ID of the WC Product/Variation.
	 * @return string
	 */
	public static function get_button_text( $post_id ) {

		$text = get_post_meta( $post_id, '_llms_membership_btn_txt', true );

		// If no text saved, output the default.
		if ( ! $text ) {
			/**
			 * Filter: llms_wc_members_only_button_default_text
			 *
			 * Modify the default text of a WC Product "Members Only" button.
			 * This text only shows up if the postmeta value is empty.
			 *
			 * @since 2.0.0
			 *
			 * @param string $text    Default text of the button.
			 * @param int    $post_id WP_Post_ID of the WC product or product variation.
			 */
			$text = apply_filters( 'llms_wc_members_only_button_default_text', __( 'Members Only', 'lifterlms-woocommerce' ), $post_id );
		}

		/**
		 * Filter: llms_wc_members_only_button_text
		 *
		 * Modify the text of a WC Product "Members Only" button.
		 *
		 * @since 2.0.0
		 *
		 * @param string $text    Saved text of the button.
		 * @param int    $post_id WP_Post_ID of the WC product or product variation.
		 */
		return apply_filters( 'llms_wc_members_only_button_text', $text, $post_id );

	}

	/**
	 * Output a members only button when a product is restricted to a membership.
	 *
	 * @since 2.0.0
	 * @since 2.0.6 Unknown.
	 * @since 2.1.2 Changed the logic to decide whether or not the default add to cart button should be added back.
	 *              Pass the membership_id to the `get_button_html()` method.
	 * @since 2.3.0 Added wrapper tag param.
	 *              Leverage new function `llms_wc_is_members_only_wc_product_restricted_for_user`.
	 *              Use the correct priority to re-add the add to car button.
	 *
	 * @param string $tag The button wrapper tag.
	 * @return void
	 */
	public function output_membership_button( $tag = 'span' ) {

		$product = $product ?? wc_get_product();
		if ( ! $product ) {
			return;
		}

		if ( llms_wc_is_product_variable( $product ) ) {
			foreach ( $product->get_available_variations() as $variation ) {

				$membership_id = get_post_meta( $variation['variation_id'], '_llms_membership_id', true );
				if ( $membership_id ) {
					echo '<' . $tag . ' class="llms-wc-members-only-button-wrap" data-variation="' . $variation['variation_id'] . '" style="display:none;">';
					echo $this->get_button_html( $variation['variation_id'], $membership_id );
					echo '</' . $tag . '>';
				}
			}
		} else {

			if ( llms_wc_is_members_only_wc_product_restricted_for_user( $product ) ) {
				$membership_id = get_post_meta( $product->get_id(), '_llms_membership_id', true );
				echo '<' . $tag . ' class="llms-wc-members-only-button-wrap">';
				echo $this->get_button_html( $product->get_id(), $membership_id );
				echo '</' . $tag . '>';
			}

			// Add removed action back in to ensure the next item in the loop is checked.
			if ( ! empty( $this->add_back_add_to_cart_btn ) ) {
				add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', $this->add_back_add_to_cart_btn_priority );
				$this->add_back_add_to_cart_btn = false;
			}
		}
	}

	/**
	 * Astra theme compatibility.
	 *
	 * Prevent Astra to output the add to cart button on members only products.
	 *
	 * @since 2.1.2
	 * @since 2.3.0 Leverage new function `llms_wc_is_members_only_wc_product_restricted_for_user`.
	 *               Update to print the members only button correctly.
	 *
	 * @param array $product_structure An array containing the loop item, or single product,
	 *                                 structure as defined by the Astra(-addons) theme.
	 * @return array
	 */
	public function maybe_prevent_astra_add_to_cart_button( $product_structure ) {

		remove_action( 'woocommerce_after_shop_loop_item', array( $this, 'output_membership_button' ), 11, 0 ); // Loop.

		if ( ! is_array( $product_structure ) ) {
			return $product_structure;
		}

		$product = wc_get_product();
		if ( ! $product || llms_wc_is_product_variable( $product ) ) {
			return $product_structure;
		}

		if ( llms_wc_is_members_only_wc_product_restricted_for_user( $product ) ) {
			$add_to_cart_index = array_search( 'add_cart', $product_structure, true );
			if ( false !== $add_to_cart_index ) {
				unset( $product_structure[ $add_to_cart_index ] );
				add_action( 'woocommerce_single_product_summary', array( $this, 'output_membership_button' ), 11, 0 ); // Single.
				add_action( 'woocommerce_after_shop_loop_item', array( $this, 'output_membership_button' ), 11, 0 ); // Loop.
			}
		}

		return $product_structure;
	}

}

return new LLMS_WC_Availability_Buttons();
