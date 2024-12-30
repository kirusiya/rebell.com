<?php namespace Invbit\Core;

/**
 * Helper methods.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\Helpers' ) ) {

    class Helpers {

        /**
         *  Get redirect URL.
         */
        static function getRedirectUrl( $default = null ) {

            if ( ! empty( $_POST['redirect'] ) ) {
                return wp_sanitize_redirect( wp_unslash( $_POST['redirect'] ) );
            } elseif ( function_exists( 'wp_get_raw_referer' ) ) {
                return wc_get_raw_referer( );
            } else {
                return $default ?? wc_get_page_permalink( 'myaccount' );
            }

        }

        /**
         *  Validate zip codes.
         */
        static function validZipCode( $zipCode ) {

            return count( self::getShippingZoneByZipCode( $zipCode ) ) > 0;
        
        }


        /**
         *  Get shipping zones by zip code.
         */
        static function getShippingZoneByZipCode( int $zipCode ) : array {

            if ( ( $zipCode = intval( $zipCode ) ) <= 0 ) return [ ];
        
            foreach ( \WC_Shipping_Zones::get_zones( ) as $idx => $zone ) {
                foreach ( $zone[ 'zone_locations' ] as $idx => $location ) {
                    if ( ! strpos( $location->code, '...' ) ) {
                        if ( $zipCode == $location->code ) return $zone;
                    } else {
                        $codes = explode( '...', $location->code );
                        foreach ( range( $codes[ 0 ], $codes[ 1 ] ) as $code ) {
                            if ( $zipCode == $code ) return $zone;
                        }
                    }
                }
            }
        
            return [ ];

        }


        /**
         *  Get shipping cost.
         */
        static function getShippingCost( String $baseCost, bool $taxedPrice = true ) : String {

            $stdTaxRates = \WC_Tax::get_base_tax_rates( );
            $stdTaxRate  = array_pop( $stdTaxRates )[ 'rate' ];
        
            if ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) ) {
                $taxPercent   = get_option( 'woocommerce_shipping_tax_class' ) ?: $stdTaxRate;
                $taxAmount    = ( 1 + ( 1 * ( $taxPercent / 100 ) ) );
                $shippingCost = $taxedPrice ? $baseCost * $taxAmount : $baseCost / $taxAmount;
            } else {
                $shippingCost = $baseCost;
            }
        
            return $shippingCost;

        }

        /**
         *  Get the order's type (delivery or takeaway) and schedule.
         */
        static function getOrderTypeAndSchedule( \WC_Order $order ) : Array {

            $isDelivery = true;
            $scheduledFor = null;
            foreach ( $order->get_meta_data( ) as $key => $value ) {
                if ( $value->get_data( )[ 'key' ] == '_is_delivery' )
                    $isDelivery = (bool) $value->get_data( )[ 'value' ];
                if ( $value->get_data( )[ 'key' ] == '_scheduled_for' )
                    $scheduledFor = $value->get_data( )[ 'value' ];
            }

            return compact( 'isDelivery', 'scheduledFor' );

        }

        /**
         *  Get all kitchens data.
         */
        static function getAllKitchens( ) {

            $kitchens = [ ];
            foreach ( get_field( 'kitchens', 'options' ) as $kitchen ) {
                $kitchens[ ] = [
                    'id'          => $kitchen['kitchen_id'],
                    'name'        => $kitchen['kitchen_name'],
                    'address'     => $kitchen['takeaway_address'],
                    'description' => $kitchen['kitchen_description'] ?? '',
                ];
            }

            return $kitchens;

        }

        /**
         *  Get the kitchen data from a given ID.
         */
        static function getKitchenByID( string $kitchenID ) : ?Array {

            $kitchens = (array) get_field( 'kitchens', 'options' );
            $foundKitchenKey = array_search( $kitchenID, array_column( $kitchens, 'kitchen_id' ) );
            
            if ( $foundKitchenKey === false ) {
                return null;
            }

            return $kitchens[ $foundKitchenKey ] ?? null;

        }

        /**
         *  Get the kitchen by the given Zipcode.
         */
        static function getKitchenByZipcode( string $zipCode ) : ?array {

            $errorMessage = sprintf( __( 'El código postal %s no dispone de servicio.', 'betheme' ), $zipCode );

            if ( ! $zipCode or ! is_numeric( $zipCode ) ) {
                throw new \Exception( $errorMessage );
            }

            $shippingZone = Helpers::getShippingZoneByZipCode( $zipCode );

            foreach ( get_field( 'kitchens', 'options' ) as $kitchen ) {
                if ( in_array( $shippingZone['id'], $kitchen['kitchen_shipping_zones'] ) ) {
                    $foundKitchen = $kitchen;
                    break;
                }
            }

            if ( isset( $foundKitchen ) ) {
                return $foundKitchen;
            }

            throw new \Exception( $errorMessage );

        }

        /**
         *  Get the kitchen's invoice prefix.
         */
        static function getKitchenInvoicePrefix( string $kitchenID ) : string {

            $kitchen = self::getKitchenByID( $kitchenID );
            return $kitchen ? $kitchen['invoicePrefix'] : 'XX';

        }

        /**
         *  Get the kitchen's next invoice number.
         */
        static function getKitchensNextInvoiceNumber( string $invoicePrefix ) {

            global $wpdb;

            $orderID = $wpdb->get_row( $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'invoicePrefix' AND meta_value = '%s' ORDER BY meta_id DESC LIMIT 1;",
                $invoicePrefix
            ) );

            if ( ! $orderID ) {
                return 1;
            }

            $order = wc_get_order( $orderID->post_id );
            $invoiceNumber = (int) $order->get_meta( 'invoiceNumber' ) + 1;

            return $invoiceNumber;

        }

        /**
         *  Get the ticket number.
         */
        static function getTicketNumber( $order ) {

            return '#' . ( $order->get_meta( 'invoiceNumber' )
                            ? str_pad( $order->get_meta( 'invoiceNumber' ), 6, 0, STR_PAD_LEFT )
                            : $order->get_id( ) );

        }

        /**
         *  Get the invoice number.
         */
        static function getInvoiceNumber( $order ) {

            return $order->get_meta( 'invoicePrefix' ) . '-' . str_pad( $order->get_meta( 'invoiceNumber' ), 6, 0, STR_PAD_LEFT );

        }

        /**
         *  Get the icon for the given order type.
         */
        static function getOrderTypeIcon( $isDelivery, $height = 'auto', $display = 'block' ) {

            ob_start( ); ?>
            <svg viewBox="0 0 24 24" style="display:<?= $display ?>" height="<?= $height ?>">
            <?php if ( $isDelivery ) : ?>
                <path fill="currentColor" d="M19 15C19.55 15 20 15.45 20 16C20 16.55 19.55 17 19 17S18 16.55 18 16C18 15.45 18.45 15 19 15M19 13C17.34 13 16 14.34 16 16S17.34 19 19 19 22 17.66 22 16 20.66 13 19 13M10 6H5V8H10V6M17 5H14V7H17V9.65L13.5 14H10V9H6C3.79 9 2 10.79 2 13V16H4C4 17.66 5.34 19 7 19S10 17.66 10 16H14.5L19 10.35V7C19 5.9 18.11 5 17 5M7 17C6.45 17 6 16.55 6 16H8C8 16.55 7.55 17 7 17Z" />
            <?php else : ?>
                <path fill="currentColor" d="M12,18H6V14H12M21,14V12L20,7H4L3,12V14H4V20H14V14H18V20H20V14M20,4H4V6H20V4Z" />
            <?php endif ?>
            </svg>
            <?php

            return ob_get_clean( );

        }

        static function getMenuCategories( ) {

            $zipcode = ZipcodeController::getCustomerZipcode( );

            try {
                $kitchen = Helpers::getKitchenByZipcode( $zipcode );
                $kitchenID = $kitchen['kitchen_id'];
            } catch ( \Exception $e ) { }
    
            if ( empty( $kitchenID ) ) {
                print do_shortcode(
                    '[alert style="error"]Error: No se ha especificado ningún código postal[/alert]'
                );
                return;
            } else {
                return array_map( function( $_cat ) {
                    return get_term( $_cat->id, 'product_cat' );
                }, ProductCatController::getSubCategoriesForKitchen( Constants::$MENU_CAT_ID, $kitchenID ) );
            }

        }

        static function getProductMainCategory( $productID, $onlyChildren = true ) {

            $categories = get_the_terms( $productID, 'product_cat' );
            $primaryCat = yoast_get_primary_term_id( 'product_cat', $productID );

            $prodCategories = [ ];
            foreach ( $categories as $cat ) {
                if ( $onlyChildren && $cat->parent == 0 ) {
                    continue;
                }

                if ( $primaryCat == $cat->term_id ) {
                    $primaryCat = $cat;
                    break;
                }
                $prodCategories[] = $cat;
            }

            return isset( $primaryCat->name ) ? $primaryCat : reset( $prodCategories );

        }

    }
        
}