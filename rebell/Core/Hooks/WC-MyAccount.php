<?php namespace Invbit\Core;

/**
 * Customize WC via hooks.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\CustomizeWCMyAccount' ) ) {

    class CustomizeWCMyAccount {

        const COUPONS_PAGE_ID  = IS_DEV_ENV ? 7943 : 12191;
        const ALLERGEN_PAGE_ID = IS_DEV_ENV ? 7941 : 12189;
        const CONTACT_PAGE_ID  = IS_DEV_ENV ?  661 : 12191;

        private static $singleton;

        /**
         * Singleton
         *
         * @static array     $singleton
         * @return instance  The one true instance.
         */
        public static function getInstance( ) {

            if ( !isset( self::$singleton ) ) self::$singleton = new self;

            return self::$singleton;

        }

        /**
         * Constructor
         */
        public function __construct( ) {

            add_action( 'init', [ $this, 'addMyAccountEndpoints' ] );
            add_action( 'query_vars', [ $this, 'addEndpointsQueryVars' ] );
            add_action( 'woocommerce_account_menu_items', [ $this, 'customizeEndpointsTabNames' ] );
            add_action( 'woocommerce_account_cupones_endpoint', [ $this, 'addCouponsEndpointContent' ] );
            add_action( 'woocommerce_account_alergenos_endpoint', [ $this, 'addAllergensEndpointContent' ] );
            add_action( 'woocommerce_account_contacto_endpoint', [ $this, 'addContactEndpointContent' ] );

        }


        public function addMyAccountEndpoints( ) {

            add_rewrite_endpoint( 'cupones',   EP_ROOT | EP_PAGES );
            add_rewrite_endpoint( 'alergenos', EP_ROOT | EP_PAGES );
            add_rewrite_endpoint( 'contacto',  EP_ROOT | EP_PAGES );

        }

        public function addEndpointsQueryVars( $vars ) {

            $vars[ ] = 'cupones';
            $vars[ ] = 'alergenos';
            $vars[ ] = 'contacto';

            return $vars;

        }

        public function customizeEndpointsTabNames( $items ) {

            unset( $items );

            $items[ 'dashboard' ]       = __( 'Mi Perfil', 'betheme' );
            $items[ 'orders' ]          = __( 'Mis Pedidos', 'betheme' );
            $items[ 'cupones' ]         = __( 'Cupones', 'betheme' );
            $items[ 'alergenos' ]       = __( 'Alérgenos', 'betheme' );
            $items[ 'contacto' ]        = __( 'Contacto', 'betheme' );
            $items[ 'customer-logout' ] = __( 'Cerrar sesión', 'betheme' );

            if ( ! is_user_logged_in( ) ) {
                unset( $items[ 'orders' ], $items[ 'cupones' ], $items[ 'customer-logout' ] );
            }

            return $items;

        }

        public function assignPage( $slug, $pageID ) { ?>

            <section class="MyAccount <?= esc_attr( $slug ) ?>">
                <?php ( new \Mfn_Builder_Front( $pageID, true ) )->show( ); ?>
            </section>

        <?php }

        public function addCouponsEndpointContent( ) {

            $this->assignPage( 'cupones', self::COUPONS_PAGE_ID );

        }

        public function addAllergensEndpointContent( ) {

            $this->assignPage( 'alergenos', self::ALLERGEN_PAGE_ID );

        }

        public function addContactEndpointContent( ) {

            $this->assignPage( 'contacto', self::CONTACT_PAGE_ID );

        }

    }
        
}

CustomizeWCMyAccount::getInstance( );