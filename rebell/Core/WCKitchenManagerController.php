<?php namespace Invbit\Core;

/**
 * WC Kitchen Manager Controller.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\WCKitchenManagerController' ) ) {

    class WCKitchenManagerController {

        protected $version;
        protected $namespace;
        protected $order;

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

            add_action( 'pre_get_posts', [ $this, 'filterOrdersByKitchen' ] );
            add_action( 'restrict_manage_posts', [ $this, 'addKitchensFilterForProducts' ], 20 );
            add_action( 'pre_get_posts', [ $this, 'handleKitchenFilteringForProducts' ], );

            add_action( 'admin_menu', [ $this, 'addMyKitchenPage' ] );
            add_action( 'admin_post_rebell_update_my_kitchen', [ $this, 'updateMyKitchen' ] );
            add_action( 'admin_post_nopriv_rebell_update_my_kitchen', 'auth_redirect' );
            add_action( 'admin_enqueue_scripts', [ $this, 'loadMyKitchenPageScripts' ] );

        }

        public function filterOrdersByKitchen( $query ) {

            global $pagenow, $post_type, $wpdb;

            if (
                ( $pagenow !== 'edit.php' ) or
                ( $post_type !== 'shop_order' ) or
                ! $this->currentUserIsKitchenManager( ) or
                ! isset( $query->query_vars['post_type'] ) or
                ( $query->query_vars['post_type'] !== 'shop_order' )
            ) {
                return;
            }

            $userKitchenIDs = "('" . implode( "','", self::getCurrentUserKitchens( ) ) . "')";

            $orderIDs = $wpdb->get_col( "SELECT DISTINCT post_id 
                FROM {$wpdb->prefix}postmeta
                WHERE meta_key = 'kitchen_for_order' AND meta_value in $userKitchenIDs;
            " );

            $query->set( 'post__in', $orderIDs );
        
            $query->set( 'paged', ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 ) );

        }

        /**
         * Add a selector for filtering products by kitchen at the top of the products listing 
         * in the admin panel.
         */
        public function addKitchensFilterForProducts( ) {

            global $pagenow, $post_type;

            if ( ! is_admin( ) or $post_type !== 'product' or $pagenow !== 'edit.php' ) {
                return;
            }
        
            $filterID = 'filter_product_by_kitchen';
            $kitchens = Helpers::getAllKitchens( );
            
            ?><select name="<?= esc_attr( $filterID ) ?>">
                <option value=""><?= esc_html( 'Todas las Cocinas', 'invbit' ) ?></option>
                <?php foreach ( $kitchens as $kitchen ) : ?>
                    <option
                        value="<?= esc_attr( $kitchen['id'] ) ?>"
                        <?= esc_attr( isset( $_GET[$filterID] ) ? selected( $kitchen['id'], $_GET[$filterID], false ) : '' ) ?>
                    >
                        <?= esc_html( $kitchen['name'] ) ?>
                    </option>
                <?php endforeach; ?>
            </select><?php

        }

        /**
         * Handle the filtering of products by kitchen.
         */
        public function handleKitchenFilteringForProducts( $query ) {

            global $pagenow, $post_type, $wpdb;

            $filterID = 'filter_product_by_kitchen';

            if (
                ! is_admin( ) or
                ! $query->is_admin or
                empty( $_GET[$filterID] ) or
                ( $pagenow !== 'edit.php' ) or
                ( $post_type !== 'product' ) or
                ! $query->is_main_query( )
            ) {
                return;
            }

            $kitchens = array_filter( (array) get_field( 'kitchens', 'options' ), function( $kitchen ) use ( $filterID ) {
                return $kitchen['kitchen_id'] == $_GET[$filterID];
            } );
        
            if ( count( $kitchens ) <= 0 ) {
                return;
            }
        
            $kitchen  = array_pop( $kitchens );

            $orderIDs = $wpdb->get_col( "SELECT DISTINCT post_id FROM {$wpdb->prefix}postmeta
                WHERE meta_key = 'kitchen_for_product' AND meta_value LIKE '%{$kitchen['kitchen_id']}%'" );

            $query->set( 'post__in', $orderIDs );
        
            $query->set( 'paged', ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 ) );

        }

        /**
         * Add My Kitchen page.
         */
        public function addMyKitchenPage() {

            add_menu_page(
                __( 'Mi Cocina', 'betheme' ),
                __( 'Mi Cocina', 'betheme' ),
                'kitchen_manager',
                'my-kitchen',
                [ $this, 'renderMyKitchenPage' ],
                'data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20height%3D%2224%22%20viewBox%3D%220%200%20400%20400%22%3E%3Cpath%20d%3D%22M163%2045l4%201%207%203%201%201-2%201h-6c-6%200-13%202-18%204-8%202-14%206-20%2012-6%205-10%2012-14%2018a98%2098%200%2000-14%2039v11c0%2010%203%2020%208%2030l12%2015%209%2010a199%20199%200%200027%2023l19%2013c5%203%2011%205%2016%206%206%202%2011%201%2017-1a210%20210%200%200076-60c6-8%2010-17%2011-26l1-19c-1-9-3-18-7-27a124%20124%200%2000-23-34c-6-6-13-9-22-11-5-2-11-3-16-3h-4v-2l2-1%2012-2%201-1h4l7%201a79%2079%200%200150%2028%20103%20103%200%200123%2047l1%2010%201%206a105%20105%200%2001-23%2064c-5%206-11%2012-18%2017l-22%2017-23%2018-4%203h-1%201l4-3c6-4%2012-6%2019-7h9l5%201c3%201%204%204%202%207-2%205-5%209-10%2013l-11%205-14%203c-4%201-6%203-7%207l-2%2015v16l-6%2018-2%203-1%206c0%205-2%208-6%2011l-11%203-1%201h-11l-8-2-7-4c-3-2-4-5-4-8%201-4-1-6-2-9a64%2064%200%2001-7-34l-1-16c-1-4-3-7-7-8l-14-3-6-2c-5-1-8-4-10-8l-5-7c-2-4-1-7%202-8l3-1%2015%201c6%202%2011%204%2016%208l3%202v-1l-7-5-33-25-18-15c-10-10-17-21-23-34-3-8-6-18-7-27l-1-12a97%2097%200%200133-71c11-10%2025-17%2040-20l4-1h14zM390%20198l-4%204-1%201c-3%201-3%204-2%207l1%206-1%201h-1l-7-4h-3l-7%203-2%201v-2l1-6c1-2%200-4-1-5l-5-5-1-1%202-1%207-1%203-2%203-8h2l3%207c1%202%202%203%204%203l9%201v1zM10%20195l9-1c2%200%203-1%203-2l4-7%201-2%201%202a343%20343%200%20016%209l7%201h1l1%202-4%203-1%201c-2%201-3%203-2%206l1%206v2h-2l-6-4h-5l-6%203-1%201-1-2a219%20219%200%20011-10l-7-7v-1z%22%20fill%3D%22%23e7df4f%22%2F%3E%3C%2Fsvg%3E',
                3
            );

        }

        /**
         * Render the My Kitchen page.
         */
        public function renderMyKitchenPage() {

            if ( ! $template = locate_template( TEMPLATES_DIR . 'Admin/my-kitchen-page.php' ) ) {
                return;
            }

            $kitchenData = array_map(function($kID) {
                return Helpers::getKitchenByID($kID);
            }, self::getCurrentUserKitchens( ));

            if ( count($kitchenData) <= 0 ) {
                die('Todavía no tienes una cocina asignada. Por favor, ponte en contacto con la administración de la tienda.');
            }

            set_query_var('kitchen', $kitchenData[0] );

            load_template( $template, false );

        }

        /**
         * Update user's kitchen data.
         */
        public function updateMyKitchen( ) {

            if (
                ! wp_verify_nonce( $_POST[ 'rebell-form-nonce' ], 'rebell-form' ) or
                ! ( current_user_can( 'kitchen_manager' ) )
            ) return wp_redirect( esc_url_raw( wp_get_referer() ) );

            $allKitchens = get_field( 'kitchens', 'options' );

            $kitchenIndex = null;
            foreach ( $allKitchens as $index => $kitchen ) {
                if ( $kitchen['kitchen_id'] === $_POST['kitchen'] ) {
                    $kitchenIndex = $index;

                    if ( ! in_array( $_POST['kitchen'], self::getCurrentUserKitchens( ) ) ) {
                        wp_die('You dont have permission to do this');
                    }
                }
            }

            $validHtmlTags = array_merge(
                wp_kses_allowed_html( ),
                [
                    'p'  => ['style' => 1],
                    'ul' => [],
                    'li' => [],
                ]
            );

            // Whatsapp number
            if ( $_POST['kitchen_whatsapp'] && is_numeric( $_POST['kitchen_whatsapp'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_kitchen_whatsapp", $_POST['kitchen_whatsapp'] );
            }

            // Description
            if ( $_POST['kitchen_description'] ) {
                update_option( "options_kitchens_{$kitchenIndex}_kitchen_description", wp_kses( $_POST['kitchen_description'], $validHtmlTags ) );
            }

            // Minimum order
            if ( $_POST['kitchen_min_order'] && is_numeric( $_POST['kitchen_min_order'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_kitchen_min_order", (int) $_POST['kitchen_min_order'] );
            }

            // Status
            if ( $_POST['kitchen_status'] && in_array( $_POST['kitchen_status'], [ 'blocked', 'closed', 'opened' ] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_kitchen_status", $_POST['kitchen_status'] );
            }

            // Blocked kitchen message
            if ( $_POST['blocked_kitchen_message'] ) {
                update_option( "options_kitchens_{$kitchenIndex}_blocked_kitchen_message", esc_html( $_POST['blocked_kitchen_message'] ) );
            }

            // Closed kitchen message
            if ( $_POST['closed_kitchen_message'] ) {
                update_option( "options_kitchens_{$kitchenIndex}_closed_kitchen_message", esc_html( $_POST['closed_kitchen_message'] ) );
            }

            // Invoice name
            if ( $_POST['invoiceName'] ) {
                update_option( "options_kitchens_{$kitchenIndex}_invoiceName", esc_html( $_POST['invoiceName'] ) );
            }

            // Invoice NIF
            if ( $_POST['invoiceNIF'] ) {
                update_option( "options_kitchens_{$kitchenIndex}_invoiceNIF", esc_html( $_POST['invoiceNIF'] ) );
            }

            // Invoice address
            if ( $_POST['invoiceAddress'] ) {
                update_option( "options_kitchens_{$kitchenIndex}_invoiceAddress", esc_html( $_POST['invoiceAddress'] ) );
            }

            /********* Takeaway orders *********/

            // Takeaway address
            if ( $_POST['takeaway_address'] ) {
                update_option( "options_kitchens_{$kitchenIndex}_takeaway_address", esc_html($_POST['takeaway_address']) );
            }

            // Takeaway maximum orders for an hour
            if ( $_POST['takeaway_max_orders_time'] && is_numeric( $_POST['takeaway_max_orders_time'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_takeaway_max_orders_time", esc_html( $_POST['takeaway_max_orders_time'] ) );
            }

            // Takeaway morning starting hour
            if ( $_POST['takeaway_morning_start'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['takeaway_morning_start'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_takeaway_morning_start", esc_html( $_POST['takeaway_morning_start'] ) );
            }

            // Takeaway morning ending hour
            if ( $_POST['takeaway_morning_end'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['takeaway_morning_end'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_takeaway_morning_end", esc_html( $_POST['takeaway_morning_end'] ) );
            }

            // Takeaway morning interval
            if ( $_POST['takeaway_morning_interval'] && is_numeric( $_POST['takeaway_morning_interval'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_takeaway_morning_interval", esc_html( $_POST['takeaway_morning_interval'] ) );
            }

            // Takeaway morning limit hour
            if ( $_POST['takeaway_morning_limit'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['takeaway_morning_limit'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_takeaway_morning_limit", esc_html( $_POST['takeaway_morning_limit'] ) );
            }

            // Takeaway morning open
            update_option( "options_kitchens_{$kitchenIndex}_takeaway_morning_open", boolval( $_POST['takeaway_morning_open'] ) );

            // Takeaway afternoon starting hour
            if ( $_POST['takeaway_afternoon_start'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['takeaway_afternoon_start'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_takeaway_afternoon_start", esc_html( $_POST['takeaway_afternoon_start'] ) );
            }

            // Takeaway afternoon ending hour
            if ( $_POST['takeaway_afternoon_end'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['takeaway_afternoon_end'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_takeaway_afternoon_end", esc_html( $_POST['takeaway_afternoon_end'] ) );
            }

            // Takeaway afternoon interval
            if ( $_POST['takeaway_afternoon_interval'] && is_numeric( $_POST['takeaway_afternoon_interval'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_takeaway_afternoon_interval", esc_html( $_POST['takeaway_afternoon_interval'] ) );
            }

            // Takeaway afternoon limit hour
            if ( $_POST['takeaway_afternoon_limit'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['takeaway_afternoon_limit'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_takeaway_afternoon_limit", esc_html( $_POST['takeaway_afternoon_limit'] ) );
            }

            // Takeaway afternoon open
            update_option( "options_kitchens_{$kitchenIndex}_takeaway_afternoon_open", boolval( $_POST['takeaway_afternoon_open'] ) );

            /********* Delivery orders *********/

            // Delivery maximum orders for an hour
            if ( $_POST['delivery_max_orders_time'] && is_numeric( $_POST['delivery_max_orders_time'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_delivery_max_orders_time", esc_html( $_POST['delivery_max_orders_time'] ) );
            }

            // Delivery morning starting hour
            if ( $_POST['delivery_morning_start'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['delivery_morning_start'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_delivery_morning_start", esc_html( $_POST['delivery_morning_start'] ) );
            }

            // Delivery morning ending hour
            if ( $_POST['delivery_morning_end'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['delivery_morning_end'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_delivery_morning_end", esc_html( $_POST['delivery_morning_end'] ) );
            }

            // Delivery morning interval
            if ( $_POST['delivery_morning_interval'] && is_numeric( $_POST['delivery_morning_interval'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_delivery_morning_interval", esc_html( $_POST['delivery_morning_interval'] ) );
            }

            // Delivery morning limit hour
            if ( $_POST['delivery_morning_limit'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['delivery_morning_limit'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_delivery_morning_limit", esc_html( $_POST['delivery_morning_limit'] ) );
            }

            // Delivery morning open
            update_option( "options_kitchens_{$kitchenIndex}_delivery_morning_open", boolval( $_POST['delivery_morning_open'] ) );

            // Delivery afternoon starting hour
            if ( $_POST['delivery_afternoon_start'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['delivery_afternoon_start'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_delivery_afternoon_start", esc_html( $_POST['delivery_afternoon_start'] ) );
            }

            // Delivery afternoon ending hour
            if ( $_POST['delivery_afternoon_end'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['delivery_afternoon_end'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_delivery_afternoon_end", esc_html( $_POST['delivery_afternoon_end'] ) );
            }

            // Delivery afternoon interval
            if ( $_POST['delivery_afternoon_interval'] && is_numeric( $_POST['delivery_afternoon_interval'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_delivery_afternoon_interval", esc_html( $_POST['delivery_afternoon_interval'] ) );
            }

            // Delivery afternoon limit hour
            if ( $_POST['delivery_afternoon_limit'] && preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $_POST['delivery_afternoon_limit'] ) ) {
                update_option( "options_kitchens_{$kitchenIndex}_delivery_afternoon_limit", esc_html( $_POST['delivery_afternoon_limit'] ) );
            }

            // Delivery afternoon open
            update_option( "options_kitchens_{$kitchenIndex}_delivery_afternoon_open", boolval( $_POST['delivery_afternoon_open'] ) );
            

            return wp_redirect( esc_url_raw( wp_get_referer() ) );

        }

        /**
         * Load scripts and styles.
         */
        public function loadMyKitchenPageScripts( $hook ) {

            if ( $hook != 'toplevel_page_my-kitchen' ) {
                return;
            }

            // ACF
            wp_enqueue_script('acf-pro-input');
            wp_enqueue_style('acf-pro-input');

            // Custom
            wp_enqueue_style( 'my-kitchen-styles',  ASSETS_DIR . '/Styles/admin-my-kitchen.css', false, CURR_VERSION );
            wp_enqueue_script( 'my-kitchen-js',  ASSETS_DIR . '/JS/admin-my-kitchen.js', [ 'jquery' ], CURR_VERSION, true );
            wp_localize_script( 'my-kitchen-js', 'myKitchenConfig', [ 'postUrl' => admin_url( 'admin-ajax.php' ) ] );

        }

        /**
         * Check whether the current user is a kitchen manager.
         */
        private function currentUserIsKitchenManager( ) {

            return in_array( 'kitchen_manager', $this->getCurrentUserRoles( ) );

        }

        /**
         * Get the roles of the current user.
         */
        private function getCurrentUserRoles( ) {

            if ( ! is_user_logged_in( ) ) {
                return [ ];
            }

            $user  = wp_get_current_user( );
            $roles = (array) $user->roles;
            return $roles;

        }

        /**
         * Get the kitchen IDs for the current user.
         */
        public static function getCurrentUserKitchens(  ) {

            $userID = get_current_user_id( );
            $userKitchenIDs = (array) get_field('kitchen_for_user', "user_{$userID}");
            $userKitchenIDs = array_map( function ( $kitchenID ) {
                return $kitchenID;
            }, $userKitchenIDs );

            return $userKitchenIDs;

        }

    }
        
}

WCKitchenManagerController::getInstance( );