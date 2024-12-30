<?php namespace Invbit\Core;

/**
 * My account controller.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */


if ( ! class_exists( __NAMESPACE__ .'\MyAccount' ) ) {

    class MyAccount {

        private static $singleton;
        protected      $defaultState   = 'C';  // A Coruña
        protected      $defaultCountry = 'ES'; // Spain

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

            add_action( 'init', [ $this, 'init' ], 10 );
            add_action( 'admin_post_rebell_update_customer_profile', [ $this, 'handleUpdateCustomerProfile' ] );
            add_action( 'admin_post_nopriv_rebell_update_customer_profile', 'auth_redirect' );

            add_action( 'wp_loaded', [ $this, 'handleCreateCustomer' ] );

        }

        /**
         * Initialize
         */
        public function init( ) {

            if ( ! session_id( ) ) {
                session_start( );
            }

            add_shortcode( 'show_my_account',    [ $this, 'myAccountLanding' ] );
            add_shortcode( 'my_account_coupons', [ $this, 'myAccountCoupons' ] );

        }

        /**
         * My account landing page.
         */
        public function myAccountLanding( $attr, $content = null ) {

            if ( is_admin( ) ) return;

            global $wp;

            extract( shortcode_atts( [
                'title'       => '¿Todavía no tienes una cuenta?',
                'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit.'
            ], $attr ) );

            if ( isset( $wp->query_vars['lost-password'] ) ) {
                return \WC_Shortcode_My_Account::lost_password( );
            }

            wc_print_notices( );

            if ( is_user_logged_in( ) ) {
                return do_shortcode('[woocommerce_my_account]');
            }

            // Let guests see the content of the following page slugs.
            foreach ( ['alergenos', 'contacto'] as $slug ) {
                if ( array_key_exists( $slug, $wp->query_vars ) ) {
                    return do_action( 'woocommerce_account_content' );
                }
            }

            if ( isset( $_GET['register'] ) ) {
                return get_template_part( 'Core/Templates/Woocommerce/Forms/form', 'register' );
            }

            if ( isset( $_GET['login'] ) ) {
                return get_template_part( 'Core/Templates/Woocommerce/Forms/form', 'login' );
            }

            ob_start( ); ?>

                <section class="MyAccountLanding">
                    <?php if ( isset( $_GET['approved'] ) ) : ?>

                        <h3 class="Title"><?= __( 'Confirma tu correo electrónico', 'betheme' ) ?></h3>

                        <?php if ( sanitize_text_field( $_GET['approved'] ) === 'false' ) : ?>
                            <p class="Description">
                                <?= get_option('user_verification_settings')['woocommerce']['message_after_registration']
                                    ?? __('Registration success, please check mail for details.', 'user-verification'); ?>
                            </p>
                        <?php endif; ?>

                    <?php else : ?>

                        <h3 class="Title"><?= sanitize_text_field( $title ) ?></h3>

                        <p class="Description"><?= sanitize_text_field( $description ) ?></p>

                        <nav class="Actions">
                            <a href="<?= wc_get_page_permalink( 'myaccount' ) . '?register' ?>" class="btn">
                                <?php esc_html_e( 'Register', 'woocommerce' ); ?>
                            </a>
                            <a href="<?= wc_get_page_permalink( 'myaccount' ) . '?login' ?>" class="btn">
                                <?php esc_html_e( 'Log in', 'woocommerce' ); ?>
                            </a>
                        </nav>

                    <?php endif; ?>
                </section>

            <?php return ob_get_clean( );

        }

        /**
         * My account coupons page.
         */
        public function myAccountCoupons( $attr, $content = null ) {

            if ( is_admin( ) ) return;

            global $wp;

            extract( shortcode_atts( [
                'title'       => 'Cupones',
                'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit.'
            ], $attr ) );

            wc_print_notices( );

            return get_template_part( 'Core/Templates/Woocommerce/MyAccount/my', 'coupons' );

        }

        /**
         * Update a customer profile
         */
        public function handleUpdateCustomerProfile( ) {

            $referrer = esc_url_raw( $_POST['_wp_http_referer'] );

            if ( ! wp_verify_nonce( $_POST[ 'woocommerce-update-profile-nonce' ], 'woocommerce-update-profile' ) ) {
                return wp_redirect( $referrer );
            }

            if ( isset( $_POST['empty_cart'] ) ) {
                WC( )->cart->empty_cart( );
            }

            $zipcode = sanitize_text_field( $_POST['zipcode'] );

            try {
                Helpers::getKitchenByZipcode($zipcode);
            } catch( \Exception $e ) {
                $_SESSION['errors']['zipcode'] = $e->getMessage( );
                return wp_redirect( $referrer );    
            }

            $userData = [
                'first_name'          => sanitize_text_field( $_POST['name'] ),

                'billing_first_name'  => sanitize_text_field( $_POST['name'] ),
                // 'billing_last_name'   => sanitize_text_field( $_POST['lastName'] ),
                'billing_address_1'   => sanitize_text_field( $_POST['address'] ),
                'billing_city'        => sanitize_text_field( $_POST['city'] ),
                'billing_postcode'    => $zipcode,
                'billing_country'     => 'ES',
                'billing_state'       => 'C',
                // 'billing_email'       => sanitize_text_field( $_POST['email'] ),
                'billing_phone'       => sanitize_text_field( $_POST['phone'] ),

                'shipping_first_name' => sanitize_text_field( $_POST['name'] ),
                // 'shipping_last_name'  => sanitize_text_field( $_POST['lastName'] ),
                'shipping_address_1'  => sanitize_text_field( $_POST['address'] ),
                'shipping_city'       => sanitize_text_field( $_POST['city'] ),
                'shipping_postcode'   => $zipcode,
                'shipping_country'    => 'ES',
                'shipping_state'      => 'C',
                // 'shipping_email'      => sanitize_text_field( $_POST['email'] ),
                'shipping_phone'      => sanitize_text_field( $_POST['phone'] ),
            ];

            foreach ( $userData as $key => $value ) {
                update_user_meta( get_current_user_id( ), $key, $value );
            }

            return wp_redirect( $referrer );

        }

        /**
         * Create a new customer
         */
        public function handleCreateCustomer( ) {

            $nonce_value = isset( $_POST['_wpnonce'] ) ? wp_unslash( $_POST['_wpnonce'] ) : '';
            $nonce_value = isset( $_POST['woocommerce-register-nonce'] ) ? wp_unslash( $_POST['woocommerce-register-nonce'] ) : $nonce_value;

            if ( ! isset( $_POST['wc_register'], $_POST['email'] ) or ! wp_verify_nonce( $nonce_value, 'woocommerce-register' ) ) {
                return;
            }

            $name     = sanitize_text_field( $_POST['first_name'] ?? null );
            $phone    = $_POST['phone'] ? (int) str_replace( ' ', '', $_POST['phone'] ) : null;
            $email    = sanitize_email( $_POST['email'] ?? null );
            $password = $_POST['password'] ?? null;
            $address  = sanitize_text_field( $_POST['address'] ?? null );
            $city     = sanitize_text_field( $_POST['city'] ?? null );
            $zipcode  = sanitize_text_field( $_POST['zipcode'] ?? null );
			$username = 'no' === get_option( 'woocommerce_registration_generate_username' ) && isset( $_POST['username'] ) ? wp_unslash( $_POST['username'] ) : '';

			try {
                $registrationData = [ $name, $phone, $email, $password, $address, $city, $zipcode, $username ];
                $this->_validateCustomerRegistrationFields( $registrationData );

				$newCustomer = wc_create_new_customer( $email, wc_clean( $username ), $password );

				if ( is_wp_error( $newCustomer ) ) {
					throw new \Exception( $newCustomer->get_error_message( ) );
				}

				if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) {
					wc_add_notice( __( 'Your account was created successfully and a password has been sent to your email address.', 'woocommerce' ) );
				} else {
					wc_add_notice( __( 'Your account was created successfully. Your login details have been sent to your email address.', 'woocommerce' ) );
				}

                // Save billing and shipping data.
                $wcFields = [
                    'first_name'    => $name,
                    'display_name'  => $name,
                    'user_nicename' => $name,
                ];
                foreach (['billing', 'shipping'] as $type) {
                    $wcFields = array_merge( $wcFields, [
                        "{$type}_first_name"  => $name,
                        "{$type}_address_1"   => $address,
                        "{$type}_city"        => $city,
                        "{$type}_postcode"    => $zipcode,
                        "{$type}_country"     => $this->defaultCountry,
                        "{$type}_state"       => $this->defaultState,
                        "{$type}_email"       => $email,
                        "{$type}_phone"       => $phone,
                    ] );
                }

                foreach ($wcFields as $key => $value) {
                    update_user_meta( $newCustomer, $key, $value );
                }

				// Only redirect after a forced login - otherwise output a success notice.
				if ( apply_filters( 'woocommerce_registration_auth_new_customer', true, $newCustomer ) ) {
					wc_set_customer_auth_cookie( $newCustomer );

					wp_safe_redirect( Helpers::getRedirectUrl( ) );
					return;
				}
			} catch ( \Exception $e ) {
                error_log( print_r( [ 'ERROR ON REGISTRATION' => WC()->session->get( 'wc_notices', [ ] ) ], 1 ) );

                if ( $e->getMessage( ) ) {
					wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $e->getMessage( ), 'error' );
				}
			}

            wp_safe_redirect( Helpers::getRedirectUrl( ) );
            return;

        }

        /**
         * Validate customer registration fields.
         */
        private function _validateCustomerRegistrationFields( $data ) {

            [ $name, $phone, $email, $password, $address, $city, $zipcode, $username ] = $data;

            $validationError  = apply_filters( 'woocommerce_process_registration_errors', new \WP_Error( ), $username, $password, $email );

            if ( ! $name ) {
                $validationError->add( 'name-error', __( 'Por favor, escribe tu nombre.', 'betheme' ) );
            }

            if ( ! $phone or ! preg_match( "/^[0-9]{9}$/i", $phone ) ) {
                $validationError->add( 'phone-error', __( 'Por favor, introduce un teléfono válido (e.g.: 600600600).', 'betheme' ) );
            }

            if ( ! $email or ! is_email( $email ) ) {
                $validationError->add( 'email-error', __( 'Por favor, introduce un email válido.', 'betheme' ) );
            }

            if ( email_exists( $email ) ) {
                $validationError->add( 'email-duplicated-error', __( 'Tu dirección de email ya existe. Por favor, introduce otra.', 'betheme' ) );
            }

            if ( ! $password ) {
                $validationError->add( 'password-error', __( 'Por favor, introduce una contraseña.', 'betheme' ) );
            }

            if ( ! $address or ! $city or ! $zipcode ) {
                $validationError->add( 'address-error', __( 'Por favor, introduce tu dirección de entrega, ciudad y código postal.', 'betheme' ) );
            }

            if ( ! $validationError->has_errors( ) ) {
                try {
                    Helpers::getKitchenByZipcode( $zipcode );
                } catch( \Exception $e ) {
                    throw new \Exception( $e->getMessage( ) );
                }
            }

            if ( $validationErrors = $validationError->get_error_messages( ) ) {
                foreach ( $validationErrors as $message ) {
                    wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $message, 'error' );
                }
                throw new \Exception( );
            }

        }

    }

}

MyAccount::getInstance( );