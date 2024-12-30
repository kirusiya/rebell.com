<?php namespace Invbit\Core;

/**
 * WC Kitchen Controller.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

if ( ! class_exists( __NAMESPACE__ .'\WCKitchenController' ) ) {

    class WCKitchenController {

        protected $version;
        protected $namespace;
        protected $order;

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

        }


        /**
         * Get the schedules for a given kitchen.
         */
        public static function getSchedulesForKitchen( $kitchenConfig )
        {
            $timezone      = new \DateTimeZone( 'Europe/Madrid' );
            $schedulesData = [
                'deliveryMorning'   => $kitchenConfig[ 'delivery_morning' ],
                'deliveryAfternoon' => $kitchenConfig[ 'delivery_afternoon' ],
                'takeawayMorning'   => $kitchenConfig[ 'takeaway_morning' ],
                'takeawayAfternoon' => $kitchenConfig[ 'takeaway_afternoon' ],
            ];

            $now  = new \DateTime( 'now', $timezone );
            $data = array_map( function( $schedule ) use( $now, $timezone ) {
                $data    = [ ];
                $current = new \DateTime( $schedule[ 'start' ], $timezone );
                $end     = new \DateTime( $schedule[ 'end' ],   $timezone );
                $limit   = new \DateTime( $schedule[ 'limit' ], $timezone );
                $opened  = ( $schedule[ 'open' ] and ( $now->getTimestamp( ) <= $limit->getTimestamp( ) ) );

                if ( $opened ) {
                    while ( $current->getTimestamp( ) < $end->getTimestamp( ) ) {
                        if ( $now->getTimestamp( ) <= $current->getTimestamp( ) ) {
                            $data[ ] = $current->format( 'H:i' );
                        }
                        $current = $current->add( new \DateInterval( "PT{$schedule['interval']}M" ) );
                    }
                    if ( $now->getTimestamp( ) <= $current->getTimestamp( ) )
                        $data[ ] = $current->format( 'H:i' );
                }

                return [ 'opened' => $opened, 'data' => $data, 'interval' => $schedule['interval'] ];
            }, $schedulesData );

            // Merge both delivery and takeaway schedules.
            return [
                'delivery' => [
                    'morning'   => $data[ 'deliveryMorning' ],
                    'afternoon' => $data[ 'deliveryAfternoon' ],
                    // Deprecated:
                    'opened' => $data[ 'deliveryMorning' ][ 'opened' ] or $data[ 'deliveryAfternoon' ][ 'opened' ],
                    'data'   => array_merge( $data[ 'deliveryMorning' ][ 'data' ], $data[ 'deliveryAfternoon' ][ 'data' ] ),
                ],
                'takeaway' => [
                    'morning'   => $data[ 'takeawayMorning' ],
                    'afternoon' => $data[ 'takeawayAfternoon' ],
                    // Deprecated:
                    'opened' => $data[ 'takeawayMorning' ][ 'opened' ] or $data[ 'takeawayAfternoon' ][ 'opened' ],
                    'data'   => array_merge( $data[ 'takeawayMorning' ][ 'data' ], $data[ 'takeawayAfternoon' ][ 'data' ] ),
                ],
            ];
        }

        /**
         * Get the customer Kitchen given a zipcode (or the current customer zipcode).
         */
        public static function getCustomerKitchenFromZipcode( $zipcode = null )
        {
            $zipCode      = $zipcode ?? ZipcodeController::getCustomerZipcode( );
            $shippingZone = Helpers::getShippingZoneByZipCode( $zipCode );
            
            foreach ( get_field( 'kitchens', 'options' ) as $kitchen ) {
                if ( in_array( $shippingZone['id'], $kitchen['kitchen_shipping_zones'] ) ) {
                    return $kitchen;
                    break;
                }
            }

            throw new \Exception(
                'Ha ocurrido un problema buscando la cocina asociada a tu usuario. ' .
                'Por favor, contacta con nosotros para resolver la incidencia.'
            );
        }

    }
        
}

WCKitchenController::getInstance( );