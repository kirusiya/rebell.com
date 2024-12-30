<?php
/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders ); ?>

<div class="WCAccountOrders">
	<h3 class="Title">Mis Pedidos</h3>

	<?php if ( $has_orders ) : ?>

		<?php foreach ( $customer_orders->orders as $customer_order ) : ?>
			<?php
				$order      = wc_get_order( $customer_order );
				$item_count = $order->get_item_count( ) - $order->get_item_count_refunded( );
				$actions    = wc_get_account_orders_actions( $order );
				$completed  = $order->get_status( ) === 'completed';
			?>
			<article class="OrderResume">
				<header class="rounded-top">
					<a href="<?= esc_url( $order->get_view_order_url( ) ); ?>">
						<?= sprintf( 'Pedido #%s', $order->get_order_number( ) ) ?>
					</a>
				</header>

				<main>
					<ul>
						<li>
							<span>Número de pedido</span>
							<a href="<?= esc_url( $order->get_view_order_url() ); ?>">
								<?= $order->get_order_number() ?>
							</a>
						</li>
						<li>
							<span>Fecha del pedido</span>
							<time datetime="<?= esc_attr( $order->get_date_created()->date( 'c' ) ); ?>">
								<?= esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
							</time>
						</li>
						<li>
							<span>Total</span>
							<span><?= wc_price( $order->get_total( ) ) ?></span>
						</li>
					</ul>
				</main>

				<div class="Status <?= $order->get_status( ) . ($completed ? '' : ' rounded-bottom border-bottom') ?>">
					<span>Estado del pedido</span>
					<span><?= esc_html( wc_get_order_status_name( $order->get_status() ) ) ?></span>
				</div>

				<?php if ( $completed ) : ?>
				<footer class="rounded-bottom">
					<a href="<?= wp_nonce_url( add_query_arg( 'order_again', $order->get_id( ), wc_get_cart_url( ) ), 'woocommerce-order_again' ) ?>">
						¡Repetir pedido!
					</a>

					<?php /* if ( ! empty( $actions ) ) : ?>
						<?php foreach ( $actions as $key => $action ) : ?>
							<a href="<?= esc_url( $action['url'] ) ?>" class="woocommerce-button button <?= sanitize_html_class( $key ) ?>">
								<?= esc_html( $action['name'] ) ?>
							</a>
						<?php endforeach; ?>
					<?php endif; */ ?>
				</footer>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>

		<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

		<?php if ( $customer_orders->max_num_pages > 1 ) : ?>
			<?php
				$showPrevious = $current_page !== 1;
				$showNext     = intval( $customer_orders->max_num_pages ) !== $current_page;
			?>
			<div class="woocommerce-pagination">
				<?php if ( $showPrevious ) : ?>
					<a class="previous" style="<?= $showNext ? 'margin-right:auto;' : '' ?>" href="<?= esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">
						&lt; Pedidos posteriores
					</a>
				<?php endif; ?>

				<?php if ( $showNext ) : ?>
					<a class="next" style="<?= $showPrevious ? 'margin-left:auto;' : '' ?>" href="<?= esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">
						Pedidos anteriores &gt;
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	<?php else : ?>
		<div style="text-align:center">
			<p>Parece que no tienes ningún pedido hasta ahora</p>
			<a class="woocommerce-Button button"
				href="<?= esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
				Ver carta
			</a>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
</div>