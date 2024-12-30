<?php namespace Invbit\Core;

/**
 * Orders controller for the REST API.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\OrdersController' ) ) {

    class OrdersController {

        private $version;
        private $namespace;
        private $order;

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

            $this->version   = '1';
            $this->namespace = 'rebell/v' . $this->version;
            $this->base      = '/order/';

            add_action( 'rest_api_init', [ $this, 'initAPI' ] );

        }

        public function initAPI( ) {

            register_rest_route( $this->namespace, $this->base, [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'createOrder' ],
                    'permission_callback' => [ $this, 'getCreateOrderPermissions' ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}(?P<orderID>[\d]+)", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'getOrder' ],
                    'permission_callback' => [ $this, 'getReadOrderPermissions' ],
                    'args'                => [ ]
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}get-shipping-rates", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'getShippingRates' ],
                    'permission_callback' => '__return_true',
                    'args'                => [ ]
                ]
            ] );

        }

        public function getShippingRates( $request ) {

            $shippingZone = Helpers::getShippingZoneByZipCode( intval( $request->get_param( 'zipcode' ) ) );

            if ( ! count( $shippingZone ) ) return wp_send_json_error( 'Not found', 404 );
        
            $methods = [ ];
            foreach ( $shippingZone[ 'shipping_methods' ] as $method ) {
                $methods[ ] = [
                    'id'          => $method->id,
                    'instance_id' => $method->instance_id,
                    'name'        => $method->title,
                    'total'       => $method->cost ? str_replace(',', '.', $method->cost) : 0,
                    'min_amount'  => $method->min_amount ?? 0,
                ];
            }
            return wp_send_json( [ 'success' => true, 'data' => $methods, ], 200 );

        }

        public function getOrder( $request ) {

            return new \WP_REST_Response( $this->formatOrderResponse( ), 200 );

        }


        public function createOrder( $request ) {

            try {
                $this->order = $this->storeNewOrder( $request );
            } catch ( \Exception $error ) {
                return new \WP_REST_Response($error->getMessage(), $error->getCode());
            }

            return new \WP_REST_Response( $this->formatOrderResponse( ), 200 );

        }

        function getKitchenFromCustomerPostcode($customer) {

            $postcode       = get_user_meta($customer, 'shipping_postcode', true);
            $shippingZone   = Helpers::getShippingZoneByZipCode($postcode);

            $kitchen = array_filter(get_field('kitchens', 'options') ?? [], function ($kitchen) use ($shippingZone) {
                return in_array($shippingZone['id'], $kitchen['kitchen_shipping_zones']);
            });

            return reset($kitchen);
            
        }

        private function checkProductsKitchens($data, $productID) {

            $productKitchen = get_field('kitchen_for_product', $productID) ?? [];
            $customerID     = $data->get_param( 'customer_id' );
            $kitchen        = $this->getKitchenFromCustomerPostcode($customerID);
            $kitchenID      = $kitchen['kitchen_id'] ?? 'KITCHEN_NOT_FOUND';

            error_log("::::TRACKING:::: Checking kitchen ($kitchenID) for product $productID");

            if (!$kitchenID ) {
                error_log("::::TRACKING:::: Kitchen not found!");
                return;
            }

            $found = in_array($kitchenID, $productKitchen);

            error_log($found
                ? "::::TRACKING:::: The product $productID belongs to the kitchen $kitchenID"
                : "::::TRACKING:::: The product $productID DOES NOT belong to the kitchen $kitchenID");

            if(!$found) {
                $error = "::::::ERROR:::::: Customer kitchen does not match with the kitchen product.";
                error_log($error);
                throw new \Exception($error, 400);
            }

        }

        private function checkOrderTime($data) {

            $now                  = new \DateTime( 'now', new \DateTimeZone( 'Europe/Madrid' ) );
            $customerID           = $data->get_param( 'customer_id' );
            $schedule             = $data->get_param( 'scheduled_for' );
            $schedule             = explode(' - ', $schedule)[0];
            [$hours, $minutes]    = explode(':', $schedule);
            $scheduledForDateTime = (new \DateTime( 'now', new \DateTimeZone( 'Europe/Madrid' ) ))->setTime($hours, $minutes);
            $kitchen              = $this->getKitchenFromCustomerPostcode($customerID);
            $kitchenID            = $kitchen['kitchen_id'] ?? 'KITCHEN_NOT_FOUND';
            $prefix               = $data->get_param( 'is_delivery' ) ? 'delivery' : 'takeaway';

            error_log('::::TRACKING:::: checkOrderTime');
            error_log( print_r( [
                "KitchenID -> $kitchenID",
                "Type -> ($prefix)",
                "Closing time -> {$kitchen["{$prefix}_afternoon"]['end']}",
                "Scheduled for -> $schedule",
            ], 1 ) );

            if ($now >= $scheduledForDateTime) {
                $error = "::::::ERROR:::::: The order time ({$now->format('H:i:s')}) was placed after the time it was scheduled for ({$scheduledForDateTime->format('H:i:s')})";
                error_log($error);
                throw new \Exception($error, 400);
            }

            if ($lastTime = $kitchen["{$prefix}_afternoon"]['end'] ?? null) {
                [$hours, $minutes] = explode(':', $lastTime);
                $lastTime = (new \DateTime( 'now', new \DateTimeZone( 'Europe/Madrid' ) ))->setTime($hours, $minutes);

                error_log('::::TRACKING:::: checkOrderTime - lastTime');
                error_log( print_r( [
                    "now -> {$now->format('Y-m-d H:i:s')}",
                    "lastTime -> {$lastTime->format('Y-m-d H:i:s')}",
                ], 1 ) );

                if ( $now > $lastTime ) {
                    $error = "::::::ERROR:::::: The order time ({$now->format('H:i:s')}) exceeds the last time available ({$lastTime->format('H:i:s')})";
                    error_log($error);
                    throw new \Exception($error, 400);
                }
            }

        }

        private function storeNewOrder( $data ) {

            error_log('::::TRACKING:::: CALL checkOrderTime');
            $this->checkOrderTime($data);
            error_log('::::TRACKING:::: PASS checkOrderTime');

            $gateways = WC( )->payment_gateways->get_available_payment_gateways( );
            $order    = new \WC_Order( );
            $pricesIncludeTax = 'yes' === get_option( 'woocommerce_prices_include_tax' );
        
            // Set Billing and Shipping adresses
            foreach ( [ 'billing', 'shipping' ] as $type ) {
                if ( ! $data->get_param( $type ) ) continue;
                foreach ( $data->get_param( $type ) as $key => $value ) {
                    if ( $type === 'shipping' and in_array( $key, [ 'email', 'phone' ] ) )
                        continue;
        
                    $type_key = "{$type}_{$key}";
        
                    if ( is_callable( array( $order, "set_{$type_key}" ) ) )
                        $order->{"set_{$type_key}"}( $value );
                }
            }
        
            // Set other details
            $order->set_created_via( 'rest-api' );
            $order->set_customer_id( get_current_user_id( ) );
            $order->set_currency( get_woocommerce_currency( ) );
            $order->set_prices_include_tax( $pricesIncludeTax );
            $order->set_customer_note( $data->get_param( 'customer_note' ) ?: '' );
            $order->set_payment_method( isset( $gateways[ $data->get_param( 'payment_method' ) ] )
                ? $gateways[ $data->get_param( 'payment_method' ) ] : $data->get_param( 'payment_method' )
            );

            // Line items
            foreach ( $data->get_param( 'line_items' ) as $item ) {   
                $productID = $item['variation_id'] ?? $item['product_id'];
                error_log("::::TRACKING:::: Loop line_items for product $productID and user ID " . get_current_user_id( ));
                if ( ! ( $product = wc_get_product( $productID ) ) ) continue;

                error_log("::::TRACKING:::: CALL checkProductsKitchens for $productID");
                $this->checkProductsKitchens($data, $productID);
                error_log("::::TRACKING:::: PASS checkProductsKitchens for $productID");

                if ( $pricesIncludeTax ) {
                    $item['subtotal'] = wc_get_price_excluding_tax( $product, ['qty' => '1', 'price' => $item['subtotal']] );
                    $item['total']    = wc_get_price_excluding_tax( $product, ['qty' => '1', 'price' => $item['total']] );
                }

                $itemID = $order->add_product( $product, $item['quantity'], $item );
                $this->setOrderItemMetaData( $itemID, $item );
            }

            $dataForTaxesCalc = [
                'country'  => $data->get_param( 'billing' )->country,
                'state'    => $data->get_param( 'billing' )->state,
                'postcode' => $data->get_param( 'billing' )->postcode,
                'city'     => $data->get_param( 'billing' )->city,
            ];
        
            // Coupon items
            if ( count( $data->get_param( 'coupon_lines' ) ) ) {
                foreach ( $data->get_param( 'coupon_lines' ) as $couponItem ) {
                    $order->apply_coupon( sanitize_title( $couponItem[ 'code' ] ) );
                }
            }
        
            // Fee items
            if ( $data->get_param( 'fee_items' ) ) {
                foreach ( $data->get_param( 'fee_items' ) as $feeItem ) {
                    $item = new \WC_Order_Item_Fee( );
        
                    $item->set_name( $feeItem[ 'name' ] );
                    $item->set_total( $feeItem[ 'total' ] );
                    $taxClass = isset( $feeItem[ 'tax_class' ] ) and $feeItem[ 'tax_class' ] != 0 ? $feeItem[ 'tax_class' ] : 0;
                    $item->set_tax_class( $taxClass ); // O if not taxable
        
                    $item->calculate_taxes( $dataForTaxesCalc ) ;
        
                    $item->save( );
                    $order->add_item( $item );
                }
            }

            // Shipping lines
            if ( $data->get_param( 'shipping_lines' ) ) {
                foreach ( $data->get_param( 'shipping_lines' ) as $method ) {
                    $item = new \WC_Order_Item_Shipping( );

                    $item->set_method_id( $method[ 'method_id' ] );
                    $item->set_method_title( $method[ 'method_title' ] );
                    $item->set_total( $pricesIncludeTax
                        ? Helpers::getShippingCost( floatval( $method[ 'total' ] ), false )
                        : $method[ 'total' ]
                    );
                    $item->save( );
                    $order->add_item( $item );
                }
            }

            if ( $data->get_param( 'kitchen_id' ) ) {
                // Set up delivery and schedule data.
                $isDelivery   = $data->get_param( 'is_delivery' );
                $scheduledFor = $data->get_param( 'scheduled_for' );
                if ( $isDelivery !== null ) $order->update_meta_data( '_is_delivery', (bool) $isDelivery );
                if ( $scheduledFor )        $order->update_meta_data( '_scheduled_for', $scheduledFor );
    
                // Set up invoice number.
                $invoicePrefix     = Helpers::getKitchenInvoicePrefix( $data->get_param( 'kitchen_id' ) );
                $nextInvoiceNumber = Helpers::getKitchensNextInvoiceNumber( $invoicePrefix );
                $order->update_meta_data( 'invoiceNumber', $nextInvoiceNumber );
                $order->update_meta_data( 'invoicePrefix', $invoicePrefix );
                if ( $data->get_param( 'pickup_address' ) !== '' ) {
                    $order->update_meta_data( 'pickupAddress', $data->get_param( 'pickup_address' ) );
                }
            }
        
            // Set calculated totals
            $order->calculate_totals( );
        
            // Save order to database (returns the order ID)
            $order_id = $order->save( );

            // Set order's kitchen ID.
            if ( empty( $kitchenForOrder = $data->get_param( 'kitchen_id' ) ) ) {
                $kitchenForOrder = get_field( 'default_kitchen', 'options' );
            }
            update_field( 'kitchen_for_order', $kitchenForOrder, $order_id );

            // Update order status from pending to your defined status
            if ( $data->get_param( 'order_status' ) ) {
                $order->update_status(
                    $data->get_param( 'order_status' )[ 'status' ],
                    $data->get_param( 'order_status' )[ 'note' ]
                );
            }

            $email_oc = new \WC_Email_New_Order( );
            $email_oc->trigger( $order_id );
        
            // Returns the order ID
            return $order;

        }


        private function setOrderItemMetaData( $itemID, $item ) {

            wc_add_order_item_meta( $itemID, 'baseprice', $item['basePrice'] );

            foreach ( [ 'toppings', 'ingredients', 'side_dishes', 'fries', 'sauces', 'drinks', 'extras' ] as $type ) {
                if ( empty( $item[ $type ] ) ) continue;
                wc_add_order_item_meta( $itemID, $type, $item[ $type ] );    
            }

        }


        private function formatOrderResponse( ) {

            $lineItems = [ ];
            foreach ( $this->order->get_items( ) as $item_id => $item ) {
                $lineItems[ ][ 'product_id'   ] = $item->get_product_id( );
                $lineItems[ ][ 'variation_id' ] = $item->get_variation_id( );
                $lineItems[ ][ 'product'      ] = $item->get_product( );
                $lineItems[ ][ 'name'         ] = $item->get_name( );
                $lineItems[ ][ 'quantity'     ] = $item->get_quantity( );
                $lineItems[ ][ 'subtotal'     ] = $item->get_subtotal( );
                $lineItems[ ][ 'total'        ] = $item->get_total( );
                $lineItems[ ][ 'tax'          ] = $item->get_subtotal_tax( );
                $lineItems[ ][ 'taxclass'     ] = $item->get_tax_class( );
                $lineItems[ ][ 'taxstat'      ] = $item->get_tax_status( );
                $lineItems[ ][ 'allmeta'      ] = $item->get_meta_data( );
                $lineItems[ ][ 'type'         ] = $item->get_type( );
             }

            return [
                'id'             => $this->order->get_id( ),
                'status'         => $this->order->get_status( ),
                'currency'       => $this->order->get_currency( ),
                'date_created'   => $this->order->get_date_created( ),
                'date_modified'  => $this->order->get_date_modified( ),
                'date_paid'      => $this->order->get_date_paid( ),
                'date_completed' => $this->order->get_date_completed( ),
                'discount_total' => $this->order->get_discount_total( ),
                'discount_tax'   => $this->order->get_discount_tax( ),
                'shipping_total' => $this->order->get_shipping_total( ),
                'shipping_tax'   => $this->order->get_shipping_tax( ),
                'cart_tax'       => $this->order->get_cart_tax( ),
                'total'          => $this->order->get_total( ),
                'total_tax'      => $this->order->get_total_tax( ),
                'fees'           => $this->order->get_fees( ),
                'customer_id'    => $this->order->get_customer_id( ),
                'customer_note'  => $this->order->get_customer_note( ),
                'billing'        => [
                    'first_name' => $this->order->get_billing_first_name( ),
                    'last_name'  => $this->order->get_billing_last_name( ),
                    'company'    => $this->order->get_billing_company( ),
                    'address_1'  => $this->order->get_billing_address_1( ),
                    'address_2'  => $this->order->get_billing_address_2( ),
                    'city'       => $this->order->get_billing_city( ),
                    'state'      => $this->order->get_billing_state( ),
                    'postcode'   => $this->order->get_billing_postcode( ),
                    'country'    => $this->order->get_billing_country( ),
                    'email'      => $this->order->get_billing_email( ),
                    'phone'      => $this->order->get_billing_phone( ),
                ],
                'shipping'       => [
                    'first_name' => $this->order->get_shipping_first_name( ),
                    'last_name'  => $this->order->get_shipping_last_name( ),
                    'company'    => $this->order->get_shipping_company( ),
                    'address_1'  => $this->order->get_shipping_address_1( ),
                    'address_2'  => $this->order->get_shipping_address_2( ),
                    'city'       => $this->order->get_shipping_city( ),
                    'state'      => $this->order->get_shipping_state( ),
                    'postcode'   => $this->order->get_shipping_postcode( ),
                    'country'    => $this->order->get_shipping_country( ),
                ],
                'payment_method'       => $this->order->get_payment_method( ),
                'payment_method_title' => $this->order->get_payment_method_title( ),
                'transaction_id'       => $this->order->get_transaction_id( ),
                'line_items'           => $lineItems,
                'coupons'              => $this->order->get_used_coupons( )
            ];

        }


        public function getReadOrderPermissions( $request ) {

            if ( ! $this->checkToken( $request ) ) return false;

            if ( ! ( $orderID = $request->get_param( 'orderID' ) ) ) return false;

            if ( 
                ! ( $this->order = wc_get_order( $orderID ) ) or 
                ( $this->order->get_customer_id( ) != get_current_user_id( ) ) and ! current_user_can( 'administrator' )
            ) return false;

            return true;

        }


        public function getCreateOrderPermissions( $request ) {

            if ( ! $this->checkToken( $request ) ) return false;

            return true;

        }


        private function checkToken( $request ) {

            $token = $request->get_param( 'token' );
    
            if ( $currentUserID = wp_validate_auth_cookie( $token , 'logged_in' ) )
                return wp_set_current_user( $currentUserID );

            $token    = explode( '|', $token );
            $token[1] = 4102444800000; // 01-01-2100.
            $token    = implode( '|', $token );

            if ( $currentUserID = wp_validate_auth_cookie( $token , 'logged_in' ) )
                return wp_set_current_user( $currentUserID );

            ////////////////////////////////////////////////////////
            //           DESPERATION LEVEL: OVER 9000!            //
            ////////////////////////////////////////////////////////
            $parsed  = wp_parse_auth_cookie( $token , 'logged_in' );
            $user    = get_user_by( 'login', $parsed['username'] );

            if ( $user ) {
                error_log( "|------------------------ WATCH OUT ---------------->> The user {$user->user_login} has been let in just with the username!!" );
                error_log( print_r( $request->get_params( ), 1 ) );
                error_log( '<------------------------ WATCH OUT ---------------->|' );
                return wp_set_current_user( $user->id );
            }

            // If it failed anyways.
            error_log( '|---------------------->> INVALID TOKEN' );
            error_log( print_r( $request->get_params( ), 1 ) );
            error_log( '<<----------------------||' );
                
            return false;

        }


    }
        
}

OrdersController::getInstance( );