<?php namespace Invbit\Core;

/**
 * Customize Rest API for both WP and WC.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\CustomRestAPI' ) ) {

    class CustomRestAPI {

        private static $singleton;
        private $THUMBNAIL_SIZE;

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

            $this->THUMBNAIL_S_SIZE = 'woocommerce_single';
            $this->THUMBNAIL_M_SIZE = 'medium_large';

            require_once( get_stylesheet_directory( ) . '/Core/WCRestController.php' );
            require_once( get_stylesheet_directory( ) . '/Core/OrdersController.php' );

            $this->filter( [
                'rest_prepare_post',
                'rest_prepare_page',
                'woocommerce_rest_prepare_product_cat',
                'woocommerce_rest_prepare_pa_alergenos',
                'woocommerce_rest_prepare_pa_caracteristicas'
            ], 'exposeCustomFields', 3 );

            $this->filter( 'woocommerce_rest_prepare_product_object', 'exposeProductAttributesACF', 3 );
            $this->filter( 'woocommerce_rest_prepare_shop_order_object', 'exposeOrderObject', 3 );
            
            $this->filter( 'woocommerce_rest_prepare_product_cat', 'customizeCategoryThumbsSize', 2 );
            $this->filter( 'woocommerce_rest_prepare_product_object', 'exposeProductThumbnail', 3 );
            $this->filter( 'woocommerce_rest_prepare_product_object', 'exposeIfProductCustomizable', 3 );
            $this->filter( 'woocommerce_rest_prepare_customer', 'exposeCustomerACF', 3 );

            $this->filter( 'woocommerce_rest_product_object_query', 'filterProductsByKitchen', 2, 9999 );
            $this->filter( 'woocommerce_rest_product_cat_query', 'customizeProductCategoriesResponseByDefaultKitchen', 2 );

        }

        /**
         * Expose custom field in the REST API.
         */
        public function exposeCustomFields( $res, $item, $req ) {

            $res->data[ 'custom-fields' ] = isset( $item->taxonomy )
                ? get_fields( "{$item->taxonomy}_{$res->data[ 'id' ]}" )
                : get_fields( $res->data[ 'id' ] );

            return $res;
        
        }

        /**
         * Expose custom field for the product's attributes in the REST API.
         */
        public function exposeProductAttributesACF( $res, $item, $req ) {

            $res = $this->exposeCustomFields( $res, $item, $req );

            if ( ! isset( $item->attributes ) or ! $res->data[ 'attributes' ] ) return $res;
            
            $attributes = [ ];
            foreach ( $item->get_attributes( ) as $attr ) {
                $attribute = [
                    'id'        => $attr->get_id( ),
                    'name'      => $attr->get_name( ),
                    'position'  => $attr->get_position( ),
                    'visible'   => $attr->get_visible( ),
                    'variation' => $attr->get_variation( ),
                    'options'   => [ ]
                ];

                foreach ( $attr->get_terms( ) as $option ) {
                    $attribute[ 'options' ][ ] = [
                        'name'        => $option->name,
                        'slug'        => $option->slug,
                        'description' => $option->description,
                        'icon'        => get_field( 'icon', "{$option->taxonomy}_{$option->term_id}" ) ?: ''
                    ];
                }

                $attributes[ ] = $attribute;
            }

            $res->data[ 'attributes' ] = $attributes;

            return $res;

        }

        /**
         * Modify the exposed order object in the REST API.
         */
        public function exposeOrderObject( $order, $object, $req ) {

            foreach( $order->data['line_items'] as &$item )
                $item[ 'categories' ] = get_the_terms( $item['product_id'], 'product_cat' );

            return $order;

        }

        /**
         * Attach a hook into WP filter.
         */
        private function filter( $hooks, $cb, $numArgs = 1, $priority = 10 ) {

            if ( ! is_array( $hooks ) )
                return add_filter( $hooks, [ $this, $cb ], $priority, $numArgs );

            foreach ( $hooks as $hook )
                add_filter( $hook, [ $this, $cb ], $priority, $numArgs );
            
        }

        /**
         * Expose product thumbnail image in the REST API.
         */
        public function exposeProductThumbnail( $res, $item, $req ) {

            if ( ! isset( $res->data[ 'id' ] ) ) return $res;

            $sImage = wp_get_attachment_image_src(
                get_post_thumbnail_id( $res->data[ 'id' ] ), $this->THUMBNAIL_S_SIZE
            );

            $mImage = wp_get_attachment_image_src(
                get_post_thumbnail_id( $res->data[ 'id' ] ), $this->THUMBNAIL_M_SIZE
            );

            if ( isset( $sImage[ 0 ] ) ) $res->data[ 'image_small'  ] = $sImage[ 0 ];
            if ( isset( $mImage[ 0 ] ) ) $res->data[ 'image_medium' ] = $mImage[ 0 ];

            return $res;

        }

        /**
         *  Customize the image size for the categories.
         */
        public function customizeCategoryThumbsSize( $item, $request ) {

            if ( ! isset( $item->data[ 'image' ][ 'src' ] ) ) return $item;
        
            $attachment = wp_get_attachment_image_src(
                $item->data[ 'image' ][ 'id' ], $this->THUMBNAIL_M_SIZE
            );
        
            if ( isset( $attachment[ 0 ] ) ) $item->data[ 'image' ][ 'src' ] = $attachment[ 0 ];
        
            return $item;

        }

        /**
         *  Expose a property to tell if the product is customizable.
         */
        public function exposeIfProductCustomizable( $item, $request ) {

            if ( ! isset( $item->data[ 'id' ] ) ) return $item;
        
            $item->data[ 'is_customizable' ] = boolval( get_field( 'is_customizable', $item->data[ 'id' ] ) );

            return $item;

        }

        /**
         *  Expose ACF fields for every customer.
         */
        public function exposeCustomerACF( $item, $request ) {

            $globalCoupons = get_field( 'global_coupons', 'options' );
            $globalCoupons = is_array( $globalCoupons ) ? $globalCoupons : [ ];
            $userCoupons   = [ ];

            if ( isset( $item->data[ 'id' ] ) ) {
                $userCoupons = get_field( 'user_coupons', "user_{$item->data[ 'id' ]}" );
                $userCoupons = is_array( $userCoupons ) ? $userCoupons : [ ];
            }

            $allCoupons = array_merge( $globalCoupons, $userCoupons );

            $coupons = [ ];
            foreach ( $allCoupons as $coupon ) {
                $_coupon = new \WC_Coupon($coupon->ID);

                $now = new \DateTime(date('Y-m-d'), new \DateTimeZone('Europe/Madrid'));

                // Check if the coupon is expired or its limit has been exceeded.
                $expired         = $_coupon->get_date_expires( ) && ( $_coupon->get_date_expires( ) < $now );
                $useLimitExeeded = $_coupon->get_usage_limit( ) && ( $_coupon->get_usage_limit( ) < $_coupon->get_usage_count( ) );

                if ( $expired or $useLimitExeeded ) {
                    continue;
                }

                $_coupon = [
                    'active'                      => ! ( $expired or $useLimitExeeded ),
                    'expired'                     => $expired,
                    'usageExeeded'                => $useLimitExeeded,
                    'amount'                      => $_coupon->get_amount( ),
                    'changes'                     => $_coupon->get_changes( ),
                    'code'                        => $_coupon->get_code( ),
                    'data'                        => $_coupon->get_data( ),
                    'data_keys'                   => $_coupon->get_data_keys( ),
                    'data_store'                  => $_coupon->get_data_store( ),
                    'date_created'                => $_coupon->get_date_created( ),
                    'date_expires'                => $_coupon->get_date_expires( ),
                    'date_modified'               => $_coupon->get_date_modified( ),
                    'description'                 => $_coupon->get_description( ),
                    'discount_type'               => $_coupon->get_discount_type( ),
                    'email_restrictions'          => $_coupon->get_email_restrictions( ),
                    'exclude_sale_items'          => $_coupon->get_exclude_sale_items( ),
                    'excluded_product_categories' => $_coupon->get_excluded_product_categories( ),
                    'excluded_product_ids'        => $_coupon->get_excluded_product_ids( ),
                    'extra_data_keys'             => $_coupon->get_extra_data_keys( ),
                    'free_shipping'               => $_coupon->get_free_shipping( ),
                    'id'                          => $_coupon->get_id( ),
                    'individual_use'              => $_coupon->get_individual_use( ),
                    'limit_usage_to_x_items'      => $_coupon->get_limit_usage_to_x_items( ),
                    'maximum_amount'              => $_coupon->get_maximum_amount( ),
                    'meta'                        => $_coupon->get_meta( ),
                    'meta_data'                   => $_coupon->get_meta_data( ),
                    'minimum_amount'              => $_coupon->get_minimum_amount( ),
                    'object_read'                 => $_coupon->get_object_read( ),
                    'product_categories'          => $_coupon->get_product_categories( ),
                    'product_ids'                 => $_coupon->get_product_ids( ),
                    'usage_count'                 => $_coupon->get_usage_count( ),
                    'usage_limit'                 => $_coupon->get_usage_limit( ),
                    'usage_limit_per_user'        => $_coupon->get_usage_limit_per_user( ),
                    'used_by'                     => $_coupon->get_used_by( ),
                    'virtual'                     => $_coupon->get_virtual( ),
                ];
                $coupons[ ] = $_coupon;
            }
            $item->data[ 'coupons' ] = $coupons;

            return $item;

        }

        /**
         *  Filter products by kitchen.
         */
        public function filterProductsByKitchen( $args, $request ) {

            if ( empty( $kitchen = $request->get_param('kitchen') ) ) {
                $kitchen = get_field( 'default_kitchen', 'options' );
            }

            $metaQuery = [
                'key'     => 'kitchen_for_product',
                'value'   => sanitize_text_field( $kitchen ),
                'compare' => 'LIKE'
            ];
            if ( isset( $args[ 'meta_query' ] ) ) {
                $args[ 'meta_query' ][ 'relation' ] = 'AND';
            } else {
                $args[ 'meta_query' ] = [ ];
            }

            $args[ 'meta_query' ][ ] = $metaQuery;

            return $args;

        }

        /**
         *  Fix for the versión 1.0 of the app to make it work with a default kitchen.
         */
        public function customizeProductCategoriesResponseByDefaultKitchen( $preparedArgs, $request ) {

            $parentID  = (int) $request->get_param( 'parent' ) ?? 94;
            $kitchenID = get_field( 'default_kitchen', 'options' );

            $categoriesIDs = array_map( function( $cat ) {
                return $cat->id;
            }, ProductCatController::getSubCategoriesForKitchen( $parentID, $kitchenID ) );

            $preparedArgs = [ 'include' => $categoriesIDs ];

            return $preparedArgs;

        }

    }
        
}

CustomRestAPI::getInstance( );