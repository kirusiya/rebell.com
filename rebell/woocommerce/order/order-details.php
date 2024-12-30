<?php
/**
 * Order details
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-details.php.
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

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id );

if ( ! $order ) {
	return;
}

$orderItems    = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$isDelivery    = $order->get_meta( '_is_delivery' );
$pickupAdderss = $order->get_meta( 'pickupAddress' ) !== '' ? $order->get_meta( 'pickupAddress' ) : 'Dirección no especificada. Por favor, contacte con soporte';
?>
<section class="WCAccountOrder">
	<h3 class="Title">Mis Pedidos</h3>

	<article class="OrderResume">
		<header class="rounded-top">
			<?= sprintf( 'Resumen #%s', $order->get_order_number( ) ) ?>
		</header>

		<main class="rounded-bottom">
			<ul>
				<?php foreach ( $orderItems as $item_id => $item ) : ?>
					<?php if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) continue; ?>
					<?php
						$product     = $item->get_product( );
						$permalink   = ( $product && $product->is_visible( ) ) ? $product->get_permalink( $item ) : '';
						$name        = $permalink ? sprintf( '<a href="%s">%s</a>', $permalink, $item->get_name( ) ) : $item->get_name( );
						$qty         = $item->get_quantity( );
						$refundedQty = $order->get_qty_refunded_for_item( $item_id );
						$qty         = $refundedQty
							? '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refundedQty * -1 ) ) . '</ins>'
							: esc_html( $qty );
					?>
					<li>
						<div class="item">
							<div>
								<span class="name"><?= $name ?></span>
								<span class="qty">&times<?= $qty ?></span>
							</div>
							<span class="price"><?= wc_price( $item->get_total( ) + $item->get_total_tax( ) ) ?></span>
						</div>

						<?php 
							foreach ($item->get_meta_data( ) as $meta) {
								if ( ! $ingredients = \Invbit\Core\CustomizeWooCommerce::$propTypes[$meta->key] ?? null ) {
									continue;
								}

								$ingredients = array_map(function($ing) {
									return $ing['name'];
								}, $meta->value );
								printf( '<small class="additional-ingredients">%s</small>', implode( ', ', $ingredients ) );
							}
						?>
					</li>
				<?php endforeach; ?>

				<li>
					<div class="item">
						<span>Gastos de envío</span>
						<span class="price"><?= 
							( $order->get_shipping_total( ) > 0 )
								? wc_price( $order->get_shipping_total( ) + $order->get_shipping_tax( ) )
								: 'GRATIS'
						?></span>
					</div>
				</li>
			</ul>
		</main>
	</article>

	<article class="OrderTotal">
		TOTAL <span class="total"><?= wc_price( $order->get_total( ) ) ?></span>
	</article>

	<article class="OrderShipping">
		<header class="rounded-top">
			<?= $isDelivery ? 'Información del envío' : 'Información de recogida' ?>
		</header>

		<main class="rounded-bottom">
			<ul>
				<li>
					<span class="name"><?= $isDelivery ? 'Dirección de envío' : 'Dirección de recogida' ?></span>
					<span class="value">
						<?= $isDelivery
							? $order->get_shipping_address_1() . ', ' .  $order->get_shipping_city()
							: $pickupAdderss
						?>
					</span>
				</li>
				<?php if ( $scheduledFor = $order->get_meta( '_scheduled_for' ) ) : ?>
				<li>
					<span class="name"><?= $isDelivery ? 'Hora estimada de entrega' : 'Hora estimada de recogida' ?></span>
					<span class="value">
						<?= $scheduledFor ?>
					</span>
				</li>
				<?php endif; ?>
			</ul>
		</main>
	</article>

	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>

</section>

