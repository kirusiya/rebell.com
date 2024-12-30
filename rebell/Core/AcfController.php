<?php

/**
 * Customize the Advanced Custom Fields plugin.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

namespace Invbit\Core;

defined('ABSPATH') or die('¯\_(ツ)_/¯');

if (!class_exists(__NAMESPACE__ . '\AcfController')) {

    class AcfController
    {

        private static $singleton;

        /**
         * Singleton
         *
         * @static array     $singleton
         * @return instance  The one true instance.
         */
        public static function getInstance(): self
        {

            if (!isset(self::$singleton)) self::$singleton = new self;

            return self::$singleton;
        }

        /**
         * Constructor
         */
        public function __construct()
        {

            add_filter('acf/load_field/name=kitchen_shipping_zones',       [$this, 'loadKitchenZonesOptions']);
            add_filter('acf/load_field/name=kitchen_for_product',          [$this, 'loadKitchensOptions']);
            add_filter('acf/load_field/name=kitchen_for_order',            [$this, 'loadKitchensOptions']);
            add_filter('acf/load_field/name=kitchen_for_user',             [$this, 'loadKitchensOptions']);
            add_filter('acf/load_field/name=kitchen_for_product_category', [$this, 'loadKitchensOptions']);
            add_filter('acf/load_field/name=default_kitchen',              [$this, 'loadKitchensOptions']);
            add_action('acf/include_field_types',                          [$this, 'addUniqueIDFieldType']);
        }

        /**
         * Customize kitchen's shipping zones options.
         */
        public function loadKitchenZonesOptions($field): array
        {

            global $wpdb;

            $query = "SELECT zone_id, zone_name FROM {$wpdb->prefix}woocommerce_shipping_zones WHERE zone_id <> 1";
            $choices = $wpdb->get_results($query);

            $field['choices'] = [];
            foreach ($choices as $choice) {
                $field['choices'][$choice->zone_id] = $choice->zone_name;
            }

            return $field;
        }

        /**
         * Customize options for kitchen selector.
         */
        public function loadKitchensOptions($field): array
        {

            $field['choices'] = [];
            foreach (get_field('kitchens', 'options') as $kitchen) {
                $field['choices'][$kitchen['kitchen_id']] = $kitchen['kitchen_name'];
            }

            return $field;
        }

        public function addUniqueIDFieldType($version): void
        {

            new ACF_UniqueID_Field();
        }
    }
}

AcfController::getInstance();

class ACF_UniqueID_Field extends \acf_field
{

    function __construct()
    {

        $this->name     = 'unique_id';
        $this->label    = __('ID', 'betheme');
        $this->category = 'basic';
        $this->l10n     = [];
        parent::__construct();
    }

    function render_field($field)
    {

        $name  = esc_attr($field['name']);
        $value = esc_attr($field['value'] ?? uniqid());
        print "<h4 style='margin:0'>$value</h4>";
        print "<input type='hidden' readonly='readonly' name='$name' value='$value' />";
    }

    function update_value($value, $post_id, $field)
    {

        return $value ?? uniqid();
    }

    function validate_value($valid, $value, $field, $input)
    {

        return true;
    }
}
