<?php namespace Invbit\Core;

/**
 * Cron Controller.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

defined('ABSPATH') or die('¯\_(ツ)_/¯');

if ( ! class_exists( __NAMESPACE__ .'\CronController' ) ) {

    class CronController
    {
        private static $singleton;

        /**
         * Singleton.
         */
        public static function getInstance(): self
        {
            if (!isset(self::$singleton)) self::$singleton = new self;

            return self::$singleton;
        }

        /**
         * Constructor.
         */
        public function __construct()
        {
            add_filter('cron_schedules', function( $schedules ) {
                $schedules['every_five_minutes'] = [
                    'interval' => 60 * 5,
                    'display'  => __( 'Every five minutes', 'betheme' )
                ];
                return $schedules;
            } );

            if ( ! wp_next_scheduled('rebell_manage_kitchen_status') ) {
                wp_schedule_event(time(), 'every_five_minutes', 'rebell_manage_kitchen_status');
            }

            add_action('rebell_manage_kitchen_status', [$this, 'handleKitchenStatus']);
        }

        /**
         *  Open or close each kitchen automatically.
         */
        public function handleKitchenStatus()
        {
            $now = strtotime( current_datetime( )->format( 'H:i' ) );

            error_log('--- HANDLING KITCHEN STATUSES ------------');

            foreach ( get_field( 'kitchens', 'options' ) as $idx => $kitchen ) {
                if ( ! $kitchen['auto_open_close'] ) {
                    continue;
                }

                $TakeawayMorningStarted       = $now >= strtotime( $kitchen['takeaway_morning']['start'] );
                $TakeawayMorningNotFinished   = $now < strtotime( $kitchen['takeaway_morning']['end'] );
                $TakeawayAfternoonStarted     = $now >= strtotime( $kitchen['takeaway_afternoon']['start'] );
                $TakeawayAfternoonNotFinished = $now < strtotime( $kitchen['takeaway_afternoon']['end'] );
                $DeliveryMorningStarted       = $now >= strtotime( $kitchen['delivery_morning']['start'] );
                $DeliveryMorningNotFinished   = $now < strtotime( $kitchen['delivery_morning']['end'] );
                $DeliveryAfternoonStarted     = $now >= strtotime( $kitchen['delivery_afternoon']['start'] );
                $DeliveryAfternoonNotFinished = $now < strtotime( $kitchen['delivery_afternoon']['end'] );

                $option = "options_kitchens_{$idx}";
                update_option( "{$option}_takeaway_morning_open", ( $TakeawayMorningStarted && $TakeawayMorningNotFinished ) );
                update_option( "{$option}_takeaway_afternoon_open", $TakeawayAfternoonStarted && $TakeawayAfternoonNotFinished );
                update_option( "{$option}_delivery_morning_open", ( $DeliveryMorningStarted && $DeliveryMorningNotFinished ) );
                update_option( "{$option}_delivery_afternoon_open", $DeliveryAfternoonStarted && $DeliveryAfternoonNotFinished );


                error_log("KITCHEN: {$kitchen['kitchen_id']}");

                $status = ['CERRADO', 'ABIERTO'];
                error_log('Takeaway Morning: '   . $status[ boolval( $TakeawayMorningStarted && $TakeawayMorningNotFinished )     ] );
                error_log('Takeaway Afternoon: ' . $status[ boolval( $TakeawayAfternoonStarted && $TakeawayAfternoonNotFinished ) ] );
                error_log('Delivery Morning: '   . $status[ boolval( $DeliveryMorningStarted && $DeliveryMorningNotFinished )     ] );
                error_log('Delivery Afternoon: ' . $status[ boolval( $DeliveryAfternoonStarted && $DeliveryAfternoonNotFinished ) ] );
            }

            error_log('--- / HANDLING KITCHEN STATUSES ----------');

            wp_die('Done handling kitchen statuses');
        }

    }

}

CronController::getInstance( );