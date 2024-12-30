<?php namespace Invbit\Core;

/**
 * Locations controller.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */


if ( ! class_exists( __NAMESPACE__ .'\LocationsController' ) ) {

    class LocationsController {

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
            add_filter( 'wp_enqueue_scripts', [ $this, 'loadStylesAndScripts' ] );

        }

        /**
         * Initialize
         */
        public function init( ) {

            add_shortcode( 'locations', [ $this, 'renderKitchenLocations' ] );

        }

        /**
         * Render the postcode request form.
         */
        public function loadStylesAndScripts(  ) {

            global $wp;

            if ( $wp->request !== 'ubicaciones' ) {
                return;
            }

            wp_enqueue_style( 'locations-css', ASSETS_DIR . 'Styles/frontend/pages/locations.css' );
            wp_enqueue_script( 'micromodal-js', ASSETS_DIR . 'JS/plugins/micromodal.min.js', [ ], null, true );
            wp_enqueue_script( 'locations-js', ASSETS_DIR . 'JS/pages/locations.js', [ 'jquery', 'micromodal-js' ], null, true );

        }

        /**
         * Render the postcode request form.
         */
        public function renderKitchenLocations( $attr, $content = null ) {

            $allKitchens     = Helpers::getAllKitchens( );
            $customerZipCode = ZipcodeController::getCustomerZipcode( );
            $customerKitchen = null;

            if ( $customerZipCode ) {
                $customerKitchen = Helpers::getKitchenByZipcode( $customerZipCode );
                $customerKitchen = $customerKitchen['kitchen_id'] ?? null;
            }

            ob_start( ); ?>

            <ul class="Locations">
                <?php foreach ( $allKitchens as $kitchen ) : ?>
                    <li>
                        <button class="Location__Button" data-id="<?= $kitchen['id'] ?>">
                            <?= $kitchen['name'] ?>
                            <?php if ( $customerKitchen === $kitchen['id'] ) : ?>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 283.5 283.5" xml:space="preserve" class="currentIcon">
                                <path fill="currentColor" d="M131 0h21l11 1c27 5 51 16 72 34 24 21 40 48 46 80l2 17v23l-3 18A142 142 0 1 1 115 3l16-3zm-14 172-2-2-31-40c-4-5-10-6-15-2-4 4-5 10-1 15l39 49c6 7 12 7 18 1l87-87 3-3c2-3 3-6 2-9-1-4-3-6-7-8-4-1-7 1-10 3l-34 35-49 48z"/>
                            </svg>
                            <?php endif; ?>
                        </button>

                        <aside class="modal micromodal-slide" id="<?= $kitchen['id'] ?>" aria-hidden="true">
                            <div class="modal__overlay" tabindex="-1" data-micromodal-close>
                                <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-1-title">
                                    <header class="modal__header">
                                        <h2 class="modal__title"><?= $kitchen['name'] ?></h2>
                                        <?php if ( $customerKitchen === $kitchen['id'] ) : ?>
                                            <small>(Actual)</small>
                                        <?php endif; ?>
                                    </header>
                                    <main class="modal__content">
                                        <div class="modal__address"><?= $kitchen['address'] ?></div>
                                        <div class="modal__description"><?= $kitchen['description'] ?></div>
                                    </main>
                                    <footer class="modal__footer">
                                        <button class="modal__btn" data-micromodal-close
                                            aria-label="Cerrar información">Ok!</button>
                                    </footer>
                                </div>
                            </div>
                        </aside>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php return ob_get_clean( );

        }

    }

}

LocationsController::getInstance( );