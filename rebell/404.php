<?php

/**
 * 404 Page.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

$translate['404-title']    = mfn_opts_get( 'translate' ) ? mfn_opts_get( 'translate-404-title', 'Ooops... Error 404' ) : __( 'Ooops... Error 404', 'betheme' );
$translate['404-subtitle'] = mfn_opts_get( 'translate' ) ? mfn_opts_get( 'translate-404-subtitle', 'We are sorry, but the page you are looking for does not exist' ) : __( 'We are sorry, but the page you are looking for does not exist', 'betheme' );
$translate['404-text']     = mfn_opts_get( 'translate' ) ? mfn_opts_get( 'translate-404-text', 'Please check entered address and try again or' ) : __( 'Please check entered address and try again or ', 'betheme' );
$translate['404-btn']      = mfn_opts_get( 'translate' ) ? mfn_opts_get( 'translate-404-btn', 'go to homepage' ) : __( 'go to homepage', 'betheme' ); ?>

<!DOCTYPE html>
<html <?php language_attributes( ); ?> class="no-js<?= esc_attr( mfn_user_os( ) ); ?>">
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>" />
        <?php wp_head( ); ?>
    </head>

    <?php
        $customID = mfn_opts_get( 'error404-page' );
        $body_class = '';
        if ( $customID ) {
            $body_class .= 'custom-404';
        }
    ?>

    <body <?php body_class( $body_class ); ?>>

        <?php if ( $customID ) : ?>

            <div id="Content">
                <div class="content_wrapper clearfix">

                    <div class="sections_group">
                        <?php ( new Mfn_Builder_Front( $customID, true ) )->show( ); ?>
                    </div>

                    <?php get_sidebar( ); ?>

                </div>
            </div>

        <?php else : ?>

            <div id="Error_404" style="width:100%">
                <div class="container">
                    <div class="column one">

                        <div class="error_pic">
                            <i class="<?= esc_attr( mfn_opts_get( 'error404-icon', 'icon-traffic-cone' ) ); ?>"></i>
                        </div>

                        <div class="error_desk">
                            <h2>
                                <?= esc_html( $translate['404-title'] ); ?>
                            </h2>
                            <h4>
                                <?= esc_html( $translate['404-subtitle'] ); ?>
                            </h4>
                            <p>
                                <span class="check"><?= wp_kses_post( $translate['404-text'] ); ?></span> 
                                <a class="btn" href="<?= esc_url( site_url( ) ); ?>">
                                    <?= esc_html( $translate['404-btn'] ); ?>
                                </a>
                            </p>
                        </div>

                    </div>
                </div>
            </div>

        <?php endif; ?>

        <?php wp_footer( ); ?>

    </body>

</html>
