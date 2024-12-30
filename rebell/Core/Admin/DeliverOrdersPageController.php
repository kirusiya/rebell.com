<?php

/**
 * New List Table for admin page "Warehouse Orders".
 *
 * @package Invbit\ClickAndCollect
 * @author  Invbit <info@invbit.com>
 * @link    https://www.invbit.com
 */

use Invbit\Core\Helpers;

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

if ( ! class_exists( 'DeliverOrdersPageController' ) ) {

    class DeliverOrdersPageController extends WP_List_Table {

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
         * Constructor.
         */
        public function __construct( ) {

            parent::__construct( [
                'singular' => __( 'Pedido', 'betheme' ),
                'plural'   => __( 'Pedidos', 'betheme' ),
                'ajax'     => false
            ] );

        }

        /**
         * Message to be displayed when there are no items
         */
        public function no_items( ) {

            _e( 'De momento no quedan entregas pendientes.', 'betheme' );

        }

        /**
         * Gets a list of columns.
         *
         * @return array
         */
        public function get_columns( ) {

            $columns = [
                'address' => __( 'Enviar a', 'betheme' ),
                'date'    => __( 'Fecha', 'betheme' ),
                'order'   => __( 'Pedido', 'betheme' ),
                // 'status'  => __( 'Estado', 'betheme' ),
                // 'phone'   => __( 'Teléfono', 'betheme' ),
                'price'   => __( 'Total', 'betheme' ),
                'actions' => __( 'Acciones', 'betheme' )
            ];
            
            return $columns;

        }

        /**
         * Gets a list of sortable columns.
         *
         * @return array
         */
        protected function get_sortable_columns( ) {

            return [ ];

        }

        /**
         * Prepares the list of items for displaying.
         *
         * @uses WP_List_Table::set_pagination_args( )
         */
        public function prepare_items( ) {

            $this->_column_headers = [ $this->get_columns( ), $this->get_hidden_columns( ), $this->get_sortable_columns( ) ];

            $data = $this->_get_orders( );
            
            $perPage      = 10;
            $current_page = $this->get_pagenum( );
            $totalItems   = count( $data );
            
            $this->set_pagination_args( [
                'total_items' => $totalItems,
                'per_page'    => $perPage
            ] );

            $data = array_slice( $data, ( ( $current_page-1 )*$perPage ), $perPage );
            
            $this->items = $data;

        }

        /**
         * Render a column when no column specific method exists.
         * 
         * @param object|array $item
         * @param string $column_name
         */
        public function column_default( $item, $column_name ) {

            switch ( $column_name ) {
                case 'address':
                case 'date':
                case 'order':
                // case 'status':
                // case 'phone':
                case 'price':
                case 'actions':
                    return $item[ $column_name ];
                default:
                    return print_r( $item, true );
            }

        }

        /**
         * Get the orders from the database.
         * 
         * @return array List of orders
         */
        private function _get_orders( ) {

            global $wpdb;

            $userID    = get_current_user_id( );
            $rangeDays = $_GET['days'] ?? 2;
            $sort      = $_GET['order'] ?? 'DESC';
            $sortBy    = $_GET['orderby'] ?? 'ID';
            $orders    = $wpdb->get_results( $wpdb->prepare(
                "SELECT post_id, CAST(p.post_date AS datetime) AS date FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p
                    ON p.ID = pm.post_id
                WHERE pm.meta_key = 'order_rider'
                    AND pm.meta_value = '%s'
                    AND CAST(p.post_date AS date) >= CURRENT_DATE + interval -2 day
                ORDER BY %s %s;",
                $userID, (int) $rangeDays, $sortBy, $sort
            ) );

            $result = [];
            foreach( $orders as $order ) {
                $orderID = $order->post_id;
                $order   = wc_get_order( $orderID );

                $i = 0;
                $content = '';
                foreach( $order->get_items( ) as $item_id => $item ) {
                    if( $i ) $content .= '<br/>';
                    $content .= $item->get_quantity( ) . ' x ' . $item->get_name( );
                    $i++;
                    if ( $i == 5 ) {
                        $content .= '</br>...';
                        break;
                    }
                }

                $ticket    = Helpers::getTicketNumber( $order );
                $number    = $order->get_order_number();

                $result[ ] = [
                    'address'  => $this->_getOrderResume( $order ),
                    'date'     => $order->get_date_created( )->date_i18n( 'j F Y H:i' ),
                    'order'    => "Ticket: $ticket ($number) <br> Pedido: ($number)",
                    // 'status'   => '<div class="order-status status-' . esc_attr( $order->get_status( ) ) . '">'
                    //     .  esc_html( wc_get_order_status_name( $order->get_status( ) ) ) 
                    //     . '</div>',
                    // 'phone'    => "<a href='tel:+34$phone'>$phone</a>",
                    'price'    => $order->get_formatted_order_total( ),
                    'actions'  => $this->_getOrderActions( $order )
                ];
            }
            
            return $result;

        }

        private function _getOrderResume( $order ) {

            $customer  = $order->get_billing_first_name( ) .' '. $order->get_billing_last_name( );
            $phone     = trim( $order->get_billing_phone( ) );
            $deliverTo = $order->get_shipping_address_1( ) . ' ' . $order->get_shipping_address_2( ) . '<br>'
                . $order->get_shipping_postcode( ) . ' - ' . $order->get_shipping_city( );

            return "<div class='block mb-1/2 order-status status-{$order->get_status( )}'>"
                    . esc_html( wc_get_order_status_name( $order->get_status( ) ) ) 
                . "</div>"
                . "<div class='deliverTo'>"
                    . "<strong>Cliente</strong> $customer <br>"
                    . "<strong>Dirección</strong> $deliverTo <br>"
                    . "<strong>Teléfono</strong> <a href='tel:+34$phone'>$phone</a>"
                . "</div>";

        }

        private function _getOrderActions( $order ) {

            $completeAction = wp_nonce_url( admin_url( "admin-ajax.php?action=rebell_complete_order&order_id={$order->get_id( )}" ), 'woocommerce-complete-order' );
            $processAction = wp_nonce_url( admin_url( "admin-ajax.php?action=rebell_process_order&order_id={$order->get_id( )}" ), 'woocommerce-process-order' );

            if ( $order->get_status( ) === 'processing' ) {
                $actions = "<a class='button button-primary w-full text-center' href='$completeAction'>Entregar</a>";
            } else {
                $actions = "<a class='ProcessOrderButton button w-full text-center' href='$processAction'>Pendiente</a>";
            }
            $actions .= "<div style='margin-top:1rem'>"
                . "<textarea placeholder='Motivo de cancelación' class='CancelOrderReason w-full'></textarea>"
                . "<input class='CancelOrderID' type='hidden' value='{$order->get_id( )}'>"
                . "<button class='CancelOrderButton button w-full text-center'>" . __( 'Cancelar', 'betheme' ) . "</button>"
            . "</div>";

            return $actions;

        }

    }
        
}

return new DeliverOrdersPageController( );