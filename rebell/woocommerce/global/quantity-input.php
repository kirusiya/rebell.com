<?php
/**
 * Product quantity inputs
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/quantity-input.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 4.0.0
 */

 defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

if ( $max_value and ( $min_value === $max_value ) ) : ?>

	<div class="quantity hidden">
		<input type="hidden" class="qty"
			id="<?= esc_attr( $input_id ); ?>"
			name="<?= esc_attr( $input_name ); ?>"
			value="<?= esc_attr( $min_value ); ?>"
		/>
	</div>

<?php else :

	global $product;

	$randomID = bin2hex( random_bytes( ( 5 ) ) );
	$productInCart = is_cart( ) ?: in_array( $product->get_id( ), array_column( WC( )->cart->get_cart( ), 'product_id' ) );

	/* translators: %s: Quantity. */
	$label = ! empty( $args['product_name'] )
		? sprintf( esc_html__( '%s quantity', 'woocommerce' ), wp_strip_all_tags( $args['product_name'] ) )
		: esc_html__( 'Quantity', 'woocommerce' ); ?>

	<div class="<?= "quantity $randomID " . ( ( is_product( ) and has_term( 'menus', 'product_cat' ) ) ? 'menus' : '' )?>"
		style="<?= ! $productInCart ? 'display:none' : '' ?>"
	>
		<?php do_action( 'woocommerce_before_quantity_input_field' ); ?>

		<label class="screen-reader-text" for="<?= esc_attr( $input_id ); ?>">
			<?= esc_attr( $label ); ?>
		</label>

		<div class="QuantityWrapper center-x">
			<?php if ( is_page( 'cart' ) or is_cart( ) ) : ?>
				<button
					class="Qty QuantityBtn"
					type="button"
					data-qty-action="minus"
					data-qty-id="<?= $randomID ?>"
					data-product-id="<?= $args['product_id'] ?>"
				>
					<span class="dashicons dashicons-minus"></span>
				</button>
				<input
					type="text"
					id="<?= esc_attr( $input_id ); ?>"
					class="Qty QuantityInput <?= esc_attr( join( ' ', (array) $classes ) ); ?>"
					name="<?= esc_attr( $input_name ); ?>"
					value="<?= esc_attr( $input_value ); ?>"
					title="<?= esc_attr_x( 'Qty', 'Product quantity input tooltip', 'woocommerce' ); ?>"
					inputmode="<?= esc_attr( $inputmode ); ?>"
					data-qty-input="<?= $randomID ?>"
				/>
				<button
					class="Qty QuantityBtn"
					type="button"
					data-qty-action="plus"
					data-qty-id="<?= $randomID ?>"
					data-product-id="<?= $args['product_id'] ?>"
				>
					<span class="dashicons dashicons-plus-alt2"></span>
				</button>
			<?php else : ?>
				<button
					class="center-x-y ajax_add_to_cart SubtractQuantityToCart"
					type="button"
					data-qty-action="minus"
					data-qty-id="<?= $randomID ?>"
					data-product_id="<?= $product->get_id(); ?>"
				>
					<span class="dashicons dashicons-minus"></span>
				</button>
				<input
					type="text"
					id="<?= esc_attr( $input_id ); ?>"
					class="Qty InputQuantity <?= esc_attr( join( ' ', (array) $classes ) ); ?>"
					name="<?= esc_attr( $input_name ); ?>"
					value="<?= esc_attr( Invbit\Core\CustomizeWooCommerce::getCartItemQuantity( $product ) ); ?>"
					title="<?= esc_attr_x( 'Qty', 'Product quantity input tooltip', 'woocommerce' ); ?>"
					inputmode="<?= esc_attr( $inputmode ); ?>"
					data-qty-input="<?= $randomID ?>"
					disabled
				/>
				<button
					class="center-x-y ajax_add_to_cart AddQuantityToCart"
					type="button"
					data-qty-action="plus"
					data-qty-id="<?= $randomID ?>"
					data-product_id="<?= $product->get_id(); ?>"
				>
					<span class="dashicons dashicons-plus-alt2"></span>
				</button>
			<?php endif; ?>
		</div>

		<?php do_action( 'woocommerce_after_quantity_input_field' ); ?>

	</div>

<?php endif;
