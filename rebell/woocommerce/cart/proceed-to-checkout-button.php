<?php
/**
 * Proceed to checkout button
 *
 * Contains the markup for the proceed to checkout button on the cart.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/proceed-to-checkout-button.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 2.4.0
 */

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

if (!is_user_logged_in()) : ?>
	<div class="ProceedToCheckout">
		<a href="<?= wp_login_url( '/carrito' ) ?>" class="button alt">
			<?= esc_html( 'Identifícate', 'betheme' ); ?>
		</a>
	</div>
	<?php return;
endif; ?>

<div class="ProceedToCheckout">
	<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward">
		<?= esc_html( 'Realizar pedido', 'betheme' ); ?>
	</a>

	<button id="EmptyCart" data-modal-id="ConfirmEmptyCart" type="button">
		<?= esc_html( 'Cancelar pedido', 'betheme' ); ?>
	</button>

	<!-- Confirm modal -->
	<aside class="modal micromodal-slide" id="ConfirmEmptyCart" aria-hidden="true">
		<div class="modal__overlay" tabindex="-1" data-micromodal-close>
			<div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
				<header class="modal__header">
					<h2 class="modal__title">Cancelar pedido</h2>
				</header>
				<main class="modal__content">
					<div class="modal__description">
						Estás a punto de eliminar todos los platos del pedido. ¿Deseas continuar?
					</div>
				</main>
				<footer class="modal__footer">
					<button class="modal__btn" data-micromodal-close aria-label="Cerrar información">
						No
					</button>
					<a class="modal__btn" href="<?php echo esc_url( wc_get_cart_url() ); ?>?empty-cart=<?= wp_create_nonce( 'empty-cart' ) ?>">
						Cancelar
					</a>
				</footer>
			</div>
		</div>
	</aside>
</div>
