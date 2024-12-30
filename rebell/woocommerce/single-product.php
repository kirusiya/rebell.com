<?php

/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce/Templates
 * @version     1.6.4
 */

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

$_product     = wc_get_product(get_the_ID());
$_thumbSize   = apply_filters('woocommerce_product_thumbnails_large_size', 'full');
$_thumbID     = $_product->get_image_id();
$_image       = wp_get_attachment_image_src($_thumbID, $_thumbSize);
$_imageHeight = 300;

get_header('shop'); ?>

    <?php if ( isset( $_image[0] ) ) : ?>
        <?php $imageStyles = preg_replace("/;\s+/i", '; ', "
            height:{$_imageHeight}px;
            background: url({$_image[0]}) no-repeat center;
            background-size: cover;
        "); ?>
        <div class="section mcb-section full-width">
            <div class="section_wrapper mcb-section-inner">
                <div class="wrap mcb-wrap mcb-wrap-ncogoxugi one valign-middle bg-cover clearfix"
                    style="<?= $imageStyles ?>">
                    <div class="mcb-wrap-inner"></div>
                </div>
            </div>
        </div>
    <?php endif ?>

	<?php do_action('woocommerce_before_main_content'); ?>

		<?php while (have_posts()) : ?>
			<?php the_post(); ?>

			<?php wc_get_template_part('content', 'single-product'); ?>

		<?php endwhile; ?>

	<?php do_action('woocommerce_after_main_content'); ?>

	<?php do_action('woocommerce_sidebar'); ?>

<?php get_footer('shop');