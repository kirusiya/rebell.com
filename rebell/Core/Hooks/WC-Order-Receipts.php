<?php namespace Invbit\Core;

/**
 * Add logic to allow printing the WC orders' receipts.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\OrderReceiptsController' ) ) {

    class OrderReceiptsController {

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

            add_action( 'woocommerce_order_actions_start',                 [ $this, 'renderReceiptButtons' ] );
            add_action( 'admin_post_rebell_print_order_receipt',           [ $this, 'printOrderReceipt'  ] );
            add_action( 'admin_post_nopriv_rebell_print_order_receipt',    'auth_redirect' );
            add_action( 'admin_post_rebell_print_invoice',                 [ $this, 'printOrderInvoice'  ] );
            add_action( 'admin_post_nopriv_rebell_print_invoice',          'auth_redirect' );

        }


        /**
         * Render the button to print the receipt.
         */
        public function renderReceiptButtons( $orderID ) {

            global $post;

            $order = wc_get_order( intval( $post->ID ) );
            if ( $order->get_meta( 'invoicePrefix' ) and $order->get_meta( 'invoiceNumber' ) ) {
                $invoiceNumber = Helpers::getInvoiceNumber( $order );
            }
            ?>

            <script>
                ( function ( $ ) {
                    $( document ).ready( function( ) {
                        $( document.body ).append(
                            '<form id="PrintOrderForm" target="_blank" action="<?= esc_url( admin_url( 'admin-post.php' ) ) ?>" method="POST">' + 
                                '<?php wp_nonce_field( 'rebell-form', 'rebell-form-nonce' ); ?>' +
                                '<input type="hidden" name="action" value="rebell_print_order_receipt">' +
                                '<input type="hidden" name="order_id" value="<?= $orderID ?>">' +
                                '<input type="hidden" name="rebell_request_referrer" value="<?= home_url( add_query_arg( [ ] ) ); ?>">' +
                            '</form>' +
                            '<form id="PrintInvoiceForm" target="_blank" action="<?= esc_url( admin_url( 'admin-post.php' ) ) ?>" method="POST">' + 
                                '<?php wp_nonce_field( 'rebell-form', 'rebell-form-nonce' ); ?>' +
                                '<input type="hidden" name="action" value="rebell_print_invoice">' +
                                '<input type="hidden" name="order_id" value="<?= $orderID ?>">' +
                                '<input type="hidden" name="rebell_request_referrer" value="<?= home_url( add_query_arg( [ ] ) ); ?>">' +
                            '</form>'
                        );
                        $( '#PrintOrder' ).click( function( ) { $( '#PrintOrderForm' ).submit( ); } );
                        $( '#PrintInvoice' ).click( function( ) { $( '#PrintInvoiceForm' ).submit( ); } );
                    } );
                } )( jQuery );
            </script>
            <li class="wide">
                <a id="PrintOrder" class="PrintBtn button" title="Imprimir Comanda" href="#">
                    <?= $this->_getIcon('receipt') ?>
                    <span style="margin-left:.5rem">Comanda</span>
                </a>
            </li>

            <li class="wide">
                <a id="PrintInvoice" class="PrintBtn button" title="Imprimir Factura del Cliente" href="#">
                    <?= $this->_getIcon('invoice') ?>
                    <?php if ( !empty( $invoiceNumber ) ) : ?>
                        <div style="margin-left:.5rem; text-align:left; line-height:1rem;">
                            Factura
                            <span style="display:block"><?= $invoiceNumber ?? '' ?></span>
                        </div>
                    <?php else : ?>
                        <span style="margin-left:.5rem">Factura</span>
                    <?php endif; ?>
                </a>
            </li>
<?php if ( isset($_GET['debug']) ) : ?>

<li class="wide">
    <a id="PrintOrderStandalone" class="PrintBtn button" title="Imprimir Comanda" href="#">
        <?= $this->_getIcon('receipt') ?>
        <span style="margin-left:.5rem">Comanda</span>
    </a>
</li>

<style>
    #TicketTemplate {
        display    : none;
        position   : fixed;
        top        : 0;
        right      : 0;
        bottom     : 0;
        left       : 0;
        background : white;
        z-index    : -1;
    }
    @media print {
        .wp-admin #TicketTemplate {
            visibility: visible;
        }
    }
</style>

<script>
    jQuery('#PrintOrderStandalone').on('click', function() {
        jQuery('#TicketTemplate').show();
        window.print();
        jQuery('#TicketTemplate').hide();
    })
</script>
<?php 
    $ticketNumber = Helpers::getTicketNumber( $order );

    extract( Helpers::getOrderTypeAndSchedule( $order ) );
?>
<div id="TicketTemplate">

    <h1 class="fixed-width mb centered hidden-print">
        Ticket <?= $ticketNumber ?>
    </h1>

    <main class="ticket fixed-width">

        <!-- Header info -->
        <header>
            <figure>
                <img src="<?= ASSETS_DIR . 'Images/receipt-logo.jpg' ?>" alt="Rebell Homeburger">
            </figure>
            <section class="mt">
                <h3>Ticket</h3>
                <p>
                    Pedido: #<?= $order->get_id( ) ?>
                    <br>
                    Número: <?= $ticketNumber ?>
                    <br>
                    Fecha: <?= $order->get_date_created( )->date_i18n( 'd-m-Y H:i' ) ?>
                </p>
            </section>
            <section class="mt">
                <h3>Cliente:</h3>
                <ul>
                    <li><?= $order->get_shipping_first_name( ) . ' ' . $order->get_shipping_last_name( ) ?></li>
                    <?php if ( $isDelivery ) : ?>
                    <li><?= $order->get_shipping_address_1( ) ?></li>
                    <li><?= $order->get_shipping_postcode( ) ?> - <?= $order->get_shipping_city( ) ?></li>
                    <li>Email: <?= $order->get_billing_email( ) ?></li>
                    <?php endif; ?>
                    <li>Tel: <?= $order->get_billing_phone( ) ?></li>
                </ul>

                <br>

                <h3><?= $isDelivery ? 'Envío a domicilio' : 'Recogida en Local' ?></h3>

                <?php if ($scheduledFor) : ?>
                    <br>
                    Hora <u>aproximada</u>: <strong><?= $scheduledFor ?></strong>
                <?php endif; ?>
            </section>
        </header>

        <!-- List of products and shipping costs -->
        <table class="big-my">
            <thead>
                <tr>
                    <th class="quantity">Uds.</th>
                    <th class="description">Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $orderItems = [ ];
                $allItems = $order->get_items( );
                usort( $allItems, function( $a, $b ) {
                    $orderA = wc_get_product( $a['product_id'] )->get_menu_order( );
                    $orderB = wc_get_product( $b['product_id'] )->get_menu_order( );
                    return strnatcmp( $orderA, $orderB );
                } );
                foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'parent' => Constants::$MENU_CAT_ID ] ) as $cat ) {
                    foreach ( $allItems as $item ) {
                        $prodCats = get_the_terms( $item[ 'product_id' ], 'product_cat' );
                        if ( $cat->term_id != $prodCats[ 0 ]->term_id ) continue;
                        $orderItems[ ] = $item;
                    }
                }

                foreach ( $orderItems as $item ) : ?>
                    <tr>
                        <td class="quantity"><?= $item->get_quantity( ) ?></td>
                        <td class="description bold"><?= $item->get_name( ) ?></td>
                    </tr>
                    <?php foreach ( $item->get_meta_data( ) as $meta ) : $data = $meta->get_data( ); ?>
                        <?php foreach ( CustomizeWooCommerce::$propTypes as $prop => $___ ) : ?>
                            <?php if ( $data['key'] != $prop ) continue; ?>
                            <?php foreach ( $data[ 'value' ] as $extra ) : ?>
                            <tr>
                                <td class="quantity"><?= $item->get_quantity( ) ?></td>
                                <td class="description"><?= strip_tags( $extra[ 'name' ] ) ?></td>
                            </tr>
                            <?php endforeach ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach ?>
                <?php if ( $order->get_shipping_total( ) > 0 ) : ?>
                    <tr>
                        <td class="quantity">1</td>
                        <td class="description bold">Envío</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <p class="bold b-border">
                A cobrar: <?= $order->get_total( ) . $order->get_currency( ) ?>
            </p>
            <p><?= 
                $order->get_payment_method_title( ) ? $order->get_payment_method_title( ) : null
            ?></p>
        </div>

        <div id="qrcode" style="display:flex; justify-content:center;"></div>

        <!-- Footer -->
        <footer>
            <button id="OpenPrintDialog" class="hidden-print" onclick="window.print()">
                <?= $this->_getIcon('receipt') ?>
                Imprimir
            </button>
        </footer>

    </main>
</div>

<?php endif; ?>

            <?php

        }


        /**
         * Handle generating the PDF invoice for the order.
         */
        public function printOrderReceipt( ) {

            $this->checkReferrer( );

            $order        = wc_get_order( intval( $_POST[ 'order_id' ] ) );
            $ticketNumber = Helpers::getTicketNumber( $order );

            extract( Helpers::getOrderTypeAndSchedule( $order ) );
            ?>

            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <meta http-equiv="X-UA-Compatible" content="ie=edge">
                    <link rel="stylesheet" href="<?= ASSETS_DIR . 'Styles/order_receipt.css' ?>">
                    <script src="/wp-includes/js/jquery/jquery.js?ver=1.12.4-wp"></script>
                    <script src="<?= ASSETS_DIR . 'JS/qrcode.min.js' ?>"></script>
                    <script type="text/javascript">
                        // jQuery(document).ready(function ($) {
                        //     new QRCode(
                        //         document.getElementById('qrcode'), {
                        //         text: "<?= admin_url( 'admin.php?page=deliver_orders&order_marked_complete=' . $order->get_id() ) ?>",
                        //         width: 180,
                        //         height: 180,
                        //         // colorDark : "#d2c62f",
                        //         // colorLight : "transparent",
                        //     });
                        // });
                    </script>
                    <title>Comanda #<?= $order->get_id( ) ?></title>
                </head>
                <body>

                    <h1 class="fixed-width mb centered hidden-print">
                        Ticket <?= $ticketNumber ?>
                    </h1>

                    <main class="ticket fixed-width">

                        <!-- Header info -->
                        <header>
                            <figure>
                                <img src="<?= ASSETS_DIR . 'Images/receipt-logo.jpg' ?>" alt="Rebell Homeburger">
                            </figure>
                            <section class="mt">
                                <h3>Ticket</h3>
                                <p>
                                    Pedido: #<?= $order->get_id( ) ?>
                                    <br>
                                    Número: <?= $ticketNumber ?>
                                    <br>
                                    Fecha: <?= $order->get_date_created( )->date_i18n( 'd-m-Y H:i' ) ?>
                                </p>
                            </section>
                            <section class="mt">
                                <h3>Cliente:</h3>
                                <ul>
                                    <li><?= $order->get_shipping_first_name( ) . ' ' . $order->get_shipping_last_name( ) ?></li>
                                    <?php if ( $isDelivery ) : ?>
                                    <li><?= $order->get_shipping_address_1( ) ?></li>
                                    <li><?= $order->get_shipping_postcode( ) ?> - <?= $order->get_shipping_city( ) ?></li>
                                    <li>Email: <?= $order->get_billing_email( ) ?></li>
                                    <?php endif; ?>
                                    <li>Tel: <?= $order->get_billing_phone( ) ?></li>
                                </ul>

                                <br>

                                <h3><?= $isDelivery ? 'Envío a domicilio' : 'Recogida en Local' ?></h3>

                                <?php if ($scheduledFor) : ?>
                                    <br>
                                    Hora <u>aproximada</u>: <strong><?= $scheduledFor ?></strong>
                                <?php endif; ?>
                            </section>
                        </header>

                        <!-- List of products and shipping costs -->
                        <table class="big-my">
                            <thead>
                                <tr>
                                    <th class="quantity">Uds.</th>
                                    <th class="description">Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $orderItems = [ ];
                                $allItems = $order->get_items( );
                                usort( $allItems, function( $a, $b ) {
                                    $orderA = wc_get_product( $a['product_id'] )->get_menu_order( );
                                    $orderB = wc_get_product( $b['product_id'] )->get_menu_order( );
                                    return strnatcmp( $orderA, $orderB );
                                } );
                                foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'parent' => Constants::$MENU_CAT_ID ] ) as $cat ) {
                                    foreach ( $allItems as $item ) {
                                        $prodCats = get_the_terms( $item[ 'product_id' ], 'product_cat' );
                                        if ( $cat->term_id != $prodCats[ 0 ]->term_id ) continue;
                                        $orderItems[ ] = $item;
                                    }
                                }

                                foreach ( $orderItems as $item ) : ?>
                                    <tr>
                                        <td class="quantity"><?= $item->get_quantity( ) ?></td>
                                        <td class="description bold"><?= $item->get_name( ) ?></td>
                                    </tr>
                                    <?php foreach ( $item->get_meta_data( ) as $meta ) : $data = $meta->get_data( ); ?>
                                        <?php foreach ( CustomizeWooCommerce::$propTypes as $prop => $___ ) : ?>
                                            <?php if ( $data['key'] != $prop ) continue; ?>
                                            <?php foreach ( $data[ 'value' ] as $extra ) : ?>
                                            <tr>
                                                <td class="quantity"><?= $item->get_quantity( ) ?></td>
                                                <td class="description"><?= strip_tags( $extra[ 'name' ] ) ?></td>
                                            </tr>
                                            <?php endforeach ?>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach ?>
                                <?php if ( $order->get_shipping_total( ) > 0 ) : ?>
                                    <tr>
                                        <td class="quantity">1</td>
                                        <td class="description bold">Envío</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Totals -->
                        <div class="totals">
                            <p class="bold b-border">
                                A cobrar: <?= $order->get_total( ) . $order->get_currency( ) ?>
                            </p>
                            <p><?= 
                                $order->get_payment_method_title( ) ? $order->get_payment_method_title( ) : null
                            ?></p>
                        </div>

                        <div id="qrcode" style="display:flex; justify-content:center;"></div>

                        <!-- Footer -->
                        <footer>
                            <button id="OpenPrintDialog" class="hidden-print" onclick="window.print()">
                                <?= $this->_getIcon('receipt') ?>
                                Imprimir
                            </button>
                        </footer>

                    </main>
                </body>
            </html>
        <?php }


        /**
         * Handle generating the PDF invoice for the order.
         */
        public function printOrderInvoice( ) {

            $this->checkReferrer( );

            $order         = wc_get_order( intval( $_POST[ 'order_id' ] ) );
            $catsWithProds = $this->_getProductsSortedByMenuCategories( $order->get_items( ) );
            $kitchen       = Helpers::getKitchenByID( $order->get_meta( 'kitchen_for_order' ) );
            $invoiceData   = [
                'name'    => $kitchen['invoiceName'] ?? get_field( 'storeName', 'options' ),
                'nif'     => $kitchen['invoiceNIF'] ?? get_field( 'storeAddress', 'options' ),
                'address' => $kitchen['invoiceAddress'] ?? get_field( 'storeIDNumber', 'options' ),
            ];

            if ( $order->get_meta( 'invoicePrefix' ) and $order->get_meta( 'invoiceNumber' ) ) {
                $invoiceNumber = Helpers::getInvoiceNumber( $order );
            } else {
                $invoiceNumber = "F/{$order->get_id( )}";
            }
            ?>

            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <meta http-equiv="X-UA-Compatible" content="ie=edge">
                    <link rel="stylesheet" href="<?= ASSETS_DIR . 'Styles/order_receipt.css' ?>">
                    <title>
                        Factura <?= $invoiceNumber ?>
                    </title>
                </head>
                <body>

                    <h1 class="fixed-width mb centered hidden-print">
                        Factura <?= $invoiceNumber ?>
                    </h1>

                    <main class="ticket fixed-width">

                        <!-- Header info -->
                        <header>
                            <figure>
                                <img src="<?= ASSETS_DIR . 'Images/receipt-logo.jpg' ?>" alt="Rebell Homeburger">
                            </figure>
                            <h3><?= $invoiceData['name'] ?></h3>
                            <section class="mt">
                                <p>
                                    Dirección: <?= $invoiceData['address'] ?>
                                    <br>
                                    CIF: <?= $invoiceData['nif'] ?>
                                </p>
                            </section>
                            <section class="mt">
                                <h3>Factura</h3>
                                <p>
                                    Pedido: #<?= $order->get_id( ) ?>
                                    <br>
                                    Número: <?= $invoiceNumber ?>
                                    <br>
                                    Fecha: <?= $order->get_date_created( )->date_i18n( 'd-m-Y H:i' ) ?>
                                </p>
                            </section>
                            <section class="mt">
                                <h3>Cliente</h3>
                                <p>
                                    <?= $order->get_shipping_first_name( ) . ' ' . $order->get_shipping_last_name( ) ?>
                                    <br>
                                    <?= $order->get_shipping_address_1( ) ?>. <?= $order->get_shipping_postcode( ) ?> - <?= $order->get_shipping_city( ) ?>
                                    <br>
                                    <?= $order->get_billing_phone( ) ?>
                                </p>
                            </section>
                        </header>

                        <!-- List of products and shipping costs -->
                        <table class="big-my">
                            <thead>
                                <tr>
                                    <th class="quantity">Ud.</th>
                                    <th class="description">Descripción</th>
                                    <th class="price right">Precio</th>
                                    <th class="price right">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ( $catsWithProds as $key => $cat ) : ?>
                                <?php foreach ( $cat['products'] as $item ) : ?>
                                    <?php
                                        $prod      = wc_get_product( $item->get_product_id( ) );
                                        $basePrice = $item->get_meta( 'baseprice' );
                                    ?>
                                    <tr>
                                        <td class="quantity"><?= $item->get_quantity( ) ?></td>
                                        <td class="description bold"><?= $item->get_name( ) ?></td>
                                        <td class="price right"><?= $this->_formatPrice( $prod->get_price( ) ) ?></td>
                                        <td class="price right"><?= $this->_formatPrice( $item->get_quantity( ) * $basePrice ) ?></td>
                                        <!-- <td class="price right"><?= $this->_formatPrice(
                                            $basePrice > $prod->get_price( ) ? $item->get_total( ) : $prod->get_price( )
                                            // ( $item->get_quantity( ) > 1 ) ? $item->get_total( ) : $prod->get_price( )
                                        ) ?></td> -->
                                    </tr>
                                    <?php foreach ( $item->get_meta_data( ) as $meta ) : $data = $meta->get_data( ); ?>

                                        <?php foreach ( CustomizeWooCommerce::$propTypes as $prop => $___ ) : ?>
                                            <?php if ( $data['key'] != $prop ) continue; ?>
                                            <?php foreach ( $data[ 'value' ] as $extra ) : ?>
                                            <tr>
                                                <td class="quantity"><?= $item->get_quantity( ) ?></td>
                                                <td class="description"><?= strip_tags( $extra[ 'name' ] ) ?></td>
                                                <td class="price right"><?= $this->_formatPrice( ($extra[ 'price' ] ?: '0') ) ?></td>
                                                <td class="price right"><?= $this->_formatPrice( ($item->get_quantity( ) * $extra[ 'price' ] ?: '0') ) ?></td>
                                            </tr>
                                            <?php endforeach ?>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach ?>
                            <?php endforeach ?>

                            <?php if ( $order->get_shipping_total( ) > 0 ) : ?>
                                <tr>
                                    <td class="quantity">1</td>
                                    <td class="description bold">Envío</td>
                                    <td class="price right"><?= $this->_getShippingCost( $order->get_shipping_total( ) ) ?></td>
                                    <td class="price right"><?= $this->_getShippingCost( $order->get_shipping_total( ) ) ?></td>
                                </tr>
                            <?php endif; ?>

                            </tbody>
                        </table>

                        <!-- Taxes -->
                        <table class="big-my">
                            <thead>
                                <tr>
                                    <th class="quantity twothirds" colspan="2">IVA</th>
                                    <th class="price right onethird">B. Imponible</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $order->get_items( 'tax' ) as $item ) : ?>
                                <?php
                                    $taxTotals = $item->get_tax_total( ) + $item->get_shipping_tax_total( );
                                    $totals    = ( $taxTotals * 100 ) / $item->get_rate_percent( );
                                    ?>
                                    <tr>
                                        <td class="quantity onethird"><?= $item->get_rate_percent( ) ?>%</td>
                                        <td class="description right onethird"><?= $this->_formatPrice( $taxTotals ) ?></td>
                                        <td class="price right onethird"><?= $this->_formatPrice( $totals ) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>

                        <!-- Coupons -->
                        <?php if ( count( $order->get_items( 'coupon' ) ) > 0 ) : ?>
                        <table class="big-my">
                            <thead>
                                <tr>
                                    <th class="quantity twothirds" colspan="2">Descuentos</th>
                                    <th class="price right onethird">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $order->get_items( 'coupon' ) as $item_id => $item ) : ?>
                                    <tr>
                                        <td class="quantity onethird"></td>
                                        <td class="description right onethird"></td>
                                        <td class="price right onethird">-<?= $this->_formatPrice( $item->get_discount( ) + $item->get_discount_tax( ) ) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                        <?php endif; ?>

                        <!-- Totals -->
                        <div class="totals">
                            <p class="bold b-border">
                                Total a pagar: <?= $order->get_total( ) . $order->get_currency( ) ?>
                            </p>
                            <p><?= 
                                $order->get_payment_method_title( ) ? $order->get_payment_method_title( ) : null
                            ?></p>
                        </div>


                        <!-- Footer -->
                        <footer>
                            <p class="centered my">Gracias por su visita</p>

                            <button id="OpenPrintDialog" class="hidden-print" onclick="window.print()">
                                <?= $this->_getIcon('invoice') ?>
                                Imprimir
                            </button>
                        </footer>

                    </main>
                </body>
            </html>
        <?php }


        private function _getIcon( String $icon ) : String {

            if ( $icon == 'invoice' ) {
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 326 453.5">
                    <path d="M321 349V27c0-11-6-16-11-18-7-3-14-2-20 5l-13 12-9 9-21-21c-9-9-18-9-27 0l-2 1a763 763 0 00-20 21l-2-2-19-20c-9-9-19-9-28 0l-14 14-7 7-2-2-18-18c-4-4-9-8-15-8-7 0-11 4-15 8L59 33l-2 3-1-2-20-20c-7-7-13-9-20-5-5 2-11 7-11 18v397c0 7 1 13 5 17 5 5 11 5 17 5l272 1c5 0 12-1 17-6 4-4 5-11 5-16v-32-44zM72 70l18-19 3-2 6 6 15 15c9 9 19 9 27 0a4123 4123 0 0022-21l5 4 13 14 4 4c8 8 18 8 26 0l16-16 7-6 15 15 7 7c8 8 17 8 26 0l4-5v58l1 288a79667 79667 0 00-248 0l1-308V66l3 4c10 9 19 9 29 0z"/>
                    <path d="M163 152H90l-7-1c-4-1-6-4-6-8s2-6 5-7l5-1a126706 126706 0 01156 0c4 2 6 4 6 9 0 4-3 7-7 7l-34 1h-45zM162 202H87c-4 0-8-1-10-6s2-10 9-10h153c6 0 9 2 10 6 1 6-3 10-9 10h-78zM204 353c-4 5-10 9-16 11-6 3-14 4-22 4-15 0-27-4-36-11-9-8-15-19-18-32H97l5-11h8v-9H97l5-11h9a63 63 0 0120-35l16-10c6-2 13-3 19-3l15 1 13 4 8 6c2 3 3 6 3 10 0 3-1 5-3 7s-5 3-8 3c-5 0-8-3-8-9l1-3v-3c0-3-2-5-6-6l-14-1c-5 0-8 1-12 3-3 2-6 5-8 9-3 3-5 8-7 12l-3 15h43l-3 11h-40a120 120 0 001 9h36l-4 11h-30c2 10 5 18 10 24s12 9 20 9l15-2 12-9 7 6z"/>
                </svg>';
            } else if ( $icon == 'receipt' ) {
                return '<svg viewBox="0 0 477.9 477.9">
                    <path d="M461 119h-86V17c0-9-7-17-17-17H119c-9 0-17 8-17 17v102H17c-9 0-17 8-17 18v221c0 10 8 17 17 17h85v86c0 9 8 17 17 17h239c10 0 17-8 17-17v-86h86c9 0 17-7 17-17V137c0-10-8-18-17-18zM137 34h204v85H137V34zm204 410H137V290h204v154zm103-103h-69v-51h18a17 17 0 100-34H85a17 17 0 100 34h17v51H34V154h410v187z"/>
                    <path d="M410 188h-17a17 17 0 100 34h17a17 17 0 100-34zM290 324H188a17 17 0 100 34h102a17 17 0 100-34zM290 375H188a17 17 0 100 35h102a17 17 0 100-35z"/>
                </svg>';
            }

        }


        private function _formatPrice( String $price ) : String {

            return number_format( $price, 2, ',', '.' );

        }


        private function _getShippingCost( String $baseCost ) : String {

            return $this->_formatPrice( Helpers::getShippingCost( $baseCost ) );

        }


        private function _getProductsSortedByMenuCategories( array $listOfProducts ) : array {

            $_sortedProducts = [ ];

            foreach ( get_terms( 'product_cat', [ 'parent' => Constants::$MENU_CAT_ID ] ) as $val )
                $_sortedProducts[ $val->term_id ] = [ 'title' => $val->name, 'products' => [ ] ];

            foreach ( $listOfProducts as $val ) {
                $_product    = wc_get_product( $val->get_product_id( ) );
                $_categoryID = array_pop( $_product->get_category_ids( ) );

                if ( ! array_key_exists( $_categoryID, $_sortedProducts ) ) continue;

                $_sortedProducts[ $_categoryID ][ 'products' ][ ] = $val;
            }

            return $_sortedProducts;

        }


        public function checkReferrer( ) {

            $referrer = esc_url_raw( $_POST[ 'rebell_request_referrer' ] );

            if (
                ! wp_verify_nonce( $_POST[ 'rebell-form-nonce' ], 'rebell-form' ) or
                ! ( current_user_can( 'administrator' ) or current_user_can( 'editor' ) or current_user_can( 'kitchen_manager' ) ) or
                empty( $_POST[ 'order_id' ] )
            ) return wp_redirect( esc_url_raw( $referrer ) );

            return $referrer;

        }


    }

}

OrderReceiptsController::getInstance( );
