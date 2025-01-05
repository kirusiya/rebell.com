<?php

/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.7.0
 */

use Invbit\Core\Constants;
use Invbit\Core\CustomizeWooCommerce;
use Invbit\Core\WCCartController;
use Invbit\Core\WCKitchenController;

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

try {
	$customerKitchen = WCKitchenController::getCustomerKitchenFromZipcode( );
} catch ( \Exception $e ) {
	return wc_get_template( 'notices/error.php', [ 'messages' => [ $e->getMessage( ) ] ] );
}

$schedules = WCKitchenController::getSchedulesForKitchen( $customerKitchen );
$deliveryOpen = $schedules['delivery']['opened'];
$takeawayOpen = $schedules['takeaway']['opened'];
$deliverySelectables = WCCartController::getSchedule( $schedules['delivery'], 'delivery' );
$takeawaySelectables = WCCartController::getSchedule( $schedules['takeaway'], 'takeaway' );


do_action('woocommerce_before_cart');

?>


<form class="woocommerce-cart-form" action="<?= esc_url(wc_get_cart_url()); ?>" method="post">
	<?php do_action('woocommerce_before_cart_table'); ?>

	<div class="row">

		<div class="col-md-12 mb-4">
			<?php //echo $_SESSION['zipcode'];?>	
			<h1>TU PEDIDO</h1>
		</div>

		<!--inicio clase col-md-6 -->
		<div class="col-md-6">

		<div class="list-cart">
			

			<?php do_action('woocommerce_before_cart_contents'); ?>

			<?php
			foreach (WC()->cart->get_cart() as $cartItemKey => $cartItem) :
				$_product    = apply_filters('woocommerce_cart_item_product', $cartItem['data'], $cartItem, $cartItemKey);
				$productID   = apply_filters('woocommerce_cart_item_product_id', $cartItem['product_id'], $cartItem, $cartItemKey);
				$itemVisible = apply_filters('woocommerce_cart_item_visible', true, $cartItem, $cartItemKey);

				if (! $_product || ! $_product->exists() || $cartItem['quantity'] <= 0 || ! $itemVisible) {
					continue;
				}

				$_isCustomizable = get_field( 'is_customizable', $cartItem[ 'product_id' ] );
				$_permalink      = apply_filters(
					'woocommerce_cart_item_permalink',
					$_product->is_visible() ? $_product->get_permalink($cartItem) : '', $cartItem, $cartItemKey
				); ?>
				<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
					<tbody>
						<tr class="woocommerce-cart-form__cart-item <?= esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cartItem, $cartItemKey)); ?>">
							<th class="ItemHeading" colspan="100%">
								<div class="Wrapper">
									<div class="Item">
										<?= apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											'woocommerce_cart_item_remove_link',
											sprintf(
												'<a href="%s" class="RemoveItem" aria-label="%s" data-product_id="%s" data-product_sku="%s"><span class="dashicons dashicons-trash"></span></a>',
												esc_url(wc_get_cart_remove_url($cartItemKey)),
												esc_html__('Remove this item', 'woocommerce'),
												esc_attr($productID),
												esc_attr($_product->get_sku())
											),
											$cartItemKey
										); ?>
 	

										<?php if (!$_permalink or !$_isCustomizable) : ?>
											<span class="ItemName"><?= $_product->get_name( ) ?></span>
											<?php if ($_product->is_on_sale()) : ?>
												<span class="ItemPrice">
													<?= wc_price($_product->get_sale_price()); // Precio rebajado ?>
												</span>
											<?php else : ?>
												<span class="ItemPrice">
													<?= wc_price($_product->get_regular_price()); // Precio normal ?>
												</span>
											<?php endif; ?>


										<?php else : ?>
											<a class="ItemName" href="<?= esc_url($_permalink) ?>"><?= $_product->get_name() ?></a>
											<?php if ($_product->is_on_sale()) : ?>
												<span class="ItemPrice">
													<?= wc_price($_product->get_sale_price()); // Precio rebajado ?>
												</span>
											<?php else : ?>
												<span class="ItemPrice">
													<?= wc_price($_product->get_regular_price()); // Precio normal ?>
												</span>
											<?php endif; ?>
										<?php endif; ?>

										<?= wc_get_formatted_cart_item_data($cartItem); // PHPCS: XSS ok. ?>

										<?php if ($_product->backorders_require_notification() && $_product->is_on_backorder($cartItem['quantity'])) {
											print wp_kses_post(apply_filters(
												'woocommerce_cart_item_backorder_notification',
												'<p class="backorder_notification">' . esc_html__('Available on backorder', 'woocommerce') . '</p>'
											));
										} ?>
									</div>
									
									<div class="Handlers">
										<?php
											if ($_product->is_sold_individually()) {
												$product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cartItemKey);
											} else {
												$product_quantity = woocommerce_quantity_input(array(
													'input_name'   => "cart[{$cartItemKey}][qty]",
													'input_value'  => $cartItem['quantity'],
													'max_value'    => $_product->get_max_purchase_quantity(),
													'min_value'    => '0',
													'product_name' => $_product->get_name(),
												), $_product, false);
											}
											echo apply_filters('woocommerce_cart_item_quantity', $product_quantity, $cartItemKey, $cartItem); // PHPCS: XSS ok.
										?>
									</div>
								</div>
							</th>
						</tr>

						<!-- <tr class="Props woocommerce-cart-form__cart-item <?= esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cartItem, $cartItemKey)); ?>">
							<td class="PropName ttt"><?= $_product->get_name( ) ?></td>
							<td class="PropSpiciness"></td>
							<td class="PropPrice">
								<?php if ( isset( $cartItem[ 'base_price' ] ) ) : ?>
									<?= wc_price( $cartItem[ 'base_price' ] ) ?>
								<?php else : ?>
									<?= WC( )->cart->get_product_price($_product) ?>
								<?php endif; ?>
							</td>
						</tr> -->

						<?php foreach (CustomizeWooCommerce::$propTypes as $key => $title) : ?>
							<?php if (empty($cartItem[$key])) continue; ?>
							<?php foreach ($cartItem[$key] as $prop) : ?>
								<tr class="Props woocommerce-cart-form__cart-item <?= esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cartItem, $cartItemKey)); ?>">
									<td class="PropName"><?= $prop['name'] ?></td>
									<td class="PropSpiciness"><? //= str_repeat(Constants::$spicyIcon, $prop['spiciness']) ?></td>
									<td class="PropPrice"> <?//= wc_price($prop['price']) ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>

			<?php do_action( 'woocommerce_after_cart_contents' ); ?>

			

		</div>

		<?php do_action('woocommerce_after_cart_table'); ?>

		<?php do_action('woocommerce_before_cart_collaterals'); ?>

		<?php do_action('woocommerce_cart_collaterals'); ?>

		<!-- <div class="cart-collaterals"></div> -->

		</div>
		<!--fin clase col-md-6 -->
		
		<!--inicio clase col-md-6 -->
		<div class="col-md-6">		
			
			<table>
				<tr>
					<td colspan="6" class="actions">

						<div class="ApplyCoupon coupon">
						<?php if ( count( WC( )->cart->get_coupons( ) ) <= 0 ) : ?>
							<input type="text" name="coupon_code" class="input-text" id="coupon_code" placeholder="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>" />
							<button type="submit" class="button btn-black" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php esc_attr_e('Aplicar', 'rebell'); ?></button>
							<?php do_action('woocommerce_cart_coupon'); ?>
						<?php else : ?>
							<?php $coupon = reset(WC( )->cart->get_coupons( )); ?>
							<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="<?= $coupon->get_code( ) ?>" disabled />
							<a 	class="button"
								data-coupon="<?= esc_attr( $coupon->get_code( ) ) ?>"
								href="<?= esc_url( wc_get_cart_url( ) ); ?>?empty_coupon=<?= $coupon->get_code( ) ?>&security=<?= wp_create_nonce( 'empty_coupon' ) ?>"
							>Eliminar cupón</a>
							<?php do_action('woocommerce_cart_coupon'); ?>
						<?php endif; ?>
						</div>

		
						<?php /*
						<button type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button>
						*/ ?>
		
						<?php do_action( 'woocommerce_cart_actions' ); ?>
		
						<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
					</td>
				</tr>
			</table>

			<div class="spacer-1"></div>

			<!-- Delivery or Takeaway -->
			<section class="OrderType">
				<input type="radio" name="order_type" id="for_delivery" value="delivery" <?= ! $deliveryOpen ? 'disabled' : '' ?>>
				<label for="for_delivery"
					class="Delivery <?= ! $deliveryOpen ? 'disabled' : '' ?>"
					data-type="delivery"
					data-open="<?= (bool) $deliveryOpen ?>"
				>
					<div class="Wrapper">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
							<path d="M23 264a32 32 0 0 0 63 4l-61-15-2 11zM288 242c-12 4-32 11-41 22-4 6-9 8-13 8a32 32 0 1 0 54-30z"/>
							<path d="M246 262a94 94 0 0 1 46-23c2-1 3-3 1-5-4-7-16-22-37-18l-7 2-1-2h2c2-1 3-4 2-6l-4-11c-2-4-6-8-11-10l-2-1-11-19v-1c-2-5 6-10 12-9v-25h-13c-5 0-9 1-13 4l-6-1c-3 0-8-3-8-3l-32-16-19-18-2-6v-2c2-4-1-8-5-10h-1l1-4 5-6c12-3 21-13 24-25l7-4-9-16-3-4 12-7v-2l-17-2c-7-5-15-8-24-8-19 1-34 15-35 34 0 8 1 15 5 21l4 4-4 5c-5 4-8 4-8 4s8 5 15 2c0 0-3 5-8 8 0 0 8 4 16 1 0 0-12 28-7 56 0 0-1 6 1 7 0 0-2 14 6 18l6 2c-9-1-18-2-20 0-2 3 2 14 4 19a205 205 0 0 1 38 0l8-1c3-2 2-13-2-14l-3-1 20 3c3 0 5 2 4 5l-8 68h1l1 11h-2c-14-2-42-21-39-37l1-1c3-9 12-14 19-16 3-1 6-4 5-8 0-4-2-6-5-7h-1a195 195 0 0 0-41 0H38c-1-2-3-3-5-3a6 6 0 1 0 0 11h2l10 10c-21 11-34 25-40 33-3 3-1 7 3 8l19 5 61 15h1l10 3 21 4 66 1c23-1 31-13 39-34 2-7 6-12 10-16l3 1c-9 9-15 22-18 37-1 2 0 4 2 5 2 2 6 3 10 3 5 0 9-2 14-8zm-47-6-20 1 21-2-1 1zM38 192h40c-11 3-21 7-29 11l-11-11zm167 56-1-2c-3-2-10 0-14-2l-6-5h1l9-71c0-5-2-9-7-10l-39-10c2-1 0-6 0-6l1-17 5 5c4 3 37 14 37 14l4 2c0 3 2 5 5 5l2 1c-1 5 0 11 4 16l7 9-1 3c-2 4 10 16 13 30 2 7-10 24-20 38z"/>
							<path d="M237 132h9v29h-9zM100 143c0-5-3-8-8-8H47c-5 0-9 3-9 8v42h62v-42z"/>
						</svg>
						<span>Envío a domicilio</span>
					</div>
				</label>

				<div class="spacer-1/2"></div>

				<input type="radio" name="order_type" id="for_takeaway"  value="takeaway" <?= ! $takeawayOpen ? 'disabled' : '' ?>>
				<label for="for_takeaway"
					class="Takeaway <?= ! $takeawayOpen ? 'disabled' : '' ?>"
					data-type="takeaway"
					data-open="<?= (bool) $takeawayOpen ?>"
				>
					<div class="Wrapper">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
							<path d="M146 2C79 2 26 56 26 122s120 176 120 176 120-109 120-176S212 2 146 2zm0 202a86 86 0 1 1 0-172 86 86 0 0 1 0 172z"/>
						</svg>
						<span>Recoger en el local</span>
					</div>
				</label>
			</section> <!-- / Delivery or Takeaway -->

			<section id="OrderSchedule" class="hidden">
				<p class="title" style="display: none;"></p>

				<div class="SelectWrapper">
					<select name="schedule"
						data-delivery-schedule='<?= json_encode( $deliverySelectables ) ?>'
						data-takeaway-schedule='<?= json_encode( $takeawaySelectables ) ?>'>
						<option value="" disabled selected>¿A qué hora quieres recoger tu pedido?</option>
					</select>
				</div>
			</section> <!-- / Schedule -->								

			<!-- <div class="Totals">
				<span>Subtotal</span>
				<span class="price"><?=
					wc_price( WC( )->cart->subtotal - ( WC( )->cart->get_discount_total( ) + WC( )->cart->get_discount_tax( ) ) )
				?></span>
			</div> -->



			<?php
			// Get the zipcode from the session
			$zipcode = $_SESSION['zipcode'] ?? '';

			// Calculate shipping cost based on zipcode
			$shipping_cost = 0;
			if ($zipcode) {
				$shipping_cost = get_shipping_cost_by_zipcode($zipcode);
			}

			// Calculate totals
			$subtotal = WC()->cart->subtotal - (WC()->cart->get_discount_total() + WC()->cart->get_discount_tax());
			$total = $subtotal + $shipping_cost;
			?>

			<div class="Totals">
				<div class="TotalRow">
					<span class="TotalLabel">Subtotal</span>
					<span class="TotalValue"><?= wc_price($subtotal) ?></span>
				</div>
				<div class="TotalRow">
					<span class="TotalLabel">Envío</span>
					<span class="TotalValue"><?= wc_price($shipping_cost) ?></span>
				</div>
				<div class="TotalRow TotalRow--final">
					<span class="TotalLabel">Total</span>
					<span class="TotalValue"><?= wc_price($total) ?></span>
				</div>
			</div>


			<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>


		</div>
		<!--fin clase col-md-6 -->
		

	</div><!-- fin clase row -->					

</form>

<?php do_action('woocommerce_after_cart'); ?>

<!-- Cart errors modal -->
<aside class="modal micromodal-slide" id="CartErrors_Modal" aria-hidden="true">
	<div class="modal__overlay" tabindex="-1" data-micromodal-close>
		<div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
			<header class="modal__header">
				<h2 class="modal__title">Aviso</h2>
			</header>
			<main class="modal__content">
				<div class="modal__description" id="CartErrors_ModalContent"></div>
			</main>
			<footer class="modal__footer">
				<button class="modal__btn" data-micromodal-close aria-label="Cerrar información">
					Ok
				</button>
			</footer>
		</div>
	</div>
</aside>