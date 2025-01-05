<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.8.0
 */

use Invbit\Core\Constants;
use Invbit\Core\Helpers;
use Invbit\Core\CustomizeWooCommerce;
use Invbit\Core\WCCheckoutController;

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

$shippingCost = WCCheckoutController::setShippingTotal( ); 	?>

<div class="woocommerce-checkout-review-order-table">
	<h2>Resumen</h2>

	<div class="OrderResume">
		<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>

		<!-- Products -->
		<?php foreach ( WC( )->cart->get_cart( ) as $key => $cartItem ) : ?>
			<?php $_product = apply_filters( 'woocommerce_cart_item_product', $cartItem['data'], $cartItem, $key ); ?>
		
			<?php if ( $_product && $_product->exists( ) && $cartItem['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cartItem, $key ) ) : ?>
		
				<div class="<?= esc_attr( apply_filters( 'woocommerce_cart_item_class', 'CartItem', $cartItem, $key ) ); ?>">

					<small class="MainCategory"><?= Helpers::getProductMainCategory( $_product->get_id( ) )->name ?? '---' ?></small>
					<div class="Product">
						<div>
							<?= apply_filters( 'woocommerce_cart_item_name', $_product->get_name( ), $cartItem, $key ); ?>
							<?= apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="Qty">' . sprintf( '&times%s', $cartItem['quantity'] ) . '</strong>', $cartItem, $key ); ?>
						</div>
						<?= wc_get_formatted_cart_item_data( $cartItem ); ?>
						<?= apply_filters( 'woocommerce_cart_item_subtotal', WC( )->cart->get_product_subtotal( $_product, $cartItem['quantity'] ), $cartItem, $key ); ?>
					</div>
					<div>
						<?php foreach (CustomizeWooCommerce::$propTypes as $key => $title) : ?>
							<?php if (empty($cartItem[$key])) continue; ?>
							<?php foreach ($cartItem[$key] as $prop) : ?>
								<div class="Props">
									<small class="PropName"><?= $prop['name'] ?></small>
									<small class="PropSpiciness"><?= str_repeat(Constants::$spicyIcon, $prop['spiciness']) ?></small>
								</div>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</div>
				</div>
		
			<?php endif; ?>
		
		<?php endforeach; ?>

		<!-- Coupons -->
		<?php foreach ( WC( )->cart->get_coupons( ) as $code => $coupon ) : ?>
			<div class="CartItem Discount">
				<div class="Coupon">
					<span class="Label">
						<small>Descuento</small>
						<?= $coupon->get_code( ) ?>
					</span>
					<span class="Value">
						<?php $amount = WC( )->cart->get_coupon_discount_amount(
							$coupon->get_code( ),
							WC( )->cart->display_cart_ex_tax
						); ?>
						<?= $coupon->get_free_shipping( ) && empty( $amount )
							? __( 'Free shipping coupon', 'woocommerce' )
							: '-' . wc_price( $amount );
						?>
					</span>
				</div>
			</div>
		<?php endforeach; ?>

		<!-- Shipping -->


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

		<?php if ( $zipcode) : ?>

			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

			<div class="CartItem Shipping">
				<div class="Shipping">
					<span>Gastos de envío</span>
					<span class="Value"><?= wc_price( $shipping_cost ) ?></span>
				</div>
			</div>

			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

		<?php endif; ?>
		
		<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>
	</div>

	<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

	<div class="OrderTotal">
		<span><?= esc_html( 'Total', 'woocommerce' ) ?></span>
		<span class="Total"><?= wc_price($total); ?></span>
		<?php /* <span><?php wc_cart_totals_order_total_html(); ?></span> */ ?>
	</div>

	<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
</div>

<?php /*
<div class="cart-subtotal">
	<span><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
	<span><?php wc_cart_totals_subtotal_html(); ?></span>
</div>

<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
	<div class="fee">
		<span><?php echo esc_html( $fee->name ); ?></span>
		<span><?php wc_cart_totals_fee_html( $fee ); ?></span>
	</div>
<?php endforeach; ?>

<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
	<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
		<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited ?>
			<div class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<span><?php echo esc_html( $tax->label ); ?></span>
				<span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
			</div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="tax-total">
			<span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
			<span><?php wc_cart_totals_taxes_total_html(); ?></span>
		</div>
	<?php endif; ?>
<?php endif; ?>
*/ ?>