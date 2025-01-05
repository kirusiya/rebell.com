<?php

/**
 * Header top area.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

use Invbit\Core\Helpers;
use Invbit\Core\Constants;

global $woocommerce;

$action_bar = mfn_opts_get('action-bar');

$translate['wpml-no'] = mfn_opts_get('translate') ? mfn_opts_get('translate-wpml-no', 'No translations available for this page') : __('No translations available for this page', 'betheme');

if (('1' === $action_bar) || isset($action_bar['show'])) : ?>
    <div id="Action_bar">
        <div class="container">
            <div class="column one">

                <?php
                get_template_part('includes/include', 'slogan');

                if (has_nav_menu('social-menu')) {
                    mfn_wp_social_menu();
                } else {
                    get_template_part('includes/include', 'social');
                }
                ?>

            </div>
        </div>
    </div>
<?php endif;

if (mfn_header_style(true) == 'header-overlay') : ?>
    <div id="Overlay">
        <?php mfn_wp_overlay_menu(); ?>
    </div>

    <a class="overlay-menu-toggle" href="#">
        <i class="open icon-menu-fine"></i>
        <i class="close icon-cancel-fine"></i>
    </a>
<?php endif; ?>

<!-- .header_placeholder 4sticky  -->
<div class="header_placeholder"></div>

<div id="Top_bar" class="loading">
    <div class="container">
        <div class="column one">
            <div class="top_bar_left clearfix">
                <!-- Logo -->
                <div class="TopHeader">
                    <?php get_template_part('includes/include', 'logo'); ?>
                     <nav id="TopMenu">
                            <?php wp_nav_menu( $args ); ?>
                        </nav>
					 <a id="header_cart" href="<?= esc_url(wc_get_cart_url()) ?>">
                        <i class="shopping-cart-icon"></i>
                        <span><?= esc_html($woocommerce->cart->cart_contents_count) ?></span>
					
                    </a>
                </div>

                <div class="HeaderMenu menu_wrapper">
                    <?php
                    if ((mfn_header_style(true) != 'header-overlay') && (mfn_opts_get('menu-style') != 'hide')) {
                        // main menu
                        // if (in_array(mfn_header_style(), array('header-split', 'header-split header-semi', 'header-below header-split'))) {
                        //     mfn_wp_split_menu();
                        // } else {
                        //     mfn_wp_nav_menu();
                        // }

                        $args = [
                            'container'		=> false,
                            'menu_class'	=> 'menu menu-main', 
                            'link_before'	=> '<span>',
                            'link_after'	=> '</span>',
                            'depth' 		=> 5,
                            'fallback_cb'	=> 'mfn_wp_page_menu',
                        ];
                        
                        // Mega Menu | Custom Walker
                        $theme_disable = mfn_opts_get( 'theme-disable' );
                        if( ! isset( $theme_disable[ 'mega-menu' ] ) ){
                            $args['walker'] = new Walker_Nav_Menu_Mfn;
                        }
                        
                        // Custom Menu
                        if( mfn_ID() && is_single() && get_post_type() == 'post' && $custom_menu = mfn_opts_get( 'blog-single-menu' ) ) {
                            $args['menu'] = $custom_menu; // Theme Options | Single Posts
                        } elseif( mfn_ID() && is_single() && get_post_type() == 'portfolio' && $custom_menu = mfn_opts_get( 'portfolio-single-menu' ) ) {
                            $args['menu'] = $custom_menu; // Theme Options | Single Portfolio
                        } elseif( $custom_menu = get_post_meta( mfn_ID(), 'mfn-post-menu', true ) ) {
                            $args['menu'] = $custom_menu; // Page Options | Page
                        } else {
                            $args['theme_location'] = 'main-menu'; // Default
                        } ?>
                    
                        

                        <?php if ( is_account_page( ) ) {
                            do_action( 'woocommerce_account_navigation' );
                        } ?>

                        <?php if ( is_product_category( ) and ! is_product_category( Constants::$MENU_CAT_ID ) ) :  ?>
                            <?php $cats = Helpers::getMenuCategories( ); ?>
                            <?php if ( count( $cats ) > 0 ) : ?>
                            <nav class="woocommerce-ProductCat-navigation">
	                            <ul>
                                    <?php foreach ( $cats as $cat ) : ?>
		                            <li class="<?= is_product_category( $cat->term_id ) ? 'is-active' : '' ?>">
                                        <a href="<?= get_category_link( $cat->term_id ) ?>"><?= $cat->name ?></a>
                                    </li>
                                    <?php endforeach; ?>
                                    <?php
                                    if(!is_user_logged_in()){
                                        ?>
                                        <a href="javascript:void(0);" class="userLogin topHeaderLogin"><?php echo __( 'Iniciar Sesión', 'rebell' ) ?></a>
                                        <?php
                                    }else{

                                        ?>
                                        <a href="/mi-cuenta" class="topHeaderLogin"><?php echo __( 'Mi cuenta', 'rebell' ) ?></a>
                                        <?php
                                    }
                                    ?>        


                                    
			                    </ul>
                                            
                                
                                
                            </nav>
                            <?php endif;  ?>
                        <?php endif;  ?>

                        <?php
                        // responsive menu button
                        // $mb_class = '';
                        // if (mfn_opts_get('header-menu-mobile-sticky')) {
                        //     $mb_class .= ' is-sticky';
                        // }

                        // echo '<a class="responsive-menu-toggle ' . esc_attr($mb_class) . '" href="#">';
                        // if ($menu_text = trim(mfn_opts_get('header-menu-text'))) {
                        //     echo '<span>' . wp_kses($menu_text, mfn_allowed_html()) . '</span>';
                        // } else {
                        //     echo '<i class="icon-menu-fine"></i>';
                        // }
                        // echo '</a>';
                    }
                    ?>
                </div>

                <div class="secondary_menu_wrapper">
                    <!-- #secondary-menu -->
                    <?php mfn_wp_secondary_menu(); ?>
                </div>

                <div class="banner_wrapper">
                    <?php echo wp_kses_post(mfn_opts_get('header-banner')); ?>
                </div>

                <div class="search_wrapper">
                    <!-- #searchform -->
                    <?php get_search_form(true); ?>
                </div>

            </div>

            <!-- <?php if (!mfn_opts_get('top-bar-right-hide')) {
                get_template_part('includes/header', 'top-bar-right');
            } ?> -->
        </div>
    </div>
</div>