<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */

use Invbit\Core\Constants;
use Invbit\Core\CustomizeWooCommerce;

defined('ABSPATH') or die('¯\_(ツ)_/¯');

if (post_password_required()) {
	print get_the_password_form(); // WPCS: XSS ok.
	return;
}

global $product;

do_action('woocommerce_before_single_product');

$showButtonForm = false;
?>

<div id="product-<?php the_ID( ); ?>" <?php wc_product_class( '', $product ); ?>>

	<?php /* do_action('woocommerce_before_single_product_summary'); */ ?>

	<header class="SingleProduct__Header">
		<?php the_title( '<h1 itemprop="name" class="SingleProduct__Title">', '</h1>' ); ?>
		<span class="dash">-</span>
		<?php if ($priceHtml = $product->get_price_html()) : ?>
			<span class="price"><?php echo $priceHtml; ?></span>
		<?php endif; ?>
	</header>

	<main class="SingleProduct__Content">
		<?php /* do_action('woocommerce_single_product_summary'); */ ?>
		<?php the_content() ?>

		<!-- Extras -->
		<div class="Extras">
			<?php foreach (CustomizeWooCommerce::$propTypes as $prop => $name) : ?>
				<?php
				if ($prop === 'ingredients') {
					// Quick fix to set the correct $prop name
					$prop = "custom_$prop";
				}
				$hasExtras = get_field("has_$prop");
				$extras    = get_field($prop);

				if (!$hasExtras || !$extras) continue;
				$showButtonForm = true;
				?>

				<h3 class="Title"><?= $name ?>:</h3>
				<div class="Options">
					<?php foreach ($extras as $index => $extra) : ?>
						<input
							id="<?= "{$prop}_{$index}" ?>"
							name="<?= $prop ?>"
							data-price="<?= $extra['price'] ?>"
							data-spiciness="<?= $extra['spiciness'] ?>"
							data-required="<?= get_field("required_$prop") ? 'true' : 'false' ?>"
							type="<?= in_array($prop, ['sauces', 'fries']) ? 'radio' : 'checkbox' ?>"
							value="<?= $extra['name'] ?>"
							<?= $extra['selected'] ? 'checked' : '' ?>
						>
						<label class="Option" for="<?= "{$prop}_{$index}" ?>">
							<span class="Name">
								<?= $extra['name'] ?>
								<?php if ($extra['spiciness'] > 0) : ?>
									<?= str_repeat(Constants::$spicyIcon, $extra['spiciness']) ?>
								<?php endif; ?>
							</span>
							<span class="Price">
								<?= ($extra['price'] === '0')
									? '0€' : (
										$extra['price'] ? wc_price($extra['price']) : '&nbsp;'
									) ?>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</main>
	<footer class="SingleProduct__Footer">
		<?php if ($showButtonForm) : ?>
			<button
				type="submit"
				name="add-to-cart"
				data-product_id="<?= $product->get_id() ?>"
				data-qty="1"
				value="<?= $product->get_id() ?>"
				class="addCustomProductToCart button alt"
			><?= $product->add_to_cart_text() ?></button>
		<?php else : ?>
			<?php woocommerce_template_single_add_to_cart() ?>
		<?php endif; ?>
	</footer>

	<?php /* do_action('woocommerce_after_single_product_summary'); */ ?>
</div>

<?php do_action('woocommerce_after_single_product'); ?>