<?php
/**
 * Show enrollment data for qualifying WC Order Items
 *
 * @package  LifterLMS_WooCommerce/Views
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @var  array $llms_products lift of LifterLMS course / membership ids.
 * @var  obj $student instance of the current LLMS_Student.
 * @var  obj $order instance of the current WC_Order.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="llms-wc-enrollment-data">
	<div class="llms-wc-enrollment-row llms-wc-row-header">
		<div class="llms-wc-cell llms-product"><?php _e( 'Course / Membership', 'lifterlms-woocommerce' ); ?></div>
		<div class="llms-wc-cell llms-status"><?php _e( 'Enrollment', 'lifterlms-woocommerce' ); ?></div>
		<div class="llms-wc-cell llms-expiration"><?php _e( 'Expiration', 'lifterlms-woocommerce' ); ?></div>
	</div>

	<?php
	foreach ( $llms_products as $product_id ) :
		$llms_post      = llms_get_post( $product_id );
		$current_status = $student->get_enrollment_status( $product_id );
		$select         = '<select style="width:100%;" name="llms_wc[' . $product_id . '][status]">';
		$statuses       = llms_get_enrollment_statuses();
		$expiration     = LLMS_WC_Order_Actions::get_expiration( $order->get_id(), $product_id );
		if ( ! $current_status ) {
			$statuses = array_merge(
				array(
					'' => esc_attr__( 'None', 'lifterlms-woocommerce' ),
				),
				$statuses
			);
		}
		foreach ( $statuses as $val => $name ) {
			$select .= '<option value="' . $val . '"' . selected( $val, strtolower( $current_status ), false ) . '>' . $name . '</option>';
		}
		$select .= '</select>';
		?>
		<div class="llms-wc-enrollment-row">
			<div class="llms-wc-cell llms-product"><?php printf( '<a href="%2$s">%1$s</a>', $llms_post->get( 'title' ), get_edit_post_link( $llms_post->get( 'id' ) ) ); ?></div>
			<div class="llms-wc-cell llms-status"><?php echo $select; ?></div>
			<div class="llms-wc-cell llms-expiration">
				<a href="#llms-wc-add-expiration"<?php echo $expiration ? ' style="display:none;"' : ''; ?>><?php echo __( 'Add', 'lifterlms-woocommerce' ); ?></a>
				<div class="llms-wc-expiration-fields"<?php echo ! $expiration ? ' style="display:none;"' : ''; ?>>
					<input <?php echo ! $expiration ? ' disabled="disabled"' : ''; ?>class="date-picker" maxlength="10" name="llms_wc[<?php echo $product_id; ?>][expiration][date]" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" type="text" value="<?php echo date_i18n( 'Y-m-d', $expiration ); ?>">
					@
					<input <?php echo ! $expiration ? ' disabled="disabled"' : ''; ?>type="number" class="hour" name="llms_wc[<?php echo $product_id; ?>][expiration][hour] min="0" max="23" step="1" pattern="([01]?[0-9]{1}|2[0-3]{1})" value="<?php echo date_i18n( 'H', $expiration ); ?>">
					:
					<input <?php echo ! $expiration ? ' disabled="disabled"' : ''; ?>type="number" class="minute" name="llms_wc[<?php echo $product_id; ?>][expiration][minute] min="0" max="59" step="1" pattern="[0-5]{1}[0-9]{1}" value="<?php echo date_i18n( 'i', $expiration ); ?>">
					<a href="#llms-wc-del-expiration"><?php echo __( 'Remove', 'lifterlms-woocommerce' ); ?></a>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>
