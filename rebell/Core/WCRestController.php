<?php namespace Invbit\Core;

/**
 * WC Controller for the REST API.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\WCRestController' ) ) {

    class WCRestController {

        protected $version;
        protected $namespace;
        protected $order;

        protected $defaultCity    = 'Bertamiráns';
        protected $defaultState   = 'C';  // A Coruña
        protected $defaultCountry = 'ES'; // Spain

        private static $PAYCOMET_API_URL           = 'https://rest.paycomet.com';
        // private static $PAYCOMET_API_TOKEN         = '55134b5a90e263028b6479bd56c34723813dda09';
        private static $PAYCOMET_JET_ID            = 'ADLNWRspPrfQF4mSY6bOneCITvG1tqM5';
        private static $PAYCOMET_MERCHANT_TERMINAL = 17154;
        private static $PAYCOMET_REQUEST_HEADERS   = [
            'Content-Type'          => 'application/json',
            'Accept'                => 'application/json',
            'PAYCOMET-API-TOKEN'    => '55134b5a90e263028b6479bd56c34723813dda09',
        ];

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

            $this->version   = '1';
            $this->namespace = 'rebell/v' . $this->version;
            $this->base      = '/wc/';

            add_action( 'rest_api_init', [ $this, 'initAPI' ] );

        }


        /**
         *  Initialize REST endpoints
         */
        public function initAPI( ) {

            if ( ALLOW_REST_AUTH ) {
                register_rest_route( $this->namespace, "{$this->base}auth", [
                    [
                        'methods'             => \WP_REST_Server::READABLE,
                        'callback'            => [ $this, 'authUser' ],
                        'permission_callback' => '__return_true',
                        'args'                => [ ],
                    ]
                ] );
            }

            register_rest_route( $this->namespace, "{$this->base}get-tpv-customer-data", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'getTPVCustomerData' ],
                    'permission_callback' => [ $this, 'hasValidToken' ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}process-new-card", [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'processNewCard' ],
                    'permission_callback' => [ $this, 'hasValidToken' ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}process-payment", [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'processPayment' ],
                    'permission_callback' => [ $this, 'hasValidToken' ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}set-player-id", [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'setPlayerID' ],
                    'permission_callback' => [ $this, 'hasValidToken' ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}password-reset", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'sendPasswordResetLink' ],
                    'permission_callback' => '__return_true',
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}register-user", [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'registerUser' ],
                    'permission_callback' => [ $this, 'hasValidNonce' ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}send-contact-form", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'sendContactForm' ],
                    'permission_callback' => [ ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}valid-zip-code", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'validateZipCode' ],
                    'permission_callback' => [ ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}check-kitchen-status", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'checkKitchenStatus' ],
                    'permission_callback' => [ ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}get-kitchen-for-zipcode", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'getKitchenForZipcode' ],
                    'permission_callback' => [ ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}get-all-kitchens", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'getAllKitchens' ],
                    'permission_callback' => [ ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}check-order-schedule-limit", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'checkScheduleLimitForOrder' ],
                    'permission_callback' => [ ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}get-schedules", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'getSchedules' ],
                    'permission_callback' => [ ],
                    'args'                => [ ],
                ]
            ] );

            register_rest_route( $this->namespace, "{$this->base}get-product-categories", [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'getProductCategories' ],
                    'permission_callback' => [ ],
                    'args'                => [ ],
                ]
            ] );

        }

        /**
         *  Process new credit cards.
         */
        public function processNewCard( $request ) {

            $tokenInfo = $this->getTPVToken( $request );

            if ( empty( $tokenInfo->idUser ) or empty( $tokenInfo->tokenUser ) )
                return wp_send_json_error( 'Card could not be processed.', 400 );

            $cardInfo = $this->getTPVCardInfo( $tokenInfo->idUser, $tokenInfo->tokenUser );

            $cardInfo->idUser    = $tokenInfo->idUser;
            $cardInfo->tokenUser = $tokenInfo->tokenUser;

            $savedData = $this->saveNewCardData( $cardInfo );

            if ( is_wp_error( $savedData ) )
                return wp_send_json_error( 'There was an error while trying to save your card details.', 400 );

            return wp_send_json( $cardInfo, 200 );

        }

        /**
         *  Fetch PayComet's token.
         */
        private function getTPVToken( $request ) {

            if ( ! $jetToken = $request->get_param( 'jetToken' ) )
                return wp_send_json_error( 'Bad request', 400 );

            $url = self::$PAYCOMET_API_URL . '/v1/cards';

            $response = wp_remote_post( $url, [
                'headers' => self::$PAYCOMET_REQUEST_HEADERS,
                'body'    => json_encode( [
                    'terminal' => self::$PAYCOMET_MERCHANT_TERMINAL,
                    'jetToken' => $jetToken
                ] ),
            ] );

            $response = json_decode( $response['body'] );

            return $response;

        }

        /**
         *  Fetch credit card data from Paycomet.
         */
        private function getTPVCardInfo( $idUser, $tokenUser ) {

            $url = self::$PAYCOMET_API_URL . '/v1/cards/info';

            $response = wp_remote_post( $url, [
                'headers' => self::$PAYCOMET_REQUEST_HEADERS,
                'body'    => json_encode( [
                    'terminal'  => self::$PAYCOMET_MERCHANT_TERMINAL,
                    'idUser'    => $idUser,
                    'tokenUser' => $tokenUser
                ] ),
            ] );

            return json_decode( $response['body'] );

        }


        /**
         *  Save new card into the database.
         */
        private function saveNewCardData( $payload ) {

            global $wpdb;

            $cardNumber = substr_replace( +$payload->pan, '************', 0, -4 );

            return $wpdb->insert(
                $wpdb->prefix.'paytpv_customer',
                [
                    'paytpv_iduser'    => sanitize_text_field( $payload->idUser ),
                    'paytpv_tokenuser' => sanitize_text_field( $payload->tokenUser ),
                    'paytpv_cc'        => $cardNumber,
                    'paytpv_brand'     => sanitize_text_field( $payload->cardBrand ),
                    'id_customer'      => get_current_user_id( ),
                    'date'             => date( 'Y-m-d H:i:s' ),
                    'card_desc'        => 'Saved throught the app'
                ]
            );

        }


        /**
         *  Process the payment.
         */
        public function processPayment( $request ) {
            if ( ! $paymentInfo = $request->get_param( 'payment' ) )
                wp_send_json_error( 'Payment info not provided', 400 );

            if ( empty( $paymentInfo['order'] ) )     wp_send_json_error( 'Order not provided', 400 );
            if ( empty( $paymentInfo['amount'] ) )    wp_send_json_error( 'Amount not provided', 400 );
            if ( empty( $paymentInfo['idUser'] ) )    wp_send_json_error( 'idUser not provided', 400 );
            if ( empty( $paymentInfo['tokenUser'] ) ) wp_send_json_error( 'tokenUser not provided', 400 );

            $payload = [
                'methodId'        => '1',
                'terminal'        => self::$PAYCOMET_MERCHANT_TERMINAL,
                'order'           => sanitize_text_field( $paymentInfo['order'] ),
                'amount'          => sanitize_text_field( $paymentInfo['amount'] ),
                'currency'        => 'EUR',
                'urlOk'           => get_bloginfo( 'url' ) . '/wp-json/rebell/v1/order/complete-payment',
                'originalIp'      => '127.0.0.1',
                'secure'          => '0',
                'userInteraction' => '1',
                'idUser'          => sanitize_text_field( $paymentInfo['idUser'] ),
                'tokenUser'       => sanitize_text_field( $paymentInfo['tokenUser'] )
            ];

            $response = $this->executePayment( $payload );

            if ( ! $response ) return wp_send_json_error( 'The payment failed unexpectedly.', 400 );

            return wp_send_json( $response, 200 );

        }

        /**
         *  Execute the payment in the Paycomet platform.
         */
        private function executePayment( $paymentData ) {

            $url = self::$PAYCOMET_API_URL . '/v1/payments';

            $response = wp_remote_post( $url, [
                'headers' => self::$PAYCOMET_REQUEST_HEADERS,
                'body'    => json_encode( [ 'payment' => $paymentData ] ),
            ] );

            $response = json_decode( $response['body'] );

            if ( $response->errorCode > 0 ) return false;

            return $response;

        }




        /**
         *  Generate auth cookie.
         */
        public function authUser( $request ) {

            if ( ! $user = $request->get_param( 'user' ) ) return new \WP_REST_Response( 'User is missing', 400 );
            if ( ! $pass = $request->get_param( 'pass' ) ) return new \WP_REST_Response( 'Password is missing', 400 );

            $user = wp_signon( [
                'user_login'    => $user,
                'user_password' => $pass
            ], true );

            if ( is_wp_error( $user ) ) {
                return new \WP_REST_Response( 'Can\' verify your credentials.', 403 );
            }

            $duration   = 4102444800000; // 01-01-2100.
            $expireTime = time( ) + apply_filters( 'auth_cookie_expiration', $duration, get_current_user_id( ), true );
            return wp_generate_auth_cookie( $user->ID, $expireTime , 'logged_in' );

        }


        /**
         *  Fetch PayComet's customer meta data.
         */
        public function getTPVCustomerData( $request ) {

            global $wpdb;

            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}paytpv_customer WHERE id_customer = %d",
                get_current_user_id( )
            );

            return new \WP_REST_Response( $wpdb->get_results( $query ), 200 );

        }


        /**
         *  Set a player ID for push notifications to the customer.
         */
        public function setPlayerID( $request ) {

            error_log( sprintf(
                ">>>>>>>>>>>> Setting up player ID #%s for user %s >>>>>>>>>>>>",
                $request->get_param( 'playerID' ),
                get_current_user_id( )
            ) );
            $playerID = sanitize_text_field( $request->get_param( 'playerID' ) );
            update_user_meta( get_current_user_id( ), 'player_id', $playerID );

            $push = PushController::getInstance( );
            $push->setExternalUserID( $playerID );

            return new \WP_REST_Response( 'User #' .get_current_user_id( ). '\'s player ID updated.', 200 );

        }


        /**
         *  Send an email to the user requesting a password reset.
         */
        public function sendPasswordResetLink( $request ) {

            if ( ! $email = $request->get_param( 'email' ) )
                return new \WP_REST_Response( 'Email has not been provided.', 400 );

            if ( ! $user = get_user_by( 'email', sanitize_email( $email ) ) )
                return new \WP_REST_Response( 'Email not found.', 400 );

            $resetKey = get_password_reset_key( $user );
            $wcMails = WC( )->mailer( )->get_emails( );
            $wcMails[ 'WC_Email_Customer_Reset_Password' ]->trigger( $user->user_login, $resetKey );

        }


        /**
         *  Register a new user.
         */
        public function registerUser( $request ) {

            if ( ! $firstName = $request->get_param( 'first_name' ) )
                return new \WP_REST_Response( 'Firstname has not been provided.', 400 );
            // if ( ! $lastName = $request->get_param( 'last_name' ) )
            //     return new \WP_REST_Response( 'Lastname has not been provided.', 400 );
            if ( ! $phoneNumber = $request->get_param( 'phone' ) )
                return new \WP_REST_Response( 'Phone has not been provided.', 400 );
            if ( ! $address = $request->get_param( 'address' ) )
                return new \WP_REST_Response( 'Address has not been provided.', 400 );
            if ( !$zipCode = $request->get_param( 'zip_code' ) )
                return new \WP_REST_Response( 'Zip code has not been provided.', 400 );
            if ( ! $email = $request->get_param( 'email' ) )
                return new \WP_REST_Response( 'Email has not been provided.', 400 );
            if ( ! $passwd = $request->get_param( 'password' ) )
                return new \WP_REST_Response( 'Password has not been provided.', 400 );

            $email          = sanitize_email( $email );
            $firstName      = sanitize_text_field( $firstName );
            $lastName       = $request->get_param( 'last_name' )
                            ? sanitize_text_field( $request->get_param( 'last_name' ) ) : '';
            $phoneNumber    = str_replace( ' ', '', $phoneNumber );
            $address        = sanitize_text_field( $address );
            $city           = $request->get_param( 'city' )
                            ? sanitize_text_field( $request->get_param( 'city' ) )
                            : $this->defaultCity;
            $passwd         = sanitize_text_field( $passwd );
            $displayName    = "$firstName $lastName";
            $baseUsername   = strtolower( sanitize_user( str_replace( ' ', '', $displayName ) ) );

            // Check phone number.
            if ( ! preg_match( "/^[0-9]{9}$/i", $phoneNumber ) )
                return new \WP_REST_Response( 'Phone number is not valid.', 400 );

            // Check zip code.
            if ( !is_numeric( $zipCode ) or ! Helpers::validZipCode( $zipCode ) )
                return new \WP_REST_Response( 'Zip code not allowed.', 400 );

            // Check email.
            if ( email_exists( $email ) ) return new \WP_REST_Response( 'This email already exists.', 400 );
            if ( ! is_email( $email ) )   return new \WP_REST_Response( 'This email is not a valid email.', 400 );

            // Check username.
            $username = $baseUsername;
            $usernameSuffix = 1;
            while ( username_exists( $username ) ) {
                $usernameSuffix++;
                $username = sanitize_user( "{$baseUsername}_{$usernameSuffix}" );
            }

            $userData = [
                'firstName'   => $firstName,
                'lastName'    => $lastName,
                'username'    => $username,
                'displayName' => $displayName,
                'address'     => $address,
                'city'        => $city,
                'zipCode'     => $zipCode,
                'email'       => $email,
                'phone'       => $phoneNumber
            ];
            $newUserID = wc_create_new_customer( $email, $username, $passwd );

            wp_generate_auth_cookie(
                $newUserID,
                ( time( ) + apply_filters( 'auth_cookie_expiration', 4102444800000, $newUserID, true ) ), // 01-01-2100
                'logged_in'
            );

            $this->fillUserBillingData( $newUserID, $userData );

            return [ 'email' => $email, 'passwd' => $passwd ];

        }


        /**
         *  Fill user billing metadata.
         */
        private function fillUserBillingData( $newUserID, $data ) {

            foreach ( [
                'first_name'          => $data['firstName'],
                'last_name'           => $data['lastName'],
                'user_login'          => $data['username'],
                'display_name'        => $data['displayName'],
                'user_nicename'       => $data['displayName'],
                'nickname'            => $data['username'],

                'billing_first_name'  => $data['firstName'],
                'billing_last_name'   => $data['lastName'],
                'billing_address_1'   => $data['address'],
                'billing_city'        => $data['city'],
                'billing_postcode'    => $data['zipCode'],
                'billing_country'     => $this->defaultCountry,
                'billing_state'       => $this->defaultState,
                'billing_email'       => $data['email'],
                'billing_phone'       => $data['phone'],

                'shipping_first_name' => $data['firstName'],
                'shipping_last_name'  => $data['lastName'],
                'shipping_address_1'  => $data['address'],
                'shipping_city'       => $data['city'],
                'shipping_postcode'   => $data['zipCode'],
                'shipping_country'    => $this->defaultCountry,
                'shipping_state'      => $this->defaultState,
                'shipping_email'      => $data['email'],
                'shipping_phone'      => $data['phone'],
            ] as $key => $value ) update_user_meta( $newUserID, $key, $value );

        }


        /**
         *  Send contact form.
         */
        public function sendContactForm( $request ) {
            if ( !$name = $request->get_param( 'name' ) )
                return wp_send_json_error( 'El nombre es necesario.', 400 );
            if ( ! $email = $request->get_param( 'email' ) )
                return wp_send_json_error( 'El email es necesario.', 400 );
            if ( ! is_email( $email ) )
                return wp_send_json_error( 'El email no es válido.', 400 );
            if ( ! $phoneNumber = $request->get_param( 'phone' ) )
                return wp_send_json_error( 'El teléfono es necesario.', 400 );
            if ( ! preg_match( "/\s*\d{3}\s*\d{3}\s*\d{3}\s*/", $phoneNumber ) )
                return wp_send_json_error( 'El teléfono no es válido.', 400 );
            if ( ! $message = $request->get_param( 'message' ) )
                return wp_send_json_error( 'El mensaje es necesario.', 400 );
                
            $destination = get_bloginfo( 'admin_email' );
            $subject     = "Mensaje desde la App de Rebell";
            $content     = "Nombre: {$name}<br>";
            $content    .= "Email: {$email}<br>";
            $content    .= "Teléfono: {$phoneNumber}<br>";
            $content    .= $message;
            $headers     = [ 'Content-Type: text/html; charset=UTF-8' ];

            if ( !wp_mail( $destination, $subject, $content, $headers ) ) {
                wp_send_json_error( 'Ha ocurrido un problema al enviar tu mensaje.', 400 );
                return;
            }

            wp_send_json( 'Hemos recibido tu email, en breve te contactaremos.' );

        }


        /**
         *  Validate user token.
         */
        public function hasValidToken( $request ) {

            // If the token is in the request params, use it; otherwise fall back to searching for 
            // the token in the headers.
            $token = ( $request->has_param( 'token' ) )
                ? $request->get_param( 'token' )
                : $request->get_header( 'token' );

            if ( ! ( $currentUserID = wp_validate_auth_cookie( $token , 'logged_in' ) ) ) return false;

            return wp_set_current_user( $currentUserID );

        }


        /**
         *  Validate nonce.
         */
        public function hasValidNonce( $request ) {

            global $json_api;

            $validNonce = $json_api->get_nonce_id( 'appusers', 'register' );
            $sentNonce  = $request->get_param( 'nonce' );

            return wp_verify_nonce( $sentNonce, $validNonce );

        }


        /**
         *  Validate zip code.
         */
        public function validateZipCode( $request ) {

            if ( ! is_numeric( $request->get_param( 'zipcode' ) ) )
                return new \WP_REST_Response( 'Zip code not allowed.', 400 );

            if ( ! Helpers::validZipCode( $request->get_param( 'zipcode' ) ) )
                return new \WP_REST_Response( 'Zip code not allowed.', 400 );

            return new \WP_REST_Response( 'Zip code allowed.', 200 );

        }


        /**
         *  Check if the kitchen is opened.
         */
        public function checkKitchenStatus( $request ) {

            $kitchenID = $request->get_param( 'kitchen_id' );
            $kitchens  = (array) get_field( 'kitchens', 'options' );
            $foundKitchenKey = array_search( $kitchenID, array_column( $kitchens, 'kitchen_id' ) );

            if ( $foundKitchenKey === false || ! isset( $kitchens[ $foundKitchenKey ] ) ) {
                $kitchenStatus = get_field( 'kitchen_status', 'options' );
                $message       = '';
    
                if ( $kitchenStatus == 'closed' )  $message = get_field( 'closed_kitchen_message', 'options' );
                if ( $kitchenStatus == 'blocked' ) $message = get_field( 'blocked_kitchen_message', 'options' );

                return wp_send_json( [
                    'status'  => $kitchenStatus,
                    'message' => $message
                ], 200 );
                // return wp_send_json( [
                //     'error'   => true,
                //     'message' => 'Parameter kitchen_id is required and it must be an existing ID'
                // ], 400 );
            }

            $kitchen   = $kitchens[ $foundKitchenKey ];

            $kitchenStatus = $kitchen[ 'kitchen_status' ];
            $message       = '';

            if ( $kitchenStatus == 'closed' )  $message = $kitchen[ 'closed_kitchen_message' ];
            if ( $kitchenStatus == 'blocked' ) $message = $kitchen[ 'blocked_kitchen_message' ];

            return wp_send_json( [
                'status'  => $kitchenStatus,
                'message' => $message
            ], 200 );

        }


        /**
         *  Get the kitchen ID for the given zipcode.
         */
        public function getKitchenForZipcode( $request ) {

            $zipCode = $request->get_param( 'zipcode' );

            if ( ! $zipCode or ! is_numeric( $zipCode ) ) {
                return wp_send_json( 'Zip code not allowed.', 400 );
            }

            $shippingZone = Helpers::getShippingZoneByZipCode( $zipCode );

            foreach ( get_field( 'kitchens', 'options' ) as $kitchen ) {
                if ( in_array( $shippingZone['id'], $kitchen['kitchen_shipping_zones'] ) ) {
                    $kitchenID = $kitchen;
                    break;
                }
            }

            return wp_send_json( [ 'data' => $kitchenID ?? null ], 200 );
        }


        /**
         *  Get all kitchens data.
         */
        public function getAllKitchens( $request ) {

            $kitchens = Helpers::getAllKitchens( );

            return wp_send_json( [ 'data' => $kitchens ], 200 );

        }


        /**
         *  Check limit of orders per time frame.
         */
        public function checkScheduleLimitForOrder( $request ) {

            if (
                ( ! $kitchenID = $request->get_param( 'kitchen_id' ) ) or
                ( ! $time = $request->get_param( 'time' ) ) or
                ( ! $delivery = $request->get_param( 'is_delivery' ) )
            ) {
                return wp_send_json( [
                    'error'   => true,
                    'message' => 'Parameters kitchen_id, time and is_delivery are required'
                ], 400 );
            }

            $kitchens  = (array) get_field( 'kitchens', 'options' );
            $foundKitchenKey = array_search( $kitchenID, array_column( $kitchens, 'kitchen_id' ) );

            if ( $foundKitchenKey === false || ! isset( $kitchens[ $foundKitchenKey ] ) ) {
                return wp_send_json( [
                    'error'   => true,
                    'message' => 'Parameter kitchen_id is required and it must be an existing ID'
                ], 400 );
            }

            $kitchen   = $kitchens[ $foundKitchenKey ];
            $delivery  = $delivery === 'true';
            $maxOrders = $delivery ? $kitchen['delivery_max_orders_time'] : $kitchen['takeaway_max_orders_time'];
            $maxOrders = (int) $maxOrders ?? 9999;

            $yesterday = date( 'Y-m-d', strtotime( '-1 day' ) );
            $orders = wc_get_orders( [
                'orderby'      => 'date',
                'order'        => 'DESC',
                'meta_key'     => '_scheduled_for',
                'meta_compare' => 'REGEXP',
                'meta_value'   => "^$time",
                'date_after'   => $yesterday,
            ] );

            $orders = array_filter( $orders, function( $o ) use( $delivery ) {
                return ( (bool) $o->get_meta( '_is_delivery' ) ) === $delivery;
            } );

            $allowed = count( $orders ) < $maxOrders;

            return wp_send_json( [
                'status'  => $allowed,
                'message' => ( $allowed ? 'Allowed' : 'Not allowed' )
            ], 200 );

        }


        /**
         *  Get the schedules from the configuration.
         */
        public function getSchedules( $request ) {

            $kitchens        = (array) get_field( 'kitchens', 'options' );
            $kitchenID       = $request->get_param( 'kitchen_id' );
            $foundKitchenKey = array_search( $kitchenID, array_column( $kitchens, 'kitchen_id' ) );

            if ( $foundKitchenKey === false || ! isset( $kitchens[ $foundKitchenKey ] ) ) {
                return wp_send_json( [
                    'error'   => true,
                    'message' => 'Parameter kitchen_id is required and it must be an existing ID'
                ], 400 );
            }

            $data = [
                'schedules' => WCKitchenController::getSchedulesForKitchen( $kitchens[ $foundKitchenKey ] )
            ];

            return wp_send_json( $data );

        }

        public function getProductCategories( $request ) {

            global $wpdb;
            
            $parentID  = (int) $request->get_param( 'parent' ) ?? 94;
            $kitchenID = sanitize_text_field( $request->get_param( 'kitchen' ) );

            $categories = ProductCatController::getSubCategoriesForKitchen( $parentID, $kitchenID );

            foreach ( $categories as $cat ) {
                $image = wp_get_attachment_image_src( $cat->thumbnail_id, 'medium_large' );
                $cat->image = [ 'src' => $image[0] ?? '' ];
            }

            return wp_send_json( $categories );

        }

    }
        
}

WCRestController::getInstance( );