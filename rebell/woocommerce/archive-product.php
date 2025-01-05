<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @package 	WooCommerce/Templates
 * @version 	3.4.0
 */

use Invbit\Core\Helpers;
use Invbit\Core\Constants;
use Invbit\Core\ZipcodeController;
use Invbit\Core\ProductCatController;

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

$_CURRENT_CAT_ID = get_queried_object( )->term_id ?? null;

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' ); ?>

<header class="woocommerce-products-header">
    <h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
</header>

<?php if ( ! is_front_page( ) ) {
	if ( have_posts( ) ) {
		// The current archive category is the main parent menu.
		if ( $_CURRENT_CAT_ID === Constants::$MENU_CAT_ID ) {
			$zipcode = ZipcodeController::getCustomerZipcode( );

			try {
				$kitchen = Helpers::getKitchenByZipcode( $zipcode );
				$kitchenID = $kitchen['kitchen_id'];
			} catch ( \Exception $e ) { }

			if ( empty( $kitchenID ) ) {
				print do_shortcode(
					'[alert style="error"]Error: No se ha especificado ningún código postal[/alert]'
				);
			} else {
				$categories = array_map( function( $_cat ) {
					return get_term( $_cat->id, 'product_cat' );
				}, ProductCatController::getSubCategoriesForKitchen( Constants::$MENU_CAT_ID, $kitchenID ) );
		
				print '<div class="products_wrapper isotope_wrapper">';
					print '<ul class="products grid">';
					foreach ( $categories as $_cat ) {
						wc_get_template( 'content-product_cat.php', [ 'category' => $_cat ] );
					}
					print '</ul>';
				print '</div>';
			}
		} else {
			// print '<div class="shop-filters">';
			// 	do_action( 'woocommerce_before_shop_loop' );
			// print '</div>';

			woocommerce_product_loop_start( );
		
			while ( have_posts( ) ) {
				the_post( );
		
				do_action( 'woocommerce_shop_loop' );
		
				wc_get_template_part( 'content', 'product' );
			}
		
			woocommerce_product_loop_end( );
		
			do_action( 'woocommerce_after_shop_loop' );
		}
	} else {
		do_action( 'woocommerce_no_products_found' );
	}
} ?>

<?php

$claseLogin = is_user_logged_in() ? '' : 'userLoginProd';

// Mostrar el carrito
echo '<div class="row-cart ' . esc_attr($claseLogin) . '">';
echo '<a id="header_cart"  href="' . esc_url(wc_get_cart_url()) . '">';
echo '<span>' . esc_html(WC()->cart->get_cart_contents_count()) . '</span>';
echo '</a>';
echo '</div>';




// Código para el botón de "Ir al Carrito"
if (WC()->cart->get_cart_contents_count() > 0) { // Verificamos si hay productos en el carrito
    echo '<div class="custom-cart-button" style="text-align: center; margin: 20px 0;">';
    echo '<a href="' . esc_url(wc_get_cart_url()) . '" class="button alt ' . esc_attr($claseLogin) . '" id="custom_cart_button">Finalizar compra</a>';
    echo '</div>';
}else{

?>
<style>
	.Rebell.woocommerce.tax-product_cat .products.grid {
		margin-bottom: 100px;
	}
</style>

<?php


}
?>

<?php do_action( 'woocommerce_after_main_content' );

do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );
