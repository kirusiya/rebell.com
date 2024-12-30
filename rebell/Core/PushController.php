<?php namespace Invbit\Core;

/**
 * Push Notifications.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\PushController' ) ) {

    class PushController {

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
            $this->ONESIGNAL_URL      = 'https://onesignal.com/api/v1';
            $this->ONESIGNAL_APP_ID   = '8dccaa7f-2b83-4ead-af4c-8d4244163437';
            $this->ONESIGNAL_AUTH_KEY = 'MjY3N2M4MTgtMDJlNi00NTllLWIwNDQtYTU5ZmNhMGIwNTc1';

            add_action( 'admin_menu', [ $this, 'addAdminPage' ], 105 );

        }

        /**
         *  Create admin page.
         */
        public function addAdminPage( ) {

            add_submenu_page(
                'acf-options-configura-rebell',
                'Notificar Clientes',
                'Notificar Clientes',
                'manage_options',
                'notify-customers',
                [ $this, 'adminPageContent' ],
                2
            );

        }


        /**
         *  Build admin page content.
         */
        public function adminPageContent( ) {?>

            <section class="wrap" style="max-width:800px; margin:0 auto;">

                <?php
                $everyone = isset( $_POST[ 'everyone' ] ) ? $_POST[ 'everyone' ] : false;
                if ( isset( $_POST[ 'sent' ] ) and empty( $_GET[ 'filter_users' ] ) ) {
                    if ( ( empty( $_POST[ 'user_ids' ] ) or ! is_array( $_POST[ 'user_ids' ] ) ) and !$everyone ) {
                        $this->renderNotice( 'Error: Debes seleccionar al menos un usuario o marcar "Notificar a todos".', 'error' );
                    } else if ( empty( $_POST[ 'title' ] ) or empty( $_POST[ 'content' ] ) ) {
                        $this->renderNotice( 'Error: Debes poner un título y un contenido a tu notificación.', 'error' );
                    } else {
                        $userIDs = $_POST[ 'user_ids' ];
                        $response = $this->sendNotification(
                            $userIDs, $_POST[ 'title' ], $_POST[ 'content' ], $_POST[ 'screen' ], $everyone
                        );
                        if ( is_wp_error( $response ) ) {
                            $this->renderNotice( "Error: {$response->get_error_message}", 'error' );
                        } else if ( ! is_array( $response ) or ! isset( $response[ 'body' ] ) ) {
                            $this->renderNotice( 'Error: ' . json_encode( $response ), 'error' );
                        } else {
                            if ( $everyone ) {
                                $this->renderNotice( 'Notificación enviada a todos', 'success' );
                            } else {
                                $users = wp_list_pluck( get_users( [ 'login__in' => $userIDs ] ), 'user_nicename' );
                                $this->renderNotice( 'Notificación enviada a: ' . implode( ', ', $users ), 'success' );
                            }
                        }
                    }
                } ?>

                <h2 style="margin:1rem 0 2rem">Notificar Clientes</h2>

                <p>Envía notificaciones push personalizadas a tus clientes a través de este formulario y les llegará a su aplicación móvil.</p>

                <?php $this->renderNotificationForm( ) ?>

            </section>

        <?php }


        /**
         *  Send notification using OneSignal Rest API.
         *  @ref https://documentation.onesignal.com/reference#create-notification
         */
        public function sendNotification( $userIDs, $title, $content, $screen, $all = false ) {

            if ( !$all and ( !is_array( $userIDs ) or empty( $userIDs ) ) )
                return $this->renderNotice( 'Wrong user ids', 'error' );

            /** @todo Prevent the system from escaping text (quotes, for example) */
            $notificationTitle   = sanitize_text_field( $title );
            $notificationContent = sanitize_textarea_field( $content );
            $notificationScreen  = sanitize_text_field( $screen );

            $fields = [
                'app_id'                        => $this->ONESIGNAL_APP_ID,
                'headings'                      => [ 'en' => $notificationTitle ],
                'isAnyWeb'                      => true,
                'contents'                      => [ 'en' => $notificationContent ],
                'data'                          => [ 'screen' => $notificationScreen ]
            ];
            if ( $all ) $fields[ 'included_segments' ] = [ 'All' ];
            else        $fields[ 'include_external_user_ids' ] = $userIDs;

            error_log( '-----> SENDING NOTIFICATION TO THE FOLLOWING USERIDS' );
            if ( $all ) error_log( 'included_segments = All' );
            else        error_log( print_r( $userIDs, 1 ) );

            $response = wp_remote_post( "{$this->ONESIGNAL_URL}/notifications", [
                'headers' => [
                    'content-type'  => 'application/json;charset=utf-8',
                    'Authorization' => 'Basic '. $this->ONESIGNAL_AUTH_KEY,
                ],
                'body'    => wp_json_encode( $fields ),
                'timeout' => 3,
            ] );

            error_log( print_r( $response, 1 ) );

            return $response;

        }


        /**
         *  Set external user ID.
         *  @ref https://documentation.onesignal.com/reference/edit-device
         */
        public function setExternalUserID( $playerID ) {

            if ( empty( $playerID ) ) return new \WP_Error( 'wrong-player-id', 'Wrong player id' );

            $user = wp_get_current_user( );

            $fields = [
                'app_id'           => $this->ONESIGNAL_APP_ID,
                'isAnyWeb'         => true,
                'external_user_id' => $user->user_login
            ];

            return wp_remote_request( "{$this->ONESIGNAL_URL}/players/{$playerID}", [
                'method' => 'PUT',
                'headers' => [
                    'content-type'  => 'application/json;charset=utf-8',
                    'Authorization' => 'Basic '. $this->ONESIGNAL_AUTH_KEY,
                ],
                'body'    => wp_json_encode( $fields ),
                'timeout' => 3,
            ] );

        }


        /**
         *  Get OneSignal players.
         *  @ref https://documentation.onesignal.com/reference#create-notification
         */
        public function getOneSignalPlayers( $offset = 0, $limit = 300 ) {

            $users = isset( $_GET[ 'filter_users' ] )
                ? get_users( [ 'search' => '*' . sanitize_text_field( $_GET[ 'filter_users' ] ) . '*' ] )
                : get_users( );

            return array_filter( $users, function( $user ) {
                return get_user_meta( $user->ID, 'player_id' ) != null;
            } );

            // The following code will take only the players with a specified external ID 
            // from OneSignal.
            /* 
            $response = wp_remote_get(
                "{$this->ONESIGNAL_URL}/players?app_id={$this->ONESIGNAL_APP_ID}&limit={$limit}&offset={$offset}",
                [
                    'headers' => [
                        'Content-Type'  => 'application/json;charset=utf-8',
                        'Authorization' => 'Basic '. $this->ONESIGNAL_AUTH_KEY,
                    ],
                    'timeout' => 3,
                ]
            );

            if ( is_wp_error( $response ) )
                return $this->renderNotice( "Error: {$response->get_error_message}", 'error' );
            if ( ! is_array( $response ) or ! isset( $response[ 'body' ] ) )
                return $this->renderNotice( 'Error: ' . json_encode( $response ), 'error' );

            $body = json_decode( $response[ 'body' ] );
            if ( empty( $body->players ) )
                return $this->renderNotice( 'Error: Ha ocurrido un problema al recuperar los usuarios de OneSignal.', 'error' );

            $players = array_column( $body->players, 'external_user_id' );

            return array_values( array_filter(
                array_map( function( $u ) use ( $players ) {
                    return in_array( $u->user_login, $players ) ? $u : null;
                }, get_users( ) )
            ) );
            */

        }


        /**
         *  Render form content.
         */
        private function renderNotificationForm( ) { ?>

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
            input#everyone:checked + select {
                display : none;
            }
            </style>

            <form method="get">
                <div style="display:flex; align-items:center; margin-bottom:1rem;">
                    <input type="hidden" name="page" value="notify-customers">
                    <input
                        type="text"
                        id="filter_users"
                        name="filter_users"
                        style="width:100%"
                        placeholder="Filtrar por email, nombre o usuario"
                    >
                    <button id="filter_btn" class="button button-primary">
                        Filtrar
                    </button>
                </div>
            </form>

            <form id="NotifyUsersForm" method="post" action="/wp-admin/admin.php?page=notify-customers">
                <input type="hidden" name="sent">
                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <td>
                                <label for="title" style="font-weight:bold">Título</label>
                            </td>
                            <td>
                                <input type="text" id="title" name="title" style="width:100%">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="content" style="font-weight:bold">Contenido</label>
                            </td>
                            <td>
                                <textarea name="content" id="content" cols="30" rows="3" style="width:100%"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="content" style="font-weight:bold">Abrir Pantalla</label>
                            </td>
                            <td>
                                <select name="screen" style="width:100%;max-width:unset;">
                                    <option value="menu" selected>Carta</option>
                                    <option value="about">Rebell</option>
                                    <option value="orders">Cuenta > Mis Pedidos</option>
                                    <!--
                                    <option value="payment">Cuenta > Métodos de Pago</option>
                                    <option value="tracking">Cuenta > Seguimiento del Pedido</option>
                                    -->
                                    <option value="coupons">Cuenta > Mis Cupones</option>
                                    <option value="faq">Cuenta > Preguntas Frecuentes</option>
                                    <option value="help">Cuenta > Ayuda</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:20%">
                                <label for="content" style="font-weight:bold">Notificar a todos</label>
                            </td>
                            <td style="width:80%">
                                <input type="checkbox" name="everyone" id="everyone">
                            </td>
                        </tr>
                        <tr id="usersSelector">
                            <td>
                                <label for="content" style="font-weight:bold">Usuarios</label>
                            </td>
                            <td>
                                <select
                                    name="user_ids[]"
                                    multiple="multiple"
                                    style="width:100%;max-width:unset;min-height:200px;max-height:50vh"
                                >
                                    <?php foreach( $this->getOneSignalPlayers( ) as $user ) : ?>
                                        <?php $pID = get_user_meta( $user->ID, 'player_id' ); ?>
                                        <option
                                            value="<?= $user->user_login ?>"
                                            <?= isset( $pID[0] ) ? 'title="' . $pID[0] . '"' : '' ?>
                                        >
                                            <?= $user->user_nicename ?> &lt;<?= $user->user_email ?>&gt;
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <input
                                    type="submit"
                                    class="button button-primary"
                                    style="width:100%;font-weight:bold"
                                    value="Enviar notificación!"
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>

            <script>
                document.getElementById( 'everyone' ).addEventListener( 'change', function( evt ) {
                    document.getElementById( 'usersSelector' ).style.display = this.checked ? 'none' : 'table-row';
                } );
            </script>

        <?php }


        /**
         *  Render notification content.
         */
        private function renderNotice( $message, $type ) { ?>

            <div id="message" class="updated notice <?= $type ?>">
                <p><?= $message ?></p>
            </div>

        <?php }


    }

}

PushController::getInstance( );