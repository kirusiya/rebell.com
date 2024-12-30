<?php namespace Invbit\Core;

/**
 * My account controller.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */


if ( ! class_exists( __NAMESPACE__ .'\Shortcodes' ) ) {

    class Shortcodes {

        private static $singleton;

        /**
         * Singleton
         *
         * @static array     $singleton
         * @return instance  The one true instance.
         */
        public static function getInstance( ) : self {

            if ( !isset( self::$singleton ) ) self::$singleton = new self;

            return self::$singleton;

        }

        /**
         * Constructor
         */
        public function __construct( ) {

            add_action( 'init', [ $this, 'init' ], 10 );

        }

        /**
         * Constructor
         */
        public function init( ) {

            add_shortcode( 'whatsapp-contact', [ $this, 'renderWhatsappContactForm' ] );

        }

        /**
         * Whatsapp contact form.
         */
        public function renderWhatsappContactForm( $attr, $content = null ) {

            extract( shortcode_atts( [
                'title' => __( 'Contáctanos por Whatsapp', 'betheme' ),
            ], $attr ) );

            $whatsappNumber = get_field( 'whatsapp_number', 'options' );

            if ( is_user_logged_in( ) ) {
                global $woocommerce;

                $kitchen = null;
                $zipcode = $woocommerce->customer->get_shipping_postcode( );

                try {
                    $kitchen = Helpers::getKitchenByZipcode( $zipcode );
                } catch ( \Exception $e ) { }

                if ( $kitchen and !empty( $kitchen['kitchen_whatsapp'] ) ) {
                    $whatsappNumber = $kitchen['kitchen_whatsapp'];
                }
            }

            ob_start( ); ?>

                <section class="Whatsapp">
                    <h3><?= $title ?></h3>

                    <a href="https://wa.me/34<?= $whatsappNumber ?>" title="Whatsapp">
                        <i class="icon-whatsapp"></i>
                    </a>
                </section>

            <?php return ob_get_clean( );

        }

    }

}

Shortcodes::getInstance( );