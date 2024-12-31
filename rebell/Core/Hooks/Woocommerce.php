<?php

namespace Invbit\Core;

/**
 * Customize WC via hooks.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if (!class_exists(__NAMESPACE__ . '\CustomizeWooCommerce')) {

    class CustomizeWooCommerce
    {

        private static $singleton;
        public static $propTypes;

        /**
         * Singleton
         *
         * @static array     $singleton
         * @return instance  The one true instance.
         */
        public static function getInstance()
        {

            if (!isset(self::$singleton)) self::$singleton = new self;

            return self::$singleton;
        }

        /**
         * Constructor
         */
        public function __construct()
        {

            self::$propTypes = [
                'toppings'    => 'Toppings seleccionados',
                'ingredients' => 'Ingredientes seleccionados',
                'side_dishes' => 'Acompañamientos seleccionados',
                'fries'       => 'Patatas seleccionadas',
                'sauces'      => 'Salsas seleccionadas',
                'drinks'      => 'Bebidas seleccionadas',
                'extras'      => 'Extras seleccionados'
            ];

            add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'quantityInputsForAddToCartLink' ], 10, 2 );
            add_filter( 'woocommerce_product_single_add_to_cart_text', [ $this, 'customizeAddToCartButtonText' ]);
            add_filter( 'woocommerce_subcategory_count_html', '__return_false' );
            add_action('show_actions_for_related_products', [$this, 'showActionsForRelatedProducts']);
            add_action('show_product_characteristics_tags', [$this, 'showProductCharacteristicsTags']);
            // add_action( 'woocommerce_coupon_is_valid', [ $this, 'validateRestrictedCoupons' ], 9999, 3 );
            // add_filter( 'woocommerce_get_price_html', [ $this, 'getProductPriceHTML' ], 9999, 2 );
            // add_action( 'woocommerce_before_calculate_totals', [ $this, 'modifyProductPriceForCart' ], 9999 );
            add_action('wp_ajax_rebell_complete_order', [$this, 'markOrderCompleted']);
            add_action('wp_ajax_nopriv_rebell_complete_order', [$this, 'markOrderCompleted']);
            add_action('wp_ajax_rebell_process_order', [$this, 'markOrderProcessing']);
            add_action('wp_ajax_nopriv_rebell_process_order', [$this, 'markOrderProcessing']);
            add_action('wp_ajax_rebell_cancel_order', [$this, 'markOrderCancelled']);
            add_action('wp_ajax_nopriv_rebell_cancel_order', [$this, 'markOrderCancelled']);
            add_action('wp_ajax_get_product_modal_content', [$this, 'getProductModalContent']);
            add_action('wp_ajax_nopriv_get_product_modal_content', [$this, 'getProductModalContent']);
            add_action('admin_post_rebell_assign_order_to_rider', [$this, 'assignRiderToOrder']);
            add_action('admin_post_nopriv_rebell_assign_order_to_rider', [$this, 'assignRiderToOrder']);
            add_action('admin_post_rebell_complete_order_by_rider', [$this, 'completeOrderByRider']);
            add_action('admin_post_nopriv_rebell_complete_order_by_rider', 'auth_redirect');
            add_filter('rebell_woocommerce_add_to_cart_validation', [$this, 'validateAddProductToCart'], 10, 3);
            add_filter('woocommerce_email_classes', [$this, 'registerShippingMail'], 99, 1);

            add_filter('woocommerce_hidden_order_itemmeta', [$this, 'hideBasePriceFromOrderAtAdminPanel']);
            add_action('woocommerce_before_order_itemmeta', [$this, 'renderOrderItemMetas'], 10, 3);
            add_filter('woocommerce_display_item_meta', [$this, 'renderOrderItemMetasForMail'], 10, 3);

            add_filter('woocommerce_rest_prepare_shop_order_object', [$this, 'exposeOrderItemCustomProperties']);

            add_filter('woocommerce_add_cart_item_data', [$this, 'setUniqueKeyToCartItemsData'], 10, 2);

            add_filter('get_terms', [$this, 'sortWCCategories'], 10, 4);

            add_filter('rest_product_collection_params', [$this, 'increaseRestPerPageLimit']);
            add_filter('auth_cookie_expiration',         [$this, 'increaseCookieExpirationTime'], 999, 3);

            // Remove useless hooks to avoid admin to click the wrong functionality.
            remove_action('woocommerce_coupon_options', 'add_coupon_notification_checkbox', 10, 0);
            add_action('admin_head', function () {
                remove_meta_box('onesignal_notif_on_post', 'post', 'high');
                remove_meta_box('onesignal_notif_on_post', 'product', 'high');
            });

            if (IS_SUPERADMIN) {
                add_action('restrict_manage_posts', [$this, 'showFilterInAdminToShowOrdersByKitchen'], 20);
                add_action('pre_get_posts',         [$this, 'filterOrdersByKitchensZipCodesInAdmin']);
            }

            add_action('init', function () {
                add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
            });

            add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 3);

            add_filter('woocommerce_rest_product_object_query', function ($args) {
                return array_merge($args, ['posts_per_page' => 99999]);
            });

            add_filter('manage_shop_order_posts_columns', [$this, 'setShopOrderColumns'], 99);
            add_action('manage_shop_order_posts_custom_column', [$this, 'showShopOrderCustomColumns'], 99, 2);
            add_action( 'woocommerce_product_query', [ $this, 'handleProductsQuery' ] );

            // Fix the prices showing without taxes...
            add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );


            //codigo ajax php postal ajax - invbit
            add_action('wp_ajax_handle_save_zipcode_ajax', [$this, 'handleSaveZipcodeAjax']);
            add_action('wp_ajax_nopriv_handle_save_zipcode_ajax', [$this, 'handleSaveZipcodeAjax']);

            //codigo ajax php login -invbit
            add_action('wp_ajax_handle_login_ajax', [$this, 'handleLoginAjax']);
            add_action('wp_ajax_nopriv_handle_login_ajax', [$this, 'handleLoginAjax']);

        }

        /**
         *  Ajax para el modal login del usuario - invbit  
         */
        public function handleLoginAjax()
        {
            check_ajax_referer('login_action', 'login_nonce');
        
            $email = sanitize_email($_POST['email']);
            $password = $_POST['password'];
        
            $user = wp_authenticate($email, $password);
        
            if (is_wp_error($user)) {
                $error_message = $user->get_error_message();
                wc_add_notice($error_message, 'error');
                wp_send_json_error(['notices' => $this->get_formatted_notices()]);
            } else {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
                wp_send_json_success(['message' => 'Login successful']);
            }
        }
        
        private function get_formatted_notices() {
            ob_start();
            wc_print_notices();
            $notices = ob_get_clean();
            wc_clear_notices();
            return $notices;
        }

        /**
         *  Ajax para el modal del codigo zip - invbit  
         */
        public function handleSaveZipcodeAjax()
        {
            $zipcodeController = new ZipcodeController();
            $result = $zipcodeController->handleSaveZipcode(true);
            
            if ($result['success']) {
                wp_send_json_success(['redirect' => $result['redirect']]);
            } else {
                wp_send_json_error(['notices' => $result['notices']]);
            }
        }


        /**
         *  Create new WC email.
         */
        public function registerShippingMail($emails)
        {

            require_once 'WCCustomerShippingOrder.php';

            $emails['WC_Customer_Cancel_Order'] = new WCCustomerShippingOrder();

            return $emails;
        }


        /**
         *  Render the quantity inputs for the Woocommerce loop's add to cart link.
         */
        public function quantityInputsForAddToCartLink($html, $product)
        {

            $sku = (isset($sku) ? esc_attr($sku) : '');

            if ($product && $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock() && !$product->is_sold_individually()) {
                $html = '<form action="' . wc_get_cart_url() . esc_url($product->add_to_cart_url()) . '" class="cart" method="post" enctype="multipart/form-data">';
                $html .= woocommerce_quantity_input(array(), $product, false);
                $html .= '<button type="submit" class="button alt ajax_add_to_cart add_to_cart_button" data-product_id="' . get_the_ID() . '" data-product_sku="' . $sku . '">' . esc_html($product->add_to_cart_text()) . '</button>';
                $html .= '</form>';
            }
            return $html;
        }

        /**
         *  Customize add to cart's button text.
         */
        public function customizeAddToCartButtonText($var)
        {

            global $post;

            $product = wc_get_product($post->ID);

            if (is_object_in_term($post->ID, 'product_cat', 'menus')) {
                $button_text = str_replace('.', ',', $product->get_price());
                return __('Añadir por ' . $button_text . ' €', 'woocommerce');
            } else {
                return $var;
            }
        }

        public function showActionsForRelatedProducts()
        {

            global $main_post;

            $isMenu = false;
            if (is_tax('product_cat')) {
                $parentCat = get_term(get_queried_object()->parent, 'product_cat');
                $isMenu    = !empty($parentCat->slug) &&  $parentCat->slug == 'menus';
            }

            if ((isset($main_post) and has_term(MENUS_CATEGORY, 'product_cat', $main_post->get_id())))
                $isMenu    = true;

            if ($isMenu) : ?>

                <a href="<?php the_permalink(); ?>" class="button product_type_simple" rel="nofollow" style="margin-bottom:1em"><?= __('Ver platos', 'sage') ?></a>

            <?php else :

                remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
                if (!mfn_opts_get('shop-button'))
                    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);

                do_action('woocommerce_after_shop_loop_item');

            endif;
        }


        public function showProductCharacteristicsTags()
        {

            global $product;

            if (empty($product->get_attributes()['pa_caracteristicas'])) return;

            print '<div class="TypeOfFood">';
            foreach ($product->get_attributes()['pa_caracteristicas']->get_terms() as $idx => $attr) {
                if ($idx >= 5) break;
                print "<span class='FoodTypeTag'>{$attr->name}</span>";
            }
            print '</div>';
        }


        /**
         *  Validate if the coupon has restrictions and if they're met.
         */
        public function validateRestrictedCoupons($result, $coupon, $instance)
        {

            if (count($coupon->get_email_restrictions()) <= 0) return $result;

            $currentUser = wp_get_current_user();
            $userEmail   = $currentUser->user_email;
            $domain      = explode('@', $userEmail)[1];

            foreach ($coupon->get_email_restrictions() as $allowedAddress) {
                if ($allowedAddress == $userEmail) return true;

                $addressParts = explode('@', $allowedAddress);
                if (($addressParts[0] == '*') and ($addressParts[1] == $domain))
                    return true;
            }

            return false;
        }


        /**
         *  Modify regular price being shown.
         */
        public function getProductPriceHTML($priceHTML, $_product)
        {

            if (is_admin() or ($_product->get_price() === '')) return $priceHTML;

            if (!wc_current_user_has_role('customer')) return $priceHTML;

            // Apply a discount for logged in customers.
            $regularPrice = $_product->get_regular_price(); // wc_get_price_to_display( $_product );
            $salePrice    = $regularPrice * 0.8;
            return sprintf(
                '<span class="price"><del>%s</del> <ins>%s</ins></span>',
                wc_price($regularPrice),
                wc_price($salePrice)
            );
        }


        /**
         *  Modify regular price for the cart items.
         */
        public function modifyProductPriceForCart($cart)
        {

            if (is_admin() and !defined('DOING_AJAX')) return;
            if (did_action('woocommerce_before_calculate_totals') >= 2) return;
            // if ( ! wc_current_user_has_role( 'customer' ) ) return;

            // Apply a discount for logged in customers.
            foreach ($cart->get_cart() as $item) {
                $product = $item['data'];
                $price = $product->get_regular_price();
                $item['data']->set_regular_price($price);
                $item['data']->set_price($price * 0.8);
            }
        }

        /**
         * Mark an order as completed.
         */
        public function markOrderCompleted()
        {

            if (
                current_user_can('deliver_orders') &&
                check_admin_referer('woocommerce-complete-order') &&
                isset($_GET['order_id'])
            ) {
                if ($order  = wc_get_order(absint(wp_unslash($_GET['order_id'])))) {
                    // Initialize payment gateways in case order has hooked status transition actions.
                    WC()->payment_gateways();

                    $order->update_status('wc-completed', '', true);
                    do_action('woocommerce_order_edit_status', $order->get_id(), 'wc-completed');
                }
            }

            wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url('admin.php?page=deliver_orders'));

            exit;
        }

        /**
         * Mark an order as processing.
         */
        public function markOrderProcessing()
        {

            if (
                current_user_can('deliver_orders') &&
                check_admin_referer('woocommerce-process-order') &&
                isset($_GET['order_id'])
            ) {
                if ($order  = wc_get_order(absint(wp_unslash($_GET['order_id'])))) {
                    $order->update_status('wc-processing', '', true);
                }
            }

            wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url('admin.php?page=deliver_orders'));

            exit;
        }

        /**
         * Mark an order as cancelled.
         */
        public function markOrderCancelled()
        {

            if (current_user_can('deliver_orders') && isset($_POST['order_id'])) {
                if ($order = wc_get_order(absint(wp_unslash($_POST['order_id'])))) {
                    $user = wp_get_current_user();
                    $note = wc_sanitize_textarea($_POST['cancel_reason']);
                    $order->update_status('wc-cancelled', '', false);
                    $order->add_order_note("{$user->display_name}: $note", false, false);

                    $mailer  = WC()->mailer();
                    $subject = "Pedido Cancelado por Rider: {$user->display_name}";
                    $message = "<p>El rider \"{$user->display_name}\" ha cancelado el pedido "
                             . "con número {$order->get_id( )} con el siguiente motivo:</p>"
                             . "<pre>{$note}</pre>";
                    $mailer->send(
                        $mailer->get_emails( )['WC_Email_New_Order']->recipient,
                        "Pedido Cancelado por Rider: {$user->display_name}",
                        (new \WC_Email)->style_inline($mailer->wrap_message($subject, $message)),
                        ['Content-Type: text/html; charset=UTF-8']
                    );
                }
            }

            wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url('admin.php?page=deliver_orders'));

            exit;
        }

        public function getProductModalContent()
        {
            $productID = $_GET['product_id'];
            $product   = wc_get_product($productID);

            global $post; 
            $post = get_post( $productID );
            setup_postdata( $post );


            $_product     = wc_get_product(get_the_ID());
            $_thumbSize   = apply_filters('woocommerce_product_thumbnails_large_size', 'full');
            $_thumbID     = $_product->get_image_id();
            $_image       = wp_get_attachment_image_src($_thumbID, $_thumbSize);
            $_imageHeight = 300;
            $imageStyles  = preg_replace("/;\s+/i", '; ', "
                height:{$_imageHeight}px;
                background: url({$_image[0]}) no-repeat center;
                background-size: cover;
            ");

            ob_start() ?>

                <?php do_action('woocommerce_before_main_content'); ?>

                <?php wc_get_template_part('content', 'single-product'); ?>

                <?php do_action('woocommerce_after_main_content'); ?>

                <?php do_action('woocommerce_sidebar'); ?>

            <?php $content = ob_get_clean();

            if ( isset( $_image[0] ) ) : ?>
                <?php ob_start() ?>
                <div class="section mcb-section full-width">
                    <div class="section_wrapper mcb-section-inner">
                        <div class="wrap mcb-wrap mcb-wrap-ncogoxugi one valign-middle bg-cover clearfix"
                            style="<?= $imageStyles ?>">
                            <div class="mcb-wrap-inner"></div>
                        </div>
                    </div>
                </div>
                <?php $header = ob_get_clean() ?>
            <?php endif;

            wp_reset_postdata();
        
            wp_send_json(compact('header', 'content'));
        }

        /**
         * Assign rider to order.
         */
        public function assignRiderToOrder()
        {

            $order   = wc_get_order($_POST['order_id'] ?? null);
            $riderID = $_POST['rider_id'] ?? null;

            if ($order && $riderID !== null) {
                $order->update_meta_data('order_rider', $riderID);
                $order->save();
            }

            wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url('admin.php?page=deliver_orders'));

            exit;
        }

        /**
         * Complete order by rider.
         */
        public function completeOrderByRider()
        {
            $user  = wp_get_current_user();
            $order = wc_get_order($_REQUEST['order_id'] ?? null);

            if (!$order) {
                exit('<strong>ERROR:</strong>: El pedido no existe. Pora favor, ponte en contacto con la administración.');
            }

            if ($order->get_status() === 'completed') {
                exit('<strong>Error:</strong> El pedido ha sido completado previamente.');
            }

            $orderRider = $order->get_meta('order_rider');

            if (!in_array('kitchen_rider', $user->roles) or (intval($orderRider) !== $user->id)) {
                exit('<strong>Error:</strong> Esta funcionalidad está reservada únicamente para el <i>rider</i> encargado del pedido.');
            }

            WC()->payment_gateways();

            $order->update_status('wc-completed', '', true);
            do_action('woocommerce_order_edit_status', $order->get_id(), 'wc-completed');

            wp_safe_redirect(admin_url('admin.php?page=deliver_orders&order_marked_complete=' . $order->get_id()));

            exit;
        }

        /**
         *  Validate whether the product being passed can be added to cart or not.
         */
        public function validateAddProductToCart($valid, $product_id, $quantity)
        {
            $_product = wc_get_product($product_id);
            if (!$_product->managing_stock()) return $valid;

            if (
                ($quantity > 1) and
                (($_product->get_stock_quantity() - ($quantity - 1)) <= 0)
            ) return false;

            return $valid;
        }


        /**
         *  Get the quantity of a given product ID in the cart.
         */
        public static function getCartItemQuantity($_product)
        {
            foreach (wc()->cart->get_cart() as $key => $item) {
                if ($item['product_id'] != $_product->get_id()) continue;
                return $item['quantity'];
            }
        }


        /**
         *  Hide the base price of an order item @ admin panel.
         */
        public function hideBasePriceFromOrderAtAdminPanel($items)
        {
            $items[] = 'baseprice';
            return $items;
        }


        /**
         *  Set order item's metadata.
         */
        public function renderOrderItemMetas($item_id, $item, $product)
        {

            $metas = $this->_getItemExtrasMetaData($item);

            print ' <small style="display:block">Base: ' . sanitize_text_field($metas->basePrice) . '€</small>';

            foreach (self::$propTypes as $prop => $title) $this->_renderMetaDataTable($metas->extras, $prop, $title);
        }


        /**
         *  Set order item's metadata.
         */
        public function renderOrderItemMetasForMail($html, $item, $args)
        {

            $metas = $this->_getItemExtrasMetaData($item);

            $output = '';
            foreach (self::$propTypes as $type => $title) :
                if (!isset($metas->extras[$type])) continue;
                ob_start(); ?>
                <ul class="wc-item-meta">
                    <?php foreach ($metas->extras[$type] as $prop) : ?>
                        <li>
                            <?= sanitize_text_field($prop['name']) ?>
                            <?= ($prop['price'] != '')
                                ? sanitize_text_field(': +' . $prop['price'] . '€')
                                : ''
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php $output .= ob_get_clean();
            endforeach;

            return $output;
        }


        private function _getItemExtrasMetaData($item)
        {

            $extras    = [];
            $basePrice = null;
            foreach ($item->get_meta_data() as $meta) {
                $data = $meta->get_data();
                if ($data['key'] == 'baseprice') $basePrice = $data['value'];
                foreach (self::$propTypes as $prop => $title)
                    if ($data['key'] == $prop) $extras[$prop] = $data['value'];
            }

            return (object) ['extras' => $extras, 'basePrice' => $basePrice];
        }


        /**
         *  Render the product custom property's name & price in a table.
         */
        private function _renderMetaDataTable($data, $type, $title)
        {

            if (!isset($data[$type])) return;

            ob_start(); ?>

            <table cellspacing="0" class="display_meta"
                style="border-radius:10px;padding:.5rem 1rem;width:100%;background:#f1f1f1"
            >
                <thead>
                    <tr style="font-weight:bold;color:#333">
                        <td colspan="2"><?= $title ?></td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data[$type] as $prop) : ?>
                        <tr style="width:unset">
                            <th><?= sanitize_text_field($prop['name']) ?>:</th>
                            <td>+ <?= sanitize_text_field($prop['price']) ?>€</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php print ob_get_clean();
        }


        /**
         *  Expose order item's custom properties for the REST API.
         */
        public function exposeOrderItemCustomProperties($response)
        {

            $orderData = $response->get_data('id');
            $order     = isset($orderData['id']) ? wc_get_order($orderData['id']) : null;

            if (!$order) return $response;

            $products = [];
            foreach ($order->get_items() as $item) {
                $itemData   = $item->get_data();
                $extras     = [];
                $basePrice  = null;
                foreach ($item->get_meta_data() as $meta) {
                    $data = $meta->get_data();
                    if ($data['key'] == 'baseprice')
                        $basePrice = $data['value'];
                    foreach (self::$propTypes as $prop => $title)
                        if ($data['key'] == $prop) $extras[$prop] = $data['value'];
                }

                // // TODO: REMOVE
                // error_log( print_r( [ 'exposeOrderItemCustomProperties', wp_get_current_user( )->user_login, $item ],1 ) );

                $categories = array_map(function ($cat) {
                    return ['id' => $cat->term_id, 'name' => $cat->name, 'slug' => $cat->slug];
                }, get_the_terms($item['product_id'], 'product_cat'));

                $products[] = array_merge(
                    $itemData,
                    $extras,
                    [
                        'baseprice'       => $basePrice,
                        'categories'      => $categories,
                        'is_customizable' => get_field('is_customizable', $item['product_id'])
                    ]
                );
            }

            $customResponse = $response->get_data( );
            $response->set_data( array_merge( $customResponse, [ 'line_items' => $products ] ) );

            return $response;
        }


        /**
         *  Give cart item's data a unique key.
         */
        public function setUniqueKeyToCartItemsData($cart_item_data, $product_id)
        {

            $unique_cart_item_key = md5(microtime() . rand());
            $cart_item_data['unique_key'] = $unique_cart_item_key;

            return $cart_item_data;
        }


        /**
         *  Sort WC Categories by term_group.
         */
        public function sortWCCategories($terms, $taxonomy, $query_vars, $term_query)
        {

            if (!in_array('product_cat', $taxonomy) or $query_vars['orderby'] != 'term_group')
                return $terms;

            usort( $terms, function ( $term1, $term2 ) {
                $term1->order = get_term_meta( $term1->term_id, 'order', true );
                $term2->order = get_term_meta( $term2->term_id, 'order', true );

                if ( $term1->order == $term2->order ) return 0;
                return $term1->order > $term2->order ? 1 : -1;
            } );

            return $terms;

        }


        /**
         *  Increase the maximum products per page for the API requests.
         */
        public function increaseRestPerPageLimit($params)
        {

            $params['per_page']['maximum'] = 9999;
            return $params;
        }

        public function increaseCookieExpirationTime($expiration, $user_id, $remember)
        {

            $expiration = 4102444800000; // 01-01-2100.
            return $expiration;
        }

        /**
         *  Show the selector to filter orders by group of zip code (kitchen).
         */
        public function showFilterInAdminToShowOrdersByKitchen()
        {

            global $pagenow, $post_type;

            if ( $post_type !== 'shop_order' or $pagenow !== 'edit.php' or ! is_admin( ) ) return;

            $filterID = 'filter_product_by_kitchen';
            $kitchens = Helpers::getAllKitchens( );
            ?>

            <select name="<?= esc_attr( $filterID ) ?>">
                <option value=""><?= esc_html( 'Todas las Cocinas', 'invbit' ) ?></option>
                <?php foreach ( $kitchens as $kitchen ) : ?>
                    <option
                        value="<?= esc_attr( $kitchen[ 'id' ] ) ?>"
                        <?= esc_attr( isset( $_GET[ $filterID ] ) ? selected( $kitchen[ 'id' ], $_GET[ $filterID ], false ) : '' ) ?>
                    ><?= esc_html( $kitchen['name'] ) ?></option>
                <?php endforeach; ?>
            </select>

            <?php

        }

        /**
         *  Filter the orders in the admin panel matching the customer zip code with the ones
         *  in the selected kitchen.
         */
        public function filterOrdersByKitchensZipCodesInAdmin($query)
        {

            global $pagenow, $post_type, $wpdb;

            $filterID = 'filter_product_by_kitchen';


            if (
                !is_admin() or
                !$query->is_admin or
                ($pagenow !== 'edit.php') or
                ($post_type !== 'shop_order') or
                empty($_GET[$filterID])
            ) return;

            if (!isset($query->query_vars['post_type']) or $query->query_vars['post_type'] !== 'shop_order') {
                return;
            }

            $kitchens = array_filter((array) get_field('kitchens', 'options'), function ($kitchen) use ($filterID) {
                return $kitchen['kitchen_id'] == $_GET[$filterID];
            });

            if (count($kitchens) <= 0) {
                /*
                add_action( 'admin_notices', function( ) {
                    ?><div class="notice">
                        <p>No se ha encontrado la cocina seleccionada</p>
                    </div><?php
                } );
                */
                return;
            }

            $kitchen  = array_pop($kitchens);
            $orderIDs = $wpdb->get_col("SELECT DISTINCT post_id FROM {$wpdb->prefix}postmeta
                WHERE meta_key = 'kitchen_for_order' AND meta_value = '{$kitchen['kitchen_id']}'");

            $query->set( 'post__in', $orderIDs );

            $query->set( 'paged', ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 ) );

        }

        /**
         *  Add meta boxes to the Order @ admin panel.
         */
        public function addMetaBoxes()
        {

            add_meta_box('order-type-and-schedule', 'Tipo y Hora', [$this, 'addTypeAndSchedule'], 'shop_order', 'side', 'high');
        }


        /**
         *  Add type and schedule content to the generated metabox in the Order @ admin panel.
         */
        public function addTypeAndSchedule()
        {

            global $post, $thepostid, $theorder;

            if ( ! is_int( $thepostid ) ) $thepostid = $post->ID;

            if ( ! is_object( $theorder ) ) $theorder = wc_get_order( $thepostid );

            $order = $theorder;

            extract(Helpers::getOrderTypeAndSchedule($order));
        ?>

            <ul>
                <?php if ($scheduledFor) : ?>
                    <li class="list-item">
                        <?= Helpers::getOrderTypeIcon($isDelivery) ?>
                        <span><?= $isDelivery ? 'Envío a Domicilio' : 'Recogida en Local' ?></span>
                    </li>
                    <li class="list-item">
                        <svg viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12 20C16.4 20 20 16.4 20 12S16.4 4 12 4 4 7.6 4 12 7.6 20 12 20M12 2C17.5 2 22 6.5 22 12S17.5 22 12 22C6.5 22 2 17.5 2 12C2 6.5 6.5 2 12 2M15.3 16.2L14 17L11 11.8V7H12.5V11.4L15.3 16.2Z" />
                        </svg>
                        <span><strong><?= $scheduledFor ?></strong></span>
                    </li>
                <?php else : ?>
                    <li class="list-item disabled">
                        <span>Sin información</span>
                    </li>
                <?php endif; ?>
            </ul>

            <?php

        }

        /**
         *  Handle the order status changes.
         */
        public function handleOrderStatusChange ( $orderID, $oldStatus, $newStatus ) {

            if ( $newStatus !== 'completed' ) return;

            $order = wc_get_order($orderID);

            $this->_registerNote($order);

            $order->update_meta_data('completed_by', get_current_user_id());

            $order->save( );

        }

        /**
         *  Register order note.
         */
        private function _registerNote($order)
        {

            $note = '¡Pedido entregado al cliente!';
            $user = get_user_by('id', get_current_user_id());

            wp_insert_comment([
                'comment_post_ID'      => $order->get_id(),
                'comment_author'       => $user->display_name,
                'comment_author_email' => $user->user_email,
                'comment_author_url'   => '',
                'comment_content'      => $note,
                'comment_agent'        => 'WooCommerce',
                'comment_type'         => 'order_note',
                'comment_parent'       => 0,
                'comment_approved'     => 1,
            ]);
        }

        /**
         *  Set shop order columns.
         */
        public function setShopOrderColumns($columns)
        {

            $newColumns = [];
            foreach ($columns as $key => $title) {
                if ($key === 'order_status') {
                    $newColumns['type']  = 'Tipo';
                    $newColumns['print'] = 'Imprimir';
                    $newColumns['rider'] = 'Rider';
                }
                $newColumns[$key] = $title;
            }

            return $newColumns;
        }

        /**
         *  Show shop order custom columns content.
         */
        public function showShopOrderCustomColumns($columnName, $postID)
        {

            $order = new \WC_Order($postID);

            if ($columnName === 'type') {
                // $isDelivery, $scheduledFor
                extract(Helpers::getOrderTypeAndSchedule($order)); ?>

                <div style="color:var(--primary-color);">
                    <?= Helpers::getOrderTypeIcon($isDelivery, '2.5rem') ?>
                </div>
                <span><?= $isDelivery ? 'Domicilio' : 'Recogida' ?></span>
            <?php
            } else if ($columnName === 'order_number') {
                $kitchen = Helpers::getKitchenByID($order->get_meta('kitchen_for_order'));
                $kitchen = $kitchen['kitchen_name'] ?? null;

                if ($kitchen) {
                    print '<div>';
                    print '<small style="border-radius:.25rem; color:white; background:var(--primary-color); padding:.2rem; text-transform:uppercase">';
                    print $kitchen;
                    print '</small>';
                    print '</div>';
                }
            } else if ($columnName === 'print') { ?>
                <button class="PrintTicketBtn button" style="border:none; background:var(--primary-color); display:flex; justify-content:center; align-items:center; padding:.5rem; line-height:0;" title="Imprimir Comanda" data-order-id="<?= $postID ?>" data-endpoint="<?= esc_url(admin_url('admin-post.php')) ?>" data-referrer="<?= home_url(add_query_arg([])); ?>" data-nonce="<?= wp_create_nonce('rebell-form'); ?>">
                    <svg viewBox="0 0 477.9 477.9" style="height:1.5rem; fill:white">
                        <path d="M461 119h-86V17c0-9-7-17-17-17H119c-9 0-17 8-17 17v102H17c-9 0-17 8-17 18v221c0 10 8 17 17 17h85v86c0 9 8 17 17 17h239c10 0 17-8 17-17v-86h86c9 0 17-7 17-17V137c0-10-8-18-17-18zM137 34h204v85H137V34zm204 410H137V290h204v154zm103-103h-69v-51h18a17 17 0 100-34H85a17 17 0 100 34h17v51H34V154h410v187z"></path>
                        <path d="M410 188h-17a17 17 0 100 34h17a17 17 0 100-34zM290 324H188a17 17 0 100 34h102a17 17 0 100-34zM290 375H188a17 17 0 100 35h102a17 17 0 100-35z"></path>
                    </svg>
                </button>
            <?php } else if ($columnName === 'rider') {
                $order = wc_get_order($postID);
                $assignedRider = $order->get_meta('order_rider');
                if ($assignedRider) {
                    $assignedRider = get_userdata($assignedRider);
                }

            ?>
                <div class="rider-finder">
                    <select class="wc-customer-search" name="_selected_rider" data-placeholder="Buscar rider" data-allow_clear="true">
                        <?php if ($assignedRider) : ?>
                            <option value="<?= esc_attr($assignedRider->ID); ?>" selected="selected">
                                <?= htmlspecialchars(wp_kses_post($assignedRider->display_name)); // htmlspecialchars to prevent XSS when rendered by selectWoo. 
                                ?>
                            <option>
                            <?php endif; ?>
                    </select>
                    <button type="button" class="assign button button-small" style="margin-top:.4rem" data-order-id="<?= $postID ?>" data-endpoint="<?= esc_url(admin_url('admin-post.php')) ?>" data-referrer="<?= home_url(add_query_arg([])); ?>" data-nonce="<?= wp_create_nonce('rebell-form'); ?>">Asignar</button>
                </div><?php
                    }
        }

        public function handleProductsQuery( $query ) {

            if ( is_admin( ) || ! $query->is_main_query( ) ) {
                return $query;
            }
        
            try {
                $customerKitchen = WCKitchenController::getCustomerKitchenFromZipcode( );
                $kitchenID = $customerKitchen['kitchen_id'] ?? null;
                if ( empty( $kitchenID ) ) {
                    throw new \Exception( 'Empty kitchen ID!!' );
                }
            } catch ( \Exception $e ) {
                return $query;
            }
        
            $query->set( 'meta_query', [
                [
                    'key'     => 'kitchen_for_product',
                    'value'   => sanitize_text_field( $kitchenID ),
                    'compare' => 'LIKE'
                ]
            ] );
        
        }

    }
}

        CustomizeWooCommerce::getInstance();
