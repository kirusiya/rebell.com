<?php namespace Invbit\Core;

/**
 * WC Checkout Controller.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\WCCheckoutController' ) ) {

    class WCCheckoutController {

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
            
            add_action( 'woocommerce_cart_needs_shipping_address', '__return_false', 10 );

            add_filter( 'wc_address_book_address_select_label', [ $this, 'customizeAddressBookSelector' ], 10, 3 );
            add_filter( 'woocommerce_checkout_fields', [ $this, 'removeCheckoutFields' ] );
            add_action( 'woocommerce_checkout_after_customer_details', 'woocommerce_checkout_payment', 20 );
            add_filter( 'woocommerce_checkout_posted_data', [ $this, 'handleCheckoutPostedData' ] );
            add_action( 'woocommerce_after_checkout_validation', [ $this, 'handleAfterCheckoutValidation' ], 10, 2 );
            add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'updateOrderMeta' ], 10, 2 );
            add_action( 'woocommerce_before_checkout_form', [ $this, 'checkCustomerAddress' ], 10, 1 );

            remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
            remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );

            $this->showAddressBookForSomeRolesOnly( );

        }

        /**
         * Customize the Address Book selector data.
         */
        public function customizeAddressBookSelector( $label, $address, $name ) {

            if ( ! empty( $address[ $name . '_phone' ] ) ) {
                $label .= '. Tel.: ' . $address[ $name . '_phone' ];
            }
            return $label;

        }

        /**
         * Remove some checkout fields.
         */
        public function removeCheckoutFields( $fields ) {

            $fields['billing']['billing_postcode']['custom_attributes'] = ['readonly' => 'readonly'];

            unset( $fields['billing']['billing_last_name'] );
            unset( $fields['billing']['billing_company'] );
            unset( $fields['billing']['billing_country'] );
            unset( $fields['billing']['billing_address_2'] );
            unset( $fields['billing']['billing_state'] );
            unset( $fields['billing']['billing_email'] );
            unset( $fields['billing']['billing_mobile_phone'] );
            unset( $fields['order']['order_comments'] );

            return $fields;

        }

        /**
         *  Handle the posted data from the checkout.
         */
        public function handleCheckoutPostedData( $data ) {

            $commonFields = ['first_name', 'address_1', 'postcode', 'city', 'phone', 'address_book'];

            foreach ($commonFields as $field) {
                $data["shipping_$field"] = $_POST["billing_$field"];
            }

            return $data;

        }

        /**
         *  Handle validations after the checkout has been submitted.
         */
        public function handleAfterCheckoutValidation( $data, $errors ) {

            try {
                Helpers::getKitchenByZipcode( ZipcodeController::getCustomerZipcode( ) );
            } catch( \Exception $e ) {
                $errors->add(
                    'kitchen',
                    __( 'No tienes una cocina asignada. Revisa tu código postal.', 'betheme' )
                );
            }

            return $data;

        }

        /**
         *  Update the new order meta.
         */
        public function updateOrderMeta( $orderID, $data ) {

            $order = wc_get_order( $orderID );

            foreach ( $order->get_items( ) as $item ) {
                foreach ( WC( )->cart->get_cart( ) as $key => $cartItem ) {
                    if ( $item->get_product_id( ) == $cartItem['product_id'] ) {
                        wc_add_order_item_meta( $item->get_id( ), 'baseprice', $cartItem['base_price'] );

                        foreach ( array_keys( CustomizeWooCommerce::$propTypes ) as $type ) {
                            if ( empty( $cartItem[ $type ] ) ) continue;
                            wc_add_order_item_meta( $item->get_id( ), $type, $cartItem[ $type ] );    
                        }
                    }
                }
        
                $item->save( );
            }

            // Set up delivery and schedule data.
            $order->update_meta_data( '_is_delivery', WC( )->session->get( 'order_type' ) === 'delivery' );
            $order->update_meta_data( '_scheduled_for', WC( )->session->get( 'scheduled_for' ) );

            $order->save( );

            // Set up kitchen for order.
            try {
                $kitchen = Helpers::getKitchenByZipcode( ZipcodeController::getCustomerZipcode( ) );
            } catch( \Exception $e ) {
                exit;
            }

            update_field( 'kitchen_for_order', $kitchen['kitchen_id'], $orderID );

            if ( $kitchen['takeaway_address'] !== '' ) {
                $order->update_meta_data( 'pickupAddress', $kitchen['takeaway_address'] );
            }

            // Set up invoice number.
            $invoicePrefix     = Helpers::getKitchenInvoicePrefix( $kitchen['kitchen_id'] );
            $nextInvoiceNumber = Helpers::getKitchensNextInvoiceNumber( $invoicePrefix );
            $order->update_meta_data( 'invoiceNumber', $nextInvoiceNumber );
            $order->update_meta_data( 'invoicePrefix', $invoicePrefix );

            $order->save( );

        }

        /**
         *  Check customer billing and shipping address.
         */
        public function checkCustomerAddress( $checkout ) {

            $customer    = new \WC_Customer( get_current_user_id( ) );
            $spainStates = array_keys( ( new \WC_Countries( ) )->get_states( 'ES' ) );

            if ( wc( )->customer->get_shipping_country( ) !== 'ES' ) {
                $customer->set_billing_country( 'ES' );
                $customer->set_shipping_country( 'ES' );
            }

            // Set default state to Coruña
            if ( ! in_array( wc( )->customer->get_shipping_country( ), $spainStates ) ) {
                $customer->set_billing_state( 'C' );
                $customer->set_shipping_state( 'C' );
            }

            $customer->save( );

        }

        /**
         *  Get shipping cost.
         */
        static function setShippingTotal( ) : void {

            $shippingCost = WC( )->cart->get_shipping_total( );

            foreach ( \WC_Tax::get_shipping_tax_rates( ) ?? [ ] as $taxRate ) {
                $percentile = $taxRate['rate'] / 100;
            
                $shippingCost -= $shippingCost * $percentile;
            }
            
            WC( )->cart->set_shipping_total( $shippingCost );
            WC( )->cart->set_total( WC( )->cart->get_cart_contents_total( ) + WC( )->cart->get_taxes_total( ) + $shippingCost );

        }

        /**
         * Show the Address Book selector at the checkout for some user roles only.
         */
        private function showAddressBookForSomeRolesOnly( ) {

            global $wp_filter;

            if ( ! is_user_logged_in( ) || !! array_intersect(['kitchen_manager', 'administrator'], wp_get_current_user()->roles ) ) {
                return;
            }

            $callbacks = $wp_filter[ 'woocommerce_checkout_fields' ]->callbacks;

            $checkoutAddressSelectField = array_filter($callbacks, function( $hooks ) {
                $foundHook = array_filter( $hooks, function( $hook ) {
                    return is_array( $hook[ 'function' ] ) && in_array( 'checkout_address_select_field', $hook[ 'function' ] );
                } );

                return count( $foundHook ) > 0;
            } );

            if ( $checkoutAddressSelectField ) {
                $priority = reset( array_keys( $checkoutAddressSelectField ) );
                $checkoutAddressSelectField = reset( array_keys( reset( $checkoutAddressSelectField ) ) );

                remove_filter( 'woocommerce_checkout_fields', $checkoutAddressSelectField, $priority, 1 );
            }

        }

    }
        
}

WCCheckoutController::getInstance( );