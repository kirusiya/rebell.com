<?php
/**
 * Checkout Payment Section
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.3
 */

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

function sort_payment_gateways($a, $b) {
    // Define an array of cash payment method IDs
    $cash_methods = ['cod', 'bacs', 'cheque', 'cash']; // Add more cash method IDs if needed

    // Check if method a is a cash method
    $a_is_cash = in_array($a->id, $cash_methods);
    
    // Check if method b is a cash method
    $b_is_cash = in_array($b->id, $cash_methods);

    // If a is cash and b is not, a should come first
    if ($a_is_cash && !$b_is_cash) {
        return -1;
    }
    // If b is cash and a is not, b should come first
    elseif (!$a_is_cash && $b_is_cash) {
        return 1;
    }
    // If both are cash or both are not cash, maintain original order
    else {
        return 0;
    }
}

if ( ! is_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
} ?>

<div id="payment" class="woocommerce-checkout-payment">
    <h2>Forma de Pago</h2>

	<?php if ( WC()->cart->needs_payment() ) : ?>
		<ul class="wc_payment_methods payment_methods methods">
			<?php
			
			if ( ! empty( $available_gateways ) ) {
                // Sort the gateways
                uasort($available_gateways, 'sort_payment_gateways');
				foreach ( $available_gateways as $gateway ) {
					wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
				}
			} else {
				echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ) . '</li>'; // @codingStandardsIgnoreLine
			}
			?>
		</ul>
	<?php endif; ?>
	<div class="form-row place-order">
		<noscript>
			<?php
			/* translators: $1 and $2 opening and closing emphasis tags respectively */
			printf( esc_html__( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ), '<em>', '</em>' );
			?>
			<br/><button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>"><?php esc_html_e( 'Update totals', 'woocommerce' ); ?></button>
		</noscript>

		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php
		$zipcode = $_SESSION['zipcode'] ?? '';

		// Calculate shipping cost based on zipcode
		$shipping_cost = 0;
		if ($zipcode) {
			$shipping_cost = get_shipping_cost_by_zipcode($zipcode);
		}
		$subtotal = WC()->cart->subtotal - (WC()->cart->get_discount_total() + WC()->cart->get_discount_tax());
		$total = $subtotal + $shipping_cost;
		?>

		<h4><b>TOTAL</b> <?= wc_price($total); ?></h4>

		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // @codingStandardsIgnoreLine ?>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
</div>

<?php if ( ! is_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
