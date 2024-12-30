<?php

/**
 * Customize the admin panel.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

namespace Invbit\Core;

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

if ( ! class_exists( __NAMESPACE__ .'\AdminController' ) ) {

    class AdminController {

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
            
            add_filter( 'login_enqueue_scripts',             [ $this, 'enqueueLoginStyles'      ]     );
            add_filter( 'admin_enqueue_scripts',             [ $this, 'enqueueAdminScripts'     ]     );
            add_filter( 'login_headerurl',                   [ $this, 'customizeLoginPageLink'  ]     );
            add_filter( 'admin_footer_text',                 [ $this, 'customizeAdminFooter'    ]     );
            add_filter( 'wp_before_admin_bar_render',        [ $this, 'hideAdminBarItems'       ]     );
            add_filter( 'bulk_actions-edit-shop_order', [ $this, 'handleOrderBulkActions'      ], 99999 );

            add_action( 'init',                              [ $this, 'addPanelRoles'           ], 10 );
            add_action( 'admin_menu',                        [ $this, 'addDeliveredOrdersPage'  ], 10 );
            add_action( 'admin_menu',                        [ $this, 'hideWPUpdateNAG' ] );
            add_action( 'admin_head',                        [ $this, 'customizeAdminHead' ] );
            remove_action( 'admin_footer-edit.php', 'seur_custom_bulk_admin_footer' );

        }

        /**
         * Customize logo at login page.
         */
        public function enqueueLoginStyles( ) : void {

            wp_enqueue_style(  'login-css', ASSETS_DIR . '/Styles/login.css', false, CURR_VERSION );

        }

        /**
         * Enqueue admin scripts.
         */
        public function enqueueAdminScripts( ) : void {

            wp_enqueue_script( 'admin-js',  ASSETS_DIR . '/JS/admin.js', [ 'jquery' ], CURR_VERSION, true );
            wp_enqueue_script( 'qrcode-js',  ASSETS_DIR . '/JS/qrcode.min.js', [ 'jquery' ], CURR_VERSION, true );
            wp_localize_script( 'admin-js', 'adminConfig', [ 'postUrl' => admin_url( 'admin-ajax.php' ) ] );

        }

        /**
         * Customize logo link at login page.
         */
        public function customizeLoginPageLink( ) : string {

            return 'https://www.invbit.com';

        }

        /**
         * Customize footer for the admin panel.
         */
        public function customizeAdminFooter( ) : void {

            print '<span id="InvbitCopy">';
            print   'Desarrollado por <a href="https://www.invbit.com" target="_blank">Invbit</a>.';
            print '</span>';

        }

        /**
         * Hide the icon and options of Wordpress at the upper-left of the admin panel.
         */
        public function hideAdminBarItems( ) : void {

            global $wp_admin_bar;
            $user = wp_get_current_user();

            $wp_admin_bar->remove_menu( 'wp-logo' );
            
            if( $user->roles[0] == 'kitchen_manager' ) {
                $wp_admin_bar->remove_node('new-content');
            }

        }

        /**
         * Handle actions in bulk action drop down in admin's orders listing screen.
         */
        public function handleOrderBulkActions( array $actions ) {

            $_actions = [];
            foreach ($actions as $key => $name) {
                if ($key === 'trash') {
                    continue;
                }
                $_actions[$key] = $name;
            }
            $_actions['mark_seur-label']    = _('Mark Awaiting SEUR Label', 'seur' );
            $_actions['mark_seur-shipment'] = _('Mark Awaiting SEUR Shipment', 'seur' );
            $_actions['seur-createlabel']   = _('Create SEUR Label (Only 1 label per order)', 'seur' );
            $_actions['trash']              = 'Mover a papelera';
        
            return $_actions;

        }

        /**
         * Add new panel roles
         */
        public function addPanelRoles() {
            // $wpRoles = new \WP_Roles();
            // $wpRoles->remove_role('kitchen_manager');
            // $wpRoles->remove_role('kitchen_rider');

            if ( ! wp_roles()->is_role( 'kitchen_rider' ) ) {
                add_role( 'kitchen_rider', __( 'Rider', 'betheme' ), [
                    'read'                 => true,
                    'view_admin_dashboard' => true,
                    'deliver_orders'       => true
                ] );
            }

            if ( ! wp_roles()->is_role( 'kitchen_manager' ) ) {
                add_role( 'kitchen_manager', __( 'Responsable de cocina', 'betheme' ), [
                    'read'                         => true,
                    'view_admin_dashboard'         => true,
                    'view_kitchen_orders'          => true,

                    'assign_shop_order_terms'      => true,
                    'delete_others_shop_orders'    => true,
                    'delete_private_shop_orders'   => true,
                    'delete_published_shop_orders' => true,
                    'delete_shop_order'            => true,
                    'delete_shop_order_terms'      => true,
                    'delete_shop_orders'           => true,
                    'edit_others_shop_orders'      => true,
                    'edit_private_shop_orders'     => true,
                    'edit_published_shop_orders'   => true,
                    'edit_shop_order'              => true,
                    'edit_shop_order_terms'        => true,
                    'edit_shop_orders'             => true,
                    'manage_shop_order_terms'      => true,
                    'publish_shop_orders'          => true,
                    'read_private_shop_orders'     => true,
                    'read_shop_order'              => true,
                ] );
            }
        }

        /**
         * Add admin page for "Kitchen Riders"
         */
        public function addDeliveredOrdersPage() {
            add_menu_page( 
                __( 'Pedidos pendientes de entrega', 'betheme' ),
                __( 'Pedidos', 'betheme' ),
                'deliver_orders',
                'deliver_orders',
                function() {
                    include TEMPLATES_PATH . '/Admin/deliver-orders-page.php';
                },
                'dashicons-editor-ul',
                // 'data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20height%3D%2224%22%20viewBox%3D%220%200%20400%20400%22%3E%3Cpath%20d%3D%22M163%2045l4%201%207%203%201%201-2%201h-6c-6%200-13%202-18%204-8%202-14%206-20%2012-6%205-10%2012-14%2018a98%2098%200%2000-14%2039v11c0%2010%203%2020%208%2030l12%2015%209%2010a199%20199%200%200027%2023l19%2013c5%203%2011%205%2016%206%206%202%2011%201%2017-1a210%20210%200%200076-60c6-8%2010-17%2011-26l1-19c-1-9-3-18-7-27a124%20124%200%2000-23-34c-6-6-13-9-22-11-5-2-11-3-16-3h-4v-2l2-1%2012-2%201-1h4l7%201a79%2079%200%200150%2028%20103%20103%200%200123%2047l1%2010%201%206a105%20105%200%2001-23%2064c-5%206-11%2012-18%2017l-22%2017-23%2018-4%203h-1%201l4-3c6-4%2012-6%2019-7h9l5%201c3%201%204%204%202%207-2%205-5%209-10%2013l-11%205-14%203c-4%201-6%203-7%207l-2%2015v16l-6%2018-2%203-1%206c0%205-2%208-6%2011l-11%203-1%201h-11l-8-2-7-4c-3-2-4-5-4-8%201-4-1-6-2-9a64%2064%200%2001-7-34l-1-16c-1-4-3-7-7-8l-14-3-6-2c-5-1-8-4-10-8l-5-7c-2-4-1-7%202-8l3-1%2015%201c6%202%2011%204%2016%208l3%202v-1l-7-5-33-25-18-15c-10-10-17-21-23-34-3-8-6-18-7-27l-1-12a97%2097%200%200133-71c11-10%2025-17%2040-20l4-1h14zM390%20198l-4%204-1%201c-3%201-3%204-2%207l1%206-1%201h-1l-7-4h-3l-7%203-2%201v-2l1-6c1-2%200-4-1-5l-5-5-1-1%202-1%207-1%203-2%203-8h2l3%207c1%202%202%203%204%203l9%201v1zM10%20195l9-1c2%200%203-1%203-2l4-7%201-2%201%202a343%20343%200%20016%209l7%201h1l1%202-4%203-1%201c-2%201-3%203-2%206l1%206v2h-2l-6-4h-5l-6%203-1%201-1-2a219%20219%200%20011-10l-7-7v-1z%22%20fill%3D%22%23e7df4f%22%2F%3E%3C%2Fsvg%3E',
                6
            );
        }

        /**
         * Hide WP update notice
         */
        public function hideWPUpdateNAG( ) {

            if ( ! IS_SUPERADMIN ) {
                remove_action( 'admin_notices', 'update_nag', 3 );
            }

        }

        /**
         * Add tags to admin head
         */
        public function customizeAdminHead( ) {

            if ( get_current_screen( )->id === 'edit-shop_order'  ) {
                $mins = (int) get_field( 'admin_orders_reload', 'options' );
                if ( $mins > 0 ) {
                    $mins *= 60;
                    print "<meta http-equiv='Refresh' content='$mins'>";
                }
            }

        }

    }
        
}

AdminController::getInstance( );