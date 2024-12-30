<?php namespace Invbit\Core;

/**
 * WC Cart Controller.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\WCCartController' ) ) {

    class WCCartController {

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

            add_action( 'woocommerce_cart_contents',    [ $this, 'showCartMinimumRequired' ] );
            add_action( 'woocommerce_check_cart_items', [ $this, 'handleCheckoutValidations' ], 10, 0 );
            add_action( 'template_redirect', [ $this, 'emptyCart'] );
            add_action( 'template_redirect', [ $this, 'emptyCoupon'] );
            add_action( 'wp_ajax_rebell_woocommerce_ajax_add_to_cart',        [$this, 'ajaxAddToCart']);
            add_action( 'wp_ajax_nopriv_rebell_woocommerce_ajax_add_to_cart', [$this, 'ajaxAddToCart']);
            add_action( 'wp_ajax_rebell_set_order_type_and_schedule',        [$this, 'setOrderTypeAndSchedule']);
            add_action( 'wp_ajax_nopriv_rebell_set_order_type_and_schedule', [$this, 'setOrderTypeAndSchedule']);
            add_action( 'woocommerce_before_calculate_totals', [ $this, 'handleCartBeforeCalculatedTotals' ] );
            
            remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10 );
            remove_action( 'woocommerce_cart_is_empty', 'mfn_wc_empty_cart_message', 10 );
            
            add_filter( 'woocommerce_quantity_input_args', [ $this, 'qtyInputArgs'], 10, 2 );
            add_filter( 'wc_shipping_enabled', [ $this, 'handleShippingEnabled' ] );

        }

        /**
         * Show cart's minimum required amount to place an order.
         */
        public function showCartMinimumRequired( ) {

            $minRequired = $this->getMinimumCartAmount( );
            $coupons     = new \WC_Coupon( WC( )->cart->get_applied_coupons( ) );
            $subtotal    = WC( )->cart->subtotal - $coupons->amount;

            if ( $subtotal <= $minRequired ) {
                $message = __( '<div class="CartAlert alert" data-missing="%s"><div><h4>¡Falta poco!</h4> Te faltan <strong>%s</strong> para llegar al pedido mínimo.</div></div>', 'betheme' );

                $missing = $minRequired - $subtotal;
                printf( $message, number_format( $missing, 2 ), wc_price( $missing ) );
            }
        }

        /**
         * Check for minimum required amount before checkout.
         */
        public function handleCheckoutValidations( ) {

            if ( ! is_checkout( ) ) {
                return;
            }

            $minRequired  = $this->getMinimumCartAmount( );
            $orderType    = WC( )->session->get( 'order_type');
            $scheduledFor = WC( )->session->get( 'scheduled_for');
            $coupons      = new \WC_Coupon( WC( )->cart->get_applied_coupons( ) );
            $subtotal     = WC( )->cart->subtotal - $coupons->amount;

            if ( $subtotal <= $minRequired ) {
                $error = sprintf(
                    __( 'Te faltan <strong>%s</strong> para llegar al pedido mínimo.', 'betheme' ),
                    $minRequired - WC( )->cart->total
                );
            } else if ( ! $orderType || ! $scheduledFor ) {
                $error = __( 'Debes especificar si quieres recibir tu pedido a domicilio o recogerlo en local y a qué hora.', 'betheme' );
            }

            if ( $error ?? null ) {
                wc_add_notice( $error, 'error' );
            }

        }

        /**
         * Empty cart.
         */
        public function emptyCart( ) {

            if ( WC( )->cart && wp_verify_nonce( $_GET[ 'empty-cart' ], 'empty-cart' ) ) {
                WC( )->cart->empty_cart( );
                wp_redirect( wc_get_cart_url( ) );
                exit;
            }

        }

        /**
         * Remove the applied coupon.
         */
        public function emptyCoupon( ) {

            if ( WC( )->cart && wp_verify_nonce( $_GET[ 'security' ], 'empty_coupon' ) ) {
                WC( )->cart->remove_coupon( wc_format_coupon_code( urldecode( wp_unslash( $_GET['empty_coupon'] ) ) ) );
                wp_redirect( wc_get_cart_url( ) );
                exit;
            }

        }

        /**
         * Quantity input args.
         */
        public function qtyInputArgs( $args, $product ) {

            $args['product_id'] = $product->get_id( );

            return $args;

        }

        /**
         * Handle the shipping enabled.
         */
        public function handleShippingEnabled( $enabled ) {

            if( ! isset( WC( )->cart ) ) {
                return $enabled;
            }

            foreach ( WC( )->cart->get_applied_coupons( ) as $coupon ) {
                $coupon = new \WC_Coupon( $coupon );
                
                if( $coupon->get_free_shipping( ) ) {
                    return false;
                }
            }

            if ( WC( )->session->get( 'order_type' ) && WC( )->session->get( 'order_type' ) === 'takeaway' ) {
                return false;
            }

            return $enabled;

        }

        /**
         * Add to cart from ajax.
         */
        public function ajaxAddToCart( ) {

            $wcAjax    = new \WC_AJAX( );
            $productID = apply_filters( 'rebell_woocommerce_add_to_cart_product_id', absint( $_POST['product_id'] ) );
            $quantity  = empty( $_POST['quantity'] ) ? 0 : wc_stock_amount( $_POST['quantity'] );

            $validated = apply_filters( 'rebell_woocommerce_add_to_cart_validation', true, $productID, $quantity );
            $status    = get_post_status( $productID );
            $product   = wc_get_product( $productID );

            if ( !$validated || $status !== 'publish' ) {
                wp_send_json( [
                    'error'       => true,
                    'product_url' => apply_filters( 'rebell_woocommerce_cart_redirect_after_error', get_permalink( $productID ), $productID )
                ] );
                wp_die( );
            }

            if ( $quantity === 0 && ! empty( $_POST['cart_item_id'] ) ) {
                WC( )->cart->remove_cart_item( $_POST['cart_item_id'] );

                $wcAjax->get_refreshed_fragments( );

                wp_die( );
            }

            if ( ! empty( $_POST['cart_item_id'] ) ) {
                foreach ( WC( )->cart->get_cart( ) as $cartItemKey => $cartItem ) {
                    if ( $cartItemKey == $_POST['cart_item_id'] ) {
                        WC( )->cart->set_quantity( $cartItemKey, $quantity );
                    }
                }
            } else {
                $customizable = $product->get_meta( 'is_customizable' );
                if ( ! $customizable ) {
                    foreach ( WC( )->cart->get_cart( ) as $cartItemKey => $cartItem ) {
                        if ( $cartItem['product_id'] == $productID ) {
                            WC( )->cart->set_quantity( $cartItemKey, $quantity );
                        }
                    }
                } else {
                    WC( )->cart->add_to_cart( $productID, $quantity, 0, [ ], $_POST['extras'] ?? [ ] );
                }
            }

            $wcAjax->get_refreshed_fragments( );

            wp_die( );

        }

        /**
         * Set the order type and order schedule from ajax.
         */
        public function setOrderTypeAndSchedule( ) {
            
            $orderType    = $_POST['order_type'] ?? null;
            $scheduledFor = $_POST['scheduled_for'] ?? null;

            if ( ! $orderType || ! $scheduledFor ) {
                wp_send_json_error( [
                    'message' => 'Debes seleccionar el tipo de pedido (domicilio/local) y una hora estimada'
                ], 400 );
                wp_die( );
            }

            WC( )->session->set( 'order_type', $orderType );
            WC( )->session->set( 'scheduled_for', $scheduledFor );

            wp_die( );

        }

        /**
         * Handle cart before totals have been calculated.
         */
        public function handleCartBeforeCalculatedTotals( $cart ) {

            $cartItems = array_map( function( $cartItem ) {
                $extras = array_intersect_key( $cartItem, CustomizeWooCommerce::$propTypes );

                $extraCost = 0;
                foreach ( $extras as $key => $props ) {
                    foreach ( $props as $data ) {
                        $extraCost += $data['price'];
                    }
                }

                $price = $cartItem[ 'data' ]->get_price( );
                $cartItem[ 'data' ]->set_price( $price + $extraCost );
                $cartItem[ 'base_price' ] = $price;

                return $cartItem;
            }, $cart->get_cart( ) );

            $cart->set_cart_contents( $cartItems );

        }

        /**
         * Calculate the minimum amount required to place an order.
         */
        private function getMinimumCartAmount( ) {

            try {
                $customerZipCode  = ZipcodeController::getCustomerZipcode( );
                $customerKitchen  = Helpers::getKitchenByZipcode( $customerZipCode );
            } catch( \Exception $e ) {
                wc_add_notice($e->getMessage( ));
                // $redirect = '/codigo-postal';
                // exit( wp_redirect( $redirect ) );
            }

            $minOrderRequired = $customerKitchen[ 'kitchen_min_order' ];

            return $minOrderRequired;

        }




        /**
         * Get the list of available times filtered by type of order.
         */
        public static function getSchedule( $schedule, $type ) {
            if ( count( $schedule['morning']['data'] ) <= 0 && count( $schedule['afternoon']['data'] ) <= 0 ) {
                return [ ];
            }

            return $type === 'takeaway'
                ? array_merge( self::_formatTakeawaySchedule( $schedule['morning'] ), self::_formatTakeawaySchedule( $schedule['afternoon'] ) )
                : array_merge( self::_formatDeliverySchedule( $schedule['morning'] ), self::_formatDeliverySchedule( $schedule['afternoon'] ) );
        }

        /**
         * Format list of available times for takeaway.
         */
        private static function _formatTakeawaySchedule( $schedule ) {
            if ( count( $schedule['data'] ) <= 0 ) {
                return $schedule['data'];
            }

            $lastItem = $schedule['data'][array_key_last( $schedule['data'] )];

            return array_merge( $schedule['data'], [ self::_getScheduleNextTime( $lastItem, $schedule['interval'] ) ] );
        }
    
        /**
         * Format list of available times for delivery.
         */
        private static function _formatDeliverySchedule( $schedule ) {
            return array_map( function( $t, $i ) use( $schedule ) {
                $next = $schedule['data'][$i+1] ?? self::_getScheduleNextTime( $t, $schedule['interval'] );
        
                return "{$t} - {$next}";
            }, $schedule['data'], array_keys( $schedule['data'] ) );
        }

        /**
         * Get the next time item for a given interval.
         */
        private static function _getScheduleNextTime( $time, $interval ) {
            $currentHour = explode( ':', $time );
            $currentDate = new \DateTime( "2000-01-01 {$currentHour[0]}:{$currentHour[1]}:00" );
            $dtDiff      = $currentDate->add( new \DateInterval("PT{$interval}M" ) );
        
            return "{$dtDiff->format( 'H' )}:{$dtDiff->format( 'i' )}";
        }

    }
        
}

WCCartController::getInstance( );