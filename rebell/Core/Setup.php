<?php namespace Invbit\Core;

/**
 * Set up theme.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\Setup' ) ) {

    class Setup {

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

            $this->superadmins = [ 'Webmaster', 'base', 'desarrollo' ];

            $this->setupConstants( );

            add_action( 'init',                  [ $this, 'updateAppSettings' ] );
            add_action( 'init',                  [ $this, 'hideNotices' ] );
            add_action( 'acf/init',              [ $this, 'addOptionsPages' ] );
            add_action( 'admin_init',            [ $this, 'addCustomColorSchemes' ] );
            add_action( 'user_register',         [ $this, 'setDefaultAdminColorScheme' ] );
            add_filter( 'wp_enqueue_scripts',    [ $this, 'enqueueScriptsAndStyles' ], 101 );
            add_filter( 'admin_enqueue_scripts', [ $this, 'enqueueAdminScriptsAndStyles' ], 101 );
            add_filter( 'json_prepare_post',     [ $this, 'encodeCustomFieldsForJSONAPI' ] );
            add_filter( 'after_setup_theme',     [ $this, 'setTextDomain' ] );
            add_filter( 'wpcf7_validate_email',  [ $this, 'validateEmailAgainstBots' ], 20, 2 );

            add_filter( 'wp_nav_menu_objects',   [ $this, 'removePathFromAnchorLinks' ], 10, 2 );
            add_filter( 'admin_menu',            [ $this, 'removeAdminMenuItems' ], 999 );
            add_action( 'admin_bar_menu',        [ $this, 'removeAdminBarNodes' ], 999 );
            add_filter( 'login_redirect',        [ $this, 'loginRedirect'       ], 10, 3);
            add_action( 'wp_dashboard_setup', function( ) {
                remove_meta_box( 'dashboard_php_nag', 'dashboard', 'normal' );
            } );

        }

        private function setupConstants( ) {

            define( 'CHILD_THEME_URI', get_stylesheet_directory_uri( ) );
            define( 'ASSETS_DIR', CHILD_THEME_URI . '/Core/Assets/' );
            define( 'TEMPLATES_DIR', 'Core/Templates/' );
            define( 'TEMPLATES_PATH',   get_theme_file_path( ) . '/Core/Templates' );
            define( 'CURR_VERSION', '1.1.1' );
            define( 'REQUIRED_APP_VERSION', 2 );
            define( 'MENUS_CATEGORY', 87 );

            define( 'WHITE_LABEL', false );
            define( 'STATIC_IN_CHILD', false );

            define( 'ALLOW_REST_AUTH', false );

            define( 'IS_SUPERADMIN', in_array( wp_get_current_user( )->user_login, $this->superadmins ) );
            define( 'IS_DEV_ENV', $_SERVER['HTTP_HOST'] === 'rebell.invbit.systems' );

        }

        public function updateAppSettings( ) {

            $appSettings = get_option( 'ionic_ecommerce_app_settings' );
            $appSettings[ 'minimum_order_amount' ] = get_field( 'min_order_qty', 'options' );
            $appSettings[ 'whatsappNumber' ] = get_field( 'whatsapp_number', 'options' );

            // this will prompt the app user to update the app from the app store.
            $appSettings[ 'minimumAppVersion' ] = REQUIRED_APP_VERSION;

            update_option( 'ionic_ecommerce_app_settings', $appSettings );

        }

        public function hideNotices( ) {

            if ( IS_SUPERADMIN ) return;

            remove_all_actions( 'admin_notices' );

        }

        public function addOptionsPages( ) {

            if ( !function_exists( 'acf_add_options_page' ) ) return;

            acf_add_options_sub_page( [
                'page_title'  => 'Offers Page Content',
                'menu_title'  => 'offers-page-content',
                'parent_slug' => 'edit.php?post_type=offers',
            ] );

            acf_add_options_page( [
                'page_title'  => __( 'Configura Rebell', 'betheme' ),
                'menu_title'  => __( 'Configura Rebell', 'betheme' ),
                'icon_url'    => 'data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20height%3D%2224%22%20viewBox%3D%220%200%20400%20400%22%3E%3Cpath%20d%3D%22M163%2045l4%201%207%203%201%201-2%201h-6c-6%200-13%202-18%204-8%202-14%206-20%2012-6%205-10%2012-14%2018a98%2098%200%2000-14%2039v11c0%2010%203%2020%208%2030l12%2015%209%2010a199%20199%200%200027%2023l19%2013c5%203%2011%205%2016%206%206%202%2011%201%2017-1a210%20210%200%200076-60c6-8%2010-17%2011-26l1-19c-1-9-3-18-7-27a124%20124%200%2000-23-34c-6-6-13-9-22-11-5-2-11-3-16-3h-4v-2l2-1%2012-2%201-1h4l7%201a79%2079%200%200150%2028%20103%20103%200%200123%2047l1%2010%201%206a105%20105%200%2001-23%2064c-5%206-11%2012-18%2017l-22%2017-23%2018-4%203h-1%201l4-3c6-4%2012-6%2019-7h9l5%201c3%201%204%204%202%207-2%205-5%209-10%2013l-11%205-14%203c-4%201-6%203-7%207l-2%2015v16l-6%2018-2%203-1%206c0%205-2%208-6%2011l-11%203-1%201h-11l-8-2-7-4c-3-2-4-5-4-8%201-4-1-6-2-9a64%2064%200%2001-7-34l-1-16c-1-4-3-7-7-8l-14-3-6-2c-5-1-8-4-10-8l-5-7c-2-4-1-7%202-8l3-1%2015%201c6%202%2011%204%2016%208l3%202v-1l-7-5-33-25-18-15c-10-10-17-21-23-34-3-8-6-18-7-27l-1-12a97%2097%200%200133-71c11-10%2025-17%2040-20l4-1h14zM390%20198l-4%204-1%201c-3%201-3%204-2%207l1%206-1%201h-1l-7-4h-3l-7%203-2%201v-2l1-6c1-2%200-4-1-5l-5-5-1-1%202-1%207-1%203-2%203-8h2l3%207c1%202%202%203%204%203l9%201v1zM10%20195l9-1c2%200%203-1%203-2l4-7%201-2%201%202a343%20343%200%20016%209l7%201h1l1%202-4%203-1%201c-2%201-3%203-2%206l1%206v2h-2l-6-4h-5l-6%203-1%201-1-2a219%20219%200%20011-10l-7-7v-1z%22%20fill%3D%22%23e7df4f%22%2F%3E%3C%2Fsvg%3E'
            ] );

        }

        public function addCustomColorSchemes( ) {

            wp_admin_css_color( 'rebell', __( 'Rebell' ), ASSETS_DIR . '/Styles/rebell-scheme-colors.css', [ '#000000', '#FFFFFF', '#d2c62e' ] );

        }

        public function setDefaultAdminColorScheme( $userID ) {

            update_user_meta( $userID, 'admin_color', 'rebell' );

        }

        public function enqueueScriptsAndStyles( ) {

            if ( is_rtl( ) ) wp_enqueue_style( 'mfn-rtl', get_template_directory_uri( ) . '/rtl.css' );

            // Enqueue the child stylesheet
            wp_dequeue_style( 'style' );
            wp_enqueue_style( 'styles-css', ASSETS_DIR . 'Styles/styles.css' );
            wp_enqueue_style( 'main-css', ASSETS_DIR . 'Styles/main.css' );
            wp_enqueue_style( 'dashicons' );

            // Enqueue javascript
            wp_enqueue_script( 'micromodal-js', ASSETS_DIR . 'JS/plugins/micromodal.min.js', [ ], null, true );
            wp_enqueue_script( 'main-js', ASSETS_DIR . 'JS/main.js', [ 'jquery', 'micromodal-js' ], null, true );
            wp_enqueue_script( 'custom', get_stylesheet_directory_uri() . '/js/custom.js', [ 'jquery' ], null, true );
            wp_localize_script( 'custom', 'rebell', [
                'outOfStock'  => __( 'No hay stock disponible', 'betheme' ),
                'customProps' => CustomizeWooCommerce::$propTypes,
            ] );

        }

        public function enqueueAdminScriptsAndStyles( ) {

            if ( ! is_admin( ) ) return;

            // wp_enqueue_script( 'admin-js', ASSETS_DIR . 'JS/admin.js', [ 'jquery' ], time(), true );
            wp_enqueue_style( 'admin-css', ASSETS_DIR . 'Styles/admin.css', time() );

        }

        public function encodeCustomFieldsForJSONAPI( $post ) {

            $acf = get_fields($post['ID']);

            if (isset($post)) {
              $post['acf'] = $acf;
            }

            return $post;

        }

        public function setTextDomain( ) {

            load_child_theme_textdomain( 'betheme',  get_stylesheet_directory( ) . '/languages' );
            load_child_theme_textdomain( 'mfn-opts', get_stylesheet_directory( ) . '/languages' );

        }

        public function validateEmailAgainstBots( $result, $tag ) {

            $bot_email = isset( $_POST['email'] ) ? trim( $_POST['email'] ) : '';

            if ( !empty($bot_email) ) {
                $result->invalidate( $tag, "Maybe you are a bot, try again if not" );
            }

            return $result;

        }

        public function removePathFromAnchorLinks( $items, $args )  {

            if ( $args->theme_location != 'main-menu' ) return $items;

            $currentPath = parse_url( $_SERVER[ 'REQUEST_URI' ], PHP_URL_PATH );

            foreach ( $items as $item ) {
                $url = parse_url( $item->url );
                if ( empty( $url[ 'fragment' ] ) ) continue;

                $pathArray = explode( '/', $url[ 'path' ] );
                $lastPath = '/' . end( $pathArray );

                if ( in_array( $currentPath, [ $url[ 'path' ], $lastPath ] ) )
                    $item->url = '#' . $url[ 'fragment' ];
            }

            return $items;

        }


        public function removeAdminMenuItems( ) {

            if ( IS_SUPERADMIN ) return;

            // Debug:
            // echo '<pre style="padding-left: 10rem">' . print_r( $GLOBALS[ 'menu' ], true) . '</pre>';

            remove_menu_page( 'index.php' );                            // Dashboard
            remove_menu_page( 'betheme' );                              // Betheme
            remove_menu_page( 'separator1' );                           //
            remove_menu_page( 'edit.php' );                             // Posts
            remove_menu_page( 'edit.php?post_type=geofencing' );        // Geofencing
            // remove_menu_page( 'edit.php?post_type=page' );              // Pages
            remove_menu_page( 'edit-comments.php' );                    // Comments
            remove_menu_page( 'edit.php?post_type=cookielawinfo' );     // Cookie Law Info
            remove_menu_page( 'edit.php?post_type=slide' );             // Slide
            remove_menu_page( 'wpcf7' );                                // Contact form 7
            remove_menu_page( 'contact-form-listing' );                 // Contact form 7 Listing
            remove_menu_page( 'separator-woocommerce' );                //
            remove_menu_page( 'separator2' );                           //
            remove_menu_page( 'options-general.php' );                  // Settings
            remove_menu_page( 'plugins.php' );                          // Plugins
            // remove_menu_page( 'tools.php' );                            // Tools
            remove_menu_page( 'wp-mail-smtp' );                         // WP Mail SMTP
            remove_menu_page( 'onesignal-push' );                       // OneSignal Push
            remove_menu_page( 'ionic-ecommerce' );                      // Ionic Ecommerce
            remove_menu_page( 'banner-settings' );                      // Ionic Ecommerce Banner Settings
            remove_menu_page( 'edit.php?post_type=acf-field-group' );   // ACF
            remove_menu_page( 'separator-last' );                       //
            remove_menu_page( 'wpseo_dashboard' );                      // Yoast
            remove_menu_page( 'more_features-wbcr_dan' );               // More Features
            remove_menu_page( 'duplicator' );                           // Duplicator
            remove_menu_page( 'revslider' );                            // Revolution Slider
            remove_menu_page( 'seur' );                                 // Seur
            remove_menu_page( 'wpfastestcacheoptions' );                // Fastest Cache
            remove_menu_page( 'itsec' );                                // IT Sec
            remove_menu_page( 'wp-user-avatar' );                       // User Avatar
            remove_menu_page( 'loco' );                                 // Loco Translate

        }




        public function removeAdminBarNodes( ) {

            if ( IS_SUPERADMIN ) return;

            global $wp_admin_bar;

            $wp_admin_bar->remove_node( 'updates' );
            $wp_admin_bar->remove_node( 'new-content' );
            $wp_admin_bar->remove_node( 'ionic-ecommerce' );
            $wp_admin_bar->remove_node( 'comments' );
            $wp_admin_bar->remove_node( 'view' );

        }

        /**
         * Redirect user after login.
         */
        public function loginRedirect( $redirectTo, $request, $user ) {

            if ( in_array( 'kitchen_rider', $user->roles ) ) {
                $redirectTo = home_url( ) . '/wp-admin/admin.php?page=deliver_orders';
            }

            return $redirectTo;

        }



    }

}

Setup::getInstance( );