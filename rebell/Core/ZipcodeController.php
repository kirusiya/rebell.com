<?php namespace Invbit\Core;

/**
 * Zipcode controller.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */


if ( ! class_exists( __NAMESPACE__ .'\ZipcodeController' ) ) {

    class ZipcodeController {

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

            add_action( 'init', [ $this, 'init' ], 10 );
            add_action( 'template_redirect', [ $this, 'handleZipcodeRedirect' ] );
            add_action( 'wp_loaded', [ $this, 'handleSaveZipcode' ] );
            add_filter( 'logout_url', [ $this, 'unsetZipcodeFromSession' ], 10, 2 );

        }

        /**
         * Initialize
         */
        public function init( ) {

            add_shortcode( 'postcode-request', [ $this, 'renderZipcodeRequestForm' ] );

        }

        /**
         * Handle the postcode redirect
         */
        public function handleZipcodeRedirect( ) {

            global $wp;

            if (
                ! is_woocommerce( ) or
                is_admin( ) or
                $wp->request === 'codigo-postal' or
                isset( $_GET['activation_key'] )
            ) {
                return;
            }

            $zipcode = self::getCustomerZipcode( );

            if ( ! $zipcode ) {
                $redirect = '/codigo-postal';
                exit( wp_redirect( $redirect ) );
            }
          
        }

        /**
         * Render the postcode request form.
         */
        public function renderZipcodeRequestForm( $attr, $content = null ) {

            extract( shortcode_atts( [
                'title' => __( 'Introduce tu código postal para comprobar la disponibilidad del servicio a domicilio', 'betheme' ),
            ], $attr ) );

            ob_start( ); ?>

            <form method="post" class="ZipcodeRequestForm woocommerce-form">

                <div class="woocommerce-notices-wrapper codeZipRes">
                    <?php wc_print_notices(); ?>
                </div>

                <p class="Title"><?= $title ?></p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="set_postcode">
                        <?php esc_html_e( 'Postcode', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
                    </label>
                    <input
                        placeholder="<?php esc_html_e( 'Código Postal', 'woocommerce' ); ?>"
                        type="number"
                        class="woocommerce-Input woocommerce-Input--text input-text"
                        name="zipcode"
                        id="set_zipcode"
                        autocomplete="zipcode"
                        value="<?= ( ! empty( $_POST['zipcode'] ) ) ? esc_attr( wp_unslash( $_POST['zipcode'] ) ) : ''; ?>"
                        required
                    />
                </p>

                <?php wp_nonce_field( 'update-zipcode', 'update-zipcode-nonce' ); ?>
                <button
                    type="submit"
                    class="btn btn-black"
                    name="update_zipcode"
                    value="<?php esc_attr_e( 'Aceptar', 'betheme' ); ?>"
                >
                    <?php esc_html_e( 'Aceptar', 'betheme' ); ?>
                </button>

            </form>

            <?php return ob_get_clean( );

        }

        /**
         * Save the postcode
         */
        public function handleSaveZipcode($isAjax = false)
        {
            if (!$isAjax) {
                $nonce_value = isset($_POST['_wpnonce']) ? wp_unslash($_POST['_wpnonce']) : '';
                $nonce_value = isset($_POST['update-zipcode-nonce']) ? wp_unslash($_POST['update-zipcode-nonce']) : $nonce_value;
        
                if (!isset($_POST['update_zipcode'], $_POST['zipcode']) || !wp_verify_nonce($nonce_value, 'update-zipcode')) {
                    return;
                }
            }
        
            $zipcode = sanitize_text_field($_POST['zipcode'] ?? null);
        
            try {
                Helpers::getKitchenByZipcode($zipcode);
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                wc_add_notice("<div style='text-align:left'>$msg</div>", 'error');
                if ($isAjax) {
                    return ['success' => false, 'notices' => $this->get_formatted_notices()];
                } else {
                    return;
                }
            }
        
            $_SESSION['zipcode'] = $zipcode;
        
            if (is_user_logged_in()) {
                wc()->customer->set_billing_postcode($zipcode);
                wc()->customer->set_shipping_postcode($zipcode);
            }
        
            // Redirect user.
            $redirect = get_permalink(wc_get_page_id('shop'));
        
            if ($cat = get_term_by('slug', 'compartir', 'product_cat')) {
                if ($cat->term_id) {
                    $redirect = get_term_link($cat->term_id, 'product_cat');
                }
            }
        
            if ($isAjax) {
                return ['success' => true, 'redirect' => $redirect];
            } else {
                wp_safe_redirect($redirect);
                exit;
            }
        }

        private function get_formatted_notices() {
            $notices = wc_get_notices();
            ob_start();
            wc_print_notices();
            $formatted_notices = ob_get_clean();
            wc_clear_notices(); // Clear notices to prevent duplicates
            return $formatted_notices;
        }

        /**
         *  Unset zipcode from session.
         */
        public function unsetZipcodeFromSession( $logoutUrl, $redirect ) {

            $_SESSION['zipcode'] = null;

            return $logoutUrl;

        }

        /**
         *  Get the customer postcode.
         */
        public static function getCustomerZipcode( ) : ?string {

            global $wp;

            $zipcode = wc( )->customer->get_shipping_postcode( );

            $zipcode = $zipcode ? $zipcode : $_SESSION['zipcode'];

            if ( ! $zipcode ) {
                if ( isset( $_GET['activation_key'] ) ) {
                    wp_safe_redirect( esc_url( wc_get_account_endpoint_url( '' ) ) );
                } else if ( $wp->request === 'ubicaciones' ) {
                    return null;
                } else {
                    wp_safe_redirect( site_url() );
                }
                exit;
            }

            return $zipcode;

        }

    }

}

ZipcodeController::getInstance( );