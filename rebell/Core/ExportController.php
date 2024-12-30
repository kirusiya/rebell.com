<?php namespace Invbit\Core;

/**
 * Exports.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\ExportController' ) ) {

    class ExportController {

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
         *  Constructor
         */
        public function __construct( ) {
            if (IS_SUPERADMIN) {
                add_action( 'admin_menu', [ $this, 'addAdminPages' ], 105 );
                add_action( 'wp_dashboard_setup', [ $this, 'addCustomDashboardWidget' ]);
                add_action( 'admin_post_nopriv_rebell_export_orders', 'auth_redirect' );
                add_action( 'admin_post_rebell_export_orders', [ $this, 'downloadExportedOrders'  ] );
            }
        }

        /**
         *  Create custom widget.
         */
        public function addCustomDashboardWidget( ) {
            \wp_add_dashboard_widget(
                'custom_csv_exports',
                'Exportar pedidos',
                [ $this, 'renderCustomDashboardWidget' ]
            );
        }

        public function renderCustomDashboardWidget( ) { ?>
            <p>Selecciona un rango de fechas y exporta los pedidos desglosados según su serie de facturación (cocina).</p>

            <style>
                table, tbody {
                    border-radius: 1rem;
                }
                tbody tr td {
                    padding: 1rem !important;
                }
                tbody tr:first-of-type td:first-of-type {
                    border-top-left-radius: 1rem;
                }
                tbody tr:first-of-type td:last-of-type {
                    border-top-right-radius: 1rem;
                }
                tbody tr:last-of-type td:first-of-type {
                    border-bottom-left-radius: 1rem;
                }
                tbody tr:last-of-type td:last-of-type {
                    border-bottom-right-radius: 1rem;
                }
            </style>

            <form id="NotifyUsersForm" action="<?= esc_url( admin_url( 'admin-post.php' ) ) ?>" method="POST">
                <?php wp_nonce_field( 'rebell-form', 'rebell-form-nonce' ); ?>
                <input type="hidden" name="action" value="rebell_export_orders">
                <input type="hidden" name="rebell_request_referrer" value="<?= home_url( add_query_arg( [ ] ) ); ?>">

                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <td colspan="3">
                                <label for="title" style="font-weight:bold">Desde</label>
                                <input type="date" id="from" name="from" style="width:100%">
                            </td>
                            <td colspan="3">
                                <label for="title" style="font-weight:bold">Hasta</label>
                                <input type="date" id="to" name="to" style="width:100%">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="999">
                                <input
                                    type="submit"
                                    class="button button-primary"
                                    style="width:100%;font-weight:bold"
                                    value="Exportar!"
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>

            </section>
<?php
        }

        /**
         *  Create admin page.
         */
        public function addAdminPages( ) {
                add_submenu_page(
                    'acf-options-configura-rebell',
                    'Exportar',
                    'Exportar',
                    'manage_options',
                    'export-orders',
                    [ $this, 'adminPageContent' ],
                    2
                );
        }


        /**
         *  Build admin page content.
         */
        public function adminPageContent( ) {?>

            <section class="wrap" style="max-width:800px; margin:0 auto;">

                <h2 style="margin:1rem 0 2rem">Exportar pedidos</h2>

                <p>Esta pantalla te ayudará a exportar los pedidos desglosados según su serie de facturación (cocina).</p>

                <style>
                    table, tbody {
                        border-radius: 1rem;
                    }
                    tbody tr td {
                        padding: 1rem !important;
                    }
                    tbody tr:first-of-type td:first-of-type {
                        border-top-left-radius: 1rem;
                    }
                    tbody tr:first-of-type td:last-of-type {
                        border-top-right-radius: 1rem;
                    }
                    tbody tr:last-of-type td:first-of-type {
                        border-bottom-left-radius: 1rem;
                    }
                    tbody tr:last-of-type td:last-of-type {
                        border-bottom-right-radius: 1rem;
                    }
                </style>

                <form id="NotifyUsersForm" action="<?= esc_url( admin_url( 'admin-post.php' ) ) ?>" method="POST">
                    <?php wp_nonce_field( 'rebell-form', 'rebell-form-nonce' ); ?>
                    <input type="hidden" name="action" value="rebell_export_orders">
                    <input type="hidden" name="rebell_request_referrer" value="<?= home_url( add_query_arg( [ ] ) ); ?>">

                    <table class="widefat striped">
                        <tbody>
                            <tr>
                                <td colspan="3">
                                    <label for="from" style="font-weight:bold">Desde</label>
                                    <input type="date" id="from" name="from" style="width:100%" value="<?= date('Y-m-d', strtotime('-1 months')) ?>" required>
                                </td>
                                <td colspan="3">
                                    <label for="to" style="font-weight:bold">Hasta</label>
                                    <input type="date" id="to" name="to" style="width:100%" value="<?= date('Y-m-d') ?>" required>
                                </td>
                                <td colspan="3">
                                    <label for="kitchens" style="font-weight:bold">Kitchens</label>
                                    <select name="kitchen" id="kitchens" style="width:100%">
                                        <option value="all">Todas</option>
                                        <?php foreach ( Helpers::getAllKitchens() as $kitchen ) : ?>
                                        <option value="<?= $kitchen['id'] ?>"><?= $kitchen['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="999">
                                    <input
                                        type="submit"
                                        class="button button-primary"
                                        style="width:100%;font-weight:bold"
                                        value="Exportar!"
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>

            </section>

        <?php }

        /**
         * Download exported orders
         */
        public function downloadExportedOrders( ) {

            $referrer = esc_url_raw( $_POST[ 'rebell_request_referrer' ] );

            if (
                ! wp_verify_nonce( $_POST[ 'rebell-form-nonce' ], 'rebell-form' ) or
                ! ( current_user_can( 'administrator' ) ) or
                empty( $_POST[ 'from' ] ) and empty( $_POST[ 'to' ] )
            ) return wp_redirect( esc_url_raw( $referrer ) );

            $from    = isset($_POST['from']) ? sanitize_text_field($_POST['from']) : date('Y-m-d', strtotime('-1 months'));
            $to      = isset($_POST['to'])   ? sanitize_text_field($_POST['to'])   : date('Y-m-d');
            $kitchen = isset($_POST['kitchen']) && $_POST['kitchen'] !== 'all' ? sanitize_text_field($_POST['kitchen']) : null;
            $orders  = $this->getOrdersList($from, $to, $kitchen);

            if ( count($orders) <= 0 ) {
                return wp_redirect( esc_url_raw( $referrer ) );
            }

            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header('Content-Type: text/x-csv');
            header('Content-Disposition: attachment;filename=' . 'exported-orders.csv');

            $fieldNames = [
                'Fecha',
                'Factura',
                'Importe Base',
                'Impuestos',
                'Total',
            ];
            
            $output = fopen('php://output', 'w');
            fputcsv($output, $fieldNames);

            $rowValues = [];
            foreach ( $orders as $order ) {
                $rowValues['date']          = $order['date'];
                $rowValues['invoiceNumber'] = $order['invoiceNumber'];
                $rowValues['base']          = $order['base'];
                $rowValues['taxes']         = $order['taxes'];
                $rowValues['total']         = $order['total'];

                fputcsv($output, $rowValues);
            }
        }

        /**
         *  Get the orders list.
         */
        protected function getOrdersList( $from, $to, $kitchen = null ) {
            global $wpdb;

            $kitchenSql = $kitchen
                ? $wpdb->prepare("AND post_id IN (
                        SELECT UNIQUE(post_id) FROM {$wpdb->postmeta}
                        WHERE meta_key = 'kitchen_for_order' AND meta_value = %s)", $kitchen)
                : null;

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT *
                    FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type = 'shop_order'
                        AND p.post_status = 'wc-completed'
                        AND p.id IN (
                            SELECT post_id
                            FROM {$wpdb->postmeta}
                            WHERE meta_key = 'invoicePrefix' $kitchenSql
                        )
                        AND p.post_date >= '%s'
                        AND p.post_date <= '%s';
                    ",
                    $from, $to
                )
            );

            // $results = $wpdb->get_results(
            //     $wpdb->prepare(
            //         "SELECT *
            //         FROM {$wpdb->posts} p
            //             JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            //         WHERE p.post_type = 'shop_order'
            //             AND p.post_status = 'wc-completed'
            //             AND pm.post_id IN (
            //                 SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'invoicePrefix'
            //             )
            //             AND p.post_date >= '%s'
            //             AND p.post_date <= '%s'
            //         ",
            //         $from, $to
            //     )
            // );

            // $kitchenPosts = null;
            // if ( $kitchen ) {
            //     $kitchenPosts = $wpdb->get_col($wpdb->prepare(
            //         "SELECT UNIQUE(post_id) FROM {$wpdb->postmeta} WHERE meta_key = 'kitchen_for_order' AND meta_value = %s",
            //         $kitchen
            //     ));
            // }

            $orders = [];
            foreach ($results as $obj) {
                // if ( !in_array($obj->ID, $kitchenPosts) ) {
                //     continue;
                // }

                $orders[$obj->post_id]['date'] = $obj->post_date;
                if ( in_array( $obj->meta_key, ['invoicePrefix', 'invoiceNumber'] ) ) {
                    $orders[$obj->post_id][$obj->meta_key] = $obj->meta_value;
                }
                if ($obj->meta_key === '_order_tax') {
                    $orders[$obj->post_id]['taxes'] = round($obj->meta_value, 2);
                }
                if ($obj->meta_key === '_order_total') {
                    $orders[$obj->post_id]['total'] = round($obj->meta_value, 2);
                }
            }

            foreach ($orders as $ID => $order) {
                $orders[$ID]['invoiceNumber'] = $order['invoicePrefix'] . '-' . str_pad( $order['invoiceNumber'], 6, 0, STR_PAD_LEFT );
                $orders[$ID]['base'] = $order['total'] - $order['taxes'];
                unset($orders[$ID]['invoicePrefix']);
            }

            return $orders;
        }

    }

}

ExportController::getInstance( );