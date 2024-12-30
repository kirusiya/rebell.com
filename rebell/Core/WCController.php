<?php namespace Invbit\Core;

/**
 * Customize WC.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\WCController' ) ) {

    class WCController {

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
            add_action( 'wp_ajax_apply_my_coupon', [ $this, 'applyMyCouponHandler' ] );
            add_action( 'wp_ajax_nopriv_apply_my_coupon', 'auth_redirect' );

            // Allow only one coupon per cart.
            add_filter( 'woocommerce_apply_with_individual_use_coupon', '__return_true' );
            add_filter( 'woocommerce_coupon_get_individual_use', '__return_true' );
        }

        public function applyMyCouponHandler( ) {
            check_ajax_referer( 'apply-coupon', 'security' );

            if ( ! empty( $_POST[ 'coupon_code' ] ) ) {
                WC( )->cart->add_discount( $_POST[ 'coupon_code' ] );
            } else {
                wc_add_notice( \WC_Coupon::get_generic_coupon_error( \WC_Coupon::E_WC_COUPON_PLEASE_ENTER ), 'error' );
            }
    
            wc_print_notices( );
            wp_die( );
        }

    }

}

WCController::getInstance( );
