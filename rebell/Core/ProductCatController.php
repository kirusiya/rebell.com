<?php namespace Invbit\Core;

/**
 * My account controller.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */


if ( ! class_exists( __NAMESPACE__ .'\ProductCatController' ) ) {

    class ProductCatController {

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
        public function __construct( ) { }

        /**
         *  Get subcategories for a given kitchen.
         */
        static function getSubCategoriesForKitchen( $parentID, $kitchenID ) {

            global $wpdb;

            $sql = "SELECT 
                        t.term_id id, t.name, t.slug,
                        tm_order.meta_value order_id, tm_image.meta_value thumbnail_id
                    FROM {$wpdb->prefix}termmeta tm
                    JOIN {$wpdb->prefix}terms t
                        ON t.term_id = tm.term_id
                    JOIN {$wpdb->prefix}termmeta tm_image
                        ON (t.term_id = tm_image.term_id) AND tm_image.meta_key = 'thumbnail_id'
                    JOIN {$wpdb->prefix}term_taxonomy ttax
                        ON (t.term_id = ttax.term_id)
                    JOIN {$wpdb->prefix}termmeta tm_order
                        ON (t.term_id = tm_order.term_id) AND tm_order.meta_key = 'order'
                    WHERE (tm.meta_key = 'kitchen_for_product_category')
                        AND (tm.meta_value LIKE '%s')
                        AND (ttax.parent = $parentID)
                    ORDER BY tm_order.meta_value ASC;";

            return $wpdb->get_results( $wpdb->prepare( $sql, "%$kitchenID%" ) );

        }

    }

}

ProductCatController::getInstance( );